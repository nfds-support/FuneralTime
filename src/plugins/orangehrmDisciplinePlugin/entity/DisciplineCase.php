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
use OrangeHRM\Entity\Decorator\DisciplineCaseDecorator;

/**
 * @method DisciplineCaseDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_discipline_case")
 * @ORM\Entity
 */
class DisciplineCase
{
    use DecoratorTrait;

    public const TYPE_COMPLAINT = 'COMPLAINT';
    public const TYPE_DISCIPLINARY = 'DISCIPLINARY';

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_CLOSED = 'CLOSED';

    public const SEVERITY_LOW = 'LOW';
    public const SEVERITY_MEDIUM = 'MEDIUM';
    public const SEVERITY_HIGH = 'HIGH';
    public const SEVERITY_CRITICAL = 'CRITICAL';

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
     * @ORM\JoinColumn(name="reported_by", referencedColumnName="emp_number", nullable=true)
     */
    private ?Employee $reportedBy = null;

    /**
     * @var string
     *
     * @ORM\Column(name="case_type", type="string", length=40)
     */
    private string $caseType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="category", type="string", length=100, nullable=true)
     */
    private ?string $category = null;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    private string $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="incident_date", type="date", nullable=true)
     */
    private ?DateTime $incidentDate = null;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=40)
     */
    private string $status = self::STATUS_OPEN;

    /**
     * @var string|null
     *
     * @ORM\Column(name="severity", type="string", length=40, nullable=true)
     */
    private ?string $severity = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="action_taken", type="text", nullable=true)
     */
    private ?string $actionTaken = null;

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

    public function getReportedBy(): ?Employee
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?Employee $reportedBy): void
    {
        $this->reportedBy = $reportedBy;
    }

    public function getCaseType(): string
    {
        return $this->caseType;
    }

    public function setCaseType(string $caseType): void
    {
        $this->caseType = $caseType;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getIncidentDate(): ?DateTime
    {
        return $this->incidentDate;
    }

    public function setIncidentDate(?DateTime $incidentDate): void
    {
        $this->incidentDate = $incidentDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(?string $severity): void
    {
        $this->severity = $severity;
    }

    public function getActionTaken(): ?string
    {
        return $this->actionTaken;
    }

    public function setActionTaken(?string $actionTaken): void
    {
        $this->actionTaken = $actionTaken;
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
