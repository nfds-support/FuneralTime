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
use OrangeHRM\Entity\MoodleCohortMap;

/**
 * @OA\Schema(
 *     schema="Policy-MoodleCohortMapModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="moodleCohortId", type="integer"),
 *     @OA\Property(property="moodleCohortName", type="string"),
 *     @OA\Property(property="jobTitle", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="title", type="string")
 *     )
 * )
 */
class MoodleCohortMapModel implements Normalizable
{
    use ModelTrait;

    public function __construct(MoodleCohortMap $map)
    {
        $this->setEntity($map);
        $this->setFilters([
            'id',
            'moodleCohortId',
            'moodleCohortName',
            ['getJobTitle', 'getId'],
            ['getJobTitle', 'getJobTitleName'],
        ]);
        $this->setAttributeNames([
            'id',
            'moodleCohortId',
            'moodleCohortName',
            ['jobTitle', 'id'],
            ['jobTitle', 'title'],
        ]);
    }
}
