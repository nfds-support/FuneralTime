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

namespace OrangeHRM\Time\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Entity\TimesheetReminderConfig;

/**
 * @OA\Schema(
 *     schema="Time-TimesheetReminderConfigModel",
 *     type="object",
 *     @OA\Property(property="enabled", type="boolean"),
 *     @OA\Property(property="weekday", type="integer"),
 *     @OA\Property(property="sendTime", type="string", example="16:00"),
 *     @OA\Property(property="timezone", type="string"),
 *     @OA\Property(
 *         property="jobTitles",
 *         type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="title", type="string")
 *         )
 *     ),
 *     @OA\Property(
 *         property="employees",
 *         type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="empNumber", type="integer"),
 *             @OA\Property(property="firstName", type="string"),
 *             @OA\Property(property="middleName", type="string"),
 *             @OA\Property(property="lastName", type="string"),
 *             @OA\Property(property="employeeId", type="string")
 *         )
 *     )
 * )
 */
class TimesheetReminderConfigModel implements Normalizable
{
    private TimesheetReminderConfig $config;

    public function __construct(TimesheetReminderConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $jobTitles = [];
        /** @var JobTitle $jobTitle */
        foreach ($this->config->getJobTitles() as $jobTitle) {
            $jobTitles[] = [
                'id' => $jobTitle->getId(),
                'title' => $jobTitle->getJobTitleName(),
            ];
        }

        $employees = [];
        /** @var Employee $employee */
        foreach ($this->config->getEmployees() as $employee) {
            $employees[] = [
                'empNumber' => $employee->getEmpNumber(),
                'firstName' => $employee->getFirstName(),
                'middleName' => $employee->getMiddleName(),
                'lastName' => $employee->getLastName(),
                'employeeId' => $employee->getEmployeeId(),
            ];
        }

        return [
            'enabled' => $this->config->isEnabled(),
            'weekday' => $this->config->getWeekday(),
            'sendTime' => $this->config->getSendTime(),
            'timezone' => $this->config->getTimezone(),
            'jobTitles' => $jobTitles,
            'employees' => $employees,
        ];
    }
}
