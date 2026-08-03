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
use OrangeHRM\Entity\Decorator\DecoratorTrait;
use OrangeHRM\Entity\Decorator\EmployeeUnionDecorator;

/**
 * @method EmployeeUnionDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_employee_union")
 * @ORM\Entity
 */
class EmployeeUnion
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
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="emp_number", referencedColumnName="emp_number")
     */
    private Employee $employee;

    /**
     * @var LaborUnion
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\LaborUnion")
     * @ORM\JoinColumn(name="union_id", referencedColumnName="id")
     */
    private LaborUnion $laborUnion;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="seniority_date", type="date")
     */
    private DateTime $seniorityDate;

    /**
     * @var int|null
     *
     * @ORM\Column(name="seniority_rank", type="integer", nullable=true)
     */
    private ?int $seniorityRank = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_primary", type="boolean", options={"default" : 1})
     */
    private bool $primary = true;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    private ?DateTime $startDate = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private ?DateTime $endDate = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getLaborUnion(): LaborUnion
    {
        return $this->laborUnion;
    }

    public function setLaborUnion(LaborUnion $laborUnion): void
    {
        $this->laborUnion = $laborUnion;
    }

    public function getSeniorityDate(): DateTime
    {
        return $this->seniorityDate;
    }

    public function setSeniorityDate(DateTime $seniorityDate): void
    {
        $this->seniorityDate = $seniorityDate;
    }

    public function getSeniorityRank(): ?int
    {
        return $this->seniorityRank;
    }

    public function setSeniorityRank(?int $seniorityRank): void
    {
        $this->seniorityRank = $seniorityRank;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): void
    {
        $this->primary = $primary;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }
}
