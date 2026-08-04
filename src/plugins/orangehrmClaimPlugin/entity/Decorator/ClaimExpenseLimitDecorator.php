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
use OrangeHRM\Entity\ClaimExpenseLimit;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\ExpenseType;

class ClaimExpenseLimitDecorator
{
    use EntityManagerHelperTrait;

    private ClaimExpenseLimit $claimExpenseLimit;

    public function __construct(ClaimExpenseLimit $claimExpenseLimit)
    {
        $this->claimExpenseLimit = $claimExpenseLimit;
    }

    public function setEmployeeByEmpNumber(int $empNumber): void
    {
        /** @var Employee|null $employee */
        $employee = $this->getReference(Employee::class, $empNumber);
        $this->claimExpenseLimit->setEmployee($employee);
    }

    public function setExpenseTypeById(int $expenseTypeId): void
    {
        /** @var ExpenseType|null $expenseType */
        $expenseType = $this->getReference(ExpenseType::class, $expenseTypeId);
        $this->claimExpenseLimit->setExpenseType($expenseType);
    }
}
