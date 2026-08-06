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
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmployeeSalary;
use OrangeHRM\Entity\Membership;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Entity\Timesheet;
use OrangeHRM\Entity\TimesheetItem;
use OrangeHRM\Entity\UfcwInitiationFee;

class UfcwRemittanceDao extends BaseDao
{
    /**
     * @param string $membershipName
     * @param DateTime $monthStart
     * @param DateTime $monthEnd
     * @return Employee[]
     */
    public function getBargainingUnitEmployees(string $membershipName, DateTime $monthStart, DateTime $monthEnd): array
    {
        $q = $this->createQueryBuilder(Employee::class, 'e');
        $q->innerJoin('e.memberships', 'em')
            ->innerJoin('em.membership', 'm')
            ->andWhere('e.employeeTerminationRecord IS NULL')
            ->andWhere('m.name = :membershipName')
            ->setParameter('membershipName', $membershipName)
            ->andWhere(
                $q->expr()->orX(
                    'em.subscriptionCommenceDate IS NULL',
                    'em.subscriptionCommenceDate <= :monthEnd'
                )
            )
            ->andWhere(
                $q->expr()->orX(
                    'em.subscriptionRenewalDate IS NULL',
                    'em.subscriptionRenewalDate >= :monthStart'
                )
            )
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->orderBy('e.lastName', 'ASC')
            ->addOrderBy('e.firstName', 'ASC')
            ->distinct();

        return $q->getQuery()->execute();
    }

    /**
     * @param int $empNumber
     * @return EmployeeSalary[]
     */
    public function getEmployeeSalaries(int $empNumber): array
    {
        $q = $this->createQueryBuilder(EmployeeSalary::class, 'es');
        $q->andWhere('es.employee = :empNumber')
            ->setParameter('empNumber', $empNumber)
            ->orderBy('es.id', 'ASC');
        return $q->getQuery()->execute();
    }

    /**
     * @param DateTime $monthStart
     * @param DateTime $monthEnd
     * @return PayrollPeriod[]
     */
    public function getPayrollPeriodsOverlappingMonth(DateTime $monthStart, DateTime $monthEnd): array
    {
        $q = $this->createQueryBuilder(PayrollPeriod::class, 'p');
        $q->andWhere('p.startDate <= :monthEnd')
            ->andWhere('p.endDate >= :monthStart')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->orderBy('p.periodNumber', 'ASC');
        return $q->getQuery()->execute();
    }

    /**
     * Returns approved timesheet week rows with total duration (seconds) for an employee in range.
     *
     * @param int $empNumber
     * @param DateTime $from
     * @param DateTime $to
     * @return array<int, array{timesheetId:int,startDate:DateTime,endDate:DateTime,duration:string}>
     */
    public function getApprovedTimesheetWeekSummaries(int $empNumber, DateTime $from, DateTime $to): array
    {
        $q = $this->createQueryBuilder(Timesheet::class, 't');
        $q->select('t')
            ->andWhere('t.employee = :empNumber')
            ->andWhere('t.state = :state')
            ->andWhere('t.endDate >= :from')
            ->andWhere('t.startDate <= :to')
            ->setParameter('empNumber', $empNumber)
            ->setParameter('state', Timesheet::STATE_APPROVED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('t.endDate', 'ASC');

        /** @var Timesheet[] $timesheets */
        $timesheets = $q->getQuery()->execute();
        $summaries = [];
        foreach ($timesheets as $timesheet) {
            $durationQ = $this->createQueryBuilder(TimesheetItem::class, 'ti');
            $durationQ->select('SUM(ti.duration)')
                ->andWhere('ti.timesheet = :timesheetId')
                ->andWhere('ti.employee = :empNumber')
                ->andWhere('ti.date >= :from')
                ->andWhere('ti.date <= :to')
                ->setParameter('timesheetId', $timesheet->getId())
                ->setParameter('empNumber', $empNumber)
                ->setParameter('from', $from)
                ->setParameter('to', $to);
            $duration = (float) ($durationQ->getQuery()->getSingleScalarResult() ?? 0);
            $summaries[] = [
                'timesheetId' => $timesheet->getId(),
                'startDate' => $timesheet->getStartDate(),
                'endDate' => $timesheet->getEndDate(),
                'duration' => $duration,
            ];
        }
        return $summaries;
    }

    /**
     * @param int $empNumber
     * @return UfcwInitiationFee|null
     */
    public function getInitiationFee(int $empNumber): ?UfcwInitiationFee
    {
        return $this->getRepository(UfcwInitiationFee::class)->findOneBy(['employee' => $empNumber]);
    }

    /**
     * @param UfcwInitiationFee $fee
     * @return UfcwInitiationFee
     */
    public function saveInitiationFee(UfcwInitiationFee $fee): UfcwInitiationFee
    {
        $this->persist($fee);
        return $fee;
    }

    /**
     * @param string $membershipName
     * @return Membership|null
     */
    public function findMembershipByName(string $membershipName): ?Membership
    {
        return $this->getRepository(Membership::class)->findOneBy(['name' => $membershipName]);
    }

    /**
     * @param Membership $membership
     * @return Membership
     */
    public function saveMembership(Membership $membership): Membership
    {
        $this->persist($membership);
        return $membership;
    }
}
