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

namespace OrangeHRM\Time\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\FuelBankedTimeRequest;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Time\Dto\FuelBankedTimeRequestSearchFilterParams;

class FuelBankedTimeDao extends BaseDao
{
    public function save(FuelBankedTimeRequest $request): FuelBankedTimeRequest
    {
        $this->persist($request);
        return $request;
    }

    public function getById(int $id): ?FuelBankedTimeRequest
    {
        return $this->getRepository(FuelBankedTimeRequest::class)->find($id);
    }

    /**
     * @return FuelBankedTimeRequest[]
     */
    public function search(FuelBankedTimeRequestSearchFilterParams $filterParams): array
    {
        return $this->getSearchPaginator($filterParams)->getQuery()->execute();
    }

    public function getCount(FuelBankedTimeRequestSearchFilterParams $filterParams): int
    {
        return $this->getSearchPaginator($filterParams)->count();
    }

    protected function getSearchPaginator(FuelBankedTimeRequestSearchFilterParams $filterParams): Paginator
    {
        $q = $this->createQueryBuilder(FuelBankedTimeRequest::class, 'request');
        $q->leftJoin('request.employee', 'employee');
        $this->setSortingAndPaginationParams($q, $filterParams);

        if (!empty($filterParams->getEmpNumbers())) {
            $q->andWhere($q->expr()->in('employee.empNumber', ':empNumbers'))
                ->setParameter('empNumbers', $filterParams->getEmpNumbers());
        }
        if ($filterParams->getStatus() !== null && $filterParams->getStatus() !== '') {
            $q->andWhere('request.status = :status')
                ->setParameter('status', $filterParams->getStatus());
        }

        return $this->getPaginator($q);
    }
}
