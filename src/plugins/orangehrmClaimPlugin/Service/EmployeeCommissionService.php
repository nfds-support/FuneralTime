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

namespace OrangeHRM\Claim\Service;

use DateTime;
use OrangeHRM\Claim\Dao\ClaimDao;
use OrangeHRM\Claim\Dto\EmployeeCommissionSearchFilterParams;
use OrangeHRM\Entity\EmployeeCommission;

class EmployeeCommissionService
{
    /**
     * @var ClaimDao|null
     */
    protected ?ClaimDao $claimDao = null;

    /**
     * @return ClaimDao
     */
    public function getClaimDao(): ClaimDao
    {
        return $this->claimDao ??= new ClaimDao();
    }

    /**
     * @param ClaimDao $claimDao
     */
    public function setClaimDao(ClaimDao $claimDao): void
    {
        $this->claimDao = $claimDao;
    }

    /**
     * @param EmployeeCommission $commission
     * @return EmployeeCommission
     */
    public function saveEmployeeCommission(EmployeeCommission $commission): EmployeeCommission
    {
        return $this->getClaimDao()->saveEmployeeCommission($commission);
    }

    /**
     * @param EmployeeCommission $commission
     * @return EmployeeCommission
     */
    public function createEmployeeCommission(EmployeeCommission $commission): EmployeeCommission
    {
        $commission->setCreatedAt(new DateTime());
        return $this->getClaimDao()->saveEmployeeCommission($commission);
    }

    /**
     * @param int $id
     * @return EmployeeCommission|null
     */
    public function getEmployeeCommissionById(int $id): ?EmployeeCommission
    {
        return $this->getClaimDao()->getEmployeeCommissionById($id);
    }

    /**
     * @param EmployeeCommissionSearchFilterParams $filterParams
     * @return EmployeeCommission[]
     */
    public function getEmployeeCommissionList(EmployeeCommissionSearchFilterParams $filterParams): array
    {
        return $this->getClaimDao()->getEmployeeCommissionList($filterParams);
    }

    /**
     * @param EmployeeCommissionSearchFilterParams $filterParams
     * @return int
     */
    public function getEmployeeCommissionCount(EmployeeCommissionSearchFilterParams $filterParams): int
    {
        return $this->getClaimDao()->getEmployeeCommissionCount($filterParams);
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteEmployeeCommissionsByIds(array $ids): int
    {
        return $this->getClaimDao()->deleteEmployeeCommissionsByIds($ids);
    }

    /**
     * @param int $empNumber
     * @param int $year
     * @param int $month
     * @return float
     */
    public function getCommissionSumForMonth(int $empNumber, int $year, int $month): float
    {
        return $this->getClaimDao()->getCommissionSumForMonth($empNumber, $year, $month);
    }
}
