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

namespace OrangeHRM\Discipline\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\DisciplineCase;

/**
 * @OA\Schema(
 *     schema="Discipline-DisciplineCaseModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="caseType", type="string"),
 *     @OA\Property(property="category", type="string"),
 *     @OA\Property(property="subject", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="incidentDate", type="string", format="date"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="severity", type="string"),
 *     @OA\Property(property="actionTaken", type="string"),
 *     @OA\Property(property="createdAt", type="string", format="date"),
 *     @OA\Property(property="updatedAt", type="string", format="date"),
 *     @OA\Property(property="employee", type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="employeeId", type="string"),
 *         @OA\Property(property="terminationId", type="integer"),
 *     ),
 *     @OA\Property(property="reportedBy", type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="employeeId", type="string"),
 *         @OA\Property(property="terminationId", type="integer"),
 *     ),
 * )
 */
class DisciplineCaseModel implements Normalizable
{
    use ModelTrait;

    public function __construct(DisciplineCase $disciplineCase)
    {
        $this->setEntity($disciplineCase);
        $this->setFilters([
            'id',
            'caseType',
            'category',
            'subject',
            'description',
            ['getIncidentDate', 'Y-m-d'],
            'status',
            'severity',
            'actionTaken',
            ['getCreatedAt', 'Y-m-d'],
            ['getUpdatedAt', 'Y-m-d'],
            ['getEmployee', 'getEmpNumber'],
            ['getEmployee', 'getFirstName'],
            ['getEmployee', 'getLastName'],
            ['getEmployee', 'getMiddleName'],
            ['getEmployee', 'getEmployeeId'],
            ['getEmployee', ['getEmployeeTerminationRecord', 'getId']],
            ['getReportedBy', 'getEmpNumber'],
            ['getReportedBy', 'getFirstName'],
            ['getReportedBy', 'getLastName'],
            ['getReportedBy', 'getMiddleName'],
            ['getReportedBy', 'getEmployeeId'],
            ['getReportedBy', ['getEmployeeTerminationRecord', 'getId']],
        ]);
        $this->setAttributeNames([
            'id',
            'caseType',
            'category',
            'subject',
            'description',
            'incidentDate',
            'status',
            'severity',
            'actionTaken',
            'createdAt',
            'updatedAt',
            ['employee', 'empNumber'],
            ['employee', 'firstName'],
            ['employee', 'lastName'],
            ['employee', 'middleName'],
            ['employee', 'employeeId'],
            ['employee', 'terminationId'],
            ['reportedBy', 'empNumber'],
            ['reportedBy', 'firstName'],
            ['reportedBy', 'lastName'],
            ['reportedBy', 'middleName'],
            ['reportedBy', 'employeeId'],
            ['reportedBy', 'terminationId'],
        ]);
    }
}
