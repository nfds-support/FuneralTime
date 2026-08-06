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

namespace OrangeHRM\Policy\Service;

use DateTime;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Policy;
use OrangeHRM\Entity\PolicyAcknowledgment;
use OrangeHRM\Policy\Dao\PolicyDao;
use OrangeHRM\Policy\Dto\PolicySearchFilterParams;

class PolicyService
{
    protected ?PolicyDao $policyDao = null;

    public function getPolicyDao(): PolicyDao
    {
        return $this->policyDao ??= new PolicyDao();
    }

    public function setPolicyDao(PolicyDao $policyDao): void
    {
        $this->policyDao = $policyDao;
    }

    public function isEmployeeInAudience(Policy $policy, Employee $employee): bool
    {
        if ($policy->getAudienceType() === Policy::AUDIENCE_ALL) {
            return true;
        }
        $jobTitle = $employee->getJobTitle();
        if ($jobTitle === null) {
            return false;
        }
        foreach ($policy->getJobTitles() as $audienceTitle) {
            if ($audienceTitle->getId() === $jobTitle->getId()) {
                return true;
            }
        }
        return false;
    }

    public function acknowledgePolicy(
        Policy $policy,
        Employee $employee,
        ?string $ipAddress = null
    ): PolicyAcknowledgment {
        $existing = $this->getPolicyDao()->getAcknowledgment($policy->getId(), $employee->getEmpNumber());
        if ($existing instanceof PolicyAcknowledgment) {
            return $existing;
        }
        $acknowledgment = new PolicyAcknowledgment();
        $acknowledgment->setPolicy($policy);
        $acknowledgment->setEmployee($employee);
        $acknowledgment->setAcknowledgedAt(new DateTime());
        $acknowledgment->setIpAddress($ipAddress);
        return $this->getPolicyDao()->saveAcknowledgment($acknowledgment);
    }

    /**
     * @return Policy[]
     */
    public function getPublishedPoliciesForEmployee(Employee $employee, ?bool $pendingOnly = null): array
    {
        $filterParams = new PolicySearchFilterParams();
        $filterParams->setStatus(Policy::STATUS_PUBLISHED);
        $filterParams->setLimit(0);
        if ($employee->getJobTitle() !== null) {
            $filterParams->setJobTitleId($employee->getJobTitle()->getId());
        } else {
            $filterParams->setAudienceType(Policy::AUDIENCE_ALL);
        }
        if ($pendingOnly) {
            $filterParams->setPendingOnly(true);
            $filterParams->setEmpNumber($employee->getEmpNumber());
        }
        $policies = $this->getPolicyDao()->getPolicyList($filterParams);
        return array_values(array_filter(
            $policies,
            fn (Policy $policy) => $this->isEmployeeInAudience($policy, $employee)
        ));
    }
}
