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

class PayrollFillSheetRow
{
    private int $empNumber;
    private ?string $employeeId = null;
    private string $lastName = '';
    private string $firstName = '';
    private string $group = '';
    private float $regularW1 = 0.0;
    private float $regularW2 = 0.0;
    private float $otW1 = 0.0;
    private float $otW2 = 0.0;
    private int $onCallW1 = 0;
    private int $onCallW2 = 0;
    private float $sickHours = 0.0;
    private float $vacationW1 = 0.0;
    private float $vacationW2 = 0.0;
    private float $bankedW1 = 0.0;
    private float $bankedW2 = 0.0;

    public function getEmpNumber(): int
    {
        return $this->empNumber;
    }

    public function setEmpNumber(int $empNumber): void
    {
        $this->empNumber = $empNumber;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?string $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function getRegularW1(): float
    {
        return $this->regularW1;
    }

    public function setRegularW1(float $regularW1): void
    {
        $this->regularW1 = $regularW1;
    }

    public function getRegularW2(): float
    {
        return $this->regularW2;
    }

    public function setRegularW2(float $regularW2): void
    {
        $this->regularW2 = $regularW2;
    }

    public function getOtW1(): float
    {
        return $this->otW1;
    }

    public function setOtW1(float $otW1): void
    {
        $this->otW1 = $otW1;
    }

    public function getOtW2(): float
    {
        return $this->otW2;
    }

    public function setOtW2(float $otW2): void
    {
        $this->otW2 = $otW2;
    }

    public function getOnCallW1(): int
    {
        return $this->onCallW1;
    }

    public function setOnCallW1(int $onCallW1): void
    {
        $this->onCallW1 = $onCallW1;
    }

    public function getOnCallW2(): int
    {
        return $this->onCallW2;
    }

    public function setOnCallW2(int $onCallW2): void
    {
        $this->onCallW2 = $onCallW2;
    }

    public function getSickHours(): float
    {
        return $this->sickHours;
    }

    public function setSickHours(float $sickHours): void
    {
        $this->sickHours = $sickHours;
    }

    public function getVacationW1(): float
    {
        return $this->vacationW1;
    }

    public function setVacationW1(float $vacationW1): void
    {
        $this->vacationW1 = $vacationW1;
    }

    public function getVacationW2(): float
    {
        return $this->vacationW2;
    }

    public function setVacationW2(float $vacationW2): void
    {
        $this->vacationW2 = $vacationW2;
    }

    public function getBankedW1(): float
    {
        return $this->bankedW1;
    }

    public function setBankedW1(float $bankedW1): void
    {
        $this->bankedW1 = $bankedW1;
    }

    public function getBankedW2(): float
    {
        return $this->bankedW2;
    }

    public function setBankedW2(float $bankedW2): void
    {
        $this->bankedW2 = $bankedW2;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'empNumber' => $this->empNumber,
            'employeeId' => $this->employeeId,
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'group' => $this->group,
            'regularW1' => $this->regularW1,
            'regularW2' => $this->regularW2,
            'otW1' => $this->otW1,
            'otW2' => $this->otW2,
            'onCallW1' => $this->onCallW1,
            'onCallW2' => $this->onCallW2,
            'sickHours' => $this->sickHours,
            'vacationW1' => $this->vacationW1,
            'vacationW2' => $this->vacationW2,
            'bankedW1' => $this->bankedW1,
            'bankedW2' => $this->bankedW2,
        ];
    }
}
