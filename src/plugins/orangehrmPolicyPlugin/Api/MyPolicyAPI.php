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

namespace OrangeHRM\Policy\Api;

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
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Policy;
use OrangeHRM\Policy\Api\Model\EmployeePolicyModel;
use OrangeHRM\Policy\Api\Model\PolicyAcknowledgmentModel;
use OrangeHRM\Policy\Dto\EmployeePolicy;
use OrangeHRM\Policy\Traits\Service\PolicyServiceTrait;

class MyPolicyAPI extends Endpoint implements CollectionEndpoint
{
    use PolicyServiceTrait;
    use AuthUserTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_PENDING_ONLY = 'pendingOnly';
    public const PARAMETER_POLICY_ID = 'policyId';

    public function getAll(): EndpointResult
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        $this->throwRecordNotFoundExceptionIfNotExist($empNumber, Employee::class);
        /** @var Employee $employee */
        $employee = $this->getReference(Employee::class, $empNumber);
        $pendingOnly = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_PENDING_ONLY
        );
        $policies = $this->getPolicyService()->getPublishedPoliciesForEmployee($employee, $pendingOnly);
        $items = [];
        foreach ($policies as $policy) {
            $ack = $this->getPolicyService()->getPolicyDao()->getAcknowledgment(
                $policy->getId(),
                $empNumber
            );
            $items[] = new EmployeePolicy(
                $policy,
                $ack !== null,
                $ack ? $ack->getAcknowledgedAt()->format('Y-m-d H:i') : null
            );
        }
        return new EndpointCollectionResult(
            EmployeePolicyModel::class,
            $items,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($items)])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PENDING_ONLY, new Rule(Rules::BOOL_VAL))
            )
        );
    }

    public function create(): EndpointResult
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        $this->throwRecordNotFoundExceptionIfNotExist($empNumber, Employee::class);
        /** @var Employee $employee */
        $employee = $this->getReference(Employee::class, $empNumber);
        $policyId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_POLICY_ID);
        $policy = $this->getPolicyService()->getPolicyDao()->getPolicyById($policyId);
        $this->throwRecordNotFoundExceptionIfNotExist($policy, Policy::class);
        if ($policy->getStatus() !== Policy::STATUS_PUBLISHED) {
            throw $this->getBadRequestException('Policy is not published');
        }
        if (!$this->getPolicyService()->isEmployeeInAudience($policy, $employee)) {
            throw $this->getForbiddenException();
        }
        $ip = $this->getRequest()->getClientIp();
        $acknowledgment = $this->getPolicyService()->acknowledgePolicy($policy, $employee, $ip);
        return new EndpointResourceResult(PolicyAcknowledgmentModel::class, $acknowledgment);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_POLICY_ID, new Rule(Rules::POSITIVE))
        );
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
