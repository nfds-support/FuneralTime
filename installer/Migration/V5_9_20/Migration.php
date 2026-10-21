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

namespace OrangeHRM\Installer\Migration\V5_9_20;

use OrangeHRM\Entity\WorkflowStateMachine;
use OrangeHRM\Installer\Util\V1\AbstractMigration;

/**
 * Assign Claim only creates INITIATED drafts. Admin Submit/Approve were incorrectly
 * jumping straight to PAID; payment remains a separate Admin Pay action on APPROVED.
 */
class Migration extends AbstractMigration
{
    public function up(): void
    {
        $this->updateClaimResultingState(
            'INITIATED',
            'ADMIN',
            WorkflowStateMachine::CLAIM_ACTION_SUBMIT,
            'SUBMITTED'
        );
        $this->updateClaimResultingState(
            'SUBMITTED',
            'ADMIN',
            WorkflowStateMachine::CLAIM_ACTION_APPROVE,
            'APPROVED'
        );
    }

    public function getVersion(): string
    {
        return '5.9.20';
    }

    private function updateClaimResultingState(
        string $state,
        string $role,
        int $action,
        string $resultingState
    ): void {
        $this->createQueryBuilder()
            ->update('ohrm_workflow_state_machine')
            ->set('ohrm_workflow_state_machine.resulting_state', ':resultingState')
            ->where('ohrm_workflow_state_machine.workflow = :workflow')
            ->andWhere('ohrm_workflow_state_machine.state = :state')
            ->andWhere('ohrm_workflow_state_machine.role = :role')
            ->andWhere('ohrm_workflow_state_machine.action = :action')
            ->setParameter('resultingState', $resultingState)
            ->setParameter('workflow', (string) WorkflowStateMachine::FLOW_CLAIM)
            ->setParameter('state', $state)
            ->setParameter('role', $role)
            ->setParameter('action', (string) $action)
            ->executeQuery();
    }
}
