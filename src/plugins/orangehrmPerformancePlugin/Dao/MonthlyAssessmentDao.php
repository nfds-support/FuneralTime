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

namespace OrangeHRM\Performance\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\MonthlyAssessment;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Performance\Dto\MonthlyAssessmentSearchFilterParams;

class MonthlyAssessmentDao extends BaseDao
{
    public function getMonthlyAssessmentById(int $id): ?MonthlyAssessment
    {
        return $this->getRepository(MonthlyAssessment::class)->find($id);
    }

    public function getMonthlyAssessmentByEmployeeAndPeriod(
        int $empNumber,
        int $periodYear,
        int $periodMonth
    ): ?MonthlyAssessment {
        return $this->getRepository(MonthlyAssessment::class)->findOneBy([
            'employee' => $empNumber,
            'periodYear' => $periodYear,
            'periodMonth' => $periodMonth,
        ]);
    }

    public function saveMonthlyAssessment(MonthlyAssessment $assessment): MonthlyAssessment
    {
        $this->persist($assessment);
        return $assessment;
    }

    /**
     * @param int[] $ids
     */
    public function deleteMonthlyAssessments(array $ids): int
    {
        $q = $this->createQueryBuilder(MonthlyAssessment::class, 'monthlyAssessment');
        $q->delete()
            ->where($q->expr()->in('monthlyAssessment.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return MonthlyAssessment[]
     */
    public function getMonthlyAssessmentList(MonthlyAssessmentSearchFilterParams $filterParams): array
    {
        return $this->getMonthlyAssessmentPaginator($filterParams)->getQuery()->execute();
    }

    public function getMonthlyAssessmentCount(MonthlyAssessmentSearchFilterParams $filterParams): int
    {
        return $this->getMonthlyAssessmentPaginator($filterParams)->count();
    }

    private function getMonthlyAssessmentPaginator(MonthlyAssessmentSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(MonthlyAssessment::class, 'monthlyAssessment');
        $q->leftJoin('monthlyAssessment.employee', 'employee');
        $q->leftJoin('monthlyAssessment.manager', 'manager');

        if (!is_null($filterParams->getEmpNumber())) {
            $q->andWhere('employee.empNumber = :empNumber')
                ->setParameter('empNumber', $filterParams->getEmpNumber());
        }
        if (!is_null($filterParams->getEmpNumbers())) {
            $q->andWhere($q->expr()->in('employee.empNumber', ':empNumbers'))
                ->setParameter('empNumbers', $filterParams->getEmpNumbers());
        }
        if (!is_null($filterParams->getManagerEmpNumber())) {
            $q->andWhere('manager.empNumber = :managerEmpNumber')
                ->setParameter('managerEmpNumber', $filterParams->getManagerEmpNumber());
        }
        if (!is_null($filterParams->getPeriodYear())) {
            $q->andWhere('monthlyAssessment.periodYear = :periodYear')
                ->setParameter('periodYear', $filterParams->getPeriodYear());
        }
        if (!is_null($filterParams->getPeriodMonth())) {
            $q->andWhere('monthlyAssessment.periodMonth = :periodMonth')
                ->setParameter('periodMonth', $filterParams->getPeriodMonth());
        }
        if (!is_null($filterParams->getStatus())) {
            $q->andWhere('monthlyAssessment.status = :status')
                ->setParameter('status', $filterParams->getStatus());
        }

        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }
}
