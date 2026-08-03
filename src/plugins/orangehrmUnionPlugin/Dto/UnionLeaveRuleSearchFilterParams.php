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

namespace OrangeHRM\Union\Dto;

use OrangeHRM\Core\Dto\FilterParams;

class UnionLeaveRuleSearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = [
        'rule.minYears',
        'laborUnion.name',
        'leaveType.name',
    ];

    private ?int $unionId = null;
    private ?int $leaveTypeId = null;

    public function __construct()
    {
        $this->setSortField('rule.minYears');
    }

    public function getUnionId(): ?int
    {
        return $this->unionId;
    }

    public function setUnionId(?int $unionId): void
    {
        $this->unionId = $unionId;
    }

    public function getLeaveTypeId(): ?int
    {
        return $this->leaveTypeId;
    }

    public function setLeaveTypeId(?int $leaveTypeId): void
    {
        $this->leaveTypeId = $leaveTypeId;
    }
}
