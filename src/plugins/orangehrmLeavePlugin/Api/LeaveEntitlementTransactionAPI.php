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

namespace OrangeHRM\Leave\Api;

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
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\LeaveEntitlementTransaction;
use OrangeHRM\Leave\Api\Model\LeaveEntitlementTransactionModel;
use OrangeHRM\Leave\Dto\LeaveEntitlementTransactionSearchFilterParams;
use OrangeHRM\Leave\Traits\Service\LeaveEntitlementTransactionServiceTrait;

class LeaveEntitlementTransactionAPI extends Endpoint implements CollectionEndpoint
{
    use LeaveEntitlementTransactionServiceTrait;
    use UserRoleManagerTrait;
    use AuthUserTrait;

    public const PARAMETER_LEAVE_TYPE_ID = 'leaveTypeId';
    public const PARAMETER_TRANSACTION_TYPE = 'transactionType';
    public const PARAMETER_DAYS = 'days';
    public const PARAMETER_NOTE = 'note';
    public const PARAMETER_FROM_DATE = 'fromDate';
    public const PARAMETER_TO_DATE = 'toDate';
    public const FILTER_EMP_NUMBER = 'empNumber';
    public const FILTER_LEAVE_TYPE_ID = 'leaveTypeId';
    public const FILTER_TRANSACTION_TYPE = 'transactionType';
    public const FILTER_FROM_DATE = 'fromDate';
    public const FILTER_TO_DATE = 'toDate';
    public const PARAM_RULE_NOTE_MAX_LENGTH = 255;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new LeaveEntitlementTransactionSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);

        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_EMP_NUMBER
        );
        if (is_null($empNumber)) {
            // ESS / self-only: default to current employee
            $accessible = $this->getUserRoleManager()->getAccessibleEntityIds(\OrangeHRM\Entity\Employee::class);
            if (count($accessible) <= 1) {
                $empNumber = $this->getAuthUser()->getEmpNumber();
            }
        }
        if (!is_null($empNumber)) {
            if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)
                && $empNumber !== $this->getAuthUser()->getEmpNumber()
            ) {
                throw $this->getForbiddenException();
            }
            $filterParams->setEmpNumber($empNumber);
        }

        $filterParams->setLeaveTypeId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_LEAVE_TYPE_ID
            )
        );
        $filterParams->setTransactionType(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_TRANSACTION_TYPE
            )
        );
        $filterParams->setFromDate(
            $this->getRequestParams()->getDateTimeOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_FROM_DATE
            )
        );
        $filterParams->setToDate(
            $this->getRequestParams()->getDateTimeOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_TO_DATE
            )
        );

        $transactions = $this->getLeaveEntitlementTransactionService()->search($filterParams);
        $count = $this->getLeaveEntitlementTransactionService()->getCount($filterParams);

        return new EndpointCollectionResult(
            LeaveEntitlementTransactionModel::class,
            $transactions,
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
                new ParamRule(self::FILTER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_LEAVE_TYPE_ID, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_TRANSACTION_TYPE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::IN, [[
                        LeaveEntitlementTransaction::TYPE_ADDITION,
                        LeaveEntitlementTransaction::TYPE_DEDUCTION,
                        LeaveEntitlementTransaction::TYPE_CORRECTION,
                        LeaveEntitlementTransaction::TYPE_USAGE,
                    ]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_FROM_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_TO_DATE, new Rule(Rules::API_DATE))
            ),
            ...$this->getSortingAndPaginationParamsRules(
                LeaveEntitlementTransactionSearchFilterParams::ALLOWED_SORT_FIELDS
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $leaveTypeId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_LEAVE_TYPE_ID
        );
        $transactionType = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_TRANSACTION_TYPE
        );
        $days = $this->getRequestParams()->getFloat(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DAYS
        );
        $note = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_NOTE
        );
        $fromDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_FROM_DATE
        );
        $toDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_TO_DATE
        );

        $txn = $this->getLeaveEntitlementTransactionService()->createAdjustment(
            $empNumber,
            $leaveTypeId,
            $transactionType,
            $days,
            $note,
            $fromDate,
            $toDate
        );

        return new EndpointResourceResult(LeaveEntitlementTransactionModel::class, $txn);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_LEAVE_TYPE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_TRANSACTION_TYPE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::IN, [[
                    LeaveEntitlementTransaction::TYPE_DEDUCTION,
                    LeaveEntitlementTransaction::TYPE_CORRECTION,
                ]])
            ),
            new ParamRule(self::PARAMETER_DAYS, new Rule(Rules::NUMBER)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_NOTE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NOTE_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_FROM_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_TO_DATE, new Rule(Rules::API_DATE))
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
