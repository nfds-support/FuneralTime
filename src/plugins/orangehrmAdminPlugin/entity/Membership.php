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

use Doctrine\ORM\Mapping as ORM;

/**
 * Membership
 *
 * @ORM\Table(name="ohrm_membership")
 * @ORM\Entity
 */
class Membership
{
    public const DUES_MODE_FLAT = 'flat';
    public const DUES_MODE_RATE_WEEKLY = 'rate_weekly';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private string $name;

    /**
     * @var string flat|rate_weekly
     *
     * @ORM\Column(name="dues_mode", type="string", length=20, options={"default" : "flat"})
     */
    private string $duesMode = self::DUES_MODE_FLAT;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dues_hourly_multiplier", type="decimal", precision=8, scale=4, nullable=true)
     */
    private ?string $duesHourlyMultiplier = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dues_weekly_flat_fee", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $duesWeeklyFlatFee = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="initiation_fee_full_time", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $initiationFeeFullTime = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="initiation_fee_part_time", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $initiationFeePartTime = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="initiation_weekly_max_full_time", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $initiationWeeklyMaxFullTime = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="initiation_weekly_max_part_time", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $initiationWeeklyMaxPartTime = null;

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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDuesMode(): string
    {
        return $this->duesMode;
    }

    public function setDuesMode(string $duesMode): void
    {
        $this->duesMode = $duesMode;
    }

    public function isRateWeekly(): bool
    {
        return $this->duesMode === self::DUES_MODE_RATE_WEEKLY;
    }

    public function getDuesHourlyMultiplier(): ?float
    {
        return $this->duesHourlyMultiplier === null ? null : (float) $this->duesHourlyMultiplier;
    }

    public function setDuesHourlyMultiplier(?float $duesHourlyMultiplier): void
    {
        $this->duesHourlyMultiplier = $duesHourlyMultiplier === null
            ? null
            : number_format($duesHourlyMultiplier, 4, '.', '');
    }

    public function getDuesWeeklyFlatFee(): ?float
    {
        return $this->duesWeeklyFlatFee === null ? null : (float) $this->duesWeeklyFlatFee;
    }

    public function setDuesWeeklyFlatFee(?float $duesWeeklyFlatFee): void
    {
        $this->duesWeeklyFlatFee = $duesWeeklyFlatFee === null
            ? null
            : number_format($duesWeeklyFlatFee, 2, '.', '');
    }

    public function getInitiationFeeFullTime(): ?float
    {
        return $this->initiationFeeFullTime === null ? null : (float) $this->initiationFeeFullTime;
    }

    public function setInitiationFeeFullTime(?float $initiationFeeFullTime): void
    {
        $this->initiationFeeFullTime = $initiationFeeFullTime === null
            ? null
            : number_format($initiationFeeFullTime, 2, '.', '');
    }

    public function getInitiationFeePartTime(): ?float
    {
        return $this->initiationFeePartTime === null ? null : (float) $this->initiationFeePartTime;
    }

    public function setInitiationFeePartTime(?float $initiationFeePartTime): void
    {
        $this->initiationFeePartTime = $initiationFeePartTime === null
            ? null
            : number_format($initiationFeePartTime, 2, '.', '');
    }

    public function getInitiationWeeklyMaxFullTime(): ?float
    {
        return $this->initiationWeeklyMaxFullTime === null ? null : (float) $this->initiationWeeklyMaxFullTime;
    }

    public function setInitiationWeeklyMaxFullTime(?float $initiationWeeklyMaxFullTime): void
    {
        $this->initiationWeeklyMaxFullTime = $initiationWeeklyMaxFullTime === null
            ? null
            : number_format($initiationWeeklyMaxFullTime, 2, '.', '');
    }

    public function getInitiationWeeklyMaxPartTime(): ?float
    {
        return $this->initiationWeeklyMaxPartTime === null ? null : (float) $this->initiationWeeklyMaxPartTime;
    }

    public function setInitiationWeeklyMaxPartTime(?float $initiationWeeklyMaxPartTime): void
    {
        $this->initiationWeeklyMaxPartTime = $initiationWeeklyMaxPartTime === null
            ? null
            : number_format($initiationWeeklyMaxPartTime, 2, '.', '');
    }
}
