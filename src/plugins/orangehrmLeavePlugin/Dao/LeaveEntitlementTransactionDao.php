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

namespace OrangeHRM\Leave\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\LeaveEntitlementTransaction;
use OrangeHRM\Leave\Dto\LeaveEntitlementTransactionSearchFilterParams;
use OrangeHRM\ORM\Paginator;

class LeaveEntitlementTransactionDao extends BaseDao
{
    /**
     * @param int $id
     * @return LeaveEntitlementTransaction|null
     */
    public function getById(int $id): ?LeaveEntitlementTransaction
    {
        $txn = $this->getRepository(LeaveEntitlementTransaction::class)->find($id);
        return $txn instanceof LeaveEntitlementTransaction ? $txn : null;
    }

    /**
     * @param LeaveEntitlementTransactionSearchFilterParams $filterParams
     * @return LeaveEntitlementTransaction[]
     */
    public function search(LeaveEntitlementTransactionSearchFilterParams $filterParams): array
    {
        return $this->getSearchPaginator($filterParams)->getQuery()->execute();
    }

    /**
     * @param LeaveEntitlementTransactionSearchFilterParams $filterParams
     * @return int
     */
    public function getCount(LeaveEntitlementTransactionSearchFilterParams $filterParams): int
    {
        return $this->getSearchPaginator($filterParams)->count();
    }

    /**
     * @param LeaveEntitlementTransactionSearchFilterParams $filterParams
     * @return Paginator
     */
    private function getSearchPaginator(LeaveEntitlementTransactionSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(LeaveEntitlementTransaction::class, 'txn');
        $q->leftJoin('txn.employee', 'employee')
            ->leftJoin('txn.leaveType', 'leaveType');
        $this->setSortingAndPaginationParams($q, $filterParams);

        if (!is_null($filterParams->getEmpNumber())) {
            $q->andWhere('employee.empNumber = :empNumber')
                ->setParameter('empNumber', $filterParams->getEmpNumber());
        }
        if (!is_null($filterParams->getLeaveTypeId())) {
            $q->andWhere('leaveType.id = :leaveTypeId')
                ->setParameter('leaveTypeId', $filterParams->getLeaveTypeId());
        }
        if (!is_null($filterParams->getTransactionType())) {
            $q->andWhere('txn.transactionType = :transactionType')
                ->setParameter('transactionType', $filterParams->getTransactionType());
        }
        if (!is_null($filterParams->getFromDate())) {
            $q->andWhere('txn.createdAt >= :fromDate')
                ->setParameter('fromDate', $filterParams->getFromDate());
        }
        if (!is_null($filterParams->getToDate())) {
            $q->andWhere('txn.createdAt <= :toDate')
                ->setParameter('toDate', $filterParams->getToDate());
        }

        return $this->getPaginator($q);
    }

    /**
     * @param LeaveEntitlementTransaction $transaction
     * @return LeaveEntitlementTransaction
     */
    public function save(LeaveEntitlementTransaction $transaction): LeaveEntitlementTransaction
    {
        $this->persist($transaction);
        return $transaction;
    }
}
