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

namespace OrangeHRM\Entity\Decorator;

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Entity\Policy;

class PolicyDecorator
{
    use EntityManagerHelperTrait;

    private Policy $policy;

    public function __construct(Policy $policy)
    {
        $this->policy = $policy;
    }

    public function setCreatedByEmpNumber(?int $empNumber): void
    {
        if ($empNumber === null) {
            $this->policy->setCreatedBy(null);
            return;
        }
        /** @var Employee|null $employee */
        $employee = $this->getReference(Employee::class, $empNumber);
        $this->policy->setCreatedBy($employee);
    }

    /**
     * @param int[] $jobTitleIds
     */
    public function setJobTitlesByIds(array $jobTitleIds): void
    {
        $this->policy->clearJobTitles();
        foreach ($jobTitleIds as $jobTitleId) {
            /** @var JobTitle|null $jobTitle */
            $jobTitle = $this->getReference(JobTitle::class, $jobTitleId);
            if ($jobTitle !== null) {
                $this->policy->addJobTitle($jobTitle);
            }
        }
    }

    /**
     * @return int[]
     */
    public function getJobTitleIds(): array
    {
        $ids = [];
        foreach ($this->policy->getJobTitles() as $jobTitle) {
            $ids[] = $jobTitle->getId();
        }
        return $ids;
    }
}
