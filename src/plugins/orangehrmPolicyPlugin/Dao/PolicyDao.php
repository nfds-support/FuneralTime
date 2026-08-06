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

namespace OrangeHRM\Policy\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\MoodleCohortMap;
use OrangeHRM\Entity\Policy;
use OrangeHRM\Entity\PolicyAcknowledgment;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Policy\Dto\MoodleCohortMapSearchFilterParams;
use OrangeHRM\Policy\Dto\PolicyAcknowledgmentSearchFilterParams;
use OrangeHRM\Policy\Dto\PolicySearchFilterParams;

class PolicyDao extends BaseDao
{
    public function getPolicyById(int $id): ?Policy
    {
        return $this->getRepository(Policy::class)->find($id);
    }

    public function savePolicy(Policy $policy): Policy
    {
        $this->persist($policy);
        return $policy;
    }

    /**
     * @param int[] $ids
     */
    public function deletePolicies(array $ids): int
    {
        $q = $this->createQueryBuilder(Policy::class, 'policy');
        $q->delete()
            ->where($q->expr()->in('policy.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return Policy[]
     */
    public function getPolicyList(PolicySearchFilterParams $filterParams): array
    {
        return $this->getPolicyPaginator($filterParams)->getQuery()->execute();
    }

    public function getPolicyCount(PolicySearchFilterParams $filterParams): int
    {
        return $this->getPolicyPaginator($filterParams)->count();
    }

    private function getPolicyPaginator(PolicySearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(Policy::class, 'policy');
        $q->leftJoin('policy.jobTitles', 'jobTitle');

        if (!is_null($filterParams->getStatus())) {
            $q->andWhere('policy.status = :status')
                ->setParameter('status', $filterParams->getStatus());
        }
        if (!is_null($filterParams->getAudienceType())) {
            $q->andWhere('policy.audienceType = :audienceType')
                ->setParameter('audienceType', $filterParams->getAudienceType());
        }
        if (!is_null($filterParams->getJobTitleId())) {
            $q->andWhere(
                $q->expr()->orX(
                    'policy.audienceType = :audienceAll',
                    'jobTitle.id = :jobTitleId'
                )
            )
                ->setParameter('audienceAll', Policy::AUDIENCE_ALL)
                ->setParameter('jobTitleId', $filterParams->getJobTitleId());
        }
        if ($filterParams->getPendingOnly() && !is_null($filterParams->getEmpNumber())) {
            $sub = $this->createQueryBuilder(PolicyAcknowledgment::class, 'ack')
                ->select('IDENTITY(ack.policy)')
                ->andWhere('IDENTITY(ack.employee) = :ackEmpNumber');
            $q->andWhere($q->expr()->notIn('policy.id', $sub->getDQL()))
                ->setParameter('ackEmpNumber', $filterParams->getEmpNumber());
        }

        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }

    public function getAcknowledgment(int $policyId, int $empNumber): ?PolicyAcknowledgment
    {
        return $this->getRepository(PolicyAcknowledgment::class)->findOneBy([
            'policy' => $policyId,
            'employee' => $empNumber,
        ]);
    }

    public function saveAcknowledgment(PolicyAcknowledgment $acknowledgment): PolicyAcknowledgment
    {
        $this->persist($acknowledgment);
        return $acknowledgment;
    }

    /**
     * @return PolicyAcknowledgment[]
     */
    public function getAcknowledgmentList(PolicyAcknowledgmentSearchFilterParams $filterParams): array
    {
        return $this->getAcknowledgmentPaginator($filterParams)->getQuery()->execute();
    }

    public function getAcknowledgmentCount(PolicyAcknowledgmentSearchFilterParams $filterParams): int
    {
        return $this->getAcknowledgmentPaginator($filterParams)->count();
    }

    private function getAcknowledgmentPaginator(PolicyAcknowledgmentSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(PolicyAcknowledgment::class, 'acknowledgment');
        $q->leftJoin('acknowledgment.employee', 'employee');
        $q->leftJoin('acknowledgment.policy', 'policy');

        if (!is_null($filterParams->getPolicyId())) {
            $q->andWhere('policy.id = :policyId')
                ->setParameter('policyId', $filterParams->getPolicyId());
        }
        if (!is_null($filterParams->getEmpNumber())) {
            $q->andWhere('employee.empNumber = :empNumber')
                ->setParameter('empNumber', $filterParams->getEmpNumber());
        }

        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }

    public function getMoodleCohortMapById(int $id): ?MoodleCohortMap
    {
        return $this->getRepository(MoodleCohortMap::class)->find($id);
    }

    public function saveMoodleCohortMap(MoodleCohortMap $map): MoodleCohortMap
    {
        $this->persist($map);
        return $map;
    }

    /**
     * @param int[] $ids
     */
    public function deleteMoodleCohortMaps(array $ids): int
    {
        $q = $this->createQueryBuilder(MoodleCohortMap::class, 'map');
        $q->delete()
            ->where($q->expr()->in('map.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return MoodleCohortMap[]
     */
    public function getMoodleCohortMapList(MoodleCohortMapSearchFilterParams $filterParams): array
    {
        return $this->getMoodleCohortMapPaginator($filterParams)->getQuery()->execute();
    }

    public function getMoodleCohortMapCount(MoodleCohortMapSearchFilterParams $filterParams): int
    {
        return $this->getMoodleCohortMapPaginator($filterParams)->count();
    }

    /**
     * @return MoodleCohortMap[]
     */
    public function getAllMoodleCohortMaps(): array
    {
        return $this->getRepository(MoodleCohortMap::class)->findAll();
    }

    private function getMoodleCohortMapPaginator(MoodleCohortMapSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(MoodleCohortMap::class, 'map');
        $q->leftJoin('map.jobTitle', 'jobTitle');
        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }
}
