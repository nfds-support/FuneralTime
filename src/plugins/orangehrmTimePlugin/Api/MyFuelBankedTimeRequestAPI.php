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

use Exception;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Entity\FuelBankedTimeRequest;
use OrangeHRM\Time\Api\Model\FuelBankedTimeRequestModel;
use OrangeHRM\Time\Dto\FuelBankedTimeRequestSearchFilterParams;
use OrangeHRM\Time\Traits\Service\FuelBankedTimeServiceTrait;

class MyFuelBankedTimeRequestAPI extends Endpoint implements CollectionEndpoint
{
    use AuthUserTrait;
    use FuelBankedTimeServiceTrait;

    public const PARAMETER_AMOUNT = 'amount';
    public const PARAMETER_COMMENT = 'comment';
    public const PARAMETER_STATUS = 'status';

    /**
     * @OA\Get(
     *     path="/api/v2/time/fuel-banked-time/requests",
     *     tags={"Time/Fuel Banked Time"},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new FuelBankedTimeRequestSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setEmpNumbers([$this->getAuthUser()->getEmpNumber()]);
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

    /**
     * @OA\Post(
     *     path="/api/v2/time/fuel-banked-time/requests",
     *     tags={"Time/Fuel Banked Time"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="comment", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function create(): EndpointResult
    {
        $amount = $this->getRequestParams()->getFloat(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_AMOUNT);
        $comment = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_COMMENT
        );

        try {
            $request = $this->getFuelBankedTimeService()->createRequest(
                $this->getAuthUser()->getEmpNumber(),
                $amount,
                $comment
            );
        } catch (Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }

        return new EndpointResourceResult(FuelBankedTimeRequestModel::class, $request);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_AMOUNT,
                new Rule(Rules::NUMBER),
                new Rule(Rules::POSITIVE)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COMMENT,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, 255])
                ),
                true
            )
        );
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
