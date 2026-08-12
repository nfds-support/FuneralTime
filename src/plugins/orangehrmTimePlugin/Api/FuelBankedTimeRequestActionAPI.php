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

use Exception;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\ForbiddenException;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\FuelBankedTimeRequest;
use OrangeHRM\Time\Api\Model\FuelBankedTimeRequestModel;
use OrangeHRM\Time\Traits\Service\FuelBankedTimeServiceTrait;

class FuelBankedTimeRequestActionAPI extends Endpoint implements ResourceEndpoint
{
    use AuthUserTrait;
    use FuelBankedTimeServiceTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_ACTION = 'action';
    public const ACTION_APPROVE = 'APPROVE';
    public const ACTION_REJECT = 'REJECT';
    public const ACTION_CANCEL = 'CANCEL';

    /**
     * @OA\Put(
     *     path="/api/v2/time/fuel-banked-time/requests/{id}/action",
     *     tags={"Time/Fuel Banked Time"},
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"APPROVE", "REJECT", "CANCEL"})
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $action = strtoupper(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ACTION)
        );

        $request = $this->getFuelBankedTimeService()->getById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($request, FuelBankedTimeRequest::class);

        $empNumber = $request->getEmployee()->getEmpNumber();
        $isSelf = $empNumber === $this->getAuthUser()->getEmpNumber();

        try {
            if ($action === self::ACTION_CANCEL) {
                if (!$isSelf) {
                    throw $this->getForbiddenException();
                }
                $request = $this->getFuelBankedTimeService()->cancel($request);
            } elseif ($action === self::ACTION_APPROVE || $action === self::ACTION_REJECT) {
                if (!$this->getUserRoleManager()->isEntityAccessible(Employee::class, $empNumber)) {
                    throw $this->getForbiddenException();
                }
                $request = $action === self::ACTION_APPROVE
                    ? $this->getFuelBankedTimeService()->approve($request)
                    : $this->getFuelBankedTimeService()->reject($request);
            } else {
                throw $this->getBadRequestException('Invalid action');
            }
        } catch (ForbiddenException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }

        return new EndpointResourceResult(FuelBankedTimeRequestModel::class, $request);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_ACTION,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::IN, [[self::ACTION_APPROVE, self::ACTION_REJECT, self::ACTION_CANCEL]])
            )
        );
    }

    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
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
