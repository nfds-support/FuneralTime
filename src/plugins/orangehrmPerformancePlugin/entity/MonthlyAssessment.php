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
use OrangeHRM\Entity\Decorator\MonthlyAssessmentDecorator;

/**
 * @method MonthlyAssessmentDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_monthly_assessment")
 * @ORM\Entity
 */
class MonthlyAssessment
{
    use DecoratorTrait;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_AWAITING_MANAGER = 'AWAITING_MANAGER';
    public const STATUS_AWAITING_EMPLOYEE = 'AWAITING_EMPLOYEE';
    public const STATUS_COMPLETED = 'COMPLETED';

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
     * @var Employee|null
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="manager_emp_number", referencedColumnName="emp_number", nullable=true)
     */
    private ?Employee $manager = null;

    /**
     * @var int
     *
     * @ORM\Column(name="period_year", type="integer")
     */
    private int $periodYear;

    /**
     * @var int
     *
     * @ORM\Column(name="period_month", type="integer")
     */
    private int $periodMonth;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=40)
     */
    private string $status = self::STATUS_DRAFT;

    /**
     * @var int|null
     *
     * @ORM\Column(name="employee_overall_rating", type="integer", nullable=true)
     */
    private ?int $employeeOverallRating = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="employee_engagement_rating", type="integer", nullable=true)
     */
    private ?int $employeeEngagementRating = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="employee_accomplishments", type="text", nullable=true)
     */
    private ?string $employeeAccomplishments = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="employee_improvements", type="text", nullable=true)
     */
    private ?string $employeeImprovements = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="employee_goals", type="text", nullable=true)
     */
    private ?string $employeeGoals = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="employee_support_needed", type="text", nullable=true)
     */
    private ?string $employeeSupportNeeded = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="employee_submitted_at", type="datetime", nullable=true)
     */
    private ?DateTime $employeeSubmittedAt = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="manager_overall_rating", type="integer", nullable=true)
     */
    private ?int $managerOverallRating = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_strengths", type="text", nullable=true)
     */
    private ?string $managerStrengths = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_improvements", type="text", nullable=true)
     */
    private ?string $managerImprovements = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_goals_support", type="text", nullable=true)
     */
    private ?string $managerGoalsSupport = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_follow_up_notes", type="text", nullable=true)
     */
    private ?string $managerFollowUpNotes = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="manager_submitted_at", type="datetime", nullable=true)
     */
    private ?DateTime $managerSubmittedAt = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private ?DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

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

    public function getManager(): ?Employee
    {
        return $this->manager;
    }

    public function setManager(?Employee $manager): void
    {
        $this->manager = $manager;
    }

    public function getPeriodYear(): int
    {
        return $this->periodYear;
    }

    public function setPeriodYear(int $periodYear): void
    {
        $this->periodYear = $periodYear;
    }

    public function getPeriodMonth(): int
    {
        return $this->periodMonth;
    }

    public function setPeriodMonth(int $periodMonth): void
    {
        $this->periodMonth = $periodMonth;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getEmployeeOverallRating(): ?int
    {
        return $this->employeeOverallRating;
    }

    public function setEmployeeOverallRating(?int $employeeOverallRating): void
    {
        $this->employeeOverallRating = $employeeOverallRating;
    }

    public function getEmployeeEngagementRating(): ?int
    {
        return $this->employeeEngagementRating;
    }

    public function setEmployeeEngagementRating(?int $employeeEngagementRating): void
    {
        $this->employeeEngagementRating = $employeeEngagementRating;
    }

    public function getEmployeeAccomplishments(): ?string
    {
        return $this->employeeAccomplishments;
    }

    public function setEmployeeAccomplishments(?string $employeeAccomplishments): void
    {
        $this->employeeAccomplishments = $employeeAccomplishments;
    }

    public function getEmployeeImprovements(): ?string
    {
        return $this->employeeImprovements;
    }

    public function setEmployeeImprovements(?string $employeeImprovements): void
    {
        $this->employeeImprovements = $employeeImprovements;
    }

    public function getEmployeeGoals(): ?string
    {
        return $this->employeeGoals;
    }

    public function setEmployeeGoals(?string $employeeGoals): void
    {
        $this->employeeGoals = $employeeGoals;
    }

    public function getEmployeeSupportNeeded(): ?string
    {
        return $this->employeeSupportNeeded;
    }

    public function setEmployeeSupportNeeded(?string $employeeSupportNeeded): void
    {
        $this->employeeSupportNeeded = $employeeSupportNeeded;
    }

    public function getEmployeeSubmittedAt(): ?DateTime
    {
        return $this->employeeSubmittedAt;
    }

    public function setEmployeeSubmittedAt(?DateTime $employeeSubmittedAt): void
    {
        $this->employeeSubmittedAt = $employeeSubmittedAt;
    }

    public function getManagerOverallRating(): ?int
    {
        return $this->managerOverallRating;
    }

    public function setManagerOverallRating(?int $managerOverallRating): void
    {
        $this->managerOverallRating = $managerOverallRating;
    }

    public function getManagerStrengths(): ?string
    {
        return $this->managerStrengths;
    }

    public function setManagerStrengths(?string $managerStrengths): void
    {
        $this->managerStrengths = $managerStrengths;
    }

    public function getManagerImprovements(): ?string
    {
        return $this->managerImprovements;
    }

    public function setManagerImprovements(?string $managerImprovements): void
    {
        $this->managerImprovements = $managerImprovements;
    }

    public function getManagerGoalsSupport(): ?string
    {
        return $this->managerGoalsSupport;
    }

    public function setManagerGoalsSupport(?string $managerGoalsSupport): void
    {
        $this->managerGoalsSupport = $managerGoalsSupport;
    }

    public function getManagerFollowUpNotes(): ?string
    {
        return $this->managerFollowUpNotes;
    }

    public function setManagerFollowUpNotes(?string $managerFollowUpNotes): void
    {
        $this->managerFollowUpNotes = $managerFollowUpNotes;
    }

    public function getManagerSubmittedAt(): ?DateTime
    {
        return $this->managerSubmittedAt;
    }

    public function setManagerSubmittedAt(?DateTime $managerSubmittedAt): void
    {
        $this->managerSubmittedAt = $managerSubmittedAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
