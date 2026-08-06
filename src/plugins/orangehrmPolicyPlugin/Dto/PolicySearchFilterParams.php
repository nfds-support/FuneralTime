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

namespace OrangeHRM\Policy\Dto;

use OrangeHRM\Core\Dto\FilterParams;

class PolicySearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = [
        'policy.title',
        'policy.status',
        'policy.effectiveDate',
        'policy.createdAt',
        'policy.version',
    ];

    private ?string $status = null;
    private ?string $audienceType = null;
    private ?int $jobTitleId = null;
    private ?bool $pendingOnly = null;
    private ?int $empNumber = null;

    public function __construct()
    {
        $this->setSortField('policy.createdAt');
        $this->setSortOrder(self::SORT_DESC);
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getAudienceType(): ?string
    {
        return $this->audienceType;
    }

    public function setAudienceType(?string $audienceType): void
    {
        $this->audienceType = $audienceType;
    }

    public function getJobTitleId(): ?int
    {
        return $this->jobTitleId;
    }

    public function setJobTitleId(?int $jobTitleId): void
    {
        $this->jobTitleId = $jobTitleId;
    }

    public function getPendingOnly(): ?bool
    {
        return $this->pendingOnly;
    }

    public function setPendingOnly(?bool $pendingOnly): void
    {
        $this->pendingOnly = $pendingOnly;
    }

    public function getEmpNumber(): ?int
    {
        return $this->empNumber;
    }

    public function setEmpNumber(?int $empNumber): void
    {
        $this->empNumber = $empNumber;
    }
}
