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

namespace OrangeHRM\Tests\Claim\Dao;

use DateTime;
use OrangeHRM\Claim\Dao\ClaimDao;
use OrangeHRM\Claim\Dto\EmployeeCommissionSearchFilterParams;
use OrangeHRM\Config\Config;
use OrangeHRM\Entity\EmployeeCommission;
use OrangeHRM\Tests\Util\KernelTestCase;
use OrangeHRM\Tests\Util\TestDataService;

/**
 * @group Claim
 * @group Dao
 */
class ClaimDaoCommissionTest extends KernelTestCase
{
    private ClaimDao $claimDao;

    protected function setUp(): void
    {
        $this->claimDao = new ClaimDao();
        $fixture = Config::get(Config::PLUGINS_DIR) . '/orangehrmClaimPlugin/test/fixtures/EmployeeCommission.yaml';
        TestDataService::populate($fixture);
    }

    public function testGetEmployeeCommissionById(): void
    {
        $result = $this->claimDao->getEmployeeCommissionById(1);
        $this->assertInstanceOf(EmployeeCommission::class, $result);
        $this->assertEquals(100.0, $result->getAmount());
        $this->assertEquals('Casket package', $result->getDescription());
    }

    public function testGetEmployeeCommissionListForMonth(): void
    {
        $filterParams = new EmployeeCommissionSearchFilterParams();
        $filterParams->setEmpNumber(1);
        $filterParams->setYear(2024);
        $filterParams->setMonth(6);
        $filterParams->setLimit(0);
        $result = $this->claimDao->getEmployeeCommissionList($filterParams);
        $this->assertCount(2, $result);
        $this->assertEquals(2, $this->claimDao->getEmployeeCommissionCount($filterParams));
    }

    public function testGetCommissionSumForMonth(): void
    {
        $sum = $this->claimDao->getCommissionSumForMonth(1, 2024, 6);
        $this->assertEquals(175.5, $sum);
        $empty = $this->claimDao->getCommissionSumForMonth(1, 2024, 1);
        $this->assertEquals(0.0, $empty);
    }

    public function testSaveAndDeleteEmployeeCommission(): void
    {
        $commission = new EmployeeCommission();
        $commission->getDecorator()->setEmployeeByEmpNumber(1);
        $commission->setSaleDate(new DateTime('2024-06-28'));
        $commission->setAmount(12.34);
        $commission->setDescription('Extra sale');
        $commission->setCreatedAt(new DateTime('2024-06-28 12:00:00'));
        $saved = $this->claimDao->saveEmployeeCommission($commission);
        $this->assertGreaterThan(4, $saved->getId());

        $deleted = $this->claimDao->deleteEmployeeCommissionsByIds([$saved->getId()]);
        $this->assertEquals(1, $deleted);
        $this->assertNull($this->claimDao->getEmployeeCommissionById($saved->getId()));
    }
}
