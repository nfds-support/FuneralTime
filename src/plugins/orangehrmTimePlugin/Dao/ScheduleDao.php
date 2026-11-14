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
use OrangeHRM\Entity\Leave;
use OrangeHRM\Entity\ScheduleAssignment;
use OrangeHRM\Entity\ScheduleOnCall;
use OrangeHRM\Entity\ScheduleShiftType;
use OrangeHRM\ORM\ListSorter;

class ScheduleDao extends BaseDao
{
    /**
     * @return ScheduleShiftType[]
     */
    public function getShiftTypes(): array
    {
        return $this->createQueryBuilder(ScheduleShiftType::class, 't')
            ->addOrderBy('t.sortOrder', ListSorter::ASCENDING)
            ->addOrderBy('t.id', ListSorter::ASCENDING)
            ->getQuery()
            ->execute();
    }

    public function getShiftTypeById(int $id): ?ScheduleShiftType
    {
        return $this->getRepository(ScheduleShiftType::class)->find($id);
    }

    /**
     * @return ScheduleAssignment[]
     */
    public function getAssignmentsBetween(DateTime $from, DateTime $to): array
    {
        return $this->createQueryBuilder(ScheduleAssignment::class, 'a')
            ->leftJoin('a.employee', 'e')
            ->leftJoin('a.shiftType', 't')
            ->andWhere('a.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->addOrderBy('e.lastName', ListSorter::ASCENDING)
            ->addOrderBy('a.date', ListSorter::ASCENDING)
            ->getQuery()
            ->execute();
    }

    public function getAssignment(int $empNumber, DateTime $date): ?ScheduleAssignment
    {
        return $this->createQueryBuilder(ScheduleAssignment::class, 'a')
            ->andWhere('IDENTITY(a.employee) = :empNumber')
            ->andWhere('a.date = :date')
            ->setParameter('empNumber', $empNumber)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveAssignment(ScheduleAssignment $assignment): ScheduleAssignment
    {
        $this->persist($assignment);
        return $assignment;
    }

    public function deleteAssignment(ScheduleAssignment $assignment): void
    {
        $this->remove($assignment);
    }

    /**
     * @return ScheduleOnCall[]
     */
    public function getOnCallBetween(DateTime $from, DateTime $to): array
    {
        return $this->createQueryBuilder(ScheduleOnCall::class, 'o')
            ->leftJoin('o.employee', 'e')
            ->andWhere('o.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->addOrderBy('o.date', ListSorter::ASCENDING)
            ->getQuery()
            ->execute();
    }

    public function getOnCallByDate(DateTime $date): ?ScheduleOnCall
    {
        return $this->createQueryBuilder(ScheduleOnCall::class, 'o')
            ->andWhere('o.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveOnCall(ScheduleOnCall $onCall): ScheduleOnCall
    {
        $this->persist($onCall);
        return $onCall;
    }

    public function deleteOnCall(ScheduleOnCall $onCall): void
    {
        $this->remove($onCall);
    }

    /**
     * Current (non-terminated) employees ordered by name.
     *
     * @return Employee[]
     */
    public function getCurrentEmployees(): array
    {
        return $this->createQueryBuilder(Employee::class, 'e')
            ->andWhere('e.employeeTerminationRecord IS NULL')
            ->addOrderBy('e.lastName', ListSorter::ASCENDING)
            ->addOrderBy('e.firstName', ListSorter::ASCENDING)
            ->getQuery()
            ->execute();
    }

    /**
     * Approved / taken leave rows overlapping the week, keyed for the calendar.
     *
     * @return array<int, array{empNumber:int, date:string, leaveType:string}>
     */
    public function getLeavesBetween(DateTime $from, DateTime $to): array
    {
        $rows = $this->createQueryBuilder(Leave::class, 'l')
            ->select('IDENTITY(l.employee) AS empNumber, l.date AS leaveDate, lt.name AS leaveType')
            ->leftJoin('l.leaveType', 'lt')
            ->andWhere('l.date BETWEEN :from AND :to')
            ->andWhere('l.status IN (:statuses)')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('statuses', [
                Leave::LEAVE_STATUS_LEAVE_APPROVED,
                Leave::LEAVE_STATUS_LEAVE_TAKEN,
            ])
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $date = $row['leaveDate'];
            $dateStr = $date instanceof DateTime ? $date->format('Y-m-d') : (string) $date;
            $result[] = [
                'empNumber' => (int) $row['empNumber'],
                'date' => $dateStr,
                'leaveType' => (string) ($row['leaveType'] ?? ''),
            ];
        }
        return $result;
    }

    public function deleteAssignmentsBetween(DateTime $from, DateTime $to): void
    {
        $this->createQueryBuilder(ScheduleAssignment::class, 'a')
            ->delete()
            ->andWhere('a.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->execute();
    }

    public function deleteOnCallBetween(DateTime $from, DateTime $to): void
    {
        $this->createQueryBuilder(ScheduleOnCall::class, 'o')
            ->delete()
            ->andWhere('o.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->execute();
    }
}
