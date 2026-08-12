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
use OrangeHRM\Entity\FuelBankedTimeRequest;

/**
 * @OA\Schema(
 *     schema="Time-FuelBankedTimeRequestModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="amount", type="number"),
 *     @OA\Property(property="hourlyRate", type="number"),
 *     @OA\Property(property="hours", type="number"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="comment", type="string", nullable=true),
 *     @OA\Property(property="createdAt", type="string", format="date-time"),
 *     @OA\Property(property="actionedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="employee",
 *         type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="employeeId", type="string", nullable=true)
 *     )
 * )
 */
class FuelBankedTimeRequestModel implements Normalizable
{
    private FuelBankedTimeRequest $request;

    public function __construct(FuelBankedTimeRequest $request)
    {
        $this->request = $request;
    }

    public function toArray(): array
    {
        $employee = $this->request->getEmployee();
        return [
            'id' => $this->request->getId(),
            'amount' => (float) $this->request->getAmount(),
            'hourlyRate' => (float) $this->request->getHourlyRate(),
            'hours' => (float) $this->request->getHours(),
            'status' => $this->request->getStatus(),
            'comment' => $this->request->getComment(),
            'createdAt' => $this->request->getCreatedAt()->format('Y-m-d H:i'),
            'actionedAt' => $this->request->getActionedAt()?->format('Y-m-d H:i'),
            'employee' => [
                'empNumber' => $employee->getEmpNumber(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
                'middleName' => $employee->getMiddleName(),
                'employeeId' => $employee->getEmployeeId(),
            ],
        ];
    }
}
