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
use OrangeHRM\Entity\Decorator\DecoratorTrait;
use OrangeHRM\Entity\Decorator\UnionLeaveRuleDecorator;

/**
 * @method UnionLeaveRuleDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_union_leave_rule")
 * @ORM\Entity
 */
class UnionLeaveRule
{
    use DecoratorTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var LaborUnion|null
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\LaborUnion")
     * @ORM\JoinColumn(name="union_id", referencedColumnName="id", nullable=true)
     */
    private ?LaborUnion $laborUnion = null;

    /**
     * @var LeaveType
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\LeaveType")
     * @ORM\JoinColumn(name="leave_type_id", referencedColumnName="id")
     */
    private LeaveType $leaveType;

    /**
     * @var int
     *
     * @ORM\Column(name="min_years", type="integer", options={"default" : 0})
     */
    private int $minYears = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_years", type="integer", nullable=true)
     */
    private ?int $maxYears = null;

    /**
     * @var float
     *
     * @ORM\Column(name="entitlement_days", type="decimal", precision=8, scale=2)
     */
    private float $entitlementDays;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="string", length=255, nullable=true)
     */
    private ?string $note = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLaborUnion(): ?LaborUnion
    {
        return $this->laborUnion;
    }

    public function setLaborUnion(?LaborUnion $laborUnion): void
    {
        $this->laborUnion = $laborUnion;
    }

    public function getLeaveType(): LeaveType
    {
        return $this->leaveType;
    }

    public function setLeaveType(LeaveType $leaveType): void
    {
        $this->leaveType = $leaveType;
    }

    public function getMinYears(): int
    {
        return $this->minYears;
    }

    public function setMinYears(int $minYears): void
    {
        $this->minYears = $minYears;
    }

    public function getMaxYears(): ?int
    {
        return $this->maxYears;
    }

    public function setMaxYears(?int $maxYears): void
    {
        $this->maxYears = $maxYears;
    }

    public function getEntitlementDays(): float
    {
        return $this->entitlementDays;
    }

    public function setEntitlementDays(float $entitlementDays): void
    {
        $this->entitlementDays = $entitlementDays;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
