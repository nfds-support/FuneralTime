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
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\EmployeeUnion;
use OrangeHRM\Union\Api\Model\EmployeeUnionModel;
use OrangeHRM\Union\Dto\EmployeeUnionSearchFilterParams;
use OrangeHRM\Union\Traits\Service\UnionServiceTrait;

class EmployeeUnionAPI extends Endpoint implements CrudEndpoint
{
    use UnionServiceTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_UNION_ID = 'unionId';
    public const PARAMETER_SENIORITY_DATE = 'seniorityDate';
    public const PARAMETER_SENIORITY_RANK = 'seniorityRank';
    public const PARAMETER_PRIMARY = 'primary';
    public const PARAMETER_START_DATE = 'startDate';
    public const PARAMETER_END_DATE = 'endDate';

    public function getAll(): EndpointResult
    {
        $filterParams = new EmployeeUnionSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );
        if ($empNumber !== null) {
            if ($empNumber !== $this->getAuthUser()->getEmpNumber()
                && !$this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)) {
                throw $this->getForbiddenException();
            }
            $filterParams->setEmpNumber($empNumber);
        }
        $filterParams->setUnionId(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_UNION_ID)
        );
        $list = $this->getUnionService()->getUnionDao()->getEmployeeUnionList($filterParams);
        $count = $this->getUnionService()->getUnionDao()->getEmployeeUnionCount($filterParams);
        return new EndpointCollectionResult(
            EmployeeUnionModel::class,
            $list,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_UNION_ID, new Rule(Rules::POSITIVE))
            ),
            ...$this->getSortingAndPaginationParamsRules(EmployeeUnionSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function create(): EndpointResult
    {
        $assignment = new EmployeeUnion();
        $this->setFields($assignment);
        $this->getUnionService()->getUnionDao()->saveEmployeeUnion($assignment);
        return new EndpointResourceResult(EmployeeUnionModel::class, $assignment);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyRules();
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $assignment = $this->getUnionService()->getUnionDao()->getEmployeeUnionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($assignment, EmployeeUnion::class);
        return new EndpointResourceResult(EmployeeUnionModel::class, $assignment);
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
        $assignment = $this->getUnionService()->getUnionDao()->getEmployeeUnionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($assignment, EmployeeUnion::class);
        $this->setFields($assignment);
        $this->getUnionService()->getUnionDao()->saveEmployeeUnion($assignment);
        return new EndpointResourceResult(EmployeeUnionModel::class, $assignment);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return $this->getBodyRules(true);
    }

    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getUnionService()->getUnionDao()->deleteEmployeeUnions($ids);
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
            new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)),
            new ParamRule(self::PARAMETER_UNION_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_SENIORITY_DATE, new Rule(Rules::API_DATE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SENIORITY_RANK, new Rule(Rules::INT_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PRIMARY, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_START_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_END_DATE, new Rule(Rules::API_DATE))
            ),
        ];
        if ($withId) {
            $rules[] = new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
        }
        return new ParamRuleCollection(...$rules);
    }

    private function setFields(EmployeeUnion $assignment): void
    {
        $assignment->getDecorator()->setEmployeeByEmpNumber(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_EMP_NUMBER)
        );
        $assignment->getDecorator()->setLaborUnionById(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_UNION_ID)
        );
        $assignment->setSeniorityDate(
            $this->getRequestParams()->getDateTime(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SENIORITY_DATE)
        );
        $assignment->setSeniorityRank(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SENIORITY_RANK)
        );
        $assignment->setPrimary(
            $this->getRequestParams()->getBoolean(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PRIMARY, true)
        );
        $assignment->setStartDate(
            $this->getRequestParams()->getDateTimeOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_START_DATE)
        );
        $assignment->setEndDate(
            $this->getRequestParams()->getDateTimeOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_END_DATE)
        );
    }
}
