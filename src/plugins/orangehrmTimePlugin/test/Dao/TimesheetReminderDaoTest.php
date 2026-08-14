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

namespace OrangeHRM\Tests\Time\Dao;

use OrangeHRM\Config\Config;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Tests\Util\TestCase;
use OrangeHRM\Tests\Util\TestDataService;
use OrangeHRM\Time\Dao\TimesheetReminderDao;

/**
 * @group Time
 * @group Dao
 */
class TimesheetReminderDaoTest extends TestCase
{
    private TimesheetReminderDao $dao;

    protected function setUp(): void
    {
        $this->dao = new TimesheetReminderDao();
        $fixture = Config::get(Config::PLUGINS_DIR)
            . '/orangehrmTimePlugin/test/fixtures/TimesheetReminder.yaml';
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_job_title');
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_employee');
        TestDataService::populate($fixture, true);
    }

    public function testGetEligibleEmployeesUnionsJobTitlesAndEmployees(): void
    {
        $employees = $this->dao->getEligibleEmployees([1, 2], [5]);
        $empNumbers = $this->empNumbers($employees);
        sort($empNumbers);

        $this->assertSame([1, 2, 3, 5, 7, 9, 10], $empNumbers);
    }

    public function testGetEligibleEmployeesSkipsTerminatedAndMissingWorkEmail(): void
    {
        $employees = $this->dao->getEligibleEmployees([1], []);
        $empNumbers = $this->empNumbers($employees);
        sort($empNumbers);

        $this->assertNotContains(4, $empNumbers);
        $this->assertNotContains(8, $empNumbers);
        $this->assertSame([1, 2, 7, 9, 10], $empNumbers);
    }

    public function testGetEligibleEmployeesByManualPickOnly(): void
    {
        $employees = $this->dao->getEligibleEmployees([], [5, 6]);
        $empNumbers = $this->empNumbers($employees);
        sort($empNumbers);
        $this->assertSame([5, 6], $empNumbers);
    }

    public function testGetEligibleEmployeesEmptySelection(): void
    {
        $this->assertSame([], $this->dao->getEligibleEmployees([], []));
    }

    /**
     * @param Employee[] $employees
     * @return int[]
     */
    private function empNumbers(array $employees): array
    {
        return array_map(static fn (Employee $employee) => $employee->getEmpNumber(), $employees);
    }
}
