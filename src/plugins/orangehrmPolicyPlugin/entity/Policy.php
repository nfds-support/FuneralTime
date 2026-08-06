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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OrangeHRM\Entity\Decorator\DecoratorTrait;
use OrangeHRM\Entity\Decorator\PolicyDecorator;

/**
 * @method PolicyDecorator getDecorator()
 *
 * @ORM\Table(name="ohrm_policy")
 * @ORM\Entity
 */
class Policy
{
    use DecoratorTrait;

    public const AUDIENCE_ALL = 'ALL';
    public const AUDIENCE_JOB_TITLE = 'JOB_TITLE';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_ARCHIVED = 'ARCHIVED';

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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private string $title;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=40)
     */
    private string $version = '1.0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="summary", type="text", nullable=true)
     */
    private ?string $summary = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private ?string $content = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="document_url", type="string", length=512, nullable=true)
     */
    private ?string $documentUrl = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="moodle_course_url", type="string", length=512, nullable=true)
     */
    private ?string $moodleCourseUrl = null;

    /**
     * @var string
     *
     * @ORM\Column(name="audience_type", type="string", length=40)
     */
    private string $audienceType = self::AUDIENCE_ALL;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=40)
     */
    private string $status = self::STATUS_DRAFT;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="effective_date", type="date", nullable=true)
     */
    private ?DateTime $effectiveDate = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="due_date", type="date", nullable=true)
     */
    private ?DateTime $dueDate = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="published_at", type="datetime", nullable=true)
     */
    private ?DateTime $publishedAt = null;

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

    /**
     * @var Employee|null
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="emp_number", nullable=true)
     */
    private ?Employee $createdBy = null;

    /**
     * @var Collection|JobTitle[]
     *
     * @ORM\ManyToMany(targetEntity="OrangeHRM\Entity\JobTitle")
     * @ORM\JoinTable(
     *     name="ohrm_policy_job_title",
     *     joinColumns={@ORM\JoinColumn(name="policy_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="job_title_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $jobTitles;

    /**
     * @var Collection|PolicyAcknowledgment[]
     *
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\PolicyAcknowledgment", mappedBy="policy", cascade={"remove"})
     */
    private $acknowledgments;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->jobTitles = new ArrayCollection();
        $this->acknowledgments = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getDocumentUrl(): ?string
    {
        return $this->documentUrl;
    }

    public function setDocumentUrl(?string $documentUrl): void
    {
        $this->documentUrl = $documentUrl;
    }

    public function getMoodleCourseUrl(): ?string
    {
        return $this->moodleCourseUrl;
    }

    public function setMoodleCourseUrl(?string $moodleCourseUrl): void
    {
        $this->moodleCourseUrl = $moodleCourseUrl;
    }

    public function getAudienceType(): string
    {
        return $this->audienceType;
    }

    public function setAudienceType(string $audienceType): void
    {
        $this->audienceType = $audienceType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getEffectiveDate(): ?DateTime
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(?DateTime $effectiveDate): void
    {
        $this->effectiveDate = $effectiveDate;
    }

    public function getDueDate(): ?DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function getPublishedAt(): ?DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTime $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
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

    public function getCreatedBy(): ?Employee
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Employee $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return Collection|JobTitle[]
     */
    public function getJobTitles()
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
     * @return Collection|PolicyAcknowledgment[]
     */
    public function getAcknowledgments()
    {
        return $this->acknowledgments;
    }
}
