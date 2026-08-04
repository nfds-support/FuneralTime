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

use DateTime;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\LeaveEntitlement;
use OrangeHRM\Entity\LeaveEntitlementTransaction;
use OrangeHRM\Leave\Dao\LeaveEntitlementTransactionDao;
use OrangeHRM\Leave\Dto\LeaveEntitlementTransactionSearchFilterParams;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementServiceTrait;
use OrangeHRM\Leave\Traits\Service\LeavePeriodServiceTrait;
use OrangeHRM\ORM\Exception\TransactionException;
use Exception;

class LeaveEntitlementTransactionService
{
    use DateTimeHelperTrait;
    use UserRoleManagerTrait;
    use LeaveEntitlementServiceTrait;
    use LeavePeriodServiceTrait;

    /**
     * @var LeaveEntitlementTransactionDao|null
     */
    private ?LeaveEntitlementTransactionDao $dao = null;

    /**
     * @return LeaveEntitlementTransactionDao
     */
    public function getLeaveEntitlementTransactionDao(): LeaveEntitlementTransactionDao
    {
        if (!$this->dao instanceof LeaveEntitlementTransactionDao) {
            $this->dao = new LeaveEntitlementTransactionDao();
        }
        return $this->dao;
    }

    /**
     * @param LeaveEntitlementTransactionDao $dao
     */
    public function setLeaveEntitlementTransactionDao(LeaveEntitlementTransactionDao $dao): void
    {
        $this->dao = $dao;
    }

    /**
     * @param LeaveEntitlementTransactionSearchFilterParams $filterParams
     * @return LeaveEntitlementTransaction[]
     */
    public function search(LeaveEntitlementTransactionSearchFilterParams $filterParams): array
    {
        return $this->getLeaveEntitlementTransactionDao()->search($filterParams);
    }

    /**
     * @param LeaveEntitlementTransactionSearchFilterParams $filterParams
     * @return int
     */
    public function getCount(LeaveEntitlementTransactionSearchFilterParams $filterParams): int
    {
        return $this->getLeaveEntitlementTransactionDao()->getCount($filterParams);
    }

    /**
     * @param int $empNumber
     * @param int $leaveTypeId
     * @param float $days
     * @param int|null $entitlementId
     * @param string|null $note
     * @return LeaveEntitlementTransaction
     */
    public function logAddition(
        int $empNumber,
        int $leaveTypeId,
        float $days,
        ?int $entitlementId = null,
        ?string $note = null
    ): LeaveEntitlementTransaction {
        $balance = $this->getLeaveEntitlementService()
            ->getLeaveBalance($empNumber, $leaveTypeId)
            ->getBalance();

        return $this->createTransaction(
            $empNumber,
            $leaveTypeId,
            LeaveEntitlementTransaction::TYPE_ADDITION,
            $days,
            $balance,
            $entitlementId,
            $note
        );
    }

    /**
     * Create a deduction or correction that adjusts entitlement balance and logs a transaction.
     *
     * @param int $empNumber
     * @param int $leaveTypeId
     * @param string $transactionType
     * @param float $days
     * @param string|null $note
     * @param DateTime|null $fromDate
     * @param DateTime|null $toDate
     * @return LeaveEntitlementTransaction
     * @throws TransactionException
     */
    public function createAdjustment(
        int $empNumber,
        int $leaveTypeId,
        string $transactionType,
        float $days,
        ?string $note = null,
        ?DateTime $fromDate = null,
        ?DateTime $toDate = null
    ): LeaveEntitlementTransaction {
        $this->getLeaveEntitlementTransactionDao()->beginTransaction();
        try {
            $leavePeriod = $this->getLeavePeriodService()->getCurrentLeavePeriod();
            if ($fromDate === null && $leavePeriod !== null) {
                $fromDate = $leavePeriod->getStartDate();
            }
            if ($toDate === null && $leavePeriod !== null) {
                $toDate = $leavePeriod->getEndDate();
            }
            if ($fromDate === null) {
                $fromDate = $this->getDateTimeHelper()->getNow();
            }
            if ($toDate === null) {
                $toDate = (clone $fromDate)->modify('+1 year');
            }

            $entitlementTypeId = $transactionType === LeaveEntitlementTransaction::TYPE_DEDUCTION
                ? LeaveEntitlement::ENTITLEMENT_TYPE_DEDUCTION
                : LeaveEntitlement::ENTITLEMENT_TYPE_CORRECTION;

            $delta = $transactionType === LeaveEntitlementTransaction::TYPE_DEDUCTION
                ? -abs($days)
                : $days;

            $matching = $this->getLeaveEntitlementService()
                ->getLeaveEntitlementDao()
                ->getMatchingEntitlements($empNumber, $fromDate, $toDate, $leaveTypeId);

            $target = null;
            foreach ($matching as $existing) {
                if ($existing->getEntitlementType()->getId() === LeaveEntitlement::ENTITLEMENT_TYPE_ADD) {
                    $target = $existing;
                    break;
                }
            }

            if ($target instanceof LeaveEntitlement) {
                $target->setNoOfDays($target->getNoOfDays() + $delta);
                $this->getLeaveEntitlementService()->getLeaveEntitlementDao()->saveLeaveEntitlement($target);
            } else {
                $target = new LeaveEntitlement();
                $target->setNoOfDays($delta);
                $target->getDecorator()->setEmployeeByEmpNumber($empNumber);
                $target->getDecorator()->setLeaveTypeById($leaveTypeId);
                $target->setCreditedDate($this->getDateTimeHelper()->getNow());
                $target->setCreatedBy($this->getUserRoleManager()->getUser());
                $target->getDecorator()->setEntitlementTypeById($entitlementTypeId);
                $target->setFromDate($fromDate);
                $target->setToDate($toDate);
                $target->setNote($note ?? '');
                $this->getLeaveEntitlementService()->getLeaveEntitlementDao()->saveLeaveEntitlement($target);
            }

            $balance = $this->getLeaveEntitlementService()
                ->getLeaveBalance($empNumber, $leaveTypeId)
                ->getBalance();

            $txn = $this->createTransaction(
                $empNumber,
                $leaveTypeId,
                $transactionType,
                abs($days),
                $balance,
                $target->getId(),
                $note
            );
            $this->getLeaveEntitlementTransactionDao()->commitTransaction();
            return $txn;
        } catch (Exception $e) {
            $this->getLeaveEntitlementTransactionDao()->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @param int $empNumber
     * @param int $leaveTypeId
     * @param string $type
     * @param float $days
     * @param float|null $balanceAfter
     * @param int|null $entitlementId
     * @param string|null $note
     * @return LeaveEntitlementTransaction
     */
    private function createTransaction(
        int $empNumber,
        int $leaveTypeId,
        string $type,
        float $days,
        ?float $balanceAfter = null,
        ?int $entitlementId = null,
        ?string $note = null
    ): LeaveEntitlementTransaction {
        $txn = new LeaveEntitlementTransaction();
        $txn->getDecorator()->setEmployeeByEmpNumber($empNumber);
        $txn->getDecorator()->setLeaveTypeById($leaveTypeId);
        $txn->setTransactionType($type);
        $txn->setDays($days);
        $txn->setBalanceAfter($balanceAfter);
        $txn->setEntitlementId($entitlementId);
        $txn->setNote($note);
        $txn->setCreatedAt($this->getDateTimeHelper()->getNow());
        $user = $this->getUserRoleManager()->getUser();
        if ($user !== null) {
            $txn->setCreatedBy($user);
        }
        return $this->getLeaveEntitlementTransactionDao()->save($txn);
    }
}
