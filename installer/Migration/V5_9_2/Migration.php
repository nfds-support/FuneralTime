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
        $this->createUnionTables();
        $this->addUnionScopeToHolidays();
        $this->seedUnionModule();

        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->insertUnionMenus();

        $this->insertI18nGroup('union');
        foreach (['union', 'leave', 'pim'] as $group) {
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
        return $this->langStringHelper ??= new LangStringHelper($this->getConnection());
    }

    private function createUnionTables(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_union'])) {
            $this->getSchemaHelper()->createTable('ohrm_union')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('name', Types::STRING, ['Length' => 100, 'Notnull' => true])
                ->addColumn('description', Types::TEXT, ['Notnull' => false, 'Default' => null])
                ->addColumn('is_active', Types::BOOLEAN, ['Notnull' => true, 'Default' => true])
                ->setPrimaryKey(['id'])
                ->create();
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_employee_union'])) {
            $this->getSchemaHelper()->createTable('ohrm_employee_union')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->addColumn('union_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('seniority_date', Types::DATE_MUTABLE, ['Notnull' => true])
                ->addColumn('seniority_rank', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->addColumn('is_primary', Types::BOOLEAN, ['Notnull' => true, 'Default' => true])
                ->addColumn('start_date', Types::DATE_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('end_date', Types::DATE_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_employee_union',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'employee_union_emp_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_employee_union',
                new ForeignKeyConstraint(
                    ['union_id'],
                    'ohrm_union',
                    ['id'],
                    'employee_union_union_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_union_leave_rule'])) {
            $this->getSchemaHelper()->createTable('ohrm_union_leave_rule')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('union_id', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->addColumn('leave_type_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('min_years', Types::INTEGER, ['Notnull' => true, 'Default' => 0])
                ->addColumn('max_years', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->addColumn('entitlement_days', Types::DECIMAL, ['Precision' => 8, 'Scale' => 2, 'Notnull' => true])
                ->addColumn('note', Types::STRING, ['Length' => 255, 'Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_union_leave_rule',
                new ForeignKeyConstraint(
                    ['union_id'],
                    'ohrm_union',
                    ['id'],
                    'union_leave_rule_union_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_union_leave_rule',
                new ForeignKeyConstraint(
                    ['leave_type_id'],
                    'ohrm_leave_type',
                    ['id'],
                    'union_leave_rule_leave_type_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
    }

    private function addUnionScopeToHolidays(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_holiday', 'union_id')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_holiday',
                'union_id',
                Types::INTEGER,
                ['Notnull' => false, 'Default' => null]
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_holiday',
                new ForeignKeyConstraint(
                    ['union_id'],
                    'ohrm_union',
                    ['id'],
                    'holiday_union_fk',
                    ['onDelete' => 'SET NULL']
                )
            );
        }
    }

    private function seedUnionModule(): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'union')
            ->executeQuery()
            ->fetchOne();

        if (!$exists) {
            $this->getConnection()->createQueryBuilder()
                ->insert('ohrm_module')
                ->values([
                    'name' => ':name',
                    'status' => ':status',
                    'display_name' => ':display_name',
                ])
                ->setParameter('name', 'union')
                ->setParameter('status', 1)
                ->setParameter('display_name', 'Union')
                ->executeQuery();
        }

        $moduleId = $this->getDataGroupHelper()->getModuleIdByName('union');
        $adminRoleId = $this->getDataGroupHelper()->getUserRoleIdByName('Admin');
        $homeExists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module_default_page')
            ->where('module_id = :module_id')
            ->andWhere('user_role_id = :role')
            ->setParameter('module_id', $moduleId)
            ->setParameter('role', $adminRoleId)
            ->executeQuery()
            ->fetchOne();

        if (!$homeExists && $moduleId) {
            $this->getConnection()->createQueryBuilder()
                ->insert('ohrm_module_default_page')
                ->values([
                    'module_id' => ':module_id',
                    'user_role_id' => ':user_role_id',
                    'action' => ':action',
                    'priority' => ':priority',
                ])
                ->setParameter('module_id', $moduleId)
                ->setParameter('user_role_id', $adminRoleId)
                ->setParameter('action', 'union/viewUnions')
                ->setParameter('priority', 20)
                ->executeQuery();
        }
    }

    private function insertUnionMenus(): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Union')
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }

        $moduleScreenId = $this->getScreenId('View Union Module');
        $this->insertMenuItems('Union', $moduleScreenId, null, 1, 1150, 1, '{"icon":"admin"}');
        $unionMenuId = (int) $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Union')
            ->executeQuery()
            ->fetchOne();

        $this->insertMenuItems('Unions', $this->getScreenId('Unions'), $unionMenuId, 2, 100, 1, null);
        $this->insertMenuItems(
            'Leave Rules',
            $this->getScreenId('Union Leave Rules'),
            $unionMenuId,
            2,
            200,
            1,
            null
        );
        $this->insertMenuItems(
            'Employee Unions',
            $this->getScreenId('Employee Unions'),
            $unionMenuId,
            2,
            300,
            1,
            null
        );
    }

    private function insertI18nGroup(string $name): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_i18n_group')
            ->where('name = :name')
            ->setParameter('name', $name)
            ->executeQuery()
            ->fetchOne();
        if (!$exists) {
            $this->getConnection()->createQueryBuilder()
                ->insert('ohrm_i18n_group')
                ->values(['name' => ':name', 'title' => ':title'])
                ->setParameter('name', $name)
                ->setParameter('title', ucfirst($name))
                ->executeQuery();
        }
    }

    private function getScreenId(string $name): ?int
    {
        $id = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('name = :name')
            ->setParameter('name', $name)
            ->executeQuery()
            ->fetchOne();
        return $id === false ? null : (int) $id;
    }

    private function insertMenuItems(
        string $menuTitle,
        ?int $screenId,
        ?int $parentId,
        int $level,
        int $orderHint,
        int $status,
        ?string $additionalParams
    ): void {
        $this->getConnection()->createQueryBuilder()
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
                'status' => $status,
                'additional_params' => $additionalParams,
            ])
            ->executeQuery();
    }
}
