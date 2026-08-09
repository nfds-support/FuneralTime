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

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmployeeWorkShift;
use OrangeHRM\Entity\Timesheet;
use OrangeHRM\Entity\TimesheetItem;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementServiceTrait;
use OrangeHRM\Leave\Traits\Service\LeavePeriodServiceTrait;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;

class BankedTimeService
{
    use EntityManagerHelperTrait;
    use ConfigServiceTrait;
    use EmployeeServiceTrait;
    use LeaveEntitlementServiceTrait;
    use LeavePeriodServiceTrait;

    public const CONFIG_BANKED_TIME_TYPE_ID = 'leave.banked_time_type_id';
    public const DEFAULT_HOURS_PER_DAY = 8.0;
    public const PAY_TYPE_SALARIED = 'salaried';

    /**
     * Credit banked time leave entitlement when a salaried employee's timesheet is approved.
     *
     * @param Timesheet $timesheet
     */
    public function creditBankedTimeForApprovedTimesheet(Timesheet $timesheet): void
    {
        if ($timesheet->getState() !== Timesheet::STATE_APPROVED) {
            return;
        }

        $employee = $timesheet->getEmployee();
        if ($employee->getPayType() !== self::PAY_TYPE_SALARIED) {
            return;
        }
        if ($employee->getContractedHoursPerWeek() === null) {
            return;
        }

        $contracted = (float) $employee->getContractedHoursPerWeek();
        $workedHours = $this->getTimesheetWorkedHours($timesheet);
        $excess = $workedHours - $contracted;
        if ($excess <= 0) {
            return;
        }

        $leaveTypeId = $this->getBankedTimeLeaveTypeId();
        if ($leaveTypeId === null) {
            return;
        }

        $hoursPerDay = $this->getHoursPerDay($employee);
        $days = round($excess / $hoursPerDay, 4);
        if ($days <= 0) {
            return;
        }

        $fromDate = $timesheet->getStartDate();
        $toDate = $timesheet->getEndDate();
        $leavePeriod = $this->getLeavePeriodService()->getCurrentLeavePeriodByDate($timesheet->getStartDate());
        if ($leavePeriod !== null && $leavePeriod->getStartDate() !== null && $leavePeriod->getEndDate() !== null) {
            $fromDate = $leavePeriod->getStartDate();
            $toDate = $leavePeriod->getEndDate();
        }

        $this->getLeaveEntitlementService()->addEntitlementForEmployee(
            $employee->getEmpNumber(),
            $leaveTypeId,
            $fromDate,
            $toDate,
            $days
        );
    }

    /**
     * @param Timesheet $timesheet
     * @return float
     */
    private function getTimesheetWorkedHours(Timesheet $timesheet): float
    {
        $q = $this->createQueryBuilder(TimesheetItem::class, 'ti');
        $q->select('SUM(ti.duration)')
            ->andWhere('ti.timesheet = :timesheetId')
            ->setParameter('timesheetId', $timesheet->getId());
        $seconds = $q->getQuery()->getSingleScalarResult();
        if ($seconds === null) {
            return 0.0;
        }
        return ((float) $seconds) / 3600;
    }

    /**
     * @return int|null
     */
    public function getBankedTimeLeaveTypeId(): ?int
    {
        $value = $this->getConfigService()->getConfigDao()->getValue(self::CONFIG_BANKED_TIME_TYPE_ID);
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    public function isBankedTimeLeaveType(int $leaveTypeId): bool
    {
        $bankedTypeId = $this->getBankedTimeLeaveTypeId();
        return $bankedTypeId !== null && $bankedTypeId === $leaveTypeId;
    }

    /**
     * @param Employee $employee
     * @return float
     */
    public function getHoursPerDayForEmployee(Employee $employee): float
    {
        return $this->getHoursPerDay($employee);
    }

    /**
     * Convert leave entitlement days to display hours for Banked Time.
     */
    public function daysToHours(float $days, float $hoursPerDay): float
    {
        return round($days * $hoursPerDay, 4);
    }

    /**
     * Convert hours to leave entitlement days for Banked Time.
     */
    public function hoursToDays(float $hours, float $hoursPerDay): float
    {
        if ($hoursPerDay <= 0) {
            $hoursPerDay = self::DEFAULT_HOURS_PER_DAY;
        }
        return round($hours / $hoursPerDay, 4);
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
}
