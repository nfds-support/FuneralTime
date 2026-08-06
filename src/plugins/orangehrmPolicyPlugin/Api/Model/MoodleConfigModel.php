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

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

/**
 * @OA\Schema(
 *     schema="Policy-MoodleConfigModel",
 *     type="object",
 *     @OA\Property(property="baseUrl", type="string"),
 *     @OA\Property(property="webserviceTokenSet", type="boolean"),
 *     @OA\Property(property="syncEnabled", type="boolean")
 * )
 */
class MoodleConfigModel implements Normalizable
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function toArray(): array
    {
        return $this->config;
    }
}
