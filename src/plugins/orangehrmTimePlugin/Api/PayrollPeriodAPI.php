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
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Time\Api\Model\PayrollPeriodModel;
use OrangeHRM\Time\Dto\PayrollPeriodSearchFilterParams;
use OrangeHRM\Time\Traits\Service\PayrollPeriodServiceTrait;

class PayrollPeriodAPI extends Endpoint implements CrudEndpoint
{
    use PayrollPeriodServiceTrait;

    public const PARAMETER_PERIOD_NUMBER = 'periodNumber';
    public const PARAMETER_START_DATE = 'startDate';
    public const PARAMETER_END_DATE = 'endDate';
    public const PARAMETER_LABEL = 'label';
    public const FILTER_PERIOD_NUMBER = 'periodNumber';
    public const FILTER_FROM_DATE = 'fromDate';
    public const FILTER_TO_DATE = 'toDate';
    public const PARAM_RULE_LABEL_MAX_LENGTH = 100;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new PayrollPeriodSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setPeriodNumber(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_PERIOD_NUMBER
            )
        );
        $fromDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_FROM_DATE
        );
        $toDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_TO_DATE
        );
        $filterParams->setFromDate($fromDate);
        $filterParams->setToDate($toDate);

        $periods = $this->getPayrollPeriodService()->search($filterParams);
        $count = $this->getPayrollPeriodService()->getCount($filterParams);

        return new EndpointCollectionResult(
            PayrollPeriodModel::class,
            $periods,
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
                new ParamRule(self::FILTER_PERIOD_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_FROM_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_TO_DATE, new Rule(Rules::API_DATE))
            ),
            ...$this->getSortingAndPaginationParamsRules(PayrollPeriodSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $payrollPeriod = new PayrollPeriod();
        $this->setParamsToPayrollPeriod($payrollPeriod);
        $this->getPayrollPeriodService()->save($payrollPeriod);
        return new EndpointResourceResult(PayrollPeriodModel::class, $payrollPeriod);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(...$this->getBodyValidationRules());
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $ids = $this->getPayrollPeriodService()->getPayrollPeriodDao()->getExistingIds(
            $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS)
        );
        $this->throwRecordNotFoundExceptionIfEmptyIds($ids);
        $this->getPayrollPeriodService()->deleteByIds($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_IDS,
                new Rule(Rules::ARRAY_TYPE),
                new Rule(
                    Rules::EACH,
                    [new Rules\Composite\AllOf(new Rule(Rules::POSITIVE))]
                )
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $payrollPeriod = $this->getPayrollPeriodService()->getById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($payrollPeriod, PayrollPeriod::class);
        return new EndpointResourceResult(PayrollPeriodModel::class, $payrollPeriod);
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
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $payrollPeriod = $this->getPayrollPeriodService()->getById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($payrollPeriod, PayrollPeriod::class);
        $this->setParamsToPayrollPeriod($payrollPeriod);
        $this->getPayrollPeriodService()->save($payrollPeriod);
        return new EndpointResourceResult(PayrollPeriodModel::class, $payrollPeriod);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            ...$this->getBodyValidationRules()
        );
    }

    /**
     * @param PayrollPeriod $payrollPeriod
     */
    private function setParamsToPayrollPeriod(PayrollPeriod $payrollPeriod): void
    {
        $payrollPeriod->setPeriodNumber(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PERIOD_NUMBER)
        );
        $payrollPeriod->setStartDate(
            $this->getRequestParams()->getDateTime(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_START_DATE)
        );
        $payrollPeriod->setEndDate(
            $this->getRequestParams()->getDateTime(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_END_DATE)
        );
        $payrollPeriod->setLabel(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_LABEL)
        );
    }

    /**
     * @return ParamRule[]
     */
    private function getBodyValidationRules(): array
    {
        return [
            new ParamRule(self::PARAMETER_PERIOD_NUMBER, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_START_DATE, new Rule(Rules::API_DATE)),
            new ParamRule(self::PARAMETER_END_DATE, new Rule(Rules::API_DATE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_LABEL,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_LABEL_MAX_LENGTH])
                ),
                true
            ),
        ];
    }
}
