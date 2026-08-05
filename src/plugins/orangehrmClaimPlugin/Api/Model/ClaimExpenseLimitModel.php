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

namespace OrangeHRM\Claim\Api\Model;

use OpenApi\Annotations as OA;
use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\ClaimExpenseLimit;

/**
 * @OA\Schema(
 *     schema="Claim-ClaimExpenseLimitModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(
 *         property="expenseType",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="reportColumn", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="monthlyLimit", type="number")
 * )
 */
class ClaimExpenseLimitModel implements Normalizable
{
    use ModelTrait;

    public function __construct(ClaimExpenseLimit $claimExpenseLimit)
    {
        $this->setEntity($claimExpenseLimit);
        $this->setFilters(
            [
                'id',
                ['getExpenseType', 'getId'],
                ['getExpenseType', 'getName'],
                ['getExpenseType', 'getReportColumn'],
                'monthlyLimit',
            ]
        );
        $this->setAttributeNames(
            [
                'id',
                ['expenseType', 'id'],
                ['expenseType', 'name'],
                ['expenseType', 'reportColumn'],
                'monthlyLimit',
            ]
        );
    }
}
