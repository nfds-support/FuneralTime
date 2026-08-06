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

namespace OrangeHRM\Time\Dto;

class UfcwRemittanceSettings
{
    private float $duesHourlyMultiplier = 0.6;
    private float $duesWeeklyFlatFee = 0.25;
    private float $initiationFeeFullTime = 40.0;
    private float $initiationFeePartTime = 25.0;
    private float $initiationWeeklyMaxFullTime = 10.0;
    private float $initiationWeeklyMaxPartTime = 5.0;
    private string $employerName = 'Timiskaming Funeral Cooperative';
    private string $workLocation = 'Timiskaming Funeral Cooperative';
    private string $workLocationCode = '7297';
    private string $unionContacts = 'Michael Bernier / Sabrina Qadir';
    private string $membershipName = 'UFCW Local 175';
    private string $remittanceEmail = 'remit@ufcw175.com';
    private string $payrollEmail = '';
    private string $chequePayableTo = 'UFCW Local 175';
    private string $chequeAttention = 'Secretary-Treasurer';

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'duesHourlyMultiplier' => $this->duesHourlyMultiplier,
            'duesWeeklyFlatFee' => $this->duesWeeklyFlatFee,
            'initiationFeeFullTime' => $this->initiationFeeFullTime,
            'initiationFeePartTime' => $this->initiationFeePartTime,
            'initiationWeeklyMaxFullTime' => $this->initiationWeeklyMaxFullTime,
            'initiationWeeklyMaxPartTime' => $this->initiationWeeklyMaxPartTime,
            'employerName' => $this->employerName,
            'workLocation' => $this->workLocation,
            'workLocationCode' => $this->workLocationCode,
            'unionContacts' => $this->unionContacts,
            'membershipName' => $this->membershipName,
            'remittanceEmail' => $this->remittanceEmail,
            'payrollEmail' => $this->payrollEmail,
            'chequePayableTo' => $this->chequePayableTo,
            'chequeAttention' => $this->chequeAttention,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function apply(array $data): void
    {
        if (array_key_exists('duesHourlyMultiplier', $data)) {
            $this->duesHourlyMultiplier = (float) $data['duesHourlyMultiplier'];
        }
        if (array_key_exists('duesWeeklyFlatFee', $data)) {
            $this->duesWeeklyFlatFee = (float) $data['duesWeeklyFlatFee'];
        }
        if (array_key_exists('initiationFeeFullTime', $data)) {
            $this->initiationFeeFullTime = (float) $data['initiationFeeFullTime'];
        }
        if (array_key_exists('initiationFeePartTime', $data)) {
            $this->initiationFeePartTime = (float) $data['initiationFeePartTime'];
        }
        if (array_key_exists('initiationWeeklyMaxFullTime', $data)) {
            $this->initiationWeeklyMaxFullTime = (float) $data['initiationWeeklyMaxFullTime'];
        }
        if (array_key_exists('initiationWeeklyMaxPartTime', $data)) {
            $this->initiationWeeklyMaxPartTime = (float) $data['initiationWeeklyMaxPartTime'];
        }
        if (array_key_exists('employerName', $data)) {
            $this->employerName = (string) $data['employerName'];
        }
        if (array_key_exists('workLocation', $data)) {
            $this->workLocation = (string) $data['workLocation'];
        }
        if (array_key_exists('workLocationCode', $data)) {
            $this->workLocationCode = (string) $data['workLocationCode'];
        }
        if (array_key_exists('unionContacts', $data)) {
            $this->unionContacts = (string) $data['unionContacts'];
        }
        if (array_key_exists('membershipName', $data)) {
            $this->membershipName = (string) $data['membershipName'];
        }
        if (array_key_exists('remittanceEmail', $data)) {
            $this->remittanceEmail = (string) $data['remittanceEmail'];
        }
        if (array_key_exists('payrollEmail', $data)) {
            $this->payrollEmail = (string) $data['payrollEmail'];
        }
        if (array_key_exists('chequePayableTo', $data)) {
            $this->chequePayableTo = (string) $data['chequePayableTo'];
        }
        if (array_key_exists('chequeAttention', $data)) {
            $this->chequeAttention = (string) $data['chequeAttention'];
        }
    }

    public function getDuesHourlyMultiplier(): float
    {
        return $this->duesHourlyMultiplier;
    }

    public function getDuesWeeklyFlatFee(): float
    {
        return $this->duesWeeklyFlatFee;
    }

    public function getInitiationFeeFullTime(): float
    {
        return $this->initiationFeeFullTime;
    }

    public function getInitiationFeePartTime(): float
    {
        return $this->initiationFeePartTime;
    }

    public function getInitiationWeeklyMaxFullTime(): float
    {
        return $this->initiationWeeklyMaxFullTime;
    }

    public function getInitiationWeeklyMaxPartTime(): float
    {
        return $this->initiationWeeklyMaxPartTime;
    }

    public function getEmployerName(): string
    {
        return $this->employerName;
    }

    public function getWorkLocation(): string
    {
        return $this->workLocation;
    }

    public function getWorkLocationCode(): string
    {
        return $this->workLocationCode;
    }

    public function getUnionContacts(): string
    {
        return $this->unionContacts;
    }

    public function getMembershipName(): string
    {
        return $this->membershipName;
    }

    public function getRemittanceEmail(): string
    {
        return $this->remittanceEmail;
    }

    public function getPayrollEmail(): string
    {
        return $this->payrollEmail;
    }

    public function getChequePayableTo(): string
    {
        return $this->chequePayableTo;
    }

    public function getChequeAttention(): string
    {
        return $this->chequeAttention;
    }
}
