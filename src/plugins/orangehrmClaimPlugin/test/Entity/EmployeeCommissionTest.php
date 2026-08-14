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

namespace OrangeHRM\Tests\Claim\Entity;

use DateTime;
use OrangeHRM\Config\Config;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmployeeCommission;
use OrangeHRM\Entity\User;
use OrangeHRM\Tests\Util\EntityTestCase;
use OrangeHRM\Tests\Util\TestDataService;

/**
 * @group Claim
 * @group Entity
 */
class EmployeeCommissionTest extends EntityTestCase
{
    protected function setUp(): void
    {
        $fixture = Config::get(Config::PLUGINS_DIR) . '/orangehrmClaimPlugin/test/fixtures/EmployeeCommission.yaml';
        TestDataService::populate($fixture);
    }

    public function testEntity(): void
    {
        $commission = new EmployeeCommission();
        $commission->setEmployee($this->getReference(Employee::class, 1));
        $commission->setSaleDate(new DateTime('2024-06-30'));
        $commission->setAmount(99.99);
        $commission->setDescription('Test product');
        $commission->setAssignedBy($this->getReference(User::class, 1));
        $commission->setCreatedAt(new DateTime('2024-06-30 08:00:00'));
        $this->persist($commission);

        $this->assertGreaterThan(0, $commission->getId());
        $this->assertEquals(1, $commission->getEmployee()->getEmpNumber());
        $this->assertEquals('2024-06-30', $commission->getSaleDate()->format('Y-m-d'));
        $this->assertEquals(99.99, $commission->getAmount());
        $this->assertEquals('Test product', $commission->getDescription());
        $this->assertEquals(1, $commission->getAssignedBy()->getId());
    }
}
