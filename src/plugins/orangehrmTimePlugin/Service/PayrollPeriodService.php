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

namespace OrangeHRM\Time\Service;

use DateTime;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Time\Dao\PayrollPeriodDao;
use OrangeHRM\Time\Dto\PayrollPeriodSearchFilterParams;

class PayrollPeriodService
{
    /**
     * @var PayrollPeriodDao|null
     */
    private ?PayrollPeriodDao $payrollPeriodDao = null;

    /**
     * @return PayrollPeriodDao
     */
    public function getPayrollPeriodDao(): PayrollPeriodDao
    {
        if (!$this->payrollPeriodDao instanceof PayrollPeriodDao) {
            $this->payrollPeriodDao = new PayrollPeriodDao();
        }
        return $this->payrollPeriodDao;
    }

    /**
     * @param PayrollPeriodDao $payrollPeriodDao
     */
    public function setPayrollPeriodDao(PayrollPeriodDao $payrollPeriodDao): void
    {
        $this->payrollPeriodDao = $payrollPeriodDao;
    }

    /**
     * @param int $id
     * @return PayrollPeriod|null
     */
    public function getById(int $id): ?PayrollPeriod
    {
        return $this->getPayrollPeriodDao()->getById($id);
    }

    /**
     * @param PayrollPeriodSearchFilterParams $filterParams
     * @return PayrollPeriod[]
     */
    public function search(PayrollPeriodSearchFilterParams $filterParams): array
    {
        return $this->getPayrollPeriodDao()->search($filterParams);
    }

    /**
     * @param PayrollPeriodSearchFilterParams $filterParams
     * @return int
     */
    public function getCount(PayrollPeriodSearchFilterParams $filterParams): int
    {
        return $this->getPayrollPeriodDao()->getCount($filterParams);
    }

    /**
     * @param DateTime $fromDate
     * @param DateTime $toDate
     * @param int|null $periodNumber
     * @return PayrollPeriod[]
     */
    public function getByDateRange(DateTime $fromDate, DateTime $toDate, ?int $periodNumber = null): array
    {
        return $this->getPayrollPeriodDao()->getByDateRange($fromDate, $toDate, $periodNumber);
    }

    /**
     * @param PayrollPeriod $payrollPeriod
     * @return PayrollPeriod
     */
    public function save(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        return $this->getPayrollPeriodDao()->save($payrollPeriod);
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteByIds(array $ids): int
    {
        return $this->getPayrollPeriodDao()->deleteByIds($ids);
    }
}
