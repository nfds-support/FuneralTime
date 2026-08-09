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

namespace OrangeHRM\Tests\Time\Service;

use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmployeeSalary;
use OrangeHRM\Tests\Util\TestCase;
use OrangeHRM\Time\Dao\FuelBankedTimeDao;
use OrangeHRM\Time\Service\BankedTimeService;
use OrangeHRM\Time\Service\FuelBankedTimeService;

/**
 * @group Time
 * @group Service
 */
class FuelBankedTimeServiceTest extends TestCase
{
    public function testResolveHourlyRateUsesFirstSalaryAmount(): void
    {
        $employee = new Employee();
        $employee->setEmpNumber(1);

        $salary = $this->getMockBuilder(EmployeeSalary::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salary->method('getAmount')->willReturn('50.00');

        $dao = $this->getMockBuilder(FuelBankedTimeDao::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmployeeSalaries'])
            ->getMock();
        $dao->expects($this->once())
            ->method('getEmployeeSalaries')
            ->with(1)
            ->willReturn([$salary]);

        $service = new FuelBankedTimeService();
        $service->setDao($dao);

        $this->assertSame(50.0, $service->resolveHourlyRate($employee));
    }

    public function testDaysToHoursConversionOnBankedTimeService(): void
    {
        $service = new BankedTimeService();
        $this->assertSame(16.0, $service->daysToHours(2.0, 8.0));
        $this->assertSame(2.0, $service->hoursToDays(16.0, 8.0));
    }
}
