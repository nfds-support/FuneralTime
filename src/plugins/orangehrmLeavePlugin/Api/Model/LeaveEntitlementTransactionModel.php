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

namespace OrangeHRM\Leave\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\LeaveEntitlementTransaction;

/**
 * @OA\Schema(
 *     schema="Leave-LeaveEntitlementTransactionModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="empNumber", type="integer"),
 *     @OA\Property(property="employeeName", type="string"),
 *     @OA\Property(property="leaveType", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="entitlementId", type="integer", nullable=true),
 *     @OA\Property(property="transactionType", type="string"),
 *     @OA\Property(property="days", type="number"),
 *     @OA\Property(property="balanceAfter", type="number", nullable=true),
 *     @OA\Property(property="note", type="string", nullable=true),
 *     @OA\Property(property="createdAt", type="string"),
 *     @OA\Property(property="createdBy", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="userName", type="string")
 *     )
 * )
 */
class LeaveEntitlementTransactionModel implements Normalizable
{
    private LeaveEntitlementTransaction $transaction;

    public function __construct(LeaveEntitlementTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $employee = $this->transaction->getEmployee();
        $leaveType = $this->transaction->getLeaveType();
        $createdBy = $this->transaction->getCreatedBy();

        return [
            'id' => $this->transaction->getId(),
            'empNumber' => $employee->getEmpNumber(),
            'employeeName' => trim($employee->getFirstName() . ' ' . $employee->getLastName()),
            'leaveType' => [
                'id' => $leaveType->getId(),
                'name' => $leaveType->getName(),
            ],
            'entitlementId' => $this->transaction->getEntitlementId(),
            'transactionType' => $this->transaction->getTransactionType(),
            'days' => (float) $this->transaction->getDays(),
            'balanceAfter' => $this->transaction->getBalanceAfter() !== null
                ? (float) $this->transaction->getBalanceAfter()
                : null,
            'note' => $this->transaction->getNote(),
            'createdAt' => $this->transaction->getDecorator()->getCreatedAt(),
            'createdBy' => $createdBy !== null
                ? [
                    'id' => $createdBy->getId(),
                    'userName' => $createdBy->getUserName(),
                ]
                : null,
        ];
    }
}
