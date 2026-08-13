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

namespace OrangeHRM\Performance\Dto;

use OrangeHRM\Core\Dto\FilterParams;

class MonthlyAssessmentSearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = [
        'monthlyAssessment.periodYear',
        'monthlyAssessment.periodMonth',
        'monthlyAssessment.status',
        'monthlyAssessment.createdAt',
        'employee.lastName',
    ];

    private ?int $empNumber = null;

    /**
     * @var int[]|null
     */
    private ?array $empNumbers = null;

    private ?int $managerEmpNumber = null;
    private ?int $periodYear = null;
    private ?int $periodMonth = null;
    private ?string $status = null;

    public function __construct()
    {
        $this->setSortField('monthlyAssessment.periodYear');
        $this->setSortOrder(self::SORT_DESC);
    }

    public function getEmpNumber(): ?int
    {
        return $this->empNumber;
    }

    public function setEmpNumber(?int $empNumber): void
    {
        $this->empNumber = $empNumber;
    }

    /**
     * @return int[]|null
     */
    public function getEmpNumbers(): ?array
    {
        return $this->empNumbers;
    }

    /**
     * @param int[]|null $empNumbers
     */
    public function setEmpNumbers(?array $empNumbers): void
    {
        $this->empNumbers = $empNumbers;
    }

    public function getManagerEmpNumber(): ?int
    {
        return $this->managerEmpNumber;
    }

    public function setManagerEmpNumber(?int $managerEmpNumber): void
    {
        $this->managerEmpNumber = $managerEmpNumber;
    }

    public function getPeriodYear(): ?int
    {
        return $this->periodYear;
    }

    public function setPeriodYear(?int $periodYear): void
    {
        $this->periodYear = $periodYear;
    }

    public function getPeriodMonth(): ?int
    {
        return $this->periodMonth;
    }

    public function setPeriodMonth(?int $periodMonth): void
    {
        $this->periodMonth = $periodMonth;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }
}
