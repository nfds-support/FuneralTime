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

namespace OrangeHRM\Installer\Migration\V5_9_1;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->extendTimesheetSchema();
        $this->createDisciplineSchema();
        $this->seedDisciplineModule();
        $this->updateDefaultTheme();
        $this->setSundayTimesheetDefault();

        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->insertDisciplineMenus();

        $this->insertI18nGroup('discipline');
        $groups = ['discipline', 'time', 'general', 'leave', 'performance'];
        foreach ($groups as $group) {
            if (is_file(__DIR__ . '/lang-string/' . $group . '.yaml')) {
                $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, $group);
            }
        }
    }

    public function getVersion(): string
    {
        return '5.9.1';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function extendTimesheetSchema(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_timesheet_item', 'start_time')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_timesheet_item',
                'start_time',
                Types::TIME_MUTABLE,
                ['Notnull' => false, 'Default' => null]
            );
        }
        if (!$this->getSchemaHelper()->columnExists('ohrm_timesheet_item', 'end_time')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_timesheet_item',
                'end_time',
                Types::TIME_MUTABLE,
                ['Notnull' => false, 'Default' => null]
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_timesheet_day'])) {
            $this->getSchemaHelper()->createTable('ohrm_timesheet_day')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('timesheet_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('date', Types::DATE_MUTABLE, ['Notnull' => true])
                ->addColumn('on_call', Types::BOOLEAN, ['Notnull' => true, 'Default' => false])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_timesheet_day',
                new ForeignKeyConstraint(
                    ['timesheet_id'],
                    'ohrm_timesheet',
                    ['timesheet_id'],
                    'timesheet_day_timesheet_id',
                    ['onDelete' => 'CASCADE']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_timesheet_deduction'])) {
            $this->getSchemaHelper()->createTable('ohrm_timesheet_deduction')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('timesheet_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('date', Types::DATE_MUTABLE, ['Notnull' => true])
                ->addColumn('start_time', Types::TIME_MUTABLE, ['Notnull' => true])
                ->addColumn('end_time', Types::TIME_MUTABLE, ['Notnull' => true])
                ->addColumn('duration', Types::INTEGER, ['Notnull' => true, 'Default' => 0])
                ->addColumn('reason', Types::STRING, ['Length' => 255, 'Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_timesheet_deduction',
                new ForeignKeyConstraint(
                    ['timesheet_id'],
                    'ohrm_timesheet',
                    ['timesheet_id'],
                    'timesheet_deduction_timesheet_id',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
    }

    private function createDisciplineSchema(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_discipline_case'])) {
            $this->getSchemaHelper()->createTable('ohrm_discipline_case')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->addColumn('reported_by', Types::INTEGER, ['Notnull' => false, 'Default' => null])
                ->addColumn('case_type', Types::STRING, ['Length' => 40, 'Notnull' => true])
                ->addColumn('category', Types::STRING, ['Length' => 100, 'Notnull' => false, 'Default' => null])
                ->addColumn('subject', Types::STRING, ['Length' => 255, 'Notnull' => true])
                ->addColumn('description', Types::TEXT, ['Notnull' => false, 'Default' => null])
                ->addColumn('incident_date', Types::DATE_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('status', Types::STRING, ['Length' => 40, 'Notnull' => true, 'Default' => 'OPEN'])
                ->addColumn('severity', Types::STRING, ['Length' => 40, 'Notnull' => false, 'Default' => null])
                ->addColumn('action_taken', Types::TEXT, ['Notnull' => false, 'Default' => null])
                ->addColumn('created_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('updated_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->setPrimaryKey(['id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_discipline_case',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'discipline_case_employee',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_discipline_case',
                new ForeignKeyConstraint(
                    ['reported_by'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'discipline_case_reported_by',
                    ['onDelete' => 'SET NULL']
                )
            );
        }
    }

    private function seedDisciplineModule(): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'discipline')
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
                ->setParameter('name', 'discipline')
                ->setParameter('status', 1)
                ->setParameter('display_name', 'Discipline')
                ->executeQuery();
        }

        $moduleId = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'discipline')
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
            $supervisorRoleId = $this->getDataGroupHelper()->getUserRoleIdByName('Supervisor');

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
                ->setParameter('action', 'discipline/viewDisciplineCases')
                ->setParameter('priority', 20)
                ->executeQuery();

            if ($supervisorRoleId) {
                $this->getConnection()->createQueryBuilder()
                    ->insert('ohrm_module_default_page')
                    ->values([
                        'module_id' => ':module_id',
                        'user_role_id' => ':user_role_id',
                        'action' => ':action',
                        'priority' => ':priority',
                    ])
                    ->setParameter('module_id', $moduleId)
                    ->setParameter('user_role_id', $supervisorRoleId)
                    ->setParameter('action', 'discipline/viewDisciplineCases')
                    ->setParameter('priority', 10)
                    ->executeQuery();
            }

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
                    ->setParameter('action', 'discipline/viewMyDisciplineCases')
                    ->setParameter('priority', 20)
                    ->executeQuery();
            }
        }
    }

    private function insertDisciplineMenus(): void
    {
        $exists = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Discipline')
            ->executeQuery()
            ->fetchOne();

        if ($exists) {
            return;
        }

        $moduleScreenId = $this->getScreenId('View Discipline Module');
        $this->insertMenuItems('Discipline', $moduleScreenId, null, 1, 1250, 1, '{"icon":"performance"}');

        $disciplineMenuItemId = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Discipline')
            ->executeQuery()
            ->fetchOne();

        $casesScreenId = $this->getScreenId('Discipline Cases');
        $this->insertMenuItems('Cases', $casesScreenId, (int) $disciplineMenuItemId, 2, 100, 1, null);

        $myCasesScreenId = $this->getScreenId('My Discipline Cases');
        $this->insertMenuItems('My Cases', $myCasesScreenId, (int) $disciplineMenuItemId, 2, 200, 1, null);
    }

    private function updateDefaultTheme(): void
    {
        $variables = json_encode([
            'primaryColor' => '#0F766E',
            'primaryFontColor' => '#FFFFFF',
            'secondaryColor' => '#1E3A5F',
            'secondaryFontColor' => '#FFFFFF',
            'primaryGradientStartColor' => '#0D9488',
            'primaryGradientEndColor' => '#115E59',
        ]);

        $this->getConnection()->createQueryBuilder()
            ->update('ohrm_theme')
            ->set('variables', ':variables')
            ->where('theme_name = :theme')
            ->setParameter('variables', $variables)
            ->setParameter('theme', 'default')
            ->executeQuery();
    }

    private function setSundayTimesheetDefault(): void
    {
        $periodSet = $this->getConfigHelper()->getConfigValue('timesheet_period_set');
        if ($periodSet === 'Yes') {
            return;
        }

        // Prefer Sunday (7) → Saturday for new installs that have not defined a period yet.
        $xml = '<TimesheetPeriod><PeriodType>Weekly</PeriodType><ClassName>WeeklyTimesheetPeriod</ClassName><StartDate>7</StartDate><Heading>Week</Heading></TimesheetPeriod>';
        $this->getConfigHelper()->setConfigValue('timesheet_period_and_start_date', $xml);
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
