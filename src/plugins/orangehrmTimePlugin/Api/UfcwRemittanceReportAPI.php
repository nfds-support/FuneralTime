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
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Time\Traits\Service\UfcwRemittanceReportServiceTrait;

class UfcwRemittanceReportAPI extends Endpoint implements CollectionEndpoint
{
    use UfcwRemittanceReportServiceTrait;

    public const PARAMETER_MONTH = 'month';
    public const PARAMETER_PREPARED_BY = 'preparedBy';
    public const PARAMETER_EMPLOYEES = 'employees';
    public const PARAMETER_SEND_EMAIL = 'sendEmail';
    public const PARAMETER_UPDATE_INITIATION_BALANCES = 'updateInitiationBalances';

    /**
     * @OA\Get(
     *     path="/api/v2/time/ufcw-remittance/report",
     *     tags={"Time/UFCW Remittance"},
     *     @OA\Parameter(name="month", in="query", required=true, @OA\Schema(type="string", example="2026-08")),
     *     @OA\Parameter(name="preparedBy", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success")
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $month = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_MONTH);
        $reportMonth = $this->parseMonth($month);
        $preparedBy = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_PREPARED_BY
        );

        $report = $this->getUfcwRemittanceReportService()->buildReport($reportMonth, $preparedBy);
        unset($report['employeeRows']);

        return new EndpointCollectionResult(
            ArrayModel::class,
            $report['employees'],
            new ParameterBag([
                'reportMonth' => $report['reportMonth'],
                'reportMonthLabel' => $report['reportMonthLabel'],
                'remittanceDueDate' => $report['remittanceDueDate'],
                'preparedBy' => $report['preparedBy'],
                'datePrepared' => $report['datePrepared'],
                'payrollPeriods' => $report['payrollPeriods'],
                'status' => $report['status'],
                'settings' => $report['settings'],
                'totals' => $report['totals'],
                'sheetName' => $report['sheetName'],
                'total' => count($report['employees']),
            ])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_MONTH, new Rule(Rules::STRING_TYPE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PREPARED_BY, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [0, 255]))
            )
        );
    }

    /**
     * Email the remittance workbook (optional initiation-balance update).
     *
     * @OA\Post(
     *     path="/api/v2/time/ufcw-remittance/report",
     *     tags={"Time/UFCW Remittance"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"month"},
     *             @OA\Property(property="month", type="string", example="2026-08"),
     *             @OA\Property(property="preparedBy", type="string"),
     *             @OA\Property(property="sendEmail", type="boolean"),
     *             @OA\Property(property="updateInitiationBalances", type="boolean"),
     *             @OA\Property(property="employees", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success")
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $month = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_MONTH);
        $reportMonth = $this->parseMonth($month);
        $preparedBy = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PREPARED_BY
        );
        $sendEmail = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SEND_EMAIL,
            true
        );
        $updateBalances = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_UPDATE_INITIATION_BALANCES,
            false
        );
        $employees = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EMPLOYEES,
            []
        );
        $overrides = $this->mapOverrides($employees);

        if ($sendEmail) {
            $result = $this->getUfcwRemittanceReportService()->emailReport(
                $reportMonth,
                $preparedBy,
                $overrides,
                $updateBalances
            );
            return new EndpointResourceResult(ArrayModel::class, $result);
        }

        if ($updateBalances) {
            $report = $this->getUfcwRemittanceReportService()->buildReport($reportMonth, $preparedBy, $overrides);
            $this->getUfcwRemittanceReportService()->applyInitiationFeePayments($report['employeeRows']);
        }

        return new EndpointResourceResult(ArrayModel::class, [
            'sent' => false,
            'filename' => $this->getUfcwRemittanceReportService()->buildDownloadFilename($reportMonth),
            'recipients' => [],
        ]);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_MONTH, new Rule(Rules::STRING_TYPE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PREPARED_BY, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [0, 255]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SEND_EMAIL, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_UPDATE_INITIATION_BALANCES, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEES, new Rule(Rules::ARRAY_TYPE))
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @throws BadRequestException
     */
    private function parseMonth(string $month): DateTime
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw $this->getBadRequestException('month must be YYYY-MM');
        }
        $date = DateTime::createFromFormat('Y-m-d', $month . '-01');
        if ($date === false) {
            throw $this->getBadRequestException('Invalid month');
        }
        return $date;
    }

    /**
     * @param array<int, array<string, mixed>> $employees
     * @return array<int, array<string, mixed>>
     */
    private function mapOverrides(array $employees): array
    {
        $overrides = [];
        foreach ($employees as $employee) {
            if (!is_array($employee) || !isset($employee['empNumber'])) {
                continue;
            }
            $overrides[(int) $employee['empNumber']] = $employee;
        }
        return $overrides;
    }
}
