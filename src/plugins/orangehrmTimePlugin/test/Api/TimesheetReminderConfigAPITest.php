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

namespace OrangeHRM\Tests\Time\Api;

use OrangeHRM\Entity\TimesheetReminderConfig;
use OrangeHRM\Framework\Services;
use OrangeHRM\ORM\Doctrine;
use OrangeHRM\Tests\Util\EndpointIntegrationTestCase;
use OrangeHRM\Tests\Util\Integration\TestCaseParams;
use OrangeHRM\Time\Api\TimesheetReminderConfigAPI;

/**
 * @group Time
 * @group APIv2
 */
class TimesheetReminderConfigAPITest extends EndpointIntegrationTestCase
{
    /**
     * @dataProvider dataProviderForTestGetOne
     */
    public function testGetOne(TestCaseParams $testCaseParams): void
    {
        $this->populateFixtures('TimesheetReminder.yaml', null, true);
        $this->truncateReminderJoinTables();
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $this->registerMockDateTimeHelper($testCaseParams);
        $api = $this->getApiEndpointMock(TimesheetReminderConfigAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getOne', $testCaseParams);
    }

    public function dataProviderForTestGetOne(): array
    {
        return $this->getTestCases('TimesheetReminderConfigAPITestCase.yaml', 'GetOne');
    }

    /**
     * @dataProvider dataProviderForTestUpdate
     */
    public function testUpdate(TestCaseParams $testCaseParams): void
    {
        $this->populateFixtures('TimesheetReminder.yaml', null, true);
        $this->truncateReminderJoinTables();
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $this->registerMockDateTimeHelper($testCaseParams);
        $api = $this->getApiEndpointMock(TimesheetReminderConfigAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'update', $testCaseParams);
    }

    public function dataProviderForTestUpdate(): array
    {
        return $this->getTestCases('TimesheetReminderConfigAPITestCase.yaml', 'Update');
    }

    public function testDelete(): void
    {
        $api = new TimesheetReminderConfigAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->delete();
    }

    public function testGetValidationRuleForDelete(): void
    {
        $api = new TimesheetReminderConfigAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForDelete();
    }

    public static function attachDefaultRecipients(): void
    {
        $em = Doctrine::getEntityManager();
        /** @var TimesheetReminderConfig $config */
        $config = $em->getRepository(TimesheetReminderConfig::class)->findOneBy([]);
        $config->getDecorator()->setJobTitlesByIds([1, 2]);
        $config->getDecorator()->setEmployeesByEmpNumbers([5]);
        $em->persist($config);
        $em->flush();
    }

    private function truncateReminderJoinTables(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_job_title');
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_employee');
    }
}
