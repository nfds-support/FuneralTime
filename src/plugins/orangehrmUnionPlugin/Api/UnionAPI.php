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
use OrangeHRM\Entity\LaborUnion;
use OrangeHRM\Union\Api\Model\UnionModel;
use OrangeHRM\Union\Dto\UnionSearchFilterParams;
use OrangeHRM\Union\Traits\Service\UnionServiceTrait;

class UnionAPI extends Endpoint implements CrudEndpoint
{
    use UnionServiceTrait;

    public const PARAMETER_NAME = 'name';
    public const PARAMETER_DESCRIPTION = 'description';
    public const PARAMETER_ACTIVE = 'active';

    public function getAll(): EndpointResult
    {
        $filterParams = new UnionSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setName(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_NAME)
        );
        $filterParams->setActiveOnly(
            $this->getRequestParams()->getBooleanOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_ACTIVE)
        );
        $list = $this->getUnionService()->getUnionDao()->getUnionList($filterParams);
        $count = $this->getUnionService()->getUnionDao()->getUnionCount($filterParams);
        return new EndpointCollectionResult(
            UnionModel::class,
            $list,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_NAME, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_ACTIVE, new Rule(Rules::BOOL_VAL))
            ),
            ...$this->getSortingAndPaginationParamsRules(UnionSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function create(): EndpointResult
    {
        $union = new LaborUnion();
        $this->setUnionFields($union);
        $this->getUnionService()->getUnionDao()->saveUnion($union);
        return new EndpointResourceResult(UnionModel::class, $union);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyRules();
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $union = $this->getUnionService()->getUnionDao()->getUnionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($union, LaborUnion::class);
        return new EndpointResourceResult(UnionModel::class, $union);
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
        $union = $this->getUnionService()->getUnionDao()->getUnionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($union, LaborUnion::class);
        $this->setUnionFields($union);
        $this->getUnionService()->getUnionDao()->saveUnion($union);
        return new EndpointResourceResult(UnionModel::class, $union);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return $this->getBodyRules(true);
    }

    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getUnionService()->getUnionDao()->deleteUnions($ids);
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
            new ParamRule(self::PARAMETER_NAME, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 100])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_DESCRIPTION, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_ACTIVE, new Rule(Rules::BOOL_VAL))
            ),
        ];
        if ($withId) {
            $rules[] = new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
        }
        return new ParamRuleCollection(...$rules);
    }

    private function setUnionFields(LaborUnion $union): void
    {
        $union->setName(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME)
        );
        $union->setDescription(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DESCRIPTION)
        );
        $union->setActive(
            $this->getRequestParams()->getBoolean(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ACTIVE, true)
        );
    }
}
