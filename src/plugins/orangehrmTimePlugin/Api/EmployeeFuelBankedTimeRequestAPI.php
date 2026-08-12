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

namespace OrangeHRM\Time\Api;

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\FuelBankedTimeRequest;
use OrangeHRM\Time\Api\Model\FuelBankedTimeRequestModel;
use OrangeHRM\Time\Dto\FuelBankedTimeRequestSearchFilterParams;
use OrangeHRM\Time\Traits\Service\FuelBankedTimeServiceTrait;

class EmployeeFuelBankedTimeRequestAPI extends Endpoint implements CollectionEndpoint
{
    use FuelBankedTimeServiceTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_STATUS = 'status';

    /**
     * @OA\Get(
     *     path="/api/v2/time/employees/fuel-banked-time/requests",
     *     tags={"Time/Fuel Banked Time"},
     *     @OA\Parameter(name="empNumber", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new FuelBankedTimeRequestSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);

        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        if ($empNumber !== null) {
            $filterParams->setEmpNumbers([$empNumber]);
        } else {
            $accessible = $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class);
            $filterParams->setEmpNumbers(empty($accessible) ? [-1] : $accessible);
        }

        $status = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_STATUS
        );
        $filterParams->setStatus($status);

        $requests = $this->getFuelBankedTimeService()->search($filterParams);
        $count = $this->getFuelBankedTimeService()->getCount($filterParams);

        return new EndpointCollectionResult(
            FuelBankedTimeRequestModel::class,
            $requests,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    CommonParams::PARAMETER_EMP_NUMBER,
                    new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[
                        FuelBankedTimeRequest::STATUS_PENDING,
                        FuelBankedTimeRequest::STATUS_APPROVED,
                        FuelBankedTimeRequest::STATUS_REJECTED,
                        FuelBankedTimeRequest::STATUS_CANCELLED,
                    ]])
                )
            ),
            ...$this->getSortingAndPaginationParamsRules(FuelBankedTimeRequestSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
