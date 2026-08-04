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

use DateTime;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Time\Service\PayrollFillSheetService;
use OrangeHRM\Time\Traits\Service\PayrollPeriodServiceTrait;

class PayrollFillSheetAPI extends Endpoint implements CollectionEndpoint
{
    use PayrollPeriodServiceTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_PERIOD_ID = 'periodId';
    public const PARAMETER_FROM_DATE = 'fromDate';
    public const PARAMETER_TO_DATE = 'toDate';
    public const PARAMETER_PERIOD_NUMBER = 'periodNumber';

    /**
     * @var PayrollFillSheetService|null
     */
    private ?PayrollFillSheetService $payrollFillSheetService = null;

    /**
     * @return PayrollFillSheetService
     */
    protected function getPayrollFillSheetService(): PayrollFillSheetService
    {
        if (!$this->payrollFillSheetService instanceof PayrollFillSheetService) {
            $this->payrollFillSheetService = new PayrollFillSheetService();
        }
        return $this->payrollFillSheetService;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        [$startDate, $endDate] = $this->resolvePeriodDates();
        $accessibleEmpNumbers = $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class);
        if (empty($accessibleEmpNumbers)) {
            return new EndpointCollectionResult(
                ArrayModel::class,
                [],
                new ParameterBag([CommonParams::PARAMETER_TOTAL => 0])
            );
        }
        $rows = $this->getPayrollFillSheetService()->buildFillSheet(
            $startDate,
            $endDate,
            $accessibleEmpNumbers
        );

        $data = array_map(static fn ($row) => $row->toArray(), $rows);

        return new EndpointCollectionResult(
            ArrayModel::class,
            $data,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($data)])
        );
    }

    /**
     * @return array{0: DateTime, 1: DateTime}
     */
    private function resolvePeriodDates(): array
    {
        $periodId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_PERIOD_ID
        );
        if (!is_null($periodId)) {
            $period = $this->getPayrollPeriodService()->getById($periodId);
            $this->throwRecordNotFoundExceptionIfNotExist($period, PayrollPeriod::class);
            return [$period->getStartDate(), $period->getEndDate()];
        }

        $fromDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_FROM_DATE
        );
        $toDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_TO_DATE
        );
        return [$fromDate, $toDate];
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PERIOD_ID, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_FROM_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_TO_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PERIOD_NUMBER, new Rule(Rules::POSITIVE))
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
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
