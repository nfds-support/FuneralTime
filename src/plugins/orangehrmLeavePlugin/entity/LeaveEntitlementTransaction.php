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
use OrangeHRM\Entity\Decorator\LeaveEntitlementTransactionDecorator;

/**
 * @method LeaveEntitlementTransactionDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_leave_entitlement_transaction")
 * @ORM\Entity
 */
class LeaveEntitlementTransaction
{
    use DecoratorTrait;

    public const TYPE_ADDITION = 'addition';
    public const TYPE_DEDUCTION = 'deduction';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_USAGE = 'usage';

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
     * @var LeaveType
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\LeaveType")
     * @ORM\JoinColumn(name="leave_type_id", referencedColumnName="id")
     */
    private LeaveType $leaveType;

    /**
     * @var int|null
     *
     * @ORM\Column(name="entitlement_id", type="integer", nullable=true)
     */
    private ?int $entitlementId = null;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_type", type="string", length=20)
     */
    private string $transactionType;

    /**
     * @var string
     *
     * @ORM\Column(name="days", type="decimal", precision=8, scale=4)
     */
    private string $days;

    /**
     * @var string|null
     *
     * @ORM\Column(name="balance_after", type="decimal", precision=8, scale=4, nullable=true)
     */
    private ?string $balanceAfter = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="string", length=255, nullable=true)
     */
    private ?string $note = null;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     */
    private ?User $createdBy = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetimetz")
     */
    private DateTime $createdAt;

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
     * @return LeaveType
     */
    public function getLeaveType(): LeaveType
    {
        return $this->leaveType;
    }

    /**
     * @param LeaveType $leaveType
     */
    public function setLeaveType(LeaveType $leaveType): void
    {
        $this->leaveType = $leaveType;
    }

    /**
     * @return int|null
     */
    public function getEntitlementId(): ?int
    {
        return $this->entitlementId;
    }

    /**
     * @param int|null $entitlementId
     */
    public function setEntitlementId(?int $entitlementId): void
    {
        $this->entitlementId = $entitlementId;
    }

    /**
     * @return string
     */
    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    /**
     * @param string $transactionType
     */
    public function setTransactionType(string $transactionType): void
    {
        $this->transactionType = $transactionType;
    }

    /**
     * @return string
     */
    public function getDays(): string
    {
        return $this->days;
    }

    /**
     * @param string|float $days
     */
    public function setDays($days): void
    {
        $this->days = (string) $days;
    }

    /**
     * @return string|null
     */
    public function getBalanceAfter(): ?string
    {
        return $this->balanceAfter;
    }

    /**
     * @param string|float|null $balanceAfter
     */
    public function setBalanceAfter($balanceAfter): void
    {
        $this->balanceAfter = is_null($balanceAfter) ? null : (string) $balanceAfter;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     */
    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     */
    public function setCreatedBy(?User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
