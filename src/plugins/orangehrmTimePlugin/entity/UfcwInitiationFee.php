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
 * Tracks UFCW initiation-fee balances per bargaining-unit employee.
 *
 * @ORM\Table(name="ohrm_ufcw_initiation_fee", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="ufcw_initiation_fee_emp_unique", columns={"emp_number"})
 * })
 * @ORM\Entity
 */
class UfcwInitiationFee
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
     * @var Employee
     *
     * @ORM\OneToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="emp_number", referencedColumnName="emp_number", nullable=false, onDelete="CASCADE")
     */
    private Employee $employee;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_required", type="decimal", precision=12, scale=2)
     */
    private string $feeRequired = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="amount_paid", type="decimal", precision=12, scale=2)
     */
    private string $amountPaid = '0.00';

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Employee
     */
    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    /**
     * @param Employee $employee
     */
    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    /**
     * @return string
     */
    public function getFeeRequired(): string
    {
        return $this->feeRequired;
    }

    /**
     * @param string $feeRequired
     */
    public function setFeeRequired(string $feeRequired): void
    {
        $this->feeRequired = $feeRequired;
    }

    /**
     * @return string
     */
    public function getAmountPaid(): string
    {
        return $this->amountPaid;
    }

    /**
     * @param string $amountPaid
     */
    public function setAmountPaid(string $amountPaid): void
    {
        $this->amountPaid = $amountPaid;
    }

    /**
     * @return float
     */
    public function getRemainingBalance(): float
    {
        return max(0.0, (float) $this->feeRequired - (float) $this->amountPaid);
    }
}
