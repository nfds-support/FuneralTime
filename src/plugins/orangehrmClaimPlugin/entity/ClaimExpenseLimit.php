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

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;
use OrangeHRM\Entity\Decorator\ClaimExpenseLimitDecorator;
use OrangeHRM\Entity\Decorator\DecoratorTrait;

/**
 * @method ClaimExpenseLimitDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_claim_expense_limit")
 * @ORM\Entity
 */
class ClaimExpenseLimit
{
    use DecoratorTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="emp_number", referencedColumnName="emp_number")
     */
    private Employee $employee;

    /**
     * @var ExpenseType
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\ExpenseType")
     * @ORM\JoinColumn(name="expense_type_id", referencedColumnName="id")
     */
    private ExpenseType $expenseType;

    /**
     * @var string
     *
     * @ORM\Column(name="monthly_limit", type="decimal", precision=12, scale=2)
     */
    private string $monthlyLimit;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getExpenseType(): ExpenseType
    {
        return $this->expenseType;
    }

    public function setExpenseType(ExpenseType $expenseType): void
    {
        $this->expenseType = $expenseType;
    }

    public function getMonthlyLimit(): string
    {
        return $this->monthlyLimit;
    }

    public function setMonthlyLimit(string $monthlyLimit): void
    {
        $this->monthlyLimit = $monthlyLimit;
    }
}
