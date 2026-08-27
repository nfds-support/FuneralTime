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

namespace OrangeHRM\Claim\Api\Traits;

use OrangeHRM\Claim\Traits\Service\ClaimServiceTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\ClaimRequest;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\WorkflowStateMachine;
use OrangeHRM\Core\Api\V2\Exception\EndpointExceptionTrait;

trait ClaimRequestAPIHelperTrait
{
    use UserRoleManagerTrait;
    use EndpointExceptionTrait;
    use ClaimServiceTrait;

    /**
     * @param int $action
     * @param ClaimRequest $claimRequest
     * @return bool
     */
    public function isActionAllowed(int $action, ClaimRequest $claimRequest): bool
    {
        if (!$this->isClaimWorkflowActionAllowed($action, $claimRequest)) {
            throw $this->getForbiddenException();
        }
        return true;
    }

    /**
     * Expense and attachment mutations are allowed when the user can Submit
     * (draft / rejected) or Approve (submitted claim under review).
     *
     * @param ClaimRequest $claimRequest
     */
    public function assertClaimExpensesMutable(ClaimRequest $claimRequest): void
    {
        if (
            $this->isClaimWorkflowActionAllowed(WorkflowStateMachine::CLAIM_ACTION_SUBMIT, $claimRequest)
            || $this->isClaimWorkflowActionAllowed(WorkflowStateMachine::CLAIM_ACTION_APPROVE, $claimRequest)
        ) {
            return;
        }
        throw $this->getForbiddenException();
    }

    /**
     * @param int $action
     * @param ClaimRequest $claimRequest
     * @return bool
     */
    private function isClaimWorkflowActionAllowed(int $action, ClaimRequest $claimRequest): bool
    {
        return $this->getUserRoleManager()->isActionAllowed(
            WorkflowStateMachine::FLOW_CLAIM,
            $claimRequest->getStatus(),
            $action,
            [],
            [],
            [Employee::class => $claimRequest->getEmployee()->getEmpNumber()]
        );
    }

    /**
     * @param int $requestId
     * @return ClaimRequest
     */
    public function getClaimRequest(int $requestId): ClaimRequest
    {
        $claimRequest = $this->getClaimService()->getClaimDao()
            ->getClaimRequestById($requestId);
        return $this->assertClaimRequestAccessible($claimRequest);
    }

    /**
     * Same as getClaimRequest() but reads the claim request under a pessimistic write lock.
     * Must be called inside an active transaction.
     *
     * @param int $requestId
     * @return ClaimRequest
     */
    public function getClaimRequestForUpdate(int $requestId): ClaimRequest
    {
        $claimRequest = $this->getClaimService()->getClaimDao()
            ->getClaimRequestByIdForUpdate($requestId);
        return $this->assertClaimRequestAccessible($claimRequest);
    }

    /**
     * @param ClaimRequest|null $claimRequest
     * @return ClaimRequest
     */
    private function assertClaimRequestAccessible(?ClaimRequest $claimRequest): ClaimRequest
    {
        $this->throwRecordNotFoundExceptionIfNotExist($claimRequest, ClaimRequest::class);
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($claimRequest->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }
        return $claimRequest;
    }
}
