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

namespace OrangeHRM\Tests\Claim\Service;

use OrangeHRM\Claim\Service\ExpenseClaimReportService;
use OrangeHRM\Config\Config;
use OrangeHRM\Tests\Util\KernelTestCase;
use OrangeHRM\Tests\Util\TestDataService;

/**
 * @group Claim
 * @group Service
 */
class ExpenseClaimReportServiceTest extends KernelTestCase
{
    private ExpenseClaimReportService $service;

    protected function setUp(): void
    {
        $this->service = new ExpenseClaimReportService();
        $fixture = Config::get(Config::PLUGINS_DIR) . '/orangehrmClaimPlugin/test/fixtures/EmployeeCommission.yaml';
        TestDataService::populate($fixture);
    }

    public function testBuildRowsIncludesCombinedCommissionOnLastDay(): void
    {
        $rows = $this->service->buildRows(1, 2024, 6);
        $this->assertNotEmpty($rows);

        $last = $rows[array_key_last($rows)];
        $this->assertEquals('2024-06-30', $last['date']);
        $this->assertEquals(175.5, $last['other']);
        $this->assertEquals('Commission', $last['otherNote']);
    }

    public function testBuildRowsOmitsCommissionWhenNoneExist(): void
    {
        $rows = $this->service->buildRows(1, 2024, 1);
        foreach ($rows as $row) {
            $this->assertNotSame('Commission', $row['otherNote']);
        }
    }
}
