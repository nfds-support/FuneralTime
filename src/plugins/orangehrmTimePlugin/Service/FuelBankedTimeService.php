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

use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\FuelBankedTimeRequest;
use OrangeHRM\Entity\LeaveEntitlementTransaction;
use OrangeHRM\Entity\User;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementServiceTrait;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementTransactionServiceTrait;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use OrangeHRM\Time\Dao\FuelBankedTimeDao;
use OrangeHRM\Time\Dto\FuelBankedTimeRequestSearchFilterParams;
use OrangeHRM\Time\Traits\Service\BankedTimeServiceTrait;
use Exception;

class FuelBankedTimeService
{
    use BankedTimeServiceTrait;
    use DateTimeHelperTrait;
    use EmployeeServiceTrait;
    use LeaveEntitlementServiceTrait;
    use LeaveEntitlementTransactionServiceTrait;
    use UserRoleManagerTrait;

    private ?FuelBankedTimeDao $dao = null;

    public function getDao(): FuelBankedTimeDao
    {
        if (!$this->dao instanceof FuelBankedTimeDao) {
            $this->dao = new FuelBankedTimeDao();
        }
        return $this->dao;
    }

    public function setDao(FuelBankedTimeDao $dao): void
    {
        $this->dao = $dao;
    }

    /**
     * @return FuelBankedTimeRequest[]
     */
    public function search(FuelBankedTimeRequestSearchFilterParams $filterParams): array
    {
        return $this->getDao()->search($filterParams);
    }

    public function getCount(FuelBankedTimeRequestSearchFilterParams $filterParams): int
    {
        return $this->getDao()->getCount($filterParams);
    }

    public function getById(int $id): ?FuelBankedTimeRequest
    {
        return $this->getDao()->getById($id);
    }

    /**
     * @return array{
     *   enabled: bool,
     *   hourlyRate: float|null,
     *   bankedHours: float,
     *   hoursPerDay: float,
     *   bankedTimeLeaveTypeId: int|null
     * }
     */
    public function getEligibility(int $empNumber): array
    {
        $employee = $this->getEmployeeService()->getEmployeeByEmpNumber($empNumber);
        $hoursPerDay = $employee instanceof Employee
            ? $this->getBankedTimeService()->getHoursPerDayForEmployee($employee)
            : BankedTimeService::DEFAULT_HOURS_PER_DAY;
        $hourlyRate = $employee instanceof Employee ? $this->resolveHourlyRate($employee) : null;
        $bankedDays = 0.0;
        $leaveTypeId = $this->getBankedTimeService()->getBankedTimeLeaveTypeId();
        if ($leaveTypeId !== null && $employee instanceof Employee) {
            $bankedDays = $this->getLeaveEntitlementService()
                ->getLeaveBalance($empNumber, $leaveTypeId)
                ->getBalance();
        }

        return [
            'enabled' => $employee instanceof Employee && $employee->isFuelForBankedTimeEnabled(),
            'hourlyRate' => $hourlyRate,
            'bankedHours' => round($bankedDays * $hoursPerDay, 4),
            'hoursPerDay' => $hoursPerDay,
            'bankedTimeLeaveTypeId' => $leaveTypeId,
        ];
    }

    public function createRequest(int $empNumber, float $amount, ?string $comment = null): FuelBankedTimeRequest
    {
        $employee = $this->getEmployeeService()->getEmployeeByEmpNumber($empNumber);
        if (!$employee instanceof Employee) {
            throw new Exception('Employee not found');
        }
        if (!$employee->isFuelForBankedTimeEnabled()) {
            throw new Exception('Fuel for banked time is not enabled for this employee');
        }

        $hourlyRate = $this->resolveHourlyRate($employee);
        if ($hourlyRate === null || $hourlyRate <= 0) {
            throw new Exception('Employee hourly rate is not configured');
        }
        if ($amount <= 0) {
            throw new Exception('Fuel amount must be greater than zero');
        }

        $hours = round($amount / $hourlyRate, 4);
        $eligibility = $this->getEligibility($empNumber);
        if ($hours > $eligibility['bankedHours'] + 0.0001) {
            throw new Exception('Insufficient banked hours for this fuel amount');
        }

        $request = new FuelBankedTimeRequest();
        $request->setEmployee($employee);
        $request->setAmount(number_format($amount, 2, '.', ''));
        $request->setHourlyRate(number_format($hourlyRate, 2, '.', ''));
        $request->setHours(number_format($hours, 4, '.', ''));
        $request->setStatus(FuelBankedTimeRequest::STATUS_PENDING);
        $request->setComment($comment);
        $request->setCreatedAt($this->getDateTimeHelper()->getNow());

        return $this->getDao()->save($request);
    }

    public function approve(FuelBankedTimeRequest $request): FuelBankedTimeRequest
    {
        if ($request->getStatus() !== FuelBankedTimeRequest::STATUS_PENDING) {
            throw new Exception('Only pending requests can be approved');
        }

        $empNumber = $request->getEmployee()->getEmpNumber();
        $leaveTypeId = $this->getBankedTimeService()->getBankedTimeLeaveTypeId();
        if ($leaveTypeId === null) {
            throw new Exception('Banked Time leave type is not configured');
        }

        $hours = (float) $request->getHours();
        $hoursPerDay = $this->getBankedTimeService()->getHoursPerDayForEmployee($request->getEmployee());
        $days = round($hours / $hoursPerDay, 4);
        if ($days <= 0) {
            throw new Exception('Calculated banked days must be greater than zero');
        }

        $balance = $this->getLeaveEntitlementService()
            ->getLeaveBalance($empNumber, $leaveTypeId)
            ->getBalance();
        if ($days > $balance + 0.0001) {
            throw new Exception('Insufficient banked time balance');
        }

        $this->getLeaveEntitlementTransactionService()->createAdjustment(
            $empNumber,
            $leaveTypeId,
            LeaveEntitlementTransaction::TYPE_DEDUCTION,
            $days,
            sprintf('Fuel for banked time request #%d', $request->getId())
        );

        $request->setStatus(FuelBankedTimeRequest::STATUS_APPROVED);
        $this->markActioned($request);

        return $this->getDao()->save($request);
    }

    public function reject(FuelBankedTimeRequest $request): FuelBankedTimeRequest
    {
        if ($request->getStatus() !== FuelBankedTimeRequest::STATUS_PENDING) {
            throw new Exception('Only pending requests can be rejected');
        }
        $request->setStatus(FuelBankedTimeRequest::STATUS_REJECTED);
        $this->markActioned($request);
        return $this->getDao()->save($request);
    }

    public function cancel(FuelBankedTimeRequest $request): FuelBankedTimeRequest
    {
        if ($request->getStatus() !== FuelBankedTimeRequest::STATUS_PENDING) {
            throw new Exception('Only pending requests can be cancelled');
        }
        $request->setStatus(FuelBankedTimeRequest::STATUS_CANCELLED);
        $this->markActioned($request);
        return $this->getDao()->save($request);
    }

    private function markActioned(FuelBankedTimeRequest $request): void
    {
        $user = $this->getUserRoleManager()->getUser();
        if ($user instanceof User) {
            $request->setActionedBy($user);
        }
        $now = $this->getDateTimeHelper()->getNow();
        $request->setActionedAt($now);
        $request->setUpdatedAt($now);
    }

    public function resolveHourlyRate(Employee $employee): ?float
    {
        $hourlyRate = $employee->getHourlyRate();
        if ($hourlyRate === null || $hourlyRate === '' || !is_numeric($hourlyRate)) {
            return null;
        }
        $rate = (float) $hourlyRate;
        return $rate > 0 ? $rate : null;
    }
}
