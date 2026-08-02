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

namespace OrangeHRM\Discipline\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Discipline\Dto\DisciplineCaseSearchFilterParams;
use OrangeHRM\Entity\DisciplineCase;
use OrangeHRM\ORM\Paginator;

class DisciplineDao extends BaseDao
{
    public function getDisciplineCaseById(int $id): ?DisciplineCase
    {
        return $this->getRepository(DisciplineCase::class)->find($id);
    }

    public function saveDisciplineCase(DisciplineCase $disciplineCase): DisciplineCase
    {
        $this->persist($disciplineCase);
        return $disciplineCase;
    }

    /**
     * @param int[] $ids
     */
    public function deleteDisciplineCases(array $ids): int
    {
        $q = $this->createQueryBuilder(DisciplineCase::class, 'disciplineCase');
        $q->delete()
            ->where($q->expr()->in('disciplineCase.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return DisciplineCase[]
     */
    public function getDisciplineCaseList(DisciplineCaseSearchFilterParams $filterParams): array
    {
        return $this->getDisciplineCasePaginator($filterParams)->getQuery()->execute();
    }

    public function getDisciplineCaseCount(DisciplineCaseSearchFilterParams $filterParams): int
    {
        return $this->getDisciplineCasePaginator($filterParams)->count();
    }

    private function getDisciplineCasePaginator(DisciplineCaseSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(DisciplineCase::class, 'disciplineCase');
        $q->leftJoin('disciplineCase.employee', 'employee');
        $q->leftJoin('disciplineCase.reportedBy', 'reportedBy');

        if (!is_null($filterParams->getEmpNumber())) {
            $q->andWhere('employee.empNumber = :empNumber')
                ->setParameter('empNumber', $filterParams->getEmpNumber());
        }
        if (!is_null($filterParams->getEmpNumbers())) {
            $q->andWhere($q->expr()->in('employee.empNumber', ':empNumbers'))
                ->setParameter('empNumbers', $filterParams->getEmpNumbers());
        }
        if (!is_null($filterParams->getCaseType())) {
            $q->andWhere('disciplineCase.caseType = :caseType')
                ->setParameter('caseType', $filterParams->getCaseType());
        }
        if (!is_null($filterParams->getStatus())) {
            $q->andWhere('disciplineCase.status = :status')
                ->setParameter('status', $filterParams->getStatus());
        }

        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }
}
