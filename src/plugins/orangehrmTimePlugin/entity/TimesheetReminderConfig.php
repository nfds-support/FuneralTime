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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OrangeHRM\Entity\Decorator\DecoratorTrait;
use OrangeHRM\Entity\Decorator\TimesheetReminderConfigDecorator;

/**
 * @method TimesheetReminderConfigDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_timesheet_reminder_config")
 * @ORM\Entity
 */
class TimesheetReminderConfig
{
    use DecoratorTrait;

    public const WEEKDAY_SUNDAY = 0;
    public const WEEKDAY_FRIDAY = 5;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default" : false})
     */
    private bool $enabled = false;

    /**
     * PHP DateTime::format('w') — 0 = Sunday through 6 = Saturday.
     *
     * @var int
     *
     * @ORM\Column(name="weekday", type="smallint", options={"default" : 5})
     */
    private int $weekday = self::WEEKDAY_FRIDAY;

    /**
     * @var string
     *
     * @ORM\Column(name="send_time", type="string", length=5, options={"default" : "16:00"})
     */
    private string $sendTime = '16:00';

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=64, options={"default" : "UTC"})
     */
    private string $timezone = 'UTC';

    /**
     * @var Collection<int, JobTitle>
     *
     * @ORM\ManyToMany(targetEntity="OrangeHRM\Entity\JobTitle")
     * @ORM\JoinTable(
     *     name="ohrm_timesheet_reminder_job_title",
     *     joinColumns={@ORM\JoinColumn(name="config_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="job_title_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private Collection $jobTitles;

    /**
     * @var Collection<int, Employee>
     *
     * @ORM\ManyToMany(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinTable(
     *     name="ohrm_timesheet_reminder_employee",
     *     joinColumns={@ORM\JoinColumn(name="config_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="emp_number", referencedColumnName="emp_number", onDelete="CASCADE")}
     * )
     */
    private Collection $employees;

    public function __construct()
    {
        $this->jobTitles = new ArrayCollection();
        $this->employees = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getWeekday(): int
    {
        return $this->weekday;
    }

    public function setWeekday(int $weekday): void
    {
        $this->weekday = $weekday;
    }

    public function getSendTime(): string
    {
        return $this->sendTime;
    }

    public function setSendTime(string $sendTime): void
    {
        $this->sendTime = $sendTime;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    /**
     * @return Collection<int, JobTitle>
     */
    public function getJobTitles(): Collection
    {
        return $this->jobTitles;
    }

    public function addJobTitle(JobTitle $jobTitle): void
    {
        if (!$this->jobTitles->contains($jobTitle)) {
            $this->jobTitles->add($jobTitle);
        }
    }

    public function clearJobTitles(): void
    {
        $this->jobTitles->clear();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Employee $employee): void
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
        }
    }

    public function clearEmployees(): void
    {
        $this->employees->clear();
    }
}
