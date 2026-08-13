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

use DateTime;
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
use OrangeHRM\Core\Traits\HtmlSanitizerTrait;
use OrangeHRM\Entity\Policy;
use OrangeHRM\Policy\Api\Model\PolicyModel;
use OrangeHRM\Policy\Dto\PolicySearchFilterParams;
use OrangeHRM\Policy\Traits\Service\PolicyServiceTrait;

class PolicyAPI extends Endpoint implements CrudEndpoint
{
    use PolicyServiceTrait;
    use AuthUserTrait;
    use HtmlSanitizerTrait;

    public const PARAMETER_TITLE = 'title';
    public const PARAMETER_VERSION = 'version';
    public const PARAMETER_SUMMARY = 'summary';
    public const PARAMETER_CONTENT = 'content';
    public const PARAMETER_DOCUMENT_URL = 'documentUrl';
    public const PARAMETER_MOODLE_COURSE_URL = 'moodleCourseUrl';
    public const PARAMETER_AUDIENCE_TYPE = 'audienceType';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_EFFECTIVE_DATE = 'effectiveDate';
    public const PARAMETER_DUE_DATE = 'dueDate';
    public const PARAMETER_JOB_TITLE_IDS = 'jobTitleIds';

    public function getAll(): EndpointResult
    {
        $filterParams = new PolicySearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setStatus(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_STATUS)
        );
        $filterParams->setAudienceType(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_AUDIENCE_TYPE)
        );
        $policies = $this->getPolicyService()->getPolicyDao()->getPolicyList($filterParams);
        $count = $this->getPolicyService()->getPolicyDao()->getPolicyCount($filterParams);
        return new EndpointCollectionResult(
            PolicyModel::class,
            $policies,
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
                        Policy::STATUS_DRAFT,
                        Policy::STATUS_PUBLISHED,
                        Policy::STATUS_ARCHIVED,
                    ]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_AUDIENCE_TYPE,
                    new Rule(Rules::IN, [[Policy::AUDIENCE_ALL, Policy::AUDIENCE_JOB_TITLE]])
                )
            ),
            ...$this->getSortingAndPaginationParamsRules(PolicySearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function create(): EndpointResult
    {
        $policy = new Policy();
        $this->setPolicyFields($policy, true);
        $policy->getDecorator()->setCreatedByEmpNumber($this->getAuthUser()->getEmpNumber());
        $this->getPolicyService()->getPolicyDao()->savePolicy($policy);
        return new EndpointResourceResult(PolicyModel::class, $policy);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyRules();
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $policy = $this->getPolicyService()->getPolicyDao()->getPolicyById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($policy, Policy::class);
        return new EndpointResourceResult(PolicyModel::class, $policy);
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
        $policy = $this->getPolicyService()->getPolicyDao()->getPolicyById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($policy, Policy::class);
        $this->setPolicyFields($policy, false);
        $policy->setUpdatedAt(new DateTime());
        $this->getPolicyService()->getPolicyDao()->savePolicy($policy);
        return new EndpointResourceResult(PolicyModel::class, $policy);
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
        $this->getPolicyService()->getPolicyDao()->deletePolicies($ids);
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
            new ParamRule(self::PARAMETER_TITLE, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_VERSION, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 40]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SUMMARY, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_CONTENT, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_DOCUMENT_URL, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 512]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MOODLE_COURSE_URL, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 512]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_AUDIENCE_TYPE,
                    new Rule(Rules::IN, [[Policy::AUDIENCE_ALL, Policy::AUDIENCE_JOB_TITLE]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[
                        Policy::STATUS_DRAFT,
                        Policy::STATUS_PUBLISHED,
                        Policy::STATUS_ARCHIVED,
                    ]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EFFECTIVE_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_DUE_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_JOB_TITLE_IDS, new Rule(Rules::ARRAY_TYPE))
            ),
        );
    }

    private function setPolicyFields(Policy $policy, bool $isCreate): void
    {
        $policy->setTitle(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TITLE)
        );
        $policy->setVersion(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_VERSION,
                $isCreate ? '1.0' : $policy->getVersion()
            )
        );
        $policy->setSummary($this->getSanitizedRichTextOrNull(self::PARAMETER_SUMMARY));
        $policy->setContent($this->getSanitizedRichTextOrNull(self::PARAMETER_CONTENT));
        $policy->setDocumentUrl(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DOCUMENT_URL)
        );
        $policy->setMoodleCourseUrl(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_MOODLE_COURSE_URL)
        );
        $audienceType = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_AUDIENCE_TYPE,
            Policy::AUDIENCE_ALL
        );
        $policy->setAudienceType($audienceType);
        $previousStatus = $policy->getStatus();
        $status = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_STATUS,
            $isCreate ? Policy::STATUS_DRAFT : $previousStatus
        );
        $policy->setStatus($status);
        if ($status === Policy::STATUS_PUBLISHED && $previousStatus !== Policy::STATUS_PUBLISHED) {
            $policy->setPublishedAt(new DateTime());
        }
        $policy->setEffectiveDate(
            $this->getRequestParams()->getDateTimeOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_EFFECTIVE_DATE)
        );
        $policy->setDueDate(
            $this->getRequestParams()->getDateTimeOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DUE_DATE)
        );
        $jobTitleIds = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_JOB_TITLE_IDS
        );
        if ($audienceType === Policy::AUDIENCE_JOB_TITLE) {
            $policy->getDecorator()->setJobTitlesByIds(array_map('intval', $jobTitleIds));
        } else {
            $policy->clearJobTitles();
        }
    }
}
