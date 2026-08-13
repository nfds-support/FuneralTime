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

namespace OrangeHRM\Installer\Migration\V5_9_2;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->extendEmployeePayFields();
        $this->extendKpiRubric();
        $this->createPayrollPeriodTable();
        $this->createLeaveEntitlementTransactionTable();
        $this->seedEntitlementTypes();
        $this->seedBankedTimeLeaveType();
        $this->seedPayrollMenuAndPermissions();

        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');

        $this->insertReportMenus();

        $groups = ['pim', 'time', 'leave', 'performance'];
        foreach ($groups as $group) {
            if (is_file(__DIR__ . '/lang-string/' . $group . '.yaml')) {
                $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, $group);
            }
        }
    }

    public function getVersion(): string
    {
        return '5.9.2';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function extendEmployeePayFields(): void
    {
        $columns = [
            'pay_type' => ['type' => Types::STRING, 'options' => ['Length' => 20, 'Notnull' => false, 'Default' => null]],
            'contracted_hours_per_week' => ['type' => Types::DECIMAL, 'options' => ['Precision' => 5, 'Scale' => 2, 'Notnull' => false, 'Default' => null]],
            'overtime_threshold_hours' => ['type' => Types::DECIMAL, 'options' => ['Precision' => 5, 'Scale' => 2, 'Notnull' => false, 'Default' => 44]],
            'fd_license_class' => ['type' => Types::STRING, 'options' => ['Length' => 20, 'Notnull' => false, 'Default' => 'none']],
            'fd_license_number' => ['type' => Types::STRING, 'options' => ['Length' => 50, 'Notnull' => false, 'Default' => null]],
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->getSchemaHelper()->columnExists('hs_hr_employee', $name)) {
                $this->getSchemaHelper()->addColumn('hs_hr_employee', $name, $definition['type'], $definition['options']);
            }
        }
    }

    private function extendKpiRubric(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_kpi', 'rating_rubric')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_kpi',
                'rating_rubric',
                Types::JSON,
                ['Notnull' => false, 'Default' => null]
            );
        }
    }

    private function createPayrollPeriodTable(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_payroll_period'])) {
            return;
        }

        $this->getSchemaHelper()->createTable('ohrm_payroll_period')
            ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
            ->addColumn('period_number', Types::INTEGER, ['Notnull' => true])
            ->addColumn('start_date', Types::DATE_MUTABLE, ['Notnull' => true])
            ->addColumn('end_date', Types::DATE_MUTABLE, ['Notnull' => true])
            ->addColumn('label', Types::STRING, ['Length' => 100, 'Notnull' => false, 'Default' => null])
            ->setPrimaryKey(['id'])
            ->create();
    }

    private function createLeaveEntitlementTransactionTable(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_leave_entitlement_transaction'])) {
            $this->getSchemaHelper()->createTable('ohrm_leave_entitlement_transaction')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                // Must match ohrm_leave_type.id (INT UNSIGNED) or MySQL rejects the FK (errno 1215).
                ->addColumn('leave_type_id', Types::INTEGER, ['Unsigned' => true, 'Notnull' => true])
                ->addColumn('entitlement_id', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->addColumn('transaction_type', Types::STRING, ['Length' => 20, 'Notnull' => true])
                ->addColumn('days', Types::DECIMAL, ['Precision' => 8, 'Scale' => 4, 'Notnull' => true])
                ->addColumn('balance_after', Types::DECIMAL, ['Precision' => 8, 'Scale' => 4, 'Notnull' => false, 'Default' => null])
                ->addColumn('note', Types::STRING, ['Length' => 255, 'Notnull' => false, 'Default' => null])
                ->addColumn('created_by', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->addColumn('created_at', Types::DATETIMETZ_MUTABLE, ['Notnull' => true])
                ->setPrimaryKey(['id'])
                ->create();
        } else {
            // Repair half-applied installs that created the table with a signed leave_type_id.
            $column = $this->getSchemaHelper()->getTableColumn(
                'ohrm_leave_entitlement_transaction',
                'leave_type_id'
            );
            if ($column !== null && !$column->getUnsigned()) {
                $this->getSchemaHelper()->changeColumn(
                    'ohrm_leave_entitlement_transaction',
                    'leave_type_id',
                    ['Unsigned' => true]
                );
            }
        }

        $existingFkNames = array_map(
            static fn (ForeignKeyConstraint $fk) => $fk->getName(),
            $this->getSchemaHelper()->getSchemaManager()->listTableForeignKeys(
                'ohrm_leave_entitlement_transaction'
            )
        );

        if (!in_array('leave_entitlement_txn_emp_number', $existingFkNames, true)) {
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_leave_entitlement_transaction',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'leave_entitlement_txn_emp_number',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
        if (!in_array('leave_entitlement_txn_leave_type', $existingFkNames, true)) {
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_leave_entitlement_transaction',
                new ForeignKeyConstraint(
                    ['leave_type_id'],
                    'ohrm_leave_type',
                    ['id'],
                    'leave_entitlement_txn_leave_type',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
    }

    private function seedEntitlementTypes(): void
    {
        $existing = $this->createQueryBuilder()
            ->select('t.id', 't.name')
            ->from('ohrm_leave_entitlement_type', 't')
            ->executeQuery()
            ->fetchAllAssociative();
        $names = array_map('strtolower', array_column($existing, 'name'));

        if (!in_array('deduction', $names, true)) {
            $this->createQueryBuilder()
                ->insert('ohrm_leave_entitlement_type')
                ->values([
                    'id' => ':id',
                    'name' => ':name',
                    'is_editable' => ':editable',
                ])
                ->setParameter('id', 2)
                ->setParameter('name', 'Deduction')
                ->setParameter('editable', 1)
                ->executeQuery();
        }
        if (!in_array('correction', $names, true)) {
            $this->createQueryBuilder()
                ->insert('ohrm_leave_entitlement_type')
                ->values([
                    'id' => ':id',
                    'name' => ':name',
                    'is_editable' => ':editable',
                ])
                ->setParameter('id', 3)
                ->setParameter('name', 'Correction')
                ->setParameter('editable', 1)
                ->executeQuery();
        }
    }

    private function seedBankedTimeLeaveType(): void
    {
        $existing = $this->createQueryBuilder()
            ->select('lt.id')
            ->from('ohrm_leave_type', 'lt')
            ->where('lt.name = :name')
            ->setParameter('name', 'Banked Time')
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            $this->getConfigHelper()->setConfigValue('leave.banked_time_type_id', (string) $existing);
            return;
        }

        $this->createQueryBuilder()
            ->insert('ohrm_leave_type')
            ->values([
                'name' => ':name',
                'deleted' => ':deleted',
                'exclude_in_reports_if_no_entitlement' => ':exclude',
                'operational_country_id' => ':country',
            ])
            ->setParameter('name', 'Banked Time')
            ->setParameter('deleted', 0)
            ->setParameter('exclude', 0)
            ->setParameter('country', null)
            ->executeQuery();

        $id = (int) $this->getConnection()->lastInsertId();
        $this->getConfigHelper()->setConfigValue('leave.banked_time_type_id', (string) $id);
    }

    private function seedPayrollMenuAndPermissions(): void
    {
        $moduleId = $this->createQueryBuilder()
            ->select('m.id')
            ->from('ohrm_module', 'm')
            ->where('m.name = :name')
            ->setParameter('name', 'time')
            ->executeQuery()
            ->fetchOne();

        if (!$moduleId) {
            return;
        }

        $existing = $this->createQueryBuilder()
            ->select('s.id')
            ->from('ohrm_screen', 's')
            ->where('s.action_url = :url')
            ->setParameter('url', 'viewPayrollFillSheetReport')
            ->executeQuery()
            ->fetchOne();

        if (!$existing) {
            $this->createQueryBuilder()
                ->insert('ohrm_screen')
                ->values([
                    'name' => ':name',
                    'module_id' => ':moduleId',
                    'action_url' => ':url',
                ])
                ->setParameter('name', 'Payroll Fill Sheet Report')
                ->setParameter('moduleId', $moduleId)
                ->setParameter('url', 'viewPayrollFillSheetReport')
                ->executeQuery();
        }

        $leaveModuleId = $this->createQueryBuilder()
            ->select('m.id')
            ->from('ohrm_module', 'm')
            ->where('m.name = :name')
            ->setParameter('name', 'leave')
            ->executeQuery()
            ->fetchOne();

        if ($leaveModuleId) {
            $existingLeave = $this->createQueryBuilder()
                ->select('s.id')
                ->from('ohrm_screen', 's')
                ->where('s.action_url = :url')
                ->setParameter('url', 'viewLeaveEntitlementHistoryReport')
                ->executeQuery()
                ->fetchOne();

            if (!$existingLeave) {
                $this->createQueryBuilder()
                    ->insert('ohrm_screen')
                    ->values([
                        'name' => ':name',
                        'module_id' => ':moduleId',
                        'action_url' => ':url',
                    ])
                    ->setParameter('name', 'Leave Entitlement History Report')
                    ->setParameter('moduleId', $leaveModuleId)
                    ->setParameter('url', 'viewLeaveEntitlementHistoryReport')
                    ->executeQuery();
            }

            $existingMyLeave = $this->createQueryBuilder()
                ->select('s.id')
                ->from('ohrm_screen', 's')
                ->where('s.action_url = :url')
                ->setParameter('url', 'viewMyLeaveEntitlementHistoryReport')
                ->executeQuery()
                ->fetchOne();

            if (!$existingMyLeave) {
                $this->createQueryBuilder()
                    ->insert('ohrm_screen')
                    ->values([
                        'name' => ':name',
                        'module_id' => ':moduleId',
                        'action_url' => ':url',
                    ])
                    ->setParameter('name', 'My Leave Entitlement History Report')
                    ->setParameter('moduleId', $leaveModuleId)
                    ->setParameter('url', 'viewMyLeaveEntitlementHistoryReport')
                    ->executeQuery();
            }
        }
    }

    private function insertReportMenus(): void
    {
        $timeMenuId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :time')
            ->andWhere('level = 1')
            ->setParameter('time', 'Time')
            ->executeQuery()
            ->fetchOne();

        $timeReportsParentId = null;
        if ($timeMenuId) {
            $timeReportsParentId = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :reports')
                ->andWhere('parent_id = :parent')
                ->setParameter('reports', 'Reports')
                ->setParameter('parent', $timeMenuId)
                ->executeQuery()
                ->fetchOne();
        }

        if ($timeReportsParentId) {
            $exists = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :title')
                ->andWhere('parent_id = :parent')
                ->setParameter('title', 'Payroll Fill Sheet')
                ->setParameter('parent', $timeReportsParentId)
                ->executeQuery()
                ->fetchOne();
            if (!$exists) {
                $this->insertMenuItem(
                    'Payroll Fill Sheet',
                    $this->getScreenId('Payroll Fill Sheet Report'),
                    (int) $timeReportsParentId,
                    3,
                    400
                );
            }
        }

        $leaveMenuId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :leave')
            ->andWhere('level = 1')
            ->setParameter('leave', 'Leave')
            ->executeQuery()
            ->fetchOne();

        $leaveReportsParentId = null;
        if ($leaveMenuId) {
            $leaveReportsParentId = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :reports')
                ->andWhere('parent_id = :parent')
                ->setParameter('reports', 'Reports')
                ->setParameter('parent', $leaveMenuId)
                ->executeQuery()
                ->fetchOne();
            if (!$leaveReportsParentId) {
                $leaveReportsParentId = $leaveMenuId;
            }
        }

        if ($leaveReportsParentId) {
            $exists = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :title')
                ->andWhere('parent_id = :parent')
                ->setParameter('title', 'Entitlement History')
                ->setParameter('parent', $leaveReportsParentId)
                ->executeQuery()
                ->fetchOne();
            if (!$exists) {
                $this->insertMenuItem(
                    'Entitlement History',
                    $this->getScreenId('Leave Entitlement History Report'),
                    (int) $leaveReportsParentId,
                    $leaveReportsParentId == $leaveMenuId ? 2 : 3,
                    500
                );
            }

            $existsMy = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :title')
                ->andWhere('parent_id = :parent')
                ->setParameter('title', 'My Entitlement History')
                ->setParameter('parent', $leaveReportsParentId)
                ->executeQuery()
                ->fetchOne();
            if (!$existsMy) {
                $this->insertMenuItem(
                    'My Entitlement History',
                    $this->getScreenId('My Leave Entitlement History Report'),
                    (int) $leaveReportsParentId,
                    $leaveReportsParentId == $leaveMenuId ? 2 : 3,
                    600
                );
            }
        }
    }

    private function getScreenId(string $name): ?int
    {
        $id = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('name = :name')
            ->setParameter('name', $name)
            ->executeQuery()
            ->fetchOne();

        return $id === false || $id === null ? null : (int) $id;
    }

    private function insertMenuItem(
        string $menuTitle,
        ?int $screenId,
        int $parentId,
        int $level,
        int $orderHint
    ): void {
        $this->createQueryBuilder()
            ->insert('ohrm_menu_item')
            ->values([
                'menu_title' => ':menu_title',
                'screen_id' => ':screen_id',
                'parent_id' => ':parent_id',
                'level' => ':level',
                'order_hint' => ':order_hint',
                'status' => ':status',
                'additional_params' => ':additional_params',
            ])
            ->setParameters([
                'menu_title' => $menuTitle,
                'screen_id' => $screenId,
                'parent_id' => $parentId,
                'level' => $level,
                'order_hint' => $orderHint,
                'status' => 1,
                'additional_params' => null,
            ])
            ->executeQuery();
    }
}
