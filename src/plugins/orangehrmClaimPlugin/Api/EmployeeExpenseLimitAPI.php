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
use OrangeHRM\Claim\Api\Model\ClaimExpenseLimitModel;
use OrangeHRM\Claim\Traits\Service\ExpenseClaimLimitServiceTrait;
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
use OrangeHRM\Entity\ClaimExpenseLimit;
use OrangeHRM\Entity\ExpenseType;

class EmployeeExpenseLimitAPI extends Endpoint implements CrudEndpoint
{
    use ExpenseClaimLimitServiceTrait;

    public const PARAMETER_EXPENSE_TYPE_ID = 'expenseTypeId';
    public const PARAMETER_MONTHLY_LIMIT = 'monthlyLimit';

    /**
     * @OA\Get(
     *     path="/api/v2/claim/employees/{empNumber}/expense-limits",
     *     tags={"Claim/Expense Limits"},
     *     summary="List Employee Expense Limits",
     *     operationId="list-employee-expense-limits",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Claim-ClaimExpenseLimitModel")),
     *             @OA\Property(property="meta", type="object", @OA\Property(property="total", type="integer"))
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $limits = $this->getExpenseClaimLimitService()->getClaimExpenseLimitsByEmpNumber($empNumber);
        return new EndpointCollectionResult(
            ClaimExpenseLimitModel::class,
            $limits,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($limits),
                CommonParams::PARAMETER_EMP_NUMBER => $empNumber,
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection($this->getEmpNumberRule());
    }

    /**
     * @OA\Post(
     *     path="/api/v2/claim/employees/{empNumber}/expense-limits",
     *     tags={"Claim/Expense Limits"},
     *     summary="Create Employee Expense Limit",
     *     operationId="create-employee-expense-limit",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="expenseTypeId", type="integer"),
     *             @OA\Property(property="monthlyLimit", type="number"),
     *             required={"expenseTypeId", "monthlyLimit"}
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success")
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $expenseTypeId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EXPENSE_TYPE_ID
        );
        $existing = $this->getExpenseClaimLimitService()->getClaimExpenseLimit($empNumber, $expenseTypeId);
        if ($existing instanceof ClaimExpenseLimit) {
            throw $this->getInvalidParamException(self::PARAMETER_EXPENSE_TYPE_ID);
        }

        $limit = new ClaimExpenseLimit();
        $limit->getDecorator()->setEmployeeByEmpNumber($empNumber);
        $this->setLimitFields($limit);
        $this->getExpenseClaimLimitService()->saveClaimExpenseLimit($limit);

        return new EndpointResourceResult(
            ClaimExpenseLimitModel::class,
            $limit,
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
            new ParamRule(self::PARAMETER_EXPENSE_TYPE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_MONTHLY_LIMIT,
                new Rule(Rules::ZERO_OR_POSITIVE),
                new Rule(Rules::BETWEEN, [0, 9999999999.99])
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/claim/employees/{empNumber}/expense-limits/{id}",
     *     tags={"Claim/Expense Limits"},
     *     summary="Get Employee Expense Limit",
     *     operationId="get-employee-expense-limit",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $limit = $this->getLimitForEmployee($empNumber);
        return new EndpointResourceResult(
            ClaimExpenseLimitModel::class,
            $limit,
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
     *     path="/api/v2/claim/employees/{empNumber}/expense-limits/{id}",
     *     tags={"Claim/Expense Limits"},
     *     summary="Update Employee Expense Limit",
     *     operationId="update-employee-expense-limit",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="expenseTypeId", type="integer"),
     *             @OA\Property(property="monthlyLimit", type="number"),
     *             required={"expenseTypeId", "monthlyLimit"}
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success")
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $limit = $this->getLimitForEmployee($empNumber);
        $expenseTypeId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EXPENSE_TYPE_ID
        );
        if ($expenseTypeId !== $limit->getExpenseType()->getId()) {
            $existing = $this->getExpenseClaimLimitService()->getClaimExpenseLimit($empNumber, $expenseTypeId);
            if ($existing instanceof ClaimExpenseLimit) {
                throw $this->getInvalidParamException(self::PARAMETER_EXPENSE_TYPE_ID);
            }
        }
        $this->setLimitFields($limit);
        $this->getExpenseClaimLimitService()->saveClaimExpenseLimit($limit);

        return new EndpointResourceResult(
            ClaimExpenseLimitModel::class,
            $limit,
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
            new ParamRule(self::PARAMETER_EXPENSE_TYPE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_MONTHLY_LIMIT,
                new Rule(Rules::ZERO_OR_POSITIVE),
                new Rule(Rules::BETWEEN, [0, 9999999999.99])
            ),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/claim/employees/{empNumber}/expense-limits",
     *     tags={"Claim/Expense Limits"},
     *     summary="Delete Employee Expense Limits",
     *     operationId="delete-employee-expense-limits",
     *     @OA\PathParameter(name="empNumber", @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/requestBodies/DeleteRequestBody"),
     *     @OA\Response(response="200", ref="#/components/responses/DeleteResponse")
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $empNumber = $this->getEmpNumber();
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $ownedIds = [];
        foreach ($ids as $id) {
            $limit = $this->getExpenseClaimLimitService()->getClaimExpenseLimitById((int) $id);
            if ($limit instanceof ClaimExpenseLimit && $limit->getEmployee()->getEmpNumber() === $empNumber) {
                $ownedIds[] = $limit->getId();
            }
        }
        $this->throwRecordNotFoundExceptionIfEmptyIds($ownedIds);
        $this->getExpenseClaimLimitService()->deleteClaimExpenseLimitsByIds($ownedIds);
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
     * @param int $empNumber
     * @return ClaimExpenseLimit
     */
    private function getLimitForEmployee(int $empNumber): ClaimExpenseLimit
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $limit = $this->getExpenseClaimLimitService()->getClaimExpenseLimitById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($limit, ClaimExpenseLimit::class);
        if ($limit->getEmployee()->getEmpNumber() !== $empNumber) {
            throw $this->getRecordNotFoundException();
        }
        return $limit;
    }

    /**
     * @param ClaimExpenseLimit $limit
     */
    private function setLimitFields(ClaimExpenseLimit $limit): void
    {
        $expenseTypeId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EXPENSE_TYPE_ID
        );
        $expenseType = $this->getExpenseClaimLimitService()
            ->getClaimDao()
            ->getExpenseTypeById($expenseTypeId);
        if (!$expenseType instanceof ExpenseType) {
            throw $this->getInvalidParamException(self::PARAMETER_EXPENSE_TYPE_ID);
        }
        $limit->getDecorator()->setExpenseTypeById($expenseTypeId);

        $monthlyLimit = $this->getRequestParams()->getFloat(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_MONTHLY_LIMIT
        );
        $limit->setMonthlyLimit(number_format($monthlyLimit, 2, '.', ''));
    }
}
