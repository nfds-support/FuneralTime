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

namespace OrangeHRM\Entity\Decorator;

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\LaborUnion;
use OrangeHRM\Entity\LeaveType;
use OrangeHRM\Entity\UnionLeaveRule;

class UnionLeaveRuleDecorator
{
    use EntityManagerHelperTrait;

    private UnionLeaveRule $unionLeaveRule;

    public function __construct(UnionLeaveRule $unionLeaveRule)
    {
        $this->unionLeaveRule = $unionLeaveRule;
    }

    public function setLaborUnionById(?int $unionId): void
    {
        if ($unionId === null) {
            $this->unionLeaveRule->setLaborUnion(null);
            return;
        }
        /** @var LaborUnion $union */
        $union = $this->getReference(LaborUnion::class, $unionId);
        $this->unionLeaveRule->setLaborUnion($union);
    }

    public function setLeaveTypeById(int $leaveTypeId): void
    {
        /** @var LeaveType $leaveType */
        $leaveType = $this->getReference(LeaveType::class, $leaveTypeId);
        $this->unionLeaveRule->setLeaveType($leaveType);
    }
}
