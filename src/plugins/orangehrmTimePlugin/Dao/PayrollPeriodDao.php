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

namespace OrangeHRM\Time\Dao;

use DateTime;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Time\Dto\PayrollPeriodSearchFilterParams;

class PayrollPeriodDao extends BaseDao
{
    /**
     * @param int $id
     * @return PayrollPeriod|null
     */
    public function getById(int $id): ?PayrollPeriod
    {
        $period = $this->getRepository(PayrollPeriod::class)->find($id);
        return $period instanceof PayrollPeriod ? $period : null;
    }

    /**
     * @param PayrollPeriodSearchFilterParams $filterParams
     * @return PayrollPeriod[]
     */
    public function search(PayrollPeriodSearchFilterParams $filterParams): array
    {
        return $this->getSearchPaginator($filterParams)->getQuery()->execute();
    }

    /**
     * @param PayrollPeriodSearchFilterParams $filterParams
     * @return int
     */
    public function getCount(PayrollPeriodSearchFilterParams $filterParams): int
    {
        return $this->getSearchPaginator($filterParams)->count();
    }

    /**
     * @param PayrollPeriodSearchFilterParams $filterParams
     * @return Paginator
     */
    private function getSearchPaginator(PayrollPeriodSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(PayrollPeriod::class, 'payrollPeriod');
        $this->setSortingAndPaginationParams($q, $filterParams);

        if (!is_null($filterParams->getPeriodNumber())) {
            $q->andWhere('payrollPeriod.periodNumber = :periodNumber')
                ->setParameter('periodNumber', $filterParams->getPeriodNumber());
        }

        if ($filterParams->getFromDate() instanceof DateTime && $filterParams->getToDate() instanceof DateTime) {
            // Overlap: period.start <= toDate AND period.end >= fromDate
            $q->andWhere('payrollPeriod.startDate <= :toDate')
                ->andWhere('payrollPeriod.endDate >= :fromDate')
                ->setParameter('toDate', $filterParams->getToDate())
                ->setParameter('fromDate', $filterParams->getFromDate());
        }

        return $this->getPaginator($q);
    }

    /**
     * @param DateTime $fromDate
     * @param DateTime $toDate
     * @param int|null $periodNumber
     * @return PayrollPeriod[]
     */
    public function getByDateRange(DateTime $fromDate, DateTime $toDate, ?int $periodNumber = null): array
    {
        $filterParams = new PayrollPeriodSearchFilterParams();
        $filterParams->setFromDate($fromDate);
        $filterParams->setToDate($toDate);
        $filterParams->setPeriodNumber($periodNumber);
        $filterParams->setLimit(0);
        return $this->search($filterParams);
    }

    /**
     * @param PayrollPeriod $payrollPeriod
     * @return PayrollPeriod
     */
    public function save(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        $this->persist($payrollPeriod);
        return $payrollPeriod;
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteByIds(array $ids): int
    {
        $q = $this->createQueryBuilder(PayrollPeriod::class, 'payrollPeriod');
        $q->delete()
            ->where($q->expr()->in('payrollPeriod.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    public function getExistingIds(array $ids): array
    {
        $qb = $this->createQueryBuilder(PayrollPeriod::class, 'payrollPeriod');
        $qb->select('payrollPeriod.id')
            ->andWhere($qb->expr()->in('payrollPeriod.id', ':ids'))
            ->setParameter('ids', $ids);
        return $qb->getQuery()->getSingleColumnResult();
    }
}
