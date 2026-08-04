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

namespace OrangeHRM\Claim\Service;

use OrangeHRM\Claim\Dao\ClaimDao;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Entity\ClaimExpense;
use OrangeHRM\Entity\ClaimExpenseLimit;
use OrangeHRM\Entity\Employee;

class ExpenseClaimLimitService
{
    public const REPORT_COLUMN_MILEAGE = 'mileage';
    public const DEFAULT_MILEAGE_RATE = 0.55;

    /**
     * @var ClaimDao|null
     */
    protected ?ClaimDao $claimDao = null;

    /**
     * @return ClaimDao
     */
    public function getClaimDao(): ClaimDao
    {
        return $this->claimDao ??= new ClaimDao();
    }

    /**
     * @param ClaimDao $claimDao
     */
    public function setClaimDao(ClaimDao $claimDao): void
    {
        $this->claimDao = $claimDao;
    }

    /**
     * @param ClaimExpenseLimit $claimExpenseLimit
     * @return ClaimExpenseLimit
     */
    public function saveClaimExpenseLimit(ClaimExpenseLimit $claimExpenseLimit): ClaimExpenseLimit
    {
        return $this->getClaimDao()->saveClaimExpenseLimit($claimExpenseLimit);
    }

    /**
     * @param int $id
     * @return ClaimExpenseLimit|null
     */
    public function getClaimExpenseLimitById(int $id): ?ClaimExpenseLimit
    {
        return $this->getClaimDao()->getClaimExpenseLimitById($id);
    }

    /**
     * @param int $empNumber
     * @return ClaimExpenseLimit[]
     */
    public function getClaimExpenseLimitsByEmpNumber(int $empNumber): array
    {
        return $this->getClaimDao()->getClaimExpenseLimitsByEmpNumber($empNumber);
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteClaimExpenseLimitsByIds(array $ids): int
    {
        return $this->getClaimDao()->deleteClaimExpenseLimitsByIds($ids);
    }

    /**
     * @param int $empNumber
     * @param int $expenseTypeId
     * @return ClaimExpenseLimit|null
     */
    public function getClaimExpenseLimit(int $empNumber, int $expenseTypeId): ?ClaimExpenseLimit
    {
        return $this->getClaimDao()->getClaimExpenseLimit($empNumber, $expenseTypeId);
    }

    /**
     * @param ClaimExpense $expense
     * @throws BadRequestException
     */
    public function assertWithinMonthlyLimit(ClaimExpense $expense): void
    {
        $employee = $expense->getClaimRequest()->getEmployee();
        $expenseType = $expense->getExpenseType();
        $limit = $this->getClaimExpenseLimit($employee->getEmpNumber(), $expenseType->getId());
        if ($limit === null) {
            return;
        }

        $date = $expense->getDate();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $excludeId = $this->getExpenseIdOrNull($expense);

        $existingTotal = $this->getClaimDao()->getMonthlyCategoryExpenseTotal(
            $employee->getEmpNumber(),
            $expenseType->getId(),
            $year,
            $month,
            $excludeId
        );
        $projected = $existingTotal + $expense->getAmount();
        $monthlyLimit = (float) $limit->getMonthlyLimit();
        if ($projected > $monthlyLimit) {
            throw new BadRequestException(
                sprintf(
                    'Expense amount exceeds the monthly limit of %.2f for expense type "%s" (current total: %.2f).',
                    $monthlyLimit,
                    $expenseType->getName(),
                    $existingTotal
                )
            );
        }
    }

    /**
     * @param ClaimExpense $expense
     * @return int|null
     */
    private function getExpenseIdOrNull(ClaimExpense $expense): ?int
    {
        $ref = new \ReflectionProperty(ClaimExpense::class, 'id');
        return $ref->isInitialized($expense) ? $expense->getId() : null;
    }

    /**
     * When the expense type maps to the mileage report column, compute amount from km × rate.
     *
     * @param ClaimExpense $expense
     * @param Employee $employee
     * @throws BadRequestException
     */
    public function applyMileageAmount(ClaimExpense $expense, Employee $employee): void
    {
        if ($expense->getExpenseType()->getReportColumn() !== self::REPORT_COLUMN_MILEAGE) {
            return;
        }

        $quantityKm = $expense->getQuantityKm();
        if ($quantityKm === null || $quantityKm === '') {
            throw new BadRequestException('quantityKm is required for mileage expense types.');
        }

        $rate = $employee->getMileageReimbursementRate();
        $rateValue = $rate === null || $rate === '' ? self::DEFAULT_MILEAGE_RATE : (float) $rate;
        $expense->setAmount(round((float) $quantityKm * $rateValue, 2));
    }
}
