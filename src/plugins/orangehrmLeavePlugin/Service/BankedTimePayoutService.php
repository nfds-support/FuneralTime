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

namespace OrangeHRM\Leave\Service;

use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\LeaveEntitlementTransaction;
use OrangeHRM\Leave\Dto\LeaveEntitlementTransactionSearchFilterParams;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementServiceTrait;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementTransactionServiceTrait;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use OrangeHRM\Time\Service\BankedTimeService;
use OrangeHRM\Time\Traits\Service\BankedTimeServiceTrait;
use Exception;

class BankedTimePayoutService
{
    use BankedTimeServiceTrait;
    use EmployeeServiceTrait;
    use LeaveEntitlementServiceTrait;
    use LeaveEntitlementTransactionServiceTrait;

    public const NOTE_PREFIX = 'Banked time payout';

    /**
     * @return array{bankedHours: float, bankedDays: float, hoursPerDay: float, bankedTimeLeaveTypeId: int|null}
     */
    public function getBankedBalance(int $empNumber): array
    {
        $employee = $this->getEmployeeService()->getEmployeeByEmpNumber($empNumber);
        $hoursPerDay = $employee instanceof Employee
            ? $this->getBankedTimeService()->getHoursPerDayForEmployee($employee)
            : BankedTimeService::DEFAULT_HOURS_PER_DAY;
        $leaveTypeId = $this->getBankedTimeService()->getBankedTimeLeaveTypeId();
        $bankedDays = 0.0;
        if ($leaveTypeId !== null && $employee instanceof Employee) {
            $bankedDays = $this->getLeaveEntitlementService()
                ->getLeaveBalance($empNumber, $leaveTypeId)
                ->getBalance();
        }

        return [
            'bankedHours' => round($bankedDays * $hoursPerDay, 4),
            'bankedDays' => round($bankedDays, 4),
            'hoursPerDay' => $hoursPerDay,
            'bankedTimeLeaveTypeId' => $leaveTypeId,
        ];
    }

    /**
     * Pay out (deduct) banked hours for an employee. Hours only — no money amounts.
     *
     * @throws Exception
     */
    public function payout(int $empNumber, float $hours, ?string $comment = null): LeaveEntitlementTransaction
    {
        $employee = $this->getEmployeeService()->getEmployeeByEmpNumber($empNumber);
        if (!$employee instanceof Employee) {
            throw new Exception('Employee not found');
        }
        if ($hours <= 0) {
            throw new Exception('Payout hours must be greater than zero');
        }
        $leaveTypeId = $this->getBankedTimeService()->getBankedTimeLeaveTypeId();
        if ($leaveTypeId === null) {
            throw new Exception('Banked Time leave type is not configured');
        }

        $balance = $this->getBankedBalance($empNumber);
        if ($hours > $balance['bankedHours'] + 0.0001) {
            throw new Exception('Insufficient banked hours for this payout');
        }
        $days = $this->getBankedTimeService()->hoursToDays($hours, $balance['hoursPerDay']);
        if ($days <= 0) {
            throw new Exception('Calculated banked days must be greater than zero');
        }

        $note = self::NOTE_PREFIX;
        if (is_string($comment) && trim($comment) !== '') {
            $note .= ': ' . trim($comment);
            if (strlen($note) > 255) {
                $note = substr($note, 0, 252) . '...';
            }
        }

        return $this->getLeaveEntitlementTransactionService()->createAdjustment(
            $empNumber,
            $leaveTypeId,
            LeaveEntitlementTransaction::TYPE_DEDUCTION,
            $days,
            $note
        );
    }

    /**
     * Recent banked time payouts (hours), newest first.
     *
     * @return array<int, array{
     *   id: int, empNumber: int, employeeName: string, hours: float,
     *   balanceAfterHours: float|null, note: string|null, createdAt: string|null,
     *   createdBy: string|null
     * }>
     */
    public function getPayoutHistory(?int $empNumber = null, int $limit = 50): array
    {
        $leaveTypeId = $this->getBankedTimeService()->getBankedTimeLeaveTypeId();
        if ($leaveTypeId === null) {
            return [];
        }

        $filterParams = new LeaveEntitlementTransactionSearchFilterParams();
        $filterParams->setLeaveTypeId($leaveTypeId);
        $filterParams->setTransactionType(LeaveEntitlementTransaction::TYPE_DEDUCTION);
        if ($empNumber !== null) {
            $filterParams->setEmpNumber($empNumber);
        }
        $filterParams->setLimit(200);
        $filterParams->setOffset(0);

        $hoursPerDayByEmp = [];
        $rows = [];
        foreach ($this->getLeaveEntitlementTransactionService()->search($filterParams) as $txn) {
            $note = $txn->getNote() ?? '';
            if (strpos($note, self::NOTE_PREFIX) !== 0) {
                continue;
            }
            $txnEmpNumber = $txn->getEmployee()->getEmpNumber();
            if (!isset($hoursPerDayByEmp[$txnEmpNumber])) {
                $hoursPerDayByEmp[$txnEmpNumber] = $this->getBankedTimeService()
                    ->getHoursPerDayForEmployee($txn->getEmployee());
            }
            $hoursPerDay = $hoursPerDayByEmp[$txnEmpNumber];
            $employee = $txn->getEmployee();
            $createdBy = $txn->getCreatedBy();
            $rows[] = [
                'id' => $txn->getId(),
                'empNumber' => $txnEmpNumber,
                'employeeName' => trim($employee->getFirstName() . ' ' . $employee->getLastName()),
                'hours' => round(((float) $txn->getDays()) * $hoursPerDay, 2),
                'balanceAfterHours' => $txn->getBalanceAfter() !== null
                    ? round(((float) $txn->getBalanceAfter()) * $hoursPerDay, 2)
                    : null,
                'note' => $txn->getNote(),
                'createdAt' => $txn->getDecorator()->getCreatedAt(),
                'createdBy' => $createdBy !== null ? $createdBy->getUserName() : null,
            ];
            if (count($rows) >= $limit) {
                break;
            }
        }
        return $rows;
    }
}
