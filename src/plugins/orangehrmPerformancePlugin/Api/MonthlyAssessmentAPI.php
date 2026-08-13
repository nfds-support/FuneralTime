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

namespace OrangeHRM\Performance\Api;

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
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\MonthlyAssessment;
use OrangeHRM\Performance\Api\Model\MonthlyAssessmentModel;
use OrangeHRM\Performance\Dto\MonthlyAssessmentSearchFilterParams;
use OrangeHRM\Performance\Traits\Service\MonthlyAssessmentServiceTrait;

class MonthlyAssessmentAPI extends Endpoint implements CrudEndpoint
{
    use MonthlyAssessmentServiceTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_MANAGER_EMP_NUMBER = 'managerEmpNumber';
    public const PARAMETER_PERIOD_YEAR = 'periodYear';
    public const PARAMETER_PERIOD_MONTH = 'periodMonth';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_SUBMIT = 'submit';
    public const PARAMETER_SUBMIT_AS = 'submitAs';

    public const PARAMETER_EMPLOYEE_OVERALL_RATING = 'employeeOverallRating';
    public const PARAMETER_EMPLOYEE_ENGAGEMENT_RATING = 'employeeEngagementRating';
    public const PARAMETER_EMPLOYEE_ACCOMPLISHMENTS = 'employeeAccomplishments';
    public const PARAMETER_EMPLOYEE_IMPROVEMENTS = 'employeeImprovements';
    public const PARAMETER_EMPLOYEE_GOALS = 'employeeGoals';
    public const PARAMETER_EMPLOYEE_SUPPORT_NEEDED = 'employeeSupportNeeded';

    public const PARAMETER_MANAGER_OVERALL_RATING = 'managerOverallRating';
    public const PARAMETER_MANAGER_STRENGTHS = 'managerStrengths';
    public const PARAMETER_MANAGER_IMPROVEMENTS = 'managerImprovements';
    public const PARAMETER_MANAGER_GOALS_SUPPORT = 'managerGoalsSupport';
    public const PARAMETER_MANAGER_FOLLOW_UP_NOTES = 'managerFollowUpNotes';

    public const SUBMIT_AS_EMPLOYEE = 'employee';
    public const SUBMIT_AS_MANAGER = 'manager';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new MonthlyAssessmentSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);

        $filterParams->setPeriodYear(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_PERIOD_YEAR)
        );
        $filterParams->setPeriodMonth(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_PERIOD_MONTH)
        );
        $filterParams->setStatus(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_STATUS)
        );

        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );
        $managerEmpNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_MANAGER_EMP_NUMBER
        );

        $accessible = $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class);
        $authEmpNumber = $this->getAuthUser()->getEmpNumber();

        if (!is_null($managerEmpNumber)) {
            if ($managerEmpNumber !== $authEmpNumber
                && !$this->getUserRoleManagerHelper()->isEmployeeAccessible($managerEmpNumber)) {
                throw $this->getForbiddenException();
            }
            $filterParams->setManagerEmpNumber($managerEmpNumber);
        } elseif (!is_null($empNumber)) {
            if ($empNumber !== $authEmpNumber && !in_array($empNumber, $accessible, true)) {
                throw $this->getForbiddenException();
            }
            $filterParams->setEmpNumber($empNumber);
        } elseif (empty($accessible)) {
            $filterParams->setEmpNumber($authEmpNumber);
        } else {
            $filterParams->setEmpNumbers(array_values(array_unique(array_merge($accessible, [$authEmpNumber]))));
        }

        $dao = $this->getMonthlyAssessmentService()->getMonthlyAssessmentDao();
        $assessments = $dao->getMonthlyAssessmentList($filterParams);
        $count = $dao->getMonthlyAssessmentCount($filterParams);

        return new EndpointCollectionResult(
            MonthlyAssessmentModel::class,
            $assessments,
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
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PERIOD_YEAR, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PERIOD_MONTH, new Rule(Rules::BETWEEN, [1, 12]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[
                        MonthlyAssessment::STATUS_DRAFT,
                        MonthlyAssessment::STATUS_AWAITING_MANAGER,
                        MonthlyAssessment::STATUS_AWAITING_EMPLOYEE,
                        MonthlyAssessment::STATUS_COMPLETED,
                    ]])
                )
            ),
            ...$this->getSortingAndPaginationParamsRules(MonthlyAssessmentSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $periodYear = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PERIOD_YEAR);
        $periodMonth = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PERIOD_MONTH);
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EMP_NUMBER
        ) ?? $this->getAuthUser()->getEmpNumber();

        $this->assertCanCreateForEmployee($empNumber);

        $dao = $this->getMonthlyAssessmentService()->getMonthlyAssessmentDao();
        $existing = $dao->getMonthlyAssessmentByEmployeeAndPeriod($empNumber, $periodYear, $periodMonth);
        if ($existing instanceof MonthlyAssessment) {
            throw $this->getBadRequestException('Assessment already exists for this employee and period');
        }

        $assessment = new MonthlyAssessment();
        $assessment->getDecorator()->setEmployeeByEmpNumber($empNumber);
        $assessment->setPeriodYear($periodYear);
        $assessment->setPeriodMonth($periodMonth);
        $assessment->setCreatedAt(new DateTime());

        $managerEmpNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_MANAGER_EMP_NUMBER
        );
        if (!is_null($managerEmpNumber)) {
            $assessment->getDecorator()->setManagerByEmpNumber($managerEmpNumber);
        }

        $this->applySelfAssessmentFields($assessment);
        $this->applyManagerAssessmentFields($assessment);
        $this->handleSubmit($assessment);
        $this->refreshStatus($assessment);

        $dao->saveMonthlyAssessment($assessment);
        return new EndpointResourceResult(MonthlyAssessmentModel::class, $assessment);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyParamRuleCollection(true);
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $assessment = $this->getMonthlyAssessmentService()->getMonthlyAssessmentDao()->getMonthlyAssessmentById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($assessment, MonthlyAssessment::class);
        $this->assertAssessmentAccessible($assessment);
        return new EndpointResourceResult(MonthlyAssessmentModel::class, $assessment);
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
        $assessment = $this->getMonthlyAssessmentService()->getMonthlyAssessmentDao()->getMonthlyAssessmentById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($assessment, MonthlyAssessment::class);
        $this->assertAssessmentAccessible($assessment);

        $authEmpNumber = $this->getAuthUser()->getEmpNumber();
        $isSelf = $assessment->getEmployee()->getEmpNumber() === $authEmpNumber;
        $isManager = $assessment->getManager()
            && $assessment->getManager()->getEmpNumber() === $authEmpNumber;
        $isAdminAccess = $this->getUserRoleManagerHelper()->isEmployeeAccessible(
            $assessment->getEmployee()->getEmpNumber()
        );
        $submitAs = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SUBMIT_AS
        );

        // Only the employee (or an admin acting as employee) may edit the self-assessment section.
        if ($isSelf || ($isAdminAccess && $submitAs !== self::SUBMIT_AS_MANAGER)) {
            $this->applySelfAssessmentFields($assessment);
        }
        // Managers / admins edit the manager section.
        if ($isManager || $isAdminAccess || (!$isSelf && $submitAs === self::SUBMIT_AS_MANAGER)) {
            $this->applyManagerAssessmentFields($assessment);
            $managerEmpNumber = $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MANAGER_EMP_NUMBER
            );
            if (!is_null($managerEmpNumber)) {
                $assessment->getDecorator()->setManagerByEmpNumber($managerEmpNumber);
            } elseif (($isManager || $submitAs === self::SUBMIT_AS_MANAGER) && !$assessment->getManager()) {
                $assessment->getDecorator()->setManagerByEmpNumber($authEmpNumber);
            }
        }

        $this->handleSubmit($assessment);
        $this->refreshStatus($assessment);
        $assessment->setUpdatedAt(new DateTime());

        $this->getMonthlyAssessmentService()->getMonthlyAssessmentDao()->saveMonthlyAssessment($assessment);
        return new EndpointResourceResult(MonthlyAssessmentModel::class, $assessment);
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
        $this->getMonthlyAssessmentService()->getMonthlyAssessmentDao()->deleteMonthlyAssessments($ids);
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

    private function getBodyParamRuleCollection(bool $requirePeriod, bool $withId = false): ParamRuleCollection
    {
        $rules = [
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_OVERALL_RATING, new Rule(Rules::BETWEEN, [1, 5]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_ENGAGEMENT_RATING, new Rule(Rules::BETWEEN, [1, 5]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_ACCOMPLISHMENTS, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_IMPROVEMENTS, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_GOALS, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_SUPPORT_NEEDED, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_OVERALL_RATING, new Rule(Rules::BETWEEN, [1, 5]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_STRENGTHS, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_IMPROVEMENTS, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_GOALS_SUPPORT, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_MANAGER_FOLLOW_UP_NOTES, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SUBMIT, new Rule(Rules::BOOL_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SUBMIT_AS,
                    new Rule(Rules::IN, [[self::SUBMIT_AS_EMPLOYEE, self::SUBMIT_AS_MANAGER]])
                )
            ),
        ];

        if ($requirePeriod) {
            array_unshift(
                $rules,
                new ParamRule(self::PARAMETER_PERIOD_YEAR, new Rule(Rules::POSITIVE)),
                new ParamRule(self::PARAMETER_PERIOD_MONTH, new Rule(Rules::BETWEEN, [1, 12]))
            );
        } else {
            array_unshift(
                $rules,
                $this->getValidationDecorator()->notRequiredParamRule(
                    new ParamRule(self::PARAMETER_PERIOD_YEAR, new Rule(Rules::POSITIVE))
                ),
                $this->getValidationDecorator()->notRequiredParamRule(
                    new ParamRule(self::PARAMETER_PERIOD_MONTH, new Rule(Rules::BETWEEN, [1, 12]))
                )
            );
        }

        if ($withId) {
            $rules[] = new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
        }

        return new ParamRuleCollection(...$rules);
    }

    private function applySelfAssessmentFields(MonthlyAssessment $assessment): void
    {
        if ($assessment->getEmployeeSubmittedAt() !== null) {
            return;
        }

        $overall = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EMPLOYEE_OVERALL_RATING
        );
        if (!is_null($overall)) {
            $assessment->setEmployeeOverallRating($overall);
        }
        $engagement = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EMPLOYEE_ENGAGEMENT_RATING
        );
        if (!is_null($engagement)) {
            $assessment->setEmployeeEngagementRating($engagement);
        }

        $assessment->setEmployeeAccomplishments(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMPLOYEE_ACCOMPLISHMENTS
            ) ?? $assessment->getEmployeeAccomplishments()
        );
        $assessment->setEmployeeImprovements(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMPLOYEE_IMPROVEMENTS
            ) ?? $assessment->getEmployeeImprovements()
        );
        $assessment->setEmployeeGoals(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMPLOYEE_GOALS
            ) ?? $assessment->getEmployeeGoals()
        );
        $assessment->setEmployeeSupportNeeded(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMPLOYEE_SUPPORT_NEEDED
            ) ?? $assessment->getEmployeeSupportNeeded()
        );
    }

    private function applyManagerAssessmentFields(MonthlyAssessment $assessment): void
    {
        if ($assessment->getManagerSubmittedAt() !== null) {
            // Follow-up notes remain editable after manager submission (BambooHR-style).
            $followUp = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MANAGER_FOLLOW_UP_NOTES
            );
            if (!is_null($followUp)) {
                $assessment->setManagerFollowUpNotes($followUp);
            }
            return;
        }

        $overall = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_MANAGER_OVERALL_RATING
        );
        if (!is_null($overall)) {
            $assessment->setManagerOverallRating($overall);
        }

        $assessment->setManagerStrengths(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MANAGER_STRENGTHS
            ) ?? $assessment->getManagerStrengths()
        );
        $assessment->setManagerImprovements(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MANAGER_IMPROVEMENTS
            ) ?? $assessment->getManagerImprovements()
        );
        $assessment->setManagerGoalsSupport(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MANAGER_GOALS_SUPPORT
            ) ?? $assessment->getManagerGoalsSupport()
        );
        $assessment->setManagerFollowUpNotes(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MANAGER_FOLLOW_UP_NOTES
            ) ?? $assessment->getManagerFollowUpNotes()
        );
    }

    private function handleSubmit(MonthlyAssessment $assessment): void
    {
        $submit = $this->getRequestParams()->getBooleanOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SUBMIT);
        if (!$submit) {
            return;
        }

        $submitAs = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SUBMIT_AS
        );
        $authEmpNumber = $this->getAuthUser()->getEmpNumber();
        $isSelf = $assessment->getEmployee()->getEmpNumber() === $authEmpNumber;
        $isManager = ($assessment->getManager() && $assessment->getManager()->getEmpNumber() === $authEmpNumber)
            || $this->getUserRoleManagerHelper()->isEmployeeAccessible($assessment->getEmployee()->getEmpNumber());

        if ($submitAs === self::SUBMIT_AS_EMPLOYEE || ($submitAs === null && $isSelf)) {
            if (!$isSelf && !$this->getUserRoleManagerHelper()->isEmployeeAccessible(
                $assessment->getEmployee()->getEmpNumber()
            )) {
                throw $this->getForbiddenException();
            }
            $assessment->setEmployeeSubmittedAt(new DateTime());
        } elseif ($submitAs === self::SUBMIT_AS_MANAGER || ($submitAs === null && $isManager && !$isSelf)) {
            if (!$isManager) {
                throw $this->getForbiddenException();
            }
            if (!$assessment->getManager()) {
                $assessment->getDecorator()->setManagerByEmpNumber($authEmpNumber);
            }
            $assessment->setManagerSubmittedAt(new DateTime());
        }
    }

    private function refreshStatus(MonthlyAssessment $assessment): void
    {
        $employeeDone = $assessment->getEmployeeSubmittedAt() !== null;
        $managerDone = $assessment->getManagerSubmittedAt() !== null;

        if ($employeeDone && $managerDone) {
            $assessment->setStatus(MonthlyAssessment::STATUS_COMPLETED);
        } elseif ($employeeDone) {
            $assessment->setStatus(MonthlyAssessment::STATUS_AWAITING_MANAGER);
        } elseif ($managerDone) {
            $assessment->setStatus(MonthlyAssessment::STATUS_AWAITING_EMPLOYEE);
        } else {
            $assessment->setStatus(MonthlyAssessment::STATUS_DRAFT);
        }
    }

    private function assertCanCreateForEmployee(int $empNumber): void
    {
        if ($empNumber === $this->getAuthUser()->getEmpNumber()) {
            return;
        }
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)) {
            throw $this->getForbiddenException();
        }
    }

    private function assertAssessmentAccessible(MonthlyAssessment $assessment): void
    {
        $empNumber = $assessment->getEmployee()->getEmpNumber();
        $authEmpNumber = $this->getAuthUser()->getEmpNumber();
        if ($empNumber === $authEmpNumber) {
            return;
        }
        if ($assessment->getManager() && $assessment->getManager()->getEmpNumber() === $authEmpNumber) {
            return;
        }
        if ($this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)) {
            return;
        }
        throw $this->getForbiddenException();
    }
}
