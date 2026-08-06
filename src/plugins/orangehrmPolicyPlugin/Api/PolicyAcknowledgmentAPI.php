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

namespace OrangeHRM\Policy\Api;

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
use OrangeHRM\Entity\Policy;
use OrangeHRM\Policy\Api\Model\PolicyAcknowledgmentModel;
use OrangeHRM\Policy\Dto\PolicyAcknowledgmentSearchFilterParams;
use OrangeHRM\Policy\Traits\Service\PolicyServiceTrait;

class PolicyAcknowledgmentAPI extends Endpoint implements CollectionEndpoint
{
    use PolicyServiceTrait;

    public const PARAMETER_POLICY_ID = 'policyId';

    public function getAll(): EndpointResult
    {
        $policyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_POLICY_ID
        );
        $policy = $this->getPolicyService()->getPolicyDao()->getPolicyById($policyId);
        $this->throwRecordNotFoundExceptionIfNotExist($policy, Policy::class);

        $filterParams = new PolicyAcknowledgmentSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setPolicyId($policyId);
        $acks = $this->getPolicyService()->getPolicyDao()->getAcknowledgmentList($filterParams);
        $count = $this->getPolicyService()->getPolicyDao()->getAcknowledgmentCount($filterParams);
        return new EndpointCollectionResult(
            PolicyAcknowledgmentModel::class,
            $acks,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_POLICY_ID, new Rule(Rules::POSITIVE)),
            ...$this->getSortingAndPaginationParamsRules(
                PolicyAcknowledgmentSearchFilterParams::ALLOWED_SORT_FIELDS
            )
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
