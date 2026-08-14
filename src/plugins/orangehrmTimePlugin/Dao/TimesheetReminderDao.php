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

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\TimesheetReminderConfig;

class TimesheetReminderDao extends BaseDao
{
    public function getConfig(): ?TimesheetReminderConfig
    {
        return $this->getRepository(TimesheetReminderConfig::class)->findOneBy([]);
    }

    public function saveConfig(TimesheetReminderConfig $config): TimesheetReminderConfig
    {
        $this->persist($config);
        return $config;
    }

    /**
     * Active employees matching selected job titles and/or emp numbers, with a work email.
     *
     * @param int[] $jobTitleIds
     * @param int[] $empNumbers
     * @return Employee[]
     */
    public function getEligibleEmployees(array $jobTitleIds, array $empNumbers): array
    {
        $jobTitleIds = array_values(array_unique(array_filter(array_map('intval', $jobTitleIds))));
        $empNumbers = array_values(array_unique(array_filter(array_map('intval', $empNumbers))));
        if ($jobTitleIds === [] && $empNumbers === []) {
            return [];
        }

        $q = $this->createQueryBuilder(Employee::class, 'employee');
        $q->leftJoin('employee.jobTitle', 'jobTitle');
        $q->andWhere($q->expr()->isNull('employee.employeeTerminationRecord'));
        $q->andWhere($q->expr()->isNull('employee.purgedAt'));
        $q->andWhere($q->expr()->isNotNull('employee.workEmail'));
        $q->andWhere("employee.workEmail != ''");

        $orX = $q->expr()->orX();
        if ($jobTitleIds !== []) {
            $orX->add($q->expr()->in('jobTitle.id', ':jobTitleIds'));
            $q->setParameter('jobTitleIds', $jobTitleIds);
        }
        if ($empNumbers !== []) {
            $orX->add($q->expr()->in('employee.empNumber', ':empNumbers'));
            $q->setParameter('empNumbers', $empNumbers);
        }
        $q->andWhere($orX);

        return $q->getQuery()->execute();
    }
}
