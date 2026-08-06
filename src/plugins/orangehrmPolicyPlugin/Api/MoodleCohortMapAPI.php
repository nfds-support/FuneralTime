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
use OrangeHRM\Entity\MoodleCohortMap;
use OrangeHRM\Policy\Api\Model\MoodleCohortMapModel;
use OrangeHRM\Policy\Dto\MoodleCohortMapSearchFilterParams;
use OrangeHRM\Policy\Traits\Service\PolicyServiceTrait;

class MoodleCohortMapAPI extends Endpoint implements CrudEndpoint
{
    use PolicyServiceTrait;

    public const PARAMETER_JOB_TITLE_ID = 'jobTitleId';
    public const PARAMETER_MOODLE_COHORT_ID = 'moodleCohortId';
    public const PARAMETER_MOODLE_COHORT_NAME = 'moodleCohortName';

    public function getAll(): EndpointResult
    {
        $filterParams = new MoodleCohortMapSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $maps = $this->getPolicyService()->getPolicyDao()->getMoodleCohortMapList($filterParams);
        $count = $this->getPolicyService()->getPolicyDao()->getMoodleCohortMapCount($filterParams);
        return new EndpointCollectionResult(
            MoodleCohortMapModel::class,
            $maps,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            ...$this->getSortingAndPaginationParamsRules(MoodleCohortMapSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function create(): EndpointResult
    {
        $map = new MoodleCohortMap();
        $this->setMapFields($map);
        $this->getPolicyService()->getPolicyDao()->saveMoodleCohortMap($map);
        return new EndpointResourceResult(MoodleCohortMapModel::class, $map);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyRules();
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $map = $this->getPolicyService()->getPolicyDao()->getMoodleCohortMapById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($map, MoodleCohortMap::class);
        return new EndpointResourceResult(MoodleCohortMapModel::class, $map);
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
        $map = $this->getPolicyService()->getPolicyDao()->getMoodleCohortMapById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($map, MoodleCohortMap::class);
        $this->setMapFields($map);
        $this->getPolicyService()->getPolicyDao()->saveMoodleCohortMap($map);
        return new EndpointResourceResult(MoodleCohortMapModel::class, $map);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        $rules = $this->getBodyRules();
        $rules->addParamValidation(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
        return $rules;
    }

    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getPolicyService()->getPolicyDao()->deleteMoodleCohortMaps($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::ARRAY_TYPE))
        );
    }

    private function getBodyRules(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_JOB_TITLE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_MOODLE_COHORT_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_MOODLE_COHORT_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, 255])
                )
            ),
        );
    }

    private function setMapFields(MoodleCohortMap $map): void
    {
        $map->getDecorator()->setJobTitleById(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_JOB_TITLE_ID)
        );
        $map->setMoodleCohortId(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_MOODLE_COHORT_ID)
        );
        $map->setMoodleCohortName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MOODLE_COHORT_NAME
            )
        );
    }
}
