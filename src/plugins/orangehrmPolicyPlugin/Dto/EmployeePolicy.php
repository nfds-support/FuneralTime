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

namespace OrangeHRM\Policy\Dto;

use OrangeHRM\Entity\Policy;

class EmployeePolicy
{
    private Policy $policy;
    private bool $acknowledged;
    private ?string $acknowledgedAt;

    public function __construct(Policy $policy, bool $acknowledged, ?string $acknowledgedAt = null)
    {
        $this->policy = $policy;
        $this->acknowledged = $acknowledged;
        $this->acknowledgedAt = $acknowledgedAt;
    }

    public function getPolicy(): Policy
    {
        return $this->policy;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged;
    }

    public function getAcknowledgedAt(): ?string
    {
        return $this->acknowledgedAt;
    }
}
