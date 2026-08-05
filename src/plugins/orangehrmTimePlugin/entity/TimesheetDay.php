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

namespace OrangeHRM\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_timesheet_day")
 * @ORM\Entity
 */
class TimesheetDay
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var Timesheet
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Timesheet")
     * @ORM\JoinColumn(name="timesheet_id", referencedColumnName="timesheet_id")
     */
    private Timesheet $timesheet;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private DateTime $date;

    /**
     * @var bool
     *
     * @ORM\Column(name="on_call", type="boolean", options={"default" : 0})
     */
    private bool $onCall = false;

    /**
     * Break deduction duration in seconds.
     *
     * @var int
     *
     * @ORM\Column(name="break_duration", type="integer", options={"default" : 0})
     */
    private int $breakDuration = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Timesheet
     */
    public function getTimesheet(): Timesheet
    {
        return $this->timesheet;
    }

    /**
     * @param Timesheet $timesheet
     */
    public function setTimesheet(Timesheet $timesheet): void
    {
        $this->timesheet = $timesheet;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return bool
     */
    public function isOnCall(): bool
    {
        return $this->onCall;
    }

    /**
     * @param bool $onCall
     */
    public function setOnCall(bool $onCall): void
    {
        $this->onCall = $onCall;
    }

    /**
     * @return int
     */
    public function getBreakDuration(): int
    {
        return $this->breakDuration;
    }

    /**
     * @param int $breakDuration
     */
    public function setBreakDuration(int $breakDuration): void
    {
        $this->breakDuration = max(0, $breakDuration);
    }
}
