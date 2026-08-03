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

namespace OrangeHRM\Union\Service;

use DateTime;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmployeeUnion;
use OrangeHRM\Entity\UnionLeaveRule;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementServiceTrait;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use OrangeHRM\Union\Dao\UnionDao;

class UnionService
{
    use LeaveEntitlementServiceTrait;
    use EmployeeServiceTrait;

    protected ?UnionDao $unionDao = null;

    public function getUnionDao(): UnionDao
    {
        return $this->unionDao ??= new UnionDao();
    }

    public function setUnionDao(UnionDao $unionDao): void
    {
        $this->unionDao = $unionDao;
    }

    /**
     * Years of seniority as of $asOf, using primary union seniority date,
     * falling back to employee joined date.
     */
    public function getSeniorityYears(int $empNumber, ?DateTime $asOf = null): float
    {
        $asOf ??= new DateTime('today');
        $assignment = $this->getUnionDao()->getPrimaryEmployeeUnion($empNumber, $asOf);
        $start = $assignment?->getSeniorityDate();
        if (!$start instanceof DateTime) {
            $employee = $this->getEmployeeService()->getEmployeeByEmpNumber($empNumber);
            $start = $employee instanceof Employee ? $employee->getJoinedDate() : null;
        }
        if (!$start instanceof DateTime) {
            return 0.0;
        }
        $diff = $start->diff($asOf);
        $years = $diff->y + ($diff->m / 12) + ($diff->d / 365);
        return max(0.0, (float) $years);
    }

    public function resolveEntitlementDays(int $empNumber, int $leaveTypeId, ?DateTime $asOf = null): ?float
    {
        $asOf ??= new DateTime('today');
        $unionId = $this->getUnionDao()->getPrimaryUnionIdByEmpNumber($empNumber);
        $years = $this->getSeniorityYears($empNumber, $asOf);

        $rules = $this->getUnionDao()->findLeaveRulesForUnionAndLeaveType($unionId, $leaveTypeId);
        if (empty($rules) && $unionId !== null) {
            // Fall back to company-default (non-union) rules
            $rules = $this->getUnionDao()->findLeaveRulesForUnionAndLeaveType(null, $leaveTypeId);
        }

        foreach ($rules as $rule) {
            if ($this->ruleMatchesYears($rule, $years)) {
                return (float) $rule->getEntitlementDays();
            }
        }
        return null;
    }

    private function ruleMatchesYears(UnionLeaveRule $rule, float $years): bool
    {
        if ($years < $rule->getMinYears()) {
            return false;
        }
        if ($rule->getMaxYears() !== null && $years > $rule->getMaxYears()) {
            return false;
        }
        return true;
    }

    /**
     * @param int[]|null $empNumbers
     * @return array{generated:int, skipped:int, details:array}
     */
    public function generateEntitlementsFromRules(
        int $leaveTypeId,
        DateTime $fromDate,
        DateTime $toDate,
        ?array $empNumbers = null
    ): array {
        $generated = 0;
        $skipped = 0;
        $details = [];

        if ($empNumbers === null) {
            $assignments = $this->getUnionDao()->getAllPrimaryEmployeeUnions();
            $empNumbers = array_map(
                fn (EmployeeUnion $a) => $a->getEmployee()->getEmpNumber(),
                $assignments
            );
            // Also include non-union employees who may match company-default rules:
            // leave that to explicit empNumbers for now; primary list covers union members.
        }

        foreach (array_unique($empNumbers) as $empNumber) {
            $days = $this->resolveEntitlementDays((int) $empNumber, $leaveTypeId, $fromDate);
            if ($days === null || $days <= 0) {
                $skipped++;
                $details[] = [
                    'empNumber' => (int) $empNumber,
                    'status' => 'skipped',
                    'reason' => 'no_matching_rule',
                ];
                continue;
            }
            $this->getLeaveEntitlementService()->addEntitlementForEmployee(
                (int) $empNumber,
                $leaveTypeId,
                $fromDate,
                $toDate,
                $days
            );
            $generated++;
            $details[] = [
                'empNumber' => (int) $empNumber,
                'status' => 'generated',
                'days' => $days,
            ];
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'details' => $details,
        ];
    }
}
