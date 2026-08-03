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

namespace OrangeHRM\Entity\Decorator;

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Holiday;
use OrangeHRM\Entity\LaborUnion;

class HolidayDecorator
{
    use DateTimeHelperTrait;
    use EntityManagerHelperTrait;

    /**
     * @var Holiday
     */
    protected Holiday $holiday;

    /**
     * @param Holiday $holiday
     */
    public function __construct(Holiday $holiday)
    {
        $this->holiday = $holiday;
    }

    /**
     * @return Holiday
     */
    protected function getHoliday(): Holiday
    {
        return $this->holiday;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        $date = $this->getHoliday()->getDate();
        return $this->getDateTimeHelper()->formatDate($date);
    }

    /**
     * @return string|null
     */
    public function getLengthName(): ?string
    {
        return Holiday::HOLIDAY_LENGTH_MAP[$this->getHoliday()->getLength()] ?? null;
    }

    /**
     * @param int|null $unionId
     */
    public function setLaborUnionById(?int $unionId): void
    {
        if ($unionId === null) {
            $this->getHoliday()->setLaborUnion(null);
            return;
        }
        /** @var LaborUnion $union */
        $union = $this->getReference(LaborUnion::class, $unionId);
        $this->getHoliday()->setLaborUnion($union);
    }
}
