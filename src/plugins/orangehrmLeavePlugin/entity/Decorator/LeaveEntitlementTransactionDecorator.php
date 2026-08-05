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
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\LeaveEntitlementTransaction;
use OrangeHRM\Entity\LeaveType;
use OrangeHRM\Entity\User;

class LeaveEntitlementTransactionDecorator
{
    use EntityManagerHelperTrait;
    use DateTimeHelperTrait;

    /**
     * @var LeaveEntitlementTransaction
     */
    private LeaveEntitlementTransaction $transaction;

    /**
     * @param LeaveEntitlementTransaction $transaction
     */
    public function __construct(LeaveEntitlementTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return LeaveEntitlementTransaction
     */
    protected function getTransaction(): LeaveEntitlementTransaction
    {
        return $this->transaction;
    }

    /**
     * @param int $empNumber
     */
    public function setEmployeeByEmpNumber(int $empNumber): void
    {
        /** @var Employee $employee */
        $employee = $this->getReference(Employee::class, $empNumber);
        $this->getTransaction()->setEmployee($employee);
    }

    /**
     * @param int $leaveTypeId
     */
    public function setLeaveTypeById(int $leaveTypeId): void
    {
        /** @var LeaveType $leaveType */
        $leaveType = $this->getReference(LeaveType::class, $leaveTypeId);
        $this->getTransaction()->setLeaveType($leaveType);
    }

    /**
     * @param int|null $userId
     */
    public function setCreatedById(?int $userId): void
    {
        if ($userId === null) {
            $this->getTransaction()->setCreatedBy(null);
            return;
        }
        /** @var User $user */
        $user = $this->getReference(User::class, $userId);
        $this->getTransaction()->setCreatedBy($user);
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->getDateTimeHelper()->formatDateTimeToYmd($this->getTransaction()->getCreatedAt())
            . ' ' . $this->getDateTimeHelper()->formatDateTimeToTimeString($this->getTransaction()->getCreatedAt());
    }
}
