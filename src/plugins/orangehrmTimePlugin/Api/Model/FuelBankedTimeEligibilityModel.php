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

/**
 * @OA\Schema(
 *     schema="Time-FuelBankedTimeEligibilityModel",
 *     type="object",
 *     @OA\Property(property="enabled", type="boolean"),
 *     @OA\Property(property="hourlyRate", type="number", nullable=true),
 *     @OA\Property(property="bankedHours", type="number"),
 *     @OA\Property(property="hoursPerDay", type="number"),
 *     @OA\Property(property="bankedTimeLeaveTypeId", type="integer", nullable=true)
 * )
 */
class FuelBankedTimeEligibilityModel implements Normalizable
{
    private array $eligibility;

    public function __construct(array $eligibility)
    {
        $this->eligibility = $eligibility;
    }

    public function toArray(): array
    {
        return [
            'enabled' => (bool) ($this->eligibility['enabled'] ?? false),
            'hourlyRate' => $this->eligibility['hourlyRate'] ?? null,
            'bankedHours' => (float) ($this->eligibility['bankedHours'] ?? 0),
            'hoursPerDay' => (float) ($this->eligibility['hoursPerDay'] ?? 8),
            'bankedTimeLeaveTypeId' => $this->eligibility['bankedTimeLeaveTypeId'] ?? null,
        ];
    }
}
