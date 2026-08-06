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

namespace OrangeHRM\Time\Service;

use OrangeHRM\Core\Dao\ConfigDao;
use OrangeHRM\Time\Dto\UfcwRemittanceSettings;

class UfcwRemittanceSettingsService
{
    public const KEY_DUES_HOURLY_MULTIPLIER = 'time.ufcw.dues_hourly_multiplier';
    public const KEY_DUES_WEEKLY_FLAT_FEE = 'time.ufcw.dues_weekly_flat_fee';
    public const KEY_INITIATION_FEE_FULL_TIME = 'time.ufcw.initiation_fee_full_time';
    public const KEY_INITIATION_FEE_PART_TIME = 'time.ufcw.initiation_fee_part_time';
    public const KEY_INITIATION_WEEKLY_MAX_FULL_TIME = 'time.ufcw.initiation_weekly_max_full_time';
    public const KEY_INITIATION_WEEKLY_MAX_PART_TIME = 'time.ufcw.initiation_weekly_max_part_time';
    public const KEY_EMPLOYER_NAME = 'time.ufcw.employer_name';
    public const KEY_WORK_LOCATION = 'time.ufcw.work_location';
    public const KEY_WORK_LOCATION_CODE = 'time.ufcw.work_location_code';
    public const KEY_UNION_CONTACTS = 'time.ufcw.union_contacts';
    public const KEY_MEMBERSHIP_NAME = 'time.ufcw.membership_name';
    public const KEY_REMITTANCE_EMAIL = 'time.ufcw.remittance_email';
    public const KEY_PAYROLL_EMAIL = 'time.ufcw.payroll_email';
    public const KEY_CHEQUE_PAYABLE_TO = 'time.ufcw.cheque_payable_to';
    public const KEY_CHEQUE_ATTENTION = 'time.ufcw.cheque_attention';

    private ?ConfigDao $configDao = null;

    public function getConfigDao(): ConfigDao
    {
        return $this->configDao ??= new ConfigDao();
    }

    public function setConfigDao(ConfigDao $configDao): void
    {
        $this->configDao = $configDao;
    }

    /**
     * @return UfcwRemittanceSettings
     */
    public function getSettings(): UfcwRemittanceSettings
    {
        $settings = new UfcwRemittanceSettings();
        $settings->apply([
            'duesHourlyMultiplier' => $this->getFloat(self::KEY_DUES_HOURLY_MULTIPLIER, 0.6),
            'duesWeeklyFlatFee' => $this->getFloat(self::KEY_DUES_WEEKLY_FLAT_FEE, 0.25),
            'initiationFeeFullTime' => $this->getFloat(self::KEY_INITIATION_FEE_FULL_TIME, 40.0),
            'initiationFeePartTime' => $this->getFloat(self::KEY_INITIATION_FEE_PART_TIME, 25.0),
            'initiationWeeklyMaxFullTime' => $this->getFloat(self::KEY_INITIATION_WEEKLY_MAX_FULL_TIME, 10.0),
            'initiationWeeklyMaxPartTime' => $this->getFloat(self::KEY_INITIATION_WEEKLY_MAX_PART_TIME, 5.0),
            'employerName' => $this->getString(self::KEY_EMPLOYER_NAME, 'Timiskaming Funeral Cooperative'),
            'workLocation' => $this->getString(self::KEY_WORK_LOCATION, 'Timiskaming Funeral Cooperative'),
            'workLocationCode' => $this->getString(self::KEY_WORK_LOCATION_CODE, '7297'),
            'unionContacts' => $this->getString(self::KEY_UNION_CONTACTS, 'Michael Bernier / Sabrina Qadir'),
            'membershipName' => $this->getString(self::KEY_MEMBERSHIP_NAME, 'UFCW Local 175'),
            'remittanceEmail' => $this->getString(self::KEY_REMITTANCE_EMAIL, 'remit@ufcw175.com'),
            'payrollEmail' => $this->getString(self::KEY_PAYROLL_EMAIL, ''),
            'chequePayableTo' => $this->getString(self::KEY_CHEQUE_PAYABLE_TO, 'UFCW Local 175'),
            'chequeAttention' => $this->getString(self::KEY_CHEQUE_ATTENTION, 'Secretary-Treasurer'),
        ]);
        return $settings;
    }

    /**
     * @param UfcwRemittanceSettings $settings
     */
    public function saveSettings(UfcwRemittanceSettings $settings): void
    {
        $dao = $this->getConfigDao();
        $dao->setValue(self::KEY_DUES_HOURLY_MULTIPLIER, (string) $settings->getDuesHourlyMultiplier());
        $dao->setValue(self::KEY_DUES_WEEKLY_FLAT_FEE, (string) $settings->getDuesWeeklyFlatFee());
        $dao->setValue(self::KEY_INITIATION_FEE_FULL_TIME, (string) $settings->getInitiationFeeFullTime());
        $dao->setValue(self::KEY_INITIATION_FEE_PART_TIME, (string) $settings->getInitiationFeePartTime());
        $dao->setValue(self::KEY_INITIATION_WEEKLY_MAX_FULL_TIME, (string) $settings->getInitiationWeeklyMaxFullTime());
        $dao->setValue(self::KEY_INITIATION_WEEKLY_MAX_PART_TIME, (string) $settings->getInitiationWeeklyMaxPartTime());
        $dao->setValue(self::KEY_EMPLOYER_NAME, $settings->getEmployerName());
        $dao->setValue(self::KEY_WORK_LOCATION, $settings->getWorkLocation());
        $dao->setValue(self::KEY_WORK_LOCATION_CODE, $settings->getWorkLocationCode());
        $dao->setValue(self::KEY_UNION_CONTACTS, $settings->getUnionContacts());
        $dao->setValue(self::KEY_MEMBERSHIP_NAME, $settings->getMembershipName());
        $dao->setValue(self::KEY_REMITTANCE_EMAIL, $settings->getRemittanceEmail());
        $dao->setValue(self::KEY_PAYROLL_EMAIL, $settings->getPayrollEmail());
        $dao->setValue(self::KEY_CHEQUE_PAYABLE_TO, $settings->getChequePayableTo());
        $dao->setValue(self::KEY_CHEQUE_ATTENTION, $settings->getChequeAttention());
    }

    private function getFloat(string $key, float $default): float
    {
        $value = $this->getConfigDao()->getValue($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return (float) $value;
    }

    private function getString(string $key, string $default): string
    {
        $value = $this->getConfigDao()->getValue($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}
