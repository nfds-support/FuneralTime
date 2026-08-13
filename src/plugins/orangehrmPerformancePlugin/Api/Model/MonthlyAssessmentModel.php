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

namespace OrangeHRM\Performance\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\MonthlyAssessment;

/**
 * @OA\Schema(
 *     schema="Performance-MonthlyAssessmentModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="periodYear", type="integer"),
 *     @OA\Property(property="periodMonth", type="integer"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="employeeOverallRating", type="integer"),
 *     @OA\Property(property="employeeEngagementRating", type="integer"),
 *     @OA\Property(property="employeeAccomplishments", type="string"),
 *     @OA\Property(property="employeeImprovements", type="string"),
 *     @OA\Property(property="employeeGoals", type="string"),
 *     @OA\Property(property="employeeSupportNeeded", type="string"),
 *     @OA\Property(property="employeeSubmittedAt", type="string", format="date-time"),
 *     @OA\Property(property="managerOverallRating", type="integer"),
 *     @OA\Property(property="managerStrengths", type="string"),
 *     @OA\Property(property="managerImprovements", type="string"),
 *     @OA\Property(property="managerGoalsSupport", type="string"),
 *     @OA\Property(property="managerFollowUpNotes", type="string"),
 *     @OA\Property(property="managerSubmittedAt", type="string", format="date-time"),
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
 *     @OA\Property(property="manager", type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="employeeId", type="string"),
 *         @OA\Property(property="terminationId", type="integer"),
 *     ),
 * )
 */
class MonthlyAssessmentModel implements Normalizable
{
    use ModelTrait;

    public function __construct(MonthlyAssessment $assessment)
    {
        $this->setEntity($assessment);
        $this->setFilters([
            'id',
            'periodYear',
            'periodMonth',
            'status',
            'employeeOverallRating',
            'employeeEngagementRating',
            'employeeAccomplishments',
            'employeeImprovements',
            'employeeGoals',
            'employeeSupportNeeded',
            ['getEmployeeSubmittedAt', 'Y-m-d H:i'],
            'managerOverallRating',
            'managerStrengths',
            'managerImprovements',
            'managerGoalsSupport',
            'managerFollowUpNotes',
            ['getManagerSubmittedAt', 'Y-m-d H:i'],
            ['getCreatedAt', 'Y-m-d'],
            ['getUpdatedAt', 'Y-m-d'],
            ['getEmployee', 'getEmpNumber'],
            ['getEmployee', 'getFirstName'],
            ['getEmployee', 'getLastName'],
            ['getEmployee', 'getMiddleName'],
            ['getEmployee', 'getEmployeeId'],
            ['getEmployee', ['getEmployeeTerminationRecord', 'getId']],
            ['getManager', 'getEmpNumber'],
            ['getManager', 'getFirstName'],
            ['getManager', 'getLastName'],
            ['getManager', 'getMiddleName'],
            ['getManager', 'getEmployeeId'],
            ['getManager', ['getEmployeeTerminationRecord', 'getId']],
        ]);
        $this->setAttributeNames([
            'id',
            'periodYear',
            'periodMonth',
            'status',
            'employeeOverallRating',
            'employeeEngagementRating',
            'employeeAccomplishments',
            'employeeImprovements',
            'employeeGoals',
            'employeeSupportNeeded',
            'employeeSubmittedAt',
            'managerOverallRating',
            'managerStrengths',
            'managerImprovements',
            'managerGoalsSupport',
            'managerFollowUpNotes',
            'managerSubmittedAt',
            'createdAt',
            'updatedAt',
            ['employee', 'empNumber'],
            ['employee', 'firstName'],
            ['employee', 'lastName'],
            ['employee', 'middleName'],
            ['employee', 'employeeId'],
            ['employee', 'terminationId'],
            ['manager', 'empNumber'],
            ['manager', 'firstName'],
            ['manager', 'lastName'],
            ['manager', 'middleName'],
            ['manager', 'employeeId'],
            ['manager', 'terminationId'],
        ]);
    }
}
