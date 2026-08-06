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

namespace OrangeHRM\Policy\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Entity\Policy;

/**
 * @OA\Schema(
 *     schema="Policy-PolicyModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="version", type="string"),
 *     @OA\Property(property="summary", type="string"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="documentUrl", type="string"),
 *     @OA\Property(property="moodleCourseUrl", type="string"),
 *     @OA\Property(property="audienceType", type="string"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="effectiveDate", type="string", format="date"),
 *     @OA\Property(property="dueDate", type="string", format="date"),
 *     @OA\Property(property="publishedAt", type="string", format="date-time"),
 *     @OA\Property(property="createdAt", type="string", format="date-time"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time"),
 *     @OA\Property(
 *         property="jobTitles",
 *         type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="title", type="string")
 *         )
 *     ),
 *     @OA\Property(property="acknowledged", type="boolean"),
 *     @OA\Property(property="acknowledgedAt", type="string", format="date-time")
 * )
 */
class PolicyModel implements Normalizable
{
    private Policy $policy;
    private ?bool $acknowledged;
    private ?string $acknowledgedAt;

    public function __construct(Policy $policy, ?bool $acknowledged = null, ?string $acknowledgedAt = null)
    {
        $this->policy = $policy;
        $this->acknowledged = $acknowledged;
        $this->acknowledgedAt = $acknowledgedAt;
    }

    public function toArray(): array
    {
        $jobTitles = [];
        /** @var JobTitle $jobTitle */
        foreach ($this->policy->getJobTitles() as $jobTitle) {
            $jobTitles[] = [
                'id' => $jobTitle->getId(),
                'title' => $jobTitle->getJobTitleName(),
            ];
        }
        $data = [
            'id' => $this->policy->getId(),
            'title' => $this->policy->getTitle(),
            'version' => $this->policy->getVersion(),
            'summary' => $this->policy->getSummary(),
            'content' => $this->policy->getContent(),
            'documentUrl' => $this->policy->getDocumentUrl(),
            'moodleCourseUrl' => $this->policy->getMoodleCourseUrl(),
            'audienceType' => $this->policy->getAudienceType(),
            'status' => $this->policy->getStatus(),
            'effectiveDate' => $this->policy->getEffectiveDate()
                ? $this->policy->getEffectiveDate()->format('Y-m-d')
                : null,
            'dueDate' => $this->policy->getDueDate()
                ? $this->policy->getDueDate()->format('Y-m-d')
                : null,
            'publishedAt' => $this->policy->getPublishedAt()
                ? $this->policy->getPublishedAt()->format('Y-m-d H:i')
                : null,
            'createdAt' => $this->policy->getCreatedAt()->format('Y-m-d H:i'),
            'updatedAt' => $this->policy->getUpdatedAt()
                ? $this->policy->getUpdatedAt()->format('Y-m-d H:i')
                : null,
            'jobTitles' => $jobTitles,
        ];
        if ($this->acknowledged !== null) {
            $data['acknowledged'] = $this->acknowledged;
            $data['acknowledgedAt'] = $this->acknowledgedAt;
        }
        return $data;
    }
}
