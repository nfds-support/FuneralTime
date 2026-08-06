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

use DateTime;
use OrangeHRM\Tests\Util\TestCase;
use OrangeHRM\Time\Dto\UfcwRemittanceSettings;
use OrangeHRM\Time\Service\UfcwRemittanceReportService;

class UfcwRemittanceReportServiceTest extends TestCase
{
    public function testCalculateWeeklyDuesUsesConfiguredMultiplierAndFlatFee(): void
    {
        $service = new UfcwRemittanceReportService();
        $settings = new UfcwRemittanceSettings();
        $settings->apply([
            'duesHourlyMultiplier' => 0.6,
            'duesWeeklyFlatFee' => 0.25,
        ]);

        $this->assertSame(12.25, $service->calculateWeeklyDues(20.0, $settings));
    }

    public function testCalculateWeeklyDuesRespectsUpdatedConfigValues(): void
    {
        $service = new UfcwRemittanceReportService();
        $settings = new UfcwRemittanceSettings();
        $settings->apply([
            'duesHourlyMultiplier' => 0.5,
            'duesWeeklyFlatFee' => 1.0,
        ]);

        $this->assertSame(11.0, $service->calculateWeeklyDues(20.0, $settings));
    }

    public function testRemittanceDueDateIsFifteenthOfFollowingMonth(): void
    {
        $service = new UfcwRemittanceReportService();
        $due = $service->calculateRemittanceDueDate(new DateTime('2026-07-01'));
        $this->assertSame('2026-08-15', $due->format('Y-m-d'));

        $dueDec = $service->calculateRemittanceDueDate(new DateTime('2026-12-01'));
        $this->assertSame('2027-01-15', $dueDec->format('Y-m-d'));
    }

    public function testDownloadFilenameUsesReportMonth(): void
    {
        $service = new UfcwRemittanceReportService();
        $this->assertSame(
            'UFCW_Remittance_2026-08.xlsx',
            $service->buildDownloadFilename(new DateTime('2026-08-01'))
        );
    }
}
