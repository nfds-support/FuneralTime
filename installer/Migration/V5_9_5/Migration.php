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

namespace OrangeHRM\Installer\Migration\V5_9_5;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->createPolicySchema();
        $this->seedPolicyModule();
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->insertPolicyMenus();
        $this->getConfigHelper()->setConfigValue('moodle.base_url', '');
        $this->getConfigHelper()->setConfigValue('moodle.webservice_token', '');
        $this->getConfigHelper()->setConfigValue('moodle.sync_enabled', 'false');

        $this->insertI18nGroup('policy');
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'policy');
    }

    public function getVersion(): string
    {
        return '5.9.5';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function createPolicySchema(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_policy'])) {
            $this->getSchemaHelper()->createTable('ohrm_policy')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('title', Types::STRING, ['Length' => 255, 'Notnull' => true])
                ->addColumn('version', Types::STRING, ['Length' => 40, 'Notnull' => true, 'Default' => '1.0'])
                ->addColumn('summary', Types::TEXT, ['Notnull' => false, 'Default' => null])
                ->addColumn('content', Types::TEXT, ['Notnull' => false, 'Default' => null])
                ->addColumn('document_url', Types::STRING, ['Length' => 512, 'Notnull' => false, 'Default' => null])
                ->addColumn('moodle_course_url', Types::STRING, ['Length' => 512, 'Notnull' => false, 'Default' => null])
                ->addColumn('audience_type', Types::STRING, ['Length' => 40, 'Notnull' => true, 'Default' => 'ALL'])
                ->addColumn('status', Types::STRING, ['Length' => 40, 'Notnull' => true, 'Default' => 'DRAFT'])
                ->addColumn('effective_date', Types::DATE_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('due_date', Types::DATE_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('published_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('created_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('updated_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('created_by', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_policy',
                new ForeignKeyConstraint(
                    ['created_by'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'policy_created_by',
                    ['onDelete' => 'SET NULL']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_policy_job_title'])) {
            $this->getSchemaHelper()->createTable('ohrm_policy_job_title')
                ->addColumn('policy_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('job_title_id', Types::INTEGER, ['Notnull' => true])
                ->setPrimaryKey(['policy_id', 'job_title_id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_policy_job_title',
                new ForeignKeyConstraint(
                    ['policy_id'],
                    'ohrm_policy',
                    ['id'],
                    'policy_job_title_policy',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_policy_job_title',
                new ForeignKeyConstraint(
                    ['job_title_id'],
                    'ohrm_job_title',
                    ['id'],
                    'policy_job_title_title',
                    ['onDelete' => 'CASCADE']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_policy_acknowledgment'])) {
            $this->getSchemaHelper()->createTable('ohrm_policy_acknowledgment')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('policy_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->addColumn('acknowledged_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('ip_address', Types::STRING, ['Length' => 45, 'Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_policy_acknowledgment',
                new ForeignKeyConstraint(
                    ['policy_id'],
                    'ohrm_policy',
                    ['id'],
                    'policy_ack_policy',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_policy_acknowledgment',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'policy_ack_employee',
                    ['onDelete' => 'CASCADE']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_moodle_cohort_map'])) {
            $this->getSchemaHelper()->createTable('ohrm_moodle_cohort_map')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('job_title_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('moodle_cohort_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('moodle_cohort_name', Types::STRING, ['Length' => 255, 'Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_moodle_cohort_map',
                new ForeignKeyConstraint(
                    ['job_title_id'],
                    'ohrm_job_title',
                    ['id'],
                    'moodle_cohort_map_job_title',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
    }

    private function seedPolicyModule(): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'policy')
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
                ->setParameter('name', 'policy')
                ->setParameter('status', 1)
                ->setParameter('display_name', 'Policy')
                ->executeQuery();
        }

        $moduleId = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'policy')
            ->executeQuery()
            ->fetchOne();

        $homeExists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module_default_page')
            ->where('module_id = :module_id')
            ->andWhere('user_role_id = :role')
            ->setParameter('module_id', $moduleId)
            ->setParameter('role', 1)
            ->executeQuery()
            ->fetchOne();

        if (!$homeExists && $moduleId) {
            $adminRoleId = $this->getDataGroupHelper()->getUserRoleIdByName('Admin');
            $essRoleId = $this->getDataGroupHelper()->getUserRoleIdByName('ESS');

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
                ->setParameter('action', 'policy/viewPolicies')
                ->setParameter('priority', 20)
                ->executeQuery();

            if ($essRoleId) {
                $this->getConnection()->createQueryBuilder()
                    ->insert('ohrm_module_default_page')
                    ->values([
                        'module_id' => ':module_id',
                        'user_role_id' => ':user_role_id',
                        'action' => ':action',
                        'priority' => ':priority',
                    ])
                    ->setParameter('module_id', $moduleId)
                    ->setParameter('user_role_id', $essRoleId)
                    ->setParameter('action', 'policy/viewMyPolicies')
                    ->setParameter('priority', 20)
                    ->executeQuery();
            }
        }
    }

    private function insertPolicyMenus(): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Policy')
            ->executeQuery()
            ->fetchOne();

        if ($exists) {
            return;
        }

        $moduleScreenId = $this->getScreenId('View Policy Module');
        $this->insertMenuItems('Policy', $moduleScreenId, null, 1, 1300, 1, '{"icon":"buzz"}');

        $policyMenuItemId = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Policy')
            ->executeQuery()
            ->fetchOne();

        $this->insertMenuItems('Policies', $this->getScreenId('Policies'), (int) $policyMenuItemId, 2, 100, 1, null);
        $this->insertMenuItems('My Policies', $this->getScreenId('My Policies'), (int) $policyMenuItemId, 2, 200, 1, null);
        $this->insertMenuItems('Learning', $this->getScreenId('Learning'), (int) $policyMenuItemId, 2, 300, 1, null);
        $this->insertMenuItems('Moodle Settings', $this->getScreenId('Moodle Settings'), (int) $policyMenuItemId, 2, 400, 1, null);
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
                ->values([
                    'name' => ':name',
                    'title' => ':title',
                ])
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
