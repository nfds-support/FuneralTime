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
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Time\Api\Model\UfcwRemittanceConfigModel;
use OrangeHRM\Time\Traits\Service\UfcwRemittanceSettingsServiceTrait;

class UfcwRemittanceConfigAPI extends Endpoint implements ResourceEndpoint
{
    use UfcwRemittanceSettingsServiceTrait;

    public const PARAMETER_DUES_HOURLY_MULTIPLIER = 'duesHourlyMultiplier';
    public const PARAMETER_DUES_WEEKLY_FLAT_FEE = 'duesWeeklyFlatFee';
    public const PARAMETER_INITIATION_FEE_FULL_TIME = 'initiationFeeFullTime';
    public const PARAMETER_INITIATION_FEE_PART_TIME = 'initiationFeePartTime';
    public const PARAMETER_INITIATION_WEEKLY_MAX_FULL_TIME = 'initiationWeeklyMaxFullTime';
    public const PARAMETER_INITIATION_WEEKLY_MAX_PART_TIME = 'initiationWeeklyMaxPartTime';
    public const PARAMETER_EMPLOYER_NAME = 'employerName';
    public const PARAMETER_WORK_LOCATION = 'workLocation';
    public const PARAMETER_WORK_LOCATION_CODE = 'workLocationCode';
    public const PARAMETER_UNION_CONTACTS = 'unionContacts';
    public const PARAMETER_MEMBERSHIP_NAME = 'membershipName';
    public const PARAMETER_REMITTANCE_EMAIL = 'remittanceEmail';
    public const PARAMETER_PAYROLL_EMAIL = 'payrollEmail';
    public const PARAMETER_CHEQUE_PAYABLE_TO = 'chequePayableTo';
    public const PARAMETER_CHEQUE_ATTENTION = 'chequeAttention';

    /**
     * @OA\Get(
     *     path="/api/v2/time/ufcw-remittance/config",
     *     tags={"Time/UFCW Remittance"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        return new EndpointResourceResult(
            UfcwRemittanceConfigModel::class,
            $this->getUfcwRemittanceSettingsService()->getSettings()
        );
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/time/ufcw-remittance/config",
     *     tags={"Time/UFCW Remittance"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="duesHourlyMultiplier", type="number"),
     *             @OA\Property(property="duesWeeklyFlatFee", type="number"),
     *             @OA\Property(property="employerName", type="string"),
     *             @OA\Property(property="remittanceEmail", type="string"),
     *             @OA\Property(property="payrollEmail", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success")
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $settings = $this->getUfcwRemittanceSettingsService()->getSettings();
        $settings->apply([
            self::PARAMETER_DUES_HOURLY_MULTIPLIER => $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_DUES_HOURLY_MULTIPLIER
            ),
            self::PARAMETER_DUES_WEEKLY_FLAT_FEE => $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_DUES_WEEKLY_FLAT_FEE
            ),
            self::PARAMETER_INITIATION_FEE_FULL_TIME => $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_INITIATION_FEE_FULL_TIME
            ),
            self::PARAMETER_INITIATION_FEE_PART_TIME => $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_INITIATION_FEE_PART_TIME
            ),
            self::PARAMETER_INITIATION_WEEKLY_MAX_FULL_TIME => $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_INITIATION_WEEKLY_MAX_FULL_TIME
            ),
            self::PARAMETER_INITIATION_WEEKLY_MAX_PART_TIME => $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_INITIATION_WEEKLY_MAX_PART_TIME
            ),
            self::PARAMETER_EMPLOYER_NAME => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMPLOYER_NAME
            ),
            self::PARAMETER_WORK_LOCATION => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_WORK_LOCATION
            ),
            self::PARAMETER_WORK_LOCATION_CODE => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_WORK_LOCATION_CODE
            ),
            self::PARAMETER_UNION_CONTACTS => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_UNION_CONTACTS
            ),
            self::PARAMETER_MEMBERSHIP_NAME => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MEMBERSHIP_NAME
            ),
            self::PARAMETER_REMITTANCE_EMAIL => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_REMITTANCE_EMAIL
            ),
            self::PARAMETER_PAYROLL_EMAIL => $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_PAYROLL_EMAIL
            ) ?? '',
            self::PARAMETER_CHEQUE_PAYABLE_TO => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_CHEQUE_PAYABLE_TO
            ),
            self::PARAMETER_CHEQUE_ATTENTION => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_CHEQUE_ATTENTION
            ),
        ]);
        $this->getUfcwRemittanceSettingsService()->saveSettings($settings);

        return new EndpointResourceResult(UfcwRemittanceConfigModel::class, $settings);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID),
            new ParamRule(self::PARAMETER_DUES_HOURLY_MULTIPLIER, new Rule(Rules::FLOAT_VAL)),
            new ParamRule(self::PARAMETER_DUES_WEEKLY_FLAT_FEE, new Rule(Rules::FLOAT_VAL)),
            new ParamRule(self::PARAMETER_INITIATION_FEE_FULL_TIME, new Rule(Rules::FLOAT_VAL)),
            new ParamRule(self::PARAMETER_INITIATION_FEE_PART_TIME, new Rule(Rules::FLOAT_VAL)),
            new ParamRule(self::PARAMETER_INITIATION_WEEKLY_MAX_FULL_TIME, new Rule(Rules::FLOAT_VAL)),
            new ParamRule(self::PARAMETER_INITIATION_WEEKLY_MAX_PART_TIME, new Rule(Rules::FLOAT_VAL)),
            new ParamRule(self::PARAMETER_EMPLOYER_NAME, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            new ParamRule(self::PARAMETER_WORK_LOCATION, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            new ParamRule(self::PARAMETER_WORK_LOCATION_CODE, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 50])),
            new ParamRule(self::PARAMETER_UNION_CONTACTS, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [0, 255])),
            new ParamRule(self::PARAMETER_MEMBERSHIP_NAME, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 100])),
            new ParamRule(self::PARAMETER_REMITTANCE_EMAIL, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PAYROLL_EMAIL, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [0, 500]))
            ),
            new ParamRule(self::PARAMETER_CHEQUE_PAYABLE_TO, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            new ParamRule(self::PARAMETER_CHEQUE_ATTENTION, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
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
}
