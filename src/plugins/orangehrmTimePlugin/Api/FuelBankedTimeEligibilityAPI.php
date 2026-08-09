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
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Time\Api\Model\FuelBankedTimeEligibilityModel;
use OrangeHRM\Time\Traits\Service\FuelBankedTimeServiceTrait;

class FuelBankedTimeEligibilityAPI extends Endpoint implements ResourceEndpoint
{
    use AuthUserTrait;
    use FuelBankedTimeServiceTrait;

    /**
     * @OA\Get(
     *     path="/api/v2/time/fuel-banked-time/eligibility",
     *     tags={"Time/Fuel Banked Time"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", default=0)),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function getOne(): EndpointResult
    {
        $eligibility = $this->getFuelBankedTimeService()->getEligibility(
            $this->getAuthUser()->getEmpNumber()
        );
        return new EndpointResourceResult(FuelBankedTimeEligibilityModel::class, $eligibility);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID)
        );
    }

    public function update(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
