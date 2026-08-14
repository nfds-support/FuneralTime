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
use OrangeHRM\Entity\EmployeeCommission;
use OrangeHRM\Entity\User;

class EmployeeCommissionDecorator
{
    use EntityManagerHelperTrait;
    use DateTimeHelperTrait;

    private EmployeeCommission $employeeCommission;

    /**
     * @param EmployeeCommission $employeeCommission
     */
    public function __construct(EmployeeCommission $employeeCommission)
    {
        $this->employeeCommission = $employeeCommission;
    }

    /**
     * @param int $empNumber
     */
    public function setEmployeeByEmpNumber(int $empNumber): void
    {
        /** @var Employee|null $employee */
        $employee = $this->getReference(Employee::class, $empNumber);
        $this->employeeCommission->setEmployee($employee);
    }

    /**
     * @param int $userId
     */
    public function setAssignedByByUserId(int $userId): void
    {
        /** @var User|null $user */
        $user = $this->getReference(User::class, $userId);
        $this->employeeCommission->setAssignedBy($user);
    }

    /**
     * @return string|null in Y-m-d format
     */
    public function getSaleDate(): ?string
    {
        return $this->getDateTimeHelper()->formatDate($this->employeeCommission->getSaleDate());
    }
}
