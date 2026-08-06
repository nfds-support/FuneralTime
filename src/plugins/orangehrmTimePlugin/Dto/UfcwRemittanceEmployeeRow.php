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

class UfcwRemittanceEmployeeRow
{
    private int $empNumber = 0;
    private string $sin = '';
    private string $employeeId = '';
    private string $fullName = '';
    private string $fullAddress = '';
    private string $city = '';
    private string $province = '';
    private string $postalCode = '';
    private string $telephone = '';
    private string $email = '';
    private ?string $dateOfHire = null;
    private ?float $rateOfPay = null;
    private string $classification = '';
    private string $ftPtDesignation = '';
    private string $payrollPeriods = '';
    private string $weekEndingDates = '';
    private float $unionDuesDeducted = 0.0;
    private float $initiationFeesDeducted = 0.0;
    private string $reasonNoDeduction = '';
    private string $notes = '';
    /** @var string[] */
    private array $reviewFlags = [];
    private float $initiationFeeRequired = 0.0;
    private float $initiationFeePaidToDate = 0.0;
    private float $initiationFeeRemaining = 0.0;
    private int $weeksWithHours = 0;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'empNumber' => $this->empNumber,
            'sin' => $this->sin,
            'employeeId' => $this->employeeId,
            'fullName' => $this->fullName,
            'fullAddress' => $this->fullAddress,
            'city' => $this->city,
            'province' => $this->province,
            'postalCode' => $this->postalCode,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'dateOfHire' => $this->dateOfHire,
            'rateOfPay' => $this->rateOfPay,
            'classification' => $this->classification,
            'ftPtDesignation' => $this->ftPtDesignation,
            'payrollPeriods' => $this->payrollPeriods,
            'weekEndingDates' => $this->weekEndingDates,
            'unionDuesDeducted' => round($this->unionDuesDeducted, 2),
            'initiationFeesDeducted' => round($this->initiationFeesDeducted, 2),
            'totalDeducted' => round($this->unionDuesDeducted + $this->initiationFeesDeducted, 2),
            'reasonNoDeduction' => $this->reasonNoDeduction,
            'notes' => $this->notes,
            'reviewFlags' => $this->reviewFlags,
            'initiationFeeRequired' => round($this->initiationFeeRequired, 2),
            'initiationFeePaidToDate' => round($this->initiationFeePaidToDate, 2),
            'initiationFeeRemaining' => round($this->initiationFeeRemaining, 2),
            'weeksWithHours' => $this->weeksWithHours,
            'missingRequiredFields' => $this->getMissingRequiredFields(),
            'needsNoDeductionReason' => $this->needsNoDeductionReason(),
        ];
    }

    /**
     * @param array<string, mixed> $override
     */
    public function applyOverride(array $override): void
    {
        if (array_key_exists('unionDuesDeducted', $override) && $override['unionDuesDeducted'] !== null) {
            $this->unionDuesDeducted = (float) $override['unionDuesDeducted'];
        }
        if (array_key_exists('initiationFeesDeducted', $override) && $override['initiationFeesDeducted'] !== null) {
            $this->initiationFeesDeducted = (float) $override['initiationFeesDeducted'];
        }
        if (array_key_exists('reasonNoDeduction', $override) && $override['reasonNoDeduction'] !== null) {
            $this->reasonNoDeduction = (string) $override['reasonNoDeduction'];
        }
        if (array_key_exists('notes', $override) && $override['notes'] !== null) {
            $this->notes = (string) $override['notes'];
        }
        if (array_key_exists('telephone', $override) && $override['telephone'] !== null) {
            $this->telephone = (string) $override['telephone'];
        }
        if (array_key_exists('email', $override) && $override['email'] !== null) {
            $this->email = (string) $override['email'];
        }
        if (array_key_exists('rateOfPay', $override) && $override['rateOfPay'] !== null && $override['rateOfPay'] !== '') {
            $this->rateOfPay = (float) $override['rateOfPay'];
        }
        if (array_key_exists('classification', $override) && $override['classification'] !== null) {
            $this->classification = (string) $override['classification'];
        }
        if (array_key_exists('ftPtDesignation', $override) && $override['ftPtDesignation'] !== null) {
            $this->ftPtDesignation = (string) $override['ftPtDesignation'];
        }
        if (array_key_exists('payrollPeriods', $override) && $override['payrollPeriods'] !== null) {
            $this->payrollPeriods = (string) $override['payrollPeriods'];
        }
        if (array_key_exists('weekEndingDates', $override) && $override['weekEndingDates'] !== null) {
            $this->weekEndingDates = (string) $override['weekEndingDates'];
        }
    }

    /**
     * @return string[]
     */
    public function getMissingRequiredFields(): array
    {
        $missing = [];
        if ($this->telephone === '') {
            $missing[] = 'telephone';
        }
        if ($this->email === '') {
            $missing[] = 'email';
        }
        if ($this->rateOfPay === null) {
            $missing[] = 'rateOfPay';
        }
        if ($this->classification === '') {
            $missing[] = 'classification';
        }
        if ($this->ftPtDesignation === '' || $this->ftPtDesignation === 'Other / N/A') {
            // Other/N/A is allowed as a designation value; only blank is required-missing.
        }
        if ($this->ftPtDesignation === '') {
            $missing[] = 'ftPtDesignation';
        }
        return $missing;
    }

    public function needsNoDeductionReason(): bool
    {
        return $this->fullName !== ''
            && abs($this->unionDuesDeducted) < 0.00001
            && abs($this->initiationFeesDeducted) < 0.00001
            && trim($this->reasonNoDeduction) === '';
    }

    public function getEmpNumber(): int
    {
        return $this->empNumber;
    }

    public function setEmpNumber(int $empNumber): void
    {
        $this->empNumber = $empNumber;
    }

    public function getSin(): string
    {
        return $this->sin;
    }

    public function setSin(string $sin): void
    {
        $this->sin = $sin;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(string $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullAddress(): string
    {
        return $this->fullAddress;
    }

    public function setFullAddress(string $fullAddress): void
    {
        $this->fullAddress = $fullAddress;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getProvince(): string
    {
        return $this->province;
    }

    public function setProvince(string $province): void
    {
        $this->province = $province;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getDateOfHire(): ?string
    {
        return $this->dateOfHire;
    }

    public function setDateOfHire(?string $dateOfHire): void
    {
        $this->dateOfHire = $dateOfHire;
    }

    public function getRateOfPay(): ?float
    {
        return $this->rateOfPay;
    }

    public function setRateOfPay(?float $rateOfPay): void
    {
        $this->rateOfPay = $rateOfPay;
    }

    public function getClassification(): string
    {
        return $this->classification;
    }

    public function setClassification(string $classification): void
    {
        $this->classification = $classification;
    }

    public function getFtPtDesignation(): string
    {
        return $this->ftPtDesignation;
    }

    public function setFtPtDesignation(string $ftPtDesignation): void
    {
        $this->ftPtDesignation = $ftPtDesignation;
    }

    public function getPayrollPeriods(): string
    {
        return $this->payrollPeriods;
    }

    public function setPayrollPeriods(string $payrollPeriods): void
    {
        $this->payrollPeriods = $payrollPeriods;
    }

    public function getWeekEndingDates(): string
    {
        return $this->weekEndingDates;
    }

    public function setWeekEndingDates(string $weekEndingDates): void
    {
        $this->weekEndingDates = $weekEndingDates;
    }

    public function getUnionDuesDeducted(): float
    {
        return $this->unionDuesDeducted;
    }

    public function setUnionDuesDeducted(float $unionDuesDeducted): void
    {
        $this->unionDuesDeducted = $unionDuesDeducted;
    }

    public function getInitiationFeesDeducted(): float
    {
        return $this->initiationFeesDeducted;
    }

    public function setInitiationFeesDeducted(float $initiationFeesDeducted): void
    {
        $this->initiationFeesDeducted = $initiationFeesDeducted;
    }

    public function getReasonNoDeduction(): string
    {
        return $this->reasonNoDeduction;
    }

    public function setReasonNoDeduction(string $reasonNoDeduction): void
    {
        $this->reasonNoDeduction = $reasonNoDeduction;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return string[]
     */
    public function getReviewFlags(): array
    {
        return $this->reviewFlags;
    }

    /**
     * @param string[] $reviewFlags
     */
    public function setReviewFlags(array $reviewFlags): void
    {
        $this->reviewFlags = $reviewFlags;
    }

    public function addReviewFlag(string $flag): void
    {
        if (!in_array($flag, $this->reviewFlags, true)) {
            $this->reviewFlags[] = $flag;
        }
    }

    public function getInitiationFeeRequired(): float
    {
        return $this->initiationFeeRequired;
    }

    public function setInitiationFeeRequired(float $initiationFeeRequired): void
    {
        $this->initiationFeeRequired = $initiationFeeRequired;
    }

    public function getInitiationFeePaidToDate(): float
    {
        return $this->initiationFeePaidToDate;
    }

    public function setInitiationFeePaidToDate(float $initiationFeePaidToDate): void
    {
        $this->initiationFeePaidToDate = $initiationFeePaidToDate;
    }

    public function getInitiationFeeRemaining(): float
    {
        return $this->initiationFeeRemaining;
    }

    public function setInitiationFeeRemaining(float $initiationFeeRemaining): void
    {
        $this->initiationFeeRemaining = $initiationFeeRemaining;
    }

    public function getWeeksWithHours(): int
    {
        return $this->weeksWithHours;
    }

    public function setWeeksWithHours(int $weeksWithHours): void
    {
        $this->weeksWithHours = $weeksWithHours;
    }
}
