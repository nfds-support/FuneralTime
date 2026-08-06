<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Policy\Service;

use GuzzleHttp\Client;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\MoodleCohortMap;
use OrangeHRM\Policy\Dao\PolicyDao;
use Throwable;

/**
 * Syncs OrangeHRM employees into Moodle users and job-title cohorts.
 * Identity join key: work email (same as Google Workspace SSO).
 */
class MoodleSyncService
{
    use ConfigServiceTrait;
    use EntityManagerHelperTrait;

    protected ?PolicyDao $policyDao = null;
    protected ?Client $httpClient = null;

    public function getPolicyDao(): PolicyDao
    {
        return $this->policyDao ??= new PolicyDao();
    }

    public function setPolicyDao(PolicyDao $policyDao): void
    {
        $this->policyDao = $policyDao;
    }

    public function setHttpClient(Client $client): void
    {
        $this->httpClient = $client;
    }

    protected function getHttpClient(): Client
    {
        return $this->httpClient ??= new Client(['timeout' => 60]);
    }

    /**
     * @return array{created:int,updated:int,cohortMembers:int,skipped:int,errors:string[]}
     */
    public function sync(): array
    {
        $result = ['created' => 0, 'updated' => 0, 'cohortMembers' => 0, 'skipped' => 0, 'errors' => []];
        $baseUrl = $this->getConfigService()->getMoodleBaseUrl();
        $token = $this->getConfigService()->getMoodleWebserviceToken();
        if (empty($baseUrl) || empty($token)) {
            $result['errors'][] = 'Moodle base URL or web service token is not configured';
            return $result;
        }
        if (!$this->getConfigService()->getMoodleSyncEnabled()) {
            $result['errors'][] = 'Moodle sync is disabled';
            return $result;
        }

        $maps = $this->getPolicyDao()->getAllMoodleCohortMaps();
        $mapsByJobTitle = [];
        foreach ($maps as $map) {
            $mapsByJobTitle[$map->getJobTitle()->getId()] = $map;
        }

        /** @var Employee[] $employees */
        $employees = $this->getEntityManager()
            ->getRepository(Employee::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.jobTitle', 'jt')
            ->andWhere('e.employeeTerminationRecord IS NULL')
            ->andWhere('e.purgedAt IS NULL')
            ->getQuery()
            ->execute();

        foreach ($employees as $employee) {
            $email = $employee->getWorkEmail();
            if (empty($email)) {
                $result['skipped']++;
                continue;
            }
            try {
                $upsert = $this->upsertMoodleUser($baseUrl, $token, $employee);
                if ($upsert === null) {
                    $result['skipped']++;
                    continue;
                }
                [$moodleUserId, $wasCreated] = $upsert;
                if ($wasCreated) {
                    $result['created']++;
                } else {
                    $result['updated']++;
                }
                $jobTitle = $employee->getJobTitle();
                if ($jobTitle === null || !isset($mapsByJobTitle[$jobTitle->getId()])) {
                    continue;
                }
                /** @var MoodleCohortMap $map */
                $map = $mapsByJobTitle[$jobTitle->getId()];
                $this->addCohortMember($baseUrl, $token, $map->getMoodleCohortId(), $moodleUserId);
                $result['cohortMembers']++;
            } catch (Throwable $e) {
                $result['errors'][] = sprintf(
                    'empNumber %d: %s',
                    $employee->getEmpNumber(),
                    $e->getMessage()
                );
            }
        }

        return $result;
    }

    /**
     * @return array{0:int,1:bool}|null [moodleUserId, wasCreated]
     */
    protected function upsertMoodleUser(string $baseUrl, string $token, Employee $employee): ?array
    {
        $email = strtolower(trim((string) $employee->getWorkEmail()));
        $existing = $this->callMoodle($baseUrl, $token, 'core_user_get_users_by_field', [
            'field' => 'email',
            'values[0]' => $email,
        ]);
        $username = preg_replace('/[^a-z0-9._-]/i', '', strstr($email, '@', true) ?: $email) ?: ('emp' . $employee->getEmpNumber());
        $payload = [
            'users[0][username]' => strtolower($username),
            'users[0][firstname]' => $employee->getFirstName() ?: 'Employee',
            'users[0][lastname]' => $employee->getLastName() ?: 'User',
            'users[0][email]' => $email,
            'users[0][auth]' => 'oauth2',
            'users[0][idnumber]' => (string) $employee->getEmpNumber(),
        ];

        if (is_array($existing) && isset($existing[0]['id'])) {
            $payload['users[0][id]'] = $existing[0]['id'];
            unset($payload['users[0][username]']);
            $this->callMoodle($baseUrl, $token, 'core_user_update_users', $payload);
            return [(int) $existing[0]['id'], false];
        }

        // Create with a random password Moodle requires; auth=oauth2 means they use Google SSO.
        $payload['users[0][password]'] = bin2hex(random_bytes(16)) . 'Aa1!';
        $created = $this->callMoodle($baseUrl, $token, 'core_user_create_users', $payload);
        if (is_array($created) && isset($created[0]['id'])) {
            return [(int) $created[0]['id'], true];
        }
        return null;
    }

    protected function addCohortMember(string $baseUrl, string $token, int $cohortId, int $userId): void
    {
        $this->callMoodle($baseUrl, $token, 'core_cohort_add_cohort_members', [
            'members[0][cohorttype][type]' => 'id',
            'members[0][cohorttype][value]' => $cohortId,
            'members[0][usertype][type]' => 'id',
            'members[0][usertype][value]' => $userId,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    protected function callMoodle(string $baseUrl, string $token, string $function, array $params)
    {
        $endpoint = rtrim($baseUrl, '/') . '/webservice/rest/server.php';
        $form = array_merge([
            'wstoken' => $token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ], $params);
        $response = $this->getHttpClient()->post($endpoint, ['form_params' => $form]);
        $body = json_decode((string) $response->getBody(), true);
        if (is_array($body) && isset($body['exception'])) {
            throw new \RuntimeException($body['message'] ?? $body['exception']);
        }
        return $body;
    }
}
