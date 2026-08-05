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
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmployeeWorkShift;
use OrangeHRM\Entity\Leave;
use OrangeHRM\Entity\Timesheet;
use OrangeHRM\Entity\TimesheetDay;
use OrangeHRM\Entity\TimesheetItem;
use OrangeHRM\Pim\Dto\EmployeeSearchFilterParams;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use OrangeHRM\Time\Dto\PayrollFillSheetRow;

class PayrollFillSheetService
{
    use EntityManagerHelperTrait;
    use EmployeeServiceTrait;

    public const DEFAULT_HOURS_PER_DAY = 8.0;
    public const DEFAULT_OT_THRESHOLD = 44.0;
    public const PAY_TYPE_SALARIED = 'salaried';
    public const PAY_TYPE_HOURLY = 'hourly';
    public const GROUP_ADMINISTRATION = 'administration';
    public const GROUP_OPERATION = 'operation';

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int[]|null $empNumbers
     * @return PayrollFillSheetRow[]
     */
    public function buildFillSheet(DateTime $startDate, DateTime $endDate, ?array $empNumbers = null): array
    {
        $week1Start = clone $startDate;
        $week1End = (clone $startDate)->add(new DateInterval('P6D'));
        $week2Start = (clone $startDate)->add(new DateInterval('P7D'));
        $week2End = clone $endDate;

        $employees = $this->getEmployees($empNumbers);
        $rows = [];
        foreach ($employees as $employee) {
            $hoursPerDay = $this->getHoursPerDay($employee);
            $workedW1 = $this->getApprovedTimesheetHours($employee->getEmpNumber(), $week1Start, $week1End);
            $workedW2 = $this->getApprovedTimesheetHours($employee->getEmpNumber(), $week2Start, $week2End);
            $onCallW1 = $this->getOnCallCount($employee->getEmpNumber(), $week1Start, $week1End);
            $onCallW2 = $this->getOnCallCount($employee->getEmpNumber(), $week2Start, $week2End);
            $vacationW1 = $this->getLeaveHours($employee->getEmpNumber(), $week1Start, $week1End, ['vacation', 'annual'], $hoursPerDay);
            $vacationW2 = $this->getLeaveHours($employee->getEmpNumber(), $week2Start, $week2End, ['vacation', 'annual'], $hoursPerDay);
            $sickHours = $this->getLeaveHours($employee->getEmpNumber(), $startDate, $endDate, ['sick'], $hoursPerDay);

            $payW1 = $this->applyPayRules($employee, $workedW1, $vacationW1);
            $payW2 = $this->applyPayRules($employee, $workedW2, $vacationW2);

            $row = new PayrollFillSheetRow();
            $row->setEmpNumber($employee->getEmpNumber());
            $row->setEmployeeId($employee->getEmployeeId());
            $row->setLastName($employee->getLastName());
            $row->setFirstName($employee->getFirstName());
            $row->setGroup($this->resolveGroup($employee));
            $row->setRegularW1($payW1['regular']);
            $row->setRegularW2($payW2['regular']);
            $row->setOtW1($payW1['ot']);
            $row->setOtW2($payW2['ot']);
            $row->setOnCallW1($onCallW1);
            $row->setOnCallW2($onCallW2);
            $row->setSickHours(round($sickHours, 2));
            $row->setVacationW1(round($vacationW1, 2));
            $row->setVacationW2(round($vacationW2, 2));
            $row->setBankedW1($payW1['banked']);
            $row->setBankedW2($payW2['banked']);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param int[]|null $empNumbers
     * @return Employee[]
     */
    private function getEmployees(?array $empNumbers): array
    {
        $filterParams = new EmployeeSearchFilterParams();
        $filterParams->setIncludeEmployees(EmployeeSearchFilterParams::INCLUDE_EMPLOYEES_ONLY_CURRENT);
        $filterParams->setLimit(0);
        if (!is_null($empNumbers)) {
            $filterParams->setEmployeeNumbers($empNumbers);
        }
        return $this->getEmployeeService()->getEmployeeList($filterParams);
    }

    /**
     * @param Employee $employee
     * @return string
     */
    private function resolveGroup(Employee $employee): string
    {
        $subunit = $employee->getSubDivision();
        if ($subunit !== null && stripos($subunit->getName(), 'admin') !== false) {
            return self::GROUP_ADMINISTRATION;
        }
        return self::GROUP_OPERATION;
    }

    /**
     * @param Employee $employee
     * @return float
     */
    private function getHoursPerDay(Employee $employee): float
    {
        $workShift = $this->getEmployeeService()->getEmployeeDao()->getEmployeeWorkShift($employee->getEmpNumber());
        if ($workShift instanceof EmployeeWorkShift) {
            return (float) $workShift->getWorkShift()->getHoursPerDay();
        }
        return self::DEFAULT_HOURS_PER_DAY;
    }

    /**
     * @param int $empNumber
     * @param DateTime $from
     * @param DateTime $to
     * @return float
     */
    private function getApprovedTimesheetHours(int $empNumber, DateTime $from, DateTime $to): float
    {
        $q = $this->createQueryBuilder(TimesheetItem::class, 'ti');
        $q->select('SUM(ti.duration)')
            ->leftJoin('ti.timesheet', 't')
            ->andWhere('ti.employee = :empNumber')
            ->andWhere('t.state = :state')
            ->andWhere($q->expr()->between('ti.date', ':from', ':to'))
            ->setParameter('empNumber', $empNumber)
            ->setParameter('state', Timesheet::STATE_APPROVED)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $seconds = $q->getQuery()->getSingleScalarResult();
        if ($seconds === null) {
            return 0.0;
        }
        return round(((float) $seconds) / 3600, 2);
    }

    /**
     * @param int $empNumber
     * @param DateTime $from
     * @param DateTime $to
     * @return int
     */
    private function getOnCallCount(int $empNumber, DateTime $from, DateTime $to): int
    {
        $q = $this->createQueryBuilder(TimesheetDay::class, 'td');
        $q->select('COUNT(td.id)')
            ->leftJoin('td.timesheet', 't')
            ->andWhere('t.employee = :empNumber')
            ->andWhere('t.state = :state')
            ->andWhere('td.onCall = :onCall')
            ->andWhere($q->expr()->between('td.date', ':from', ':to'))
            ->setParameter('empNumber', $empNumber)
            ->setParameter('state', Timesheet::STATE_APPROVED)
            ->setParameter('onCall', true)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return (int) $q->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $empNumber
     * @param DateTime $from
     * @param DateTime $to
     * @param string[] $nameContains
     * @param float $hoursPerDay
     * @return float
     */
    private function getLeaveHours(
        int $empNumber,
        DateTime $from,
        DateTime $to,
        array $nameContains,
        float $hoursPerDay
    ): float {
        $q = $this->createQueryBuilder(Leave::class, 'l');
        $q->leftJoin('l.leaveType', 'lt')
            ->andWhere('l.employee = :empNumber')
            ->andWhere($q->expr()->in('l.status', ':statuses'))
            ->andWhere($q->expr()->between('l.date', ':from', ':to'))
            ->setParameter('empNumber', $empNumber)
            ->setParameter('statuses', [
                Leave::LEAVE_STATUS_LEAVE_TAKEN,
                Leave::LEAVE_STATUS_LEAVE_APPROVED,
            ])
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $orX = $q->expr()->orX();
        foreach ($nameContains as $i => $needle) {
            $param = 'leaveName' . $i;
            $orX->add($q->expr()->like('LOWER(lt.name)', ':' . $param));
            $q->setParameter($param, '%' . strtolower($needle) . '%');
        }
        $q->andWhere($orX);

        /** @var Leave[] $leaves */
        $leaves = $q->getQuery()->execute();
        $hours = 0.0;
        foreach ($leaves as $leave) {
            $hours += ((float) $leave->getLengthDays()) * $hoursPerDay;
        }
        return $hours;
    }

    /**
     * @param Employee $employee
     * @param float $workedHours
     * @param float $vacationHours
     * @return array{regular: float, ot: float, banked: float}
     */
    private function applyPayRules(Employee $employee, float $workedHours, float $vacationHours): array
    {
        $payType = $employee->getPayType();
        $contracted = $employee->getContractedHoursPerWeek() !== null
            ? (float) $employee->getContractedHoursPerWeek()
            : 0.0;
        $threshold = $employee->getOvertimeThresholdHours() !== null
            ? (float) $employee->getOvertimeThresholdHours()
            : self::DEFAULT_OT_THRESHOLD;
        $fdClass = $employee->getFdLicenseClass();
        $isFuneralDirector = $fdClass !== null && strtolower($fdClass) !== 'none';

        if ($payType === self::PAY_TYPE_SALARIED) {
            if ($vacationHours > 0) {
                $regular = max(0.0, $contracted - $vacationHours);
            } else {
                $regular = min($workedHours, $contracted);
            }
            return [
                'regular' => round($regular, 2),
                'ot' => 0.0,
                'banked' => round(max(0.0, $workedHours - $contracted), 2),
            ];
        }

        // hourly or null pay type
        if ($payType === self::PAY_TYPE_HOURLY && $isFuneralDirector) {
            return [
                'regular' => round($workedHours, 2),
                'ot' => 0.0,
                'banked' => 0.0,
            ];
        }

        // hourly non-FD or null pay type
        $otThreshold = ($payType === null) ? self::DEFAULT_OT_THRESHOLD : $threshold;
        return [
            'regular' => round(min($workedHours, $otThreshold), 2),
            'ot' => round(max(0.0, $workedHours - $otThreshold), 2),
            'banked' => 0.0,
        ];
    }
}
