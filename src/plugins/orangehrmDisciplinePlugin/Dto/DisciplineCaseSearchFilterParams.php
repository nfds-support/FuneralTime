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

namespace OrangeHRM\Discipline\Dto;

use OrangeHRM\Core\Dto\FilterParams;

class DisciplineCaseSearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = [
        'disciplineCase.incidentDate',
        'disciplineCase.createdAt',
        'disciplineCase.status',
        'disciplineCase.caseType',
        'disciplineCase.subject',
        'employee.lastName',
    ];

    private ?int $empNumber = null;
    private ?string $caseType = null;
    private ?string $status = null;
    /** @var int[]|null */
    private ?array $empNumbers = null;

    public function __construct()
    {
        $this->setSortField('disciplineCase.createdAt');
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

    public function getCaseType(): ?string
    {
        return $this->caseType;
    }

    public function setCaseType(?string $caseType): void
    {
        $this->caseType = $caseType;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
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
}
