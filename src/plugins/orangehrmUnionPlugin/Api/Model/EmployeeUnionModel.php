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

namespace OrangeHRM\Union\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\EmployeeUnion;

class EmployeeUnionModel implements Normalizable
{
    use ModelTrait;

    public function __construct(EmployeeUnion $employeeUnion)
    {
        $this->setEntity($employeeUnion);
        $this->setFilters([
            'id',
            ['getEmployee', 'getEmpNumber'],
            ['getEmployee', 'getFirstName'],
            ['getEmployee', 'getLastName'],
            ['getEmployee', 'getMiddleName'],
            ['getEmployee', 'getEmployeeId'],
            ['getEmployee', ['getEmployeeTerminationRecord', 'getId']],
            ['getLaborUnion', 'getId'],
            ['getLaborUnion', 'getName'],
            ['getSeniorityDate', 'Y-m-d'],
            'seniorityRank',
            ['isPrimary'],
            ['getStartDate', 'Y-m-d'],
            ['getEndDate', 'Y-m-d'],
        ]);
        $this->setAttributeNames([
            'id',
            ['employee', 'empNumber'],
            ['employee', 'firstName'],
            ['employee', 'lastName'],
            ['employee', 'middleName'],
            ['employee', 'employeeId'],
            ['employee', 'terminationId'],
            ['union', 'id'],
            ['union', 'name'],
            'seniorityDate',
            'seniorityRank',
            'primary',
            'startDate',
            'endDate',
        ]);
    }
}
