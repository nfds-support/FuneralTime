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

namespace OrangeHRM\Union\Api;

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\UnionLeaveRule;
use OrangeHRM\Union\Api\Model\UnionLeaveRuleModel;
use OrangeHRM\Union\Dto\UnionLeaveRuleSearchFilterParams;
use OrangeHRM\Union\Traits\Service\UnionServiceTrait;

class UnionLeaveRuleAPI extends Endpoint implements CrudEndpoint
{
    use UnionServiceTrait;

    public const PARAMETER_UNION_ID = 'unionId';
    public const PARAMETER_LEAVE_TYPE_ID = 'leaveTypeId';
    public const PARAMETER_MIN_YEARS = 'minYears';
    public const PARAMETER_MAX_YEARS = 'maxYears';
    public const PARAMETER_ENTITLEMENT_DAYS = 'entitlementDays';
    public const PARAMETER_NOTE = 'note';

    public function getAll(): EndpointResult
    {
        $filterParams = new UnionLeaveRuleSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setUnionId(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_UNION_ID)
        );
        $filterParams->setLeaveTypeId(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_LEAVE_TYPE_ID)
        );
        $list = $this->getUnionService()->getUnionDao()->getUnionLeaveRuleList($filterParams);
        $count = $this->getUnionService()->getUnionDao()->getUnionLeaveRuleCount($filterParams);
        return new EndpointCollectionResult(
            UnionLeaveRuleModel::class,
            $list,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_UNION_ID, new Rule(Rules::INT_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_LEAVE_TYPE_ID, new Rule(Rules::POSITIVE))
            ),
            ...$this->getSortingAndPaginationParamsRules(UnionLeaveRuleSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function create(): EndpointResult
    {
        $rule = new UnionLeaveRule();
        $this->setFields($rule);
        $this->getUnionService()->getUnionDao()->saveUnionLeaveRule($rule);
        return new EndpointResourceResult(UnionLeaveRuleModel::class, $rule);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyRules();
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $rule = $this->getUnionService()->getUnionDao()->getUnionLeaveRuleById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($rule, UnionLeaveRule::class);
        return new EndpointResourceResult(UnionLeaveRuleModel::class, $rule);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
    }

    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $rule = $this->getUnionService()->getUnionDao()->getUnionLeaveRuleById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($rule, UnionLeaveRule::class);
        $this->setFields($rule);
        $this->getUnionService()->getUnionDao()->saveUnionLeaveRule($rule);
        return new EndpointResourceResult(UnionLeaveRuleModel::class, $rule);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return $this->getBodyRules(true);
    }

    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getUnionService()->getUnionDao()->deleteUnionLeaveRules($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::ARRAY_TYPE))
        );
    }

    private function getBodyRules(bool $withId = false): ParamRuleCollection
    {
        $rules = [
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_UNION_ID, new Rule(Rules::POSITIVE))
            ),
            new ParamRule(self::PARAMETER_LEAVE_TYPE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_MIN_YEARS, new Rule(Rules::INT_VAL)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MAX_YEARS, new Rule(Rules::INT_VAL))
            ),
            new ParamRule(self::PARAMETER_ENTITLEMENT_DAYS, new Rule(Rules::ZERO_OR_POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_NOTE, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 255]))
            ),
        ];
        if ($withId) {
            $rules[] = new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
        }
        return new ParamRuleCollection(...$rules);
    }

    private function setFields(UnionLeaveRule $rule): void
    {
        $rule->getDecorator()->setLaborUnionById(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_UNION_ID)
        );
        $rule->getDecorator()->setLeaveTypeById(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_LEAVE_TYPE_ID)
        );
        $rule->setMinYears(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_MIN_YEARS, 0)
        );
        $rule->setMaxYears(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_MAX_YEARS)
        );
        $rule->setEntitlementDays(
            $this->getRequestParams()->getFloat(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ENTITLEMENT_DAYS)
        );
        $rule->setNote(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NOTE)
        );
    }
}
