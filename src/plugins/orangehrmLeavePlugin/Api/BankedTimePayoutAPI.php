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

use Exception;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
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
use OrangeHRM\Leave\Api\Model\LeaveEntitlementTransactionModel;
use OrangeHRM\Leave\Service\BankedTimePayoutService;

class BankedTimePayoutAPI extends Endpoint implements CollectionEndpoint
{
    public const PARAMETER_HOURS = 'hours';
    public const PARAMETER_NOTE = 'note';
    public const META_PARAMETER_BANKED_HOURS = 'bankedHours';
    public const META_PARAMETER_HOURS_PER_DAY = 'hoursPerDay';
    public const META_PARAMETER_BANKED_TIME_LEAVE_TYPE_ID = 'bankedTimeLeaveTypeId';

    private ?BankedTimePayoutService $bankedTimePayoutService = null;

    protected function getBankedTimePayoutService(): BankedTimePayoutService
    {
        if (!$this->bankedTimePayoutService instanceof BankedTimePayoutService) {
            $this->bankedTimePayoutService = new BankedTimePayoutService();
        }
        return $this->bankedTimePayoutService;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $rows = $this->getBankedTimePayoutService()->getPayoutHistory($empNumber, 50);

        $meta = [CommonParams::PARAMETER_TOTAL => count($rows)];
        if ($empNumber !== null) {
            $balance = $this->getBankedTimePayoutService()->getBankedBalance($empNumber);
            $meta[self::META_PARAMETER_BANKED_HOURS] = $balance['bankedHours'];
            $meta[self::META_PARAMETER_HOURS_PER_DAY] = $balance['hoursPerDay'];
            $meta[self::META_PARAMETER_BANKED_TIME_LEAVE_TYPE_ID] = $balance['bankedTimeLeaveTypeId'];
        }

        return new EndpointCollectionResult(
            ArrayModel::class,
            $rows,
            new ParameterBag($meta)
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            ...$this->getSortingAndPaginationParamsRules()
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
        $hours = $this->getRequestParams()->getFloat(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_HOURS
        );
        $note = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_NOTE
        );

        try {
            $txn = $this->getBankedTimePayoutService()->payout($empNumber, $hours, $note);
        } catch (Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }

        $balance = $this->getBankedTimePayoutService()->getBankedBalance($empNumber);
        return new EndpointResourceResult(
            LeaveEntitlementTransactionModel::class,
            $txn,
            new ParameterBag([
                self::META_PARAMETER_BANKED_HOURS => $balance['bankedHours'],
                self::META_PARAMETER_HOURS_PER_DAY => $balance['hoursPerDay'],
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_HOURS,
                new Rule(Rules::NUMBER),
                new Rule(Rules::POSITIVE)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_NOTE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, 200])
                ),
                true
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

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
