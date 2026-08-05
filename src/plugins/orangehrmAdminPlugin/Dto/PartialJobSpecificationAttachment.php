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

namespace OrangeHRM\Admin\Dto;

use OrangeHRM\Entity\JobSpecificationAttachment;

class PartialJobSpecificationAttachment
{
    private ?int $id;

    private ?string $fileName;

    private ?string $fileType;

    private ?int $fileSize;

    private ?int $jobTitleId;

    /**
     * @param int|null $id
     * @param string|null $fileName
     * @param string|null $fileType
     * @param int|null $fileSize
     * @param int|null $jobTitleId
     */
    public function __construct(?int $id, ?string $fileName, ?string $fileType, ?int $fileSize, ?int $jobTitleId)
    {
        $this->id = $id;
        $this->fileName = $fileName;
        $this->fileType = $fileType;
        $this->fileSize = $fileSize;
        $this->jobTitleId = $jobTitleId;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @return string|null
     */
    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    /**
     * @return int|null
     */
    public function getJobTitleId(): ?int
    {
        return $this->jobTitleId;
    }

    /**
     * Build metadata-only DTO from an already-hydrated attachment without reading file_content.
     *
     * @param JobSpecificationAttachment|null $attachment
     * @return self|null
     */
    public static function createFromAttachment(?JobSpecificationAttachment $attachment): ?self
    {
        if (!$attachment instanceof JobSpecificationAttachment) {
            return null;
        }

        return new self(
            $attachment->getId(),
            $attachment->getFileName(),
            $attachment->getFileType(),
            $attachment->getFileSize(),
            $attachment->getJobTitle() ? $attachment->getJobTitle()->getId() : null
        );
    }
}
