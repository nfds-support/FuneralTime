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

namespace OrangeHRM\Discipline\Api;

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
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Discipline\Api\Model\DisciplineCaseModel;
use OrangeHRM\Discipline\Dto\DisciplineCaseSearchFilterParams;
use OrangeHRM\Discipline\Traits\Service\DisciplineServiceTrait;
use OrangeHRM\Entity\DisciplineCase;

class DisciplineCaseAPI extends Endpoint implements CrudEndpoint
{
    use DisciplineServiceTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_CASE_TYPE = 'caseType';
    public const PARAMETER_CATEGORY = 'category';
    public const PARAMETER_SUBJECT = 'subject';
    public const PARAMETER_DESCRIPTION = 'description';
    public const PARAMETER_INCIDENT_DATE = 'incidentDate';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_SEVERITY = 'severity';
    public const PARAMETER_ACTION_TAKEN = 'actionTaken';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new DisciplineCaseSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);

        $filterParams->setCaseType(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_CASE_TYPE)
        );
        $filterParams->setStatus(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_STATUS)
        );

        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );

        $accessible = $this->getUserRoleManager()->getAccessibleEntityIds(\OrangeHRM\Entity\Employee::class);
        if (!is_null($empNumber)) {
            if (!in_array($empNumber, $accessible, true)
                && $empNumber !== $this->getAuthUser()->getEmpNumber()) {
                throw $this->getForbiddenException();
            }
            $filterParams->setEmpNumber($empNumber);
        } else {
            // ESS self-only when no broader employee access
            if (empty($accessible)) {
                $filterParams->setEmpNumber($this->getAuthUser()->getEmpNumber());
            } else {
                $filterParams->setEmpNumbers($accessible);
            }
        }

        $cases = $this->getDisciplineService()->getDisciplineDao()->getDisciplineCaseList($filterParams);
        $count = $this->getDisciplineService()->getDisciplineDao()->getDisciplineCaseCount($filterParams);
        return new EndpointCollectionResult(
            DisciplineCaseModel::class,
            $cases,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_CASE_TYPE,
                    new Rule(Rules::IN, [[DisciplineCase::TYPE_COMPLAINT, DisciplineCase::TYPE_DISCIPLINARY]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[
                        DisciplineCase::STATUS_OPEN,
                        DisciplineCase::STATUS_UNDER_REVIEW,
                        DisciplineCase::STATUS_RESOLVED,
                        DisciplineCase::STATUS_CLOSED,
                    ]])
                )
            ),
            ...$this->getSortingAndPaginationParamsRules(DisciplineCaseSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $case = new DisciplineCase();
        $this->setCaseFields($case, true);
        $case->getDecorator()->setReportedByEmpNumber($this->getAuthUser()->getEmpNumber());
        $case->setCreatedAt(new DateTime());
        $this->getDisciplineService()->getDisciplineDao()->saveDisciplineCase($case);
        return new EndpointResourceResult(DisciplineCaseModel::class, $case);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyParamRuleCollection(false);
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $case = $this->getDisciplineService()->getDisciplineDao()->getDisciplineCaseById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($case, DisciplineCase::class);
        $this->assertCaseAccessible($case);
        return new EndpointResourceResult(DisciplineCaseModel::class, $case);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $case = $this->getDisciplineService()->getDisciplineDao()->getDisciplineCaseById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($case, DisciplineCase::class);
        $this->assertCaseAccessible($case, true);
        $this->setCaseFields($case, false);
        $case->setUpdatedAt(new DateTime());
        $this->getDisciplineService()->getDisciplineDao()->saveDisciplineCase($case);
        return new EndpointResourceResult(DisciplineCaseModel::class, $case);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return $this->getBodyParamRuleCollection(false, true);
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getDisciplineService()->getDisciplineDao()->deleteDisciplineCases($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::ARRAY_TYPE))
        );
    }

    private function getBodyParamRuleCollection(bool $requireEmpNumber, bool $withId = false): ParamRuleCollection
    {
        $rules = [
            new ParamRule(
                self::PARAMETER_CASE_TYPE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::IN, [[DisciplineCase::TYPE_COMPLAINT, DisciplineCase::TYPE_DISCIPLINARY]])
            ),
            new ParamRule(self::PARAMETER_SUBJECT, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_CATEGORY, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 100]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_DESCRIPTION, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_INCIDENT_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[
                        DisciplineCase::STATUS_OPEN,
                        DisciplineCase::STATUS_UNDER_REVIEW,
                        DisciplineCase::STATUS_RESOLVED,
                        DisciplineCase::STATUS_CLOSED,
                    ]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SEVERITY,
                    new Rule(Rules::IN, [[
                        DisciplineCase::SEVERITY_LOW,
                        DisciplineCase::SEVERITY_MEDIUM,
                        DisciplineCase::SEVERITY_HIGH,
                        DisciplineCase::SEVERITY_CRITICAL,
                    ]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_ACTION_TAKEN, new Rule(Rules::STRING_TYPE))
            ),
        ];

        if ($requireEmpNumber) {
            array_unshift(
                $rules,
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS))
            );
        } else {
            array_unshift(
                $rules,
                $this->getValidationDecorator()->notRequiredParamRule(
                    new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS))
                )
            );
        }

        if ($withId) {
            $rules[] = new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
        }

        return new ParamRuleCollection(...$rules);
    }

    private function setCaseFields(DisciplineCase $case, bool $isCreate): void
    {
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EMP_NUMBER
        );
        if ($isCreate || !is_null($empNumber)) {
            $case->getDecorator()->setEmployeeByEmpNumber(
                $empNumber ?? $this->getAuthUser()->getEmpNumber()
            );
        }

        $case->setCaseType(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CASE_TYPE)
        );
        $case->setSubject(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SUBJECT)
        );
        $case->setCategory(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CATEGORY)
        );
        $case->setDescription(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DESCRIPTION)
        );
        $case->setIncidentDate(
            $this->getRequestParams()->getDateTimeOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_INCIDENT_DATE)
        );
        $case->setStatus(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_STATUS,
                DisciplineCase::STATUS_OPEN
            )
        );
        $case->setSeverity(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SEVERITY)
        );
        $case->setActionTaken(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ACTION_TAKEN)
        );
    }

    private function assertCaseAccessible(DisciplineCase $case, bool $forUpdate = false): void
    {
        $empNumber = $case->getEmployee()->getEmpNumber();
        $self = $empNumber === $this->getAuthUser()->getEmpNumber();
        if ($self && !$forUpdate) {
            return;
        }
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)) {
            throw $this->getForbiddenException();
        }
    }
}
