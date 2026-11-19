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

namespace OrangeHRM\Time\Service;

use DateInterval;
use DateTime;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\ScheduleAssignment;
use OrangeHRM\Entity\ScheduleOnCall;
use OrangeHRM\Entity\ScheduleShiftType;
use OrangeHRM\Time\Dao\ScheduleDao;

class ScheduleService
{
    use DateTimeHelperTrait;
    use EntityManagerHelperTrait;

    public const WEEK_START_MONDAY = 1;

    protected ?ScheduleDao $scheduleDao = null;

    public function getScheduleDao(): ScheduleDao
    {
        if (is_null($this->scheduleDao)) {
            $this->scheduleDao = new ScheduleDao();
        }
        return $this->scheduleDao;
    }

    public function setScheduleDao(ScheduleDao $scheduleDao): void
    {
        $this->scheduleDao = $scheduleDao;
    }

    /**
     * @return array{0: DateTime, 1: DateTime}
     */
    public function resolveWeekBounds(?string $fromDate): array
    {
        $anchor = $fromDate
            ? new DateTime($fromDate)
            : $this->getDateTimeHelper()->getNow();
        [$from, $to] = $this->getDateTimeHelper()->getWeekBoundaryForGivenDate(
            $anchor,
            self::WEEK_START_MONDAY
        );
        return [new DateTime($from), new DateTime($to)];
    }

    /**
     * @return array{
     *   fromDate: string,
     *   toDate: string,
     *   employees: array<int, array{empNumber:int, firstName:string, lastName:string}>,
     *   assignments: array<int, array{empNumber:int, date:string, shiftTypeId:int}>,
     *   leaves: array<int, array{empNumber:int, date:string, leaveType:string}>,
     *   onCall: array<int, array{date:string, empNumber:int, firstName:string, lastName:string}>
     * }
     */
    public function getWeek(?string $fromDate): array
    {
        [$from, $to] = $this->resolveWeekBounds($fromDate);
        return $this->buildWeekPayload($from, $to);
    }

    /**
     * @return ScheduleShiftType[]
     */
    public function getShiftTypes(): array
    {
        return $this->getScheduleDao()->getShiftTypes();
    }

    public function upsertAssignment(int $empNumber, DateTime $date, ?int $shiftTypeId): void
    {
        $existing = $this->getScheduleDao()->getAssignment($empNumber, $date);
        if ($shiftTypeId === null) {
            if ($existing) {
                $this->getScheduleDao()->deleteAssignment($existing);
            }
            return;
        }

        $employee = $this->getReference(Employee::class, $empNumber);
        $shiftType = $this->getReference(ScheduleShiftType::class, $shiftTypeId);
        if (!$existing) {
            $existing = new ScheduleAssignment();
            $existing->setEmployee($employee);
            $existing->setDate($date);
        }
        $existing->setShiftType($shiftType);
        $this->getScheduleDao()->saveAssignment($existing);
    }

    public function upsertOnCall(DateTime $date, ?int $empNumber): void
    {
        $existing = $this->getScheduleDao()->getOnCallByDate($date);
        if ($empNumber === null) {
            if ($existing) {
                $this->getScheduleDao()->deleteOnCall($existing);
            }
            return;
        }

        $employee = $this->getReference(Employee::class, $empNumber);
        if (!$existing) {
            $existing = new ScheduleOnCall();
            $existing->setDate($date);
        }
        $existing->setEmployee($employee);
        $this->getScheduleDao()->saveOnCall($existing);
    }

    /**
     * Copy assignments + on-call from the previous week into the week of $fromDate.
     *
     * @return array
     */
    public function copyPreviousWeek(string $fromDate): array
    {
        [$from, $to] = $this->resolveWeekBounds($fromDate);
        $prevFrom = (clone $from)->sub(new DateInterval('P7D'));
        $prevTo = (clone $to)->sub(new DateInterval('P7D'));

        $this->getScheduleDao()->deleteAssignmentsBetween($from, $to);
        $this->getScheduleDao()->deleteOnCallBetween($from, $to);

        foreach ($this->getScheduleDao()->getAssignmentsBetween($prevFrom, $prevTo) as $assignment) {
            $newDate = (clone $assignment->getDate())->add(new DateInterval('P7D'));
            $copy = new ScheduleAssignment();
            $copy->setEmployee($assignment->getEmployee());
            $copy->setDate($newDate);
            $copy->setShiftType($assignment->getShiftType());
            $this->getScheduleDao()->saveAssignment($copy);
        }

        foreach ($this->getScheduleDao()->getOnCallBetween($prevFrom, $prevTo) as $onCall) {
            $newDate = (clone $onCall->getDate())->add(new DateInterval('P7D'));
            $copy = new ScheduleOnCall();
            $copy->setDate($newDate);
            $copy->setEmployee($onCall->getEmployee());
            $this->getScheduleDao()->saveOnCall($copy);
        }

        return $this->buildWeekPayload($from, $to);
    }

    /**
     * @return array
     */
    private function buildWeekPayload(DateTime $from, DateTime $to): array
    {
        $employees = [];
        foreach ($this->getScheduleDao()->getCurrentEmployees() as $employee) {
            $employees[] = [
                'empNumber' => $employee->getEmpNumber(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
            ];
        }

        $assignments = [];
        foreach ($this->getScheduleDao()->getAssignmentsBetween($from, $to) as $assignment) {
            $assignments[] = [
                'empNumber' => $assignment->getEmployee()->getEmpNumber(),
                'date' => $assignment->getDate()->format('Y-m-d'),
                'shiftTypeId' => $assignment->getShiftType()->getId(),
            ];
        }

        $onCall = [];
        foreach ($this->getScheduleDao()->getOnCallBetween($from, $to) as $row) {
            $employee = $row->getEmployee();
            $onCall[] = [
                'date' => $row->getDate()->format('Y-m-d'),
                'empNumber' => $employee->getEmpNumber(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
            ];
        }

        return [
            'fromDate' => $from->format('Y-m-d'),
            'toDate' => $to->format('Y-m-d'),
            'employees' => $employees,
            'assignments' => $assignments,
            'leaves' => $this->getScheduleDao()->getLeavesBetween($from, $to),
            'onCall' => $onCall,
        ];
    }
}
