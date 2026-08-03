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

namespace OrangeHRM\Union\Dao;

use DateTime;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\EmployeeUnion;
use OrangeHRM\Entity\LaborUnion;
use OrangeHRM\Entity\UnionLeaveRule;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Union\Dto\EmployeeUnionSearchFilterParams;
use OrangeHRM\Union\Dto\UnionLeaveRuleSearchFilterParams;
use OrangeHRM\Union\Dto\UnionSearchFilterParams;

class UnionDao extends BaseDao
{
    public function getUnionById(int $id): ?LaborUnion
    {
        return $this->getRepository(LaborUnion::class)->find($id);
    }

    public function saveUnion(LaborUnion $union): LaborUnion
    {
        $this->persist($union);
        return $union;
    }

    /**
     * @param int[] $ids
     */
    public function deleteUnions(array $ids): int
    {
        $q = $this->createQueryBuilder(LaborUnion::class, 'laborUnion');
        $q->delete()
            ->where($q->expr()->in('laborUnion.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return LaborUnion[]
     */
    public function getUnionList(UnionSearchFilterParams $filterParams): array
    {
        return $this->getUnionPaginator($filterParams)->getQuery()->execute();
    }

    public function getUnionCount(UnionSearchFilterParams $filterParams): int
    {
        return $this->getUnionPaginator($filterParams)->count();
    }

    private function getUnionPaginator(UnionSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(LaborUnion::class, 'laborUnion');
        if (!is_null($filterParams->getActiveOnly()) && $filterParams->getActiveOnly()) {
            $q->andWhere('laborUnion.active = :active')->setParameter('active', true);
        }
        if (!is_null($filterParams->getName())) {
            $q->andWhere($q->expr()->like('laborUnion.name', ':name'))
                ->setParameter('name', '%' . $filterParams->getName() . '%');
        }
        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }

    public function getEmployeeUnionById(int $id): ?EmployeeUnion
    {
        return $this->getRepository(EmployeeUnion::class)->find($id);
    }

    public function saveEmployeeUnion(EmployeeUnion $employeeUnion): EmployeeUnion
    {
        $this->persist($employeeUnion);
        return $employeeUnion;
    }

    /**
     * @param int[] $ids
     */
    public function deleteEmployeeUnions(array $ids): int
    {
        $q = $this->createQueryBuilder(EmployeeUnion::class, 'employeeUnion');
        $q->delete()
            ->where($q->expr()->in('employeeUnion.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return EmployeeUnion[]
     */
    public function getEmployeeUnionList(EmployeeUnionSearchFilterParams $filterParams): array
    {
        return $this->getEmployeeUnionPaginator($filterParams)->getQuery()->execute();
    }

    public function getEmployeeUnionCount(EmployeeUnionSearchFilterParams $filterParams): int
    {
        return $this->getEmployeeUnionPaginator($filterParams)->count();
    }

    private function getEmployeeUnionPaginator(EmployeeUnionSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(EmployeeUnion::class, 'employeeUnion');
        $q->leftJoin('employeeUnion.employee', 'employee');
        $q->leftJoin('employeeUnion.laborUnion', 'laborUnion');
        if (!is_null($filterParams->getEmpNumber())) {
            $q->andWhere('employee.empNumber = :empNumber')
                ->setParameter('empNumber', $filterParams->getEmpNumber());
        }
        if (!is_null($filterParams->getUnionId())) {
            $q->andWhere('laborUnion.id = :unionId')
                ->setParameter('unionId', $filterParams->getUnionId());
        }
        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }

    public function getPrimaryEmployeeUnion(int $empNumber, ?DateTime $asOf = null): ?EmployeeUnion
    {
        $asOf ??= new DateTime('today');
        $q = $this->createQueryBuilder(EmployeeUnion::class, 'employeeUnion');
        $q->andWhere('IDENTITY(employeeUnion.employee) = :empNumber')
            ->setParameter('empNumber', $empNumber)
            ->andWhere('employeeUnion.primary = :primary')
            ->setParameter('primary', true)
            ->andWhere(
                $q->expr()->orX(
                    'employeeUnion.endDate IS NULL',
                    'employeeUnion.endDate >= :asOf'
                )
            )
            ->setParameter('asOf', $asOf)
            ->setMaxResults(1);

        return $this->fetchOne($q);
    }

    public function getPrimaryUnionIdByEmpNumber(int $empNumber): ?int
    {
        $assignment = $this->getPrimaryEmployeeUnion($empNumber);
        return $assignment?->getLaborUnion()->getId();
    }

    public function getUnionLeaveRuleById(int $id): ?UnionLeaveRule
    {
        return $this->getRepository(UnionLeaveRule::class)->find($id);
    }

    public function saveUnionLeaveRule(UnionLeaveRule $rule): UnionLeaveRule
    {
        $this->persist($rule);
        return $rule;
    }

    /**
     * @param int[] $ids
     */
    public function deleteUnionLeaveRules(array $ids): int
    {
        $q = $this->createQueryBuilder(UnionLeaveRule::class, 'rule');
        $q->delete()
            ->where($q->expr()->in('rule.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @return UnionLeaveRule[]
     */
    public function getUnionLeaveRuleList(UnionLeaveRuleSearchFilterParams $filterParams): array
    {
        return $this->getUnionLeaveRulePaginator($filterParams)->getQuery()->execute();
    }

    public function getUnionLeaveRuleCount(UnionLeaveRuleSearchFilterParams $filterParams): int
    {
        return $this->getUnionLeaveRulePaginator($filterParams)->count();
    }

    private function getUnionLeaveRulePaginator(UnionLeaveRuleSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(UnionLeaveRule::class, 'rule');
        $q->leftJoin('rule.laborUnion', 'laborUnion');
        $q->leftJoin('rule.leaveType', 'leaveType');
        if (!is_null($filterParams->getUnionId())) {
            if ($filterParams->getUnionId() === 0) {
                $q->andWhere('rule.laborUnion IS NULL');
            } else {
                $q->andWhere('laborUnion.id = :unionId')
                    ->setParameter('unionId', $filterParams->getUnionId());
            }
        }
        if (!is_null($filterParams->getLeaveTypeId())) {
            $q->andWhere('leaveType.id = :leaveTypeId')
                ->setParameter('leaveTypeId', $filterParams->getLeaveTypeId());
        }
        $this->setSortingAndPaginationParams($q, $filterParams);
        return $this->getPaginator($q);
    }

    /**
     * @return UnionLeaveRule[]
     */
    public function findLeaveRulesForUnionAndLeaveType(?int $unionId, int $leaveTypeId): array
    {
        $q = $this->createQueryBuilder(UnionLeaveRule::class, 'rule');
        $q->andWhere('IDENTITY(rule.leaveType) = :leaveTypeId')
            ->setParameter('leaveTypeId', $leaveTypeId);
        if ($unionId === null) {
            $q->andWhere('rule.laborUnion IS NULL');
        } else {
            $q->andWhere('IDENTITY(rule.laborUnion) = :unionId')
                ->setParameter('unionId', $unionId);
        }
        $q->addOrderBy('rule.minYears', 'ASC');
        return $q->getQuery()->execute();
    }

    /**
     * @return EmployeeUnion[]
     */
    public function getAllPrimaryEmployeeUnions(): array
    {
        $q = $this->createQueryBuilder(EmployeeUnion::class, 'employeeUnion');
        $q->andWhere('employeeUnion.primary = :primary')
            ->setParameter('primary', true)
            ->andWhere('employeeUnion.endDate IS NULL');
        return $q->getQuery()->execute();
    }
}
