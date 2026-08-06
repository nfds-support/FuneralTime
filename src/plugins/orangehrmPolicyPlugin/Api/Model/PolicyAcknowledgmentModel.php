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

namespace OrangeHRM\Policy\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\PolicyAcknowledgment;

/**
 * @OA\Schema(
 *     schema="Policy-PolicyAcknowledgmentModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="acknowledgedAt", type="string", format="date-time"),
 *     @OA\Property(property="ipAddress", type="string"),
 *     @OA\Property(property="policy", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="version", type="string")
 *     ),
 *     @OA\Property(property="employee", type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="employeeId", type="string")
 *     )
 * )
 */
class PolicyAcknowledgmentModel implements Normalizable
{
    use ModelTrait;

    public function __construct(PolicyAcknowledgment $acknowledgment)
    {
        $this->setEntity($acknowledgment);
        $this->setFilters([
            'id',
            ['getAcknowledgedAt', 'Y-m-d H:i'],
            'ipAddress',
            ['getPolicy', 'getId'],
            ['getPolicy', 'getTitle'],
            ['getPolicy', 'getVersion'],
            ['getEmployee', 'getEmpNumber'],
            ['getEmployee', 'getFirstName'],
            ['getEmployee', 'getLastName'],
            ['getEmployee', 'getMiddleName'],
            ['getEmployee', 'getEmployeeId'],
        ]);
        $this->setAttributeNames([
            'id',
            'acknowledgedAt',
            'ipAddress',
            ['policy', 'id'],
            ['policy', 'title'],
            ['policy', 'version'],
            ['employee', 'empNumber'],
            ['employee', 'firstName'],
            ['employee', 'lastName'],
            ['employee', 'middleName'],
            ['employee', 'employeeId'],
        ]);
    }
}
