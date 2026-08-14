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

namespace OrangeHRM\Claim\Api;

use OpenApi\Annotations as OA;
use OrangeHRM\Claim\Api\Model\EmployeeCommissionModel;
use OrangeHRM\Claim\Dto\EmployeeCommissionSearchFilterParams;
use OrangeHRM\Claim\Traits\Service\EmployeeCommissionServiceTrait;
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
use OrangeHRM\Core\Authorization\Dto\ResourcePermission;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\EmployeeCommission;

class EmployeeCommissionAPI extends Endpoint implements CrudEndpoint
{
    use EmployeeCommissionServiceTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_SALE_DATE = 'saleDate';
    public const PARAMETER_AMOUNT = 'amount';
    public const PARAMETER_DESCRIPTION = 'description';
    public const PARAMETER_YEAR = 'year';
    public const PARAMETER_MONTH = 'month';
    public const PARAMETER_TOTAL_AMOUNT = 'totalAmount';
    public const DESCRIPTION_MAX_LENGTH = 1000;

    /**
     * @OA\Get(
     *     path="/api/v2/claim/employees/{empNumber}/commissions",
     *     tags={"Claim/Commissions"},
     *     summary="List Employee Commissions",
     *     operationId="list-employee-commissions",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sortField",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum=EmployeeCommissionSearchFilterParams::ALLOWED_SORT_FIELDS)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/sortOrder"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Claim-EmployeeCommissionModel")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="totalAmount", type="number")
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $filterParams = new EmployeeCommissionSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setEmpNumber($empNumber);
        $year = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_YEAR
        );
        $month = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_MONTH
        );
        $filterParams->setYear($year);
        $filterParams->setMonth($month);

        $commissions = $this->getEmployeeCommissionService()->getEmployeeCommissionList($filterParams);
        $count = $this->getEmployeeCommissionService()->getEmployeeCommissionCount($filterParams);
        $totalAmount = 0.0;
        if ($year !== null && $month !== null) {
            $totalAmount = $this->getEmployeeCommissionService()->getCommissionSumForMonth(
                $empNumber,
                $year,
                $month
            );
        } else {
            foreach ($commissions as $commission) {
                $totalAmount += $commission->getAmount();
            }
        }

        return new EndpointCollectionResult(
            EmployeeCommissionModel::class,
            $commissions,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => $count,
                CommonParams::PARAMETER_EMP_NUMBER => $empNumber,
                self::PARAMETER_TOTAL_AMOUNT => $totalAmount,
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getEmpNumberRule(),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_YEAR,
                    new Rule(Rules::BETWEEN, [2000, 2100])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_MONTH,
                    new Rule(Rules::BETWEEN, [1, 12])
                )
            ),
            ...$this->getSortingAndPaginationParamsRules(EmployeeCommissionSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v2/claim/employees/{empNumber}/commissions",
     *     tags={"Claim/Commissions"},
     *     summary="Create Employee Commission",
     *     operationId="create-employee-commission",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="saleDate", type="string", format="date"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="description", type="string"),
     *             required={"saleDate", "amount"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Claim-EmployeeCommissionModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $this->assertCanCreate();
        $empNumber = $this->getEmpNumber();
        $commission = new EmployeeCommission();
        $commission->getDecorator()->setEmployeeByEmpNumber($empNumber);
        $this->setCommissionFields($commission);
        $userId = $this->getAuthUser()->getUserId();
        if ($userId !== null) {
            $commission->getDecorator()->setAssignedByByUserId($userId);
        }
        $this->getEmployeeCommissionService()->createEmployeeCommission($commission);

        return new EndpointResourceResult(
            EmployeeCommissionModel::class,
            $commission,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getEmpNumberRule(),
            ...$this->getCommissionBodyRules()
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/claim/employees/{empNumber}/commissions/{id}",
     *     tags={"Claim/Commissions"},
     *     summary="Get Employee Commission",
     *     operationId="get-employee-commission",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Claim-EmployeeCommissionModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $commission = $this->getCommissionForEmployee($empNumber);
        return new EndpointResourceResult(
            EmployeeCommissionModel::class,
            $commission,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getEmpNumberRule(),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/claim/employees/{empNumber}/commissions/{id}",
     *     tags={"Claim/Commissions"},
     *     summary="Update Employee Commission",
     *     operationId="update-employee-commission",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="saleDate", type="string", format="date"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="description", type="string"),
     *             required={"saleDate", "amount"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Claim-EmployeeCommissionModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $this->assertCanUpdate();
        $empNumber = $this->getEmpNumber();
        $commission = $this->getCommissionForEmployee($empNumber);
        $this->setCommissionFields($commission);
        $this->getEmployeeCommissionService()->saveEmployeeCommission($commission);

        return new EndpointResourceResult(
            EmployeeCommissionModel::class,
            $commission,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getEmpNumberRule(),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            ...$this->getCommissionBodyRules()
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/claim/employees/{empNumber}/commissions",
     *     tags={"Claim/Commissions"},
     *     summary="Delete Employee Commissions",
     *     operationId="delete-employee-commissions",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/requestBodies/DeleteRequestBody"),
     *     @OA\Response(response="200", ref="#/components/responses/DeleteResponse")
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $this->assertCanDelete();
        $empNumber = $this->getEmpNumber();
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $ownedIds = [];
        foreach ($ids as $id) {
            $commission = $this->getEmployeeCommissionService()->getEmployeeCommissionById((int) $id);
            if ($commission instanceof EmployeeCommission
                && $commission->getEmployee()->getEmpNumber() === $empNumber
            ) {
                $ownedIds[] = $commission->getId();
            }
        }
        $this->throwRecordNotFoundExceptionIfEmptyIds($ownedIds);
        $this->getEmployeeCommissionService()->deleteEmployeeCommissionsByIds($ownedIds);
        return new EndpointResourceResult(ArrayModel::class, $ownedIds);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getEmpNumberRule(),
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::INT_ARRAY)),
        );
    }

    /**
     * @return int
     */
    private function getEmpNumber(): int
    {
        return $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
    }

    /**
     * @return ParamRule
     */
    private function getEmpNumberRule(): ParamRule
    {
        return new ParamRule(
            CommonParams::PARAMETER_EMP_NUMBER,
            new Rule(Rules::POSITIVE),
            new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
        );
    }

    /**
     * @return ParamRule[]
     */
    private function getCommissionBodyRules(): array
    {
        return [
            new ParamRule(
                self::PARAMETER_SALE_DATE,
                new Rule(Rules::API_DATE)
            ),
            new ParamRule(
                self::PARAMETER_AMOUNT,
                new Rule(Rules::POSITIVE),
                new Rule(Rules::BETWEEN, [0.01, 9999999999.99])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DESCRIPTION,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::DESCRIPTION_MAX_LENGTH])
                ),
                true
            ),
        ];
    }

    /**
     * @param int $empNumber
     * @return EmployeeCommission
     */
    private function getCommissionForEmployee(int $empNumber): EmployeeCommission
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $commission = $this->getEmployeeCommissionService()->getEmployeeCommissionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($commission, EmployeeCommission::class);
        if ($commission->getEmployee()->getEmpNumber() !== $empNumber) {
            throw $this->getRecordNotFoundException();
        }
        return $commission;
    }

    /**
     * @param EmployeeCommission $commission
     */
    private function setCommissionFields(EmployeeCommission $commission): void
    {
        $saleDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SALE_DATE
        );
        $commission->setSaleDate($saleDate);

        $amount = $this->getRequestParams()->getFloat(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_AMOUNT
        );
        $commission->setAmount($amount);

        $description = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DESCRIPTION
        );
        $commission->setDescription($description === '' ? null : $description);
    }

    private function assertCanCreate(): void
    {
        if (!$this->getCommissionPermissions()->canCreate()) {
            throw $this->getForbiddenException();
        }
    }

    private function assertCanUpdate(): void
    {
        if (!$this->getCommissionPermissions()->canUpdate()) {
            throw $this->getForbiddenException();
        }
    }

    private function assertCanDelete(): void
    {
        if (!$this->getCommissionPermissions()->canDelete()) {
            throw $this->getForbiddenException();
        }
    }

    /**
     * @return ResourcePermission
     */
    private function getCommissionPermissions(): ResourcePermission
    {
        return $this->getUserRoleManagerHelper()->getEntityIndependentDataGroupPermissions(
            'apiv2_claim_employee_commissions'
        );
    }
}
