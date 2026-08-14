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

namespace OrangeHRM\Installer\Migration\V5_9_11;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->createTimesheetReminderTables();
        $this->seedDefaultConfig();
        // insertScreenPermissions creates ohrm_screen rows — do not also seedScreens()
        // or ScreenDao::getScreen() throws NonUniqueResultException (HTTP 500).
        $this->cleanupDuplicateScreens(['viewTimesheetReminderConfig']);

        if (!$this->dataGroupExists('apiv2_time_timesheet_reminder_config')) {
            $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        }
        if (!$this->screenExistsByActionUrl('viewTimesheetReminderConfig')) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->insertMenus();

        if (is_file(__DIR__ . '/lang-string/time.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'time');
        }
    }

    public function getVersion(): string
    {
        return '5.9.11';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function createTimesheetReminderTables(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_timesheet_reminder_config'])) {
            $this->getSchemaHelper()->createTable('ohrm_timesheet_reminder_config')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('enabled', Types::BOOLEAN, ['Notnull' => true, 'Default' => false])
                ->addColumn('weekday', Types::SMALLINT, ['Notnull' => true, 'Default' => 5])
                ->addColumn('send_time', Types::STRING, ['Length' => 5, 'Notnull' => true, 'Default' => '16:00'])
                ->addColumn('timezone', Types::STRING, ['Length' => 64, 'Notnull' => true, 'Default' => 'UTC'])
                ->setPrimaryKey(['id'])
                ->create();
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_timesheet_reminder_job_title'])) {
            $this->getSchemaHelper()->createTable('ohrm_timesheet_reminder_job_title')
                ->addColumn('config_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('job_title_id', Types::INTEGER, ['Notnull' => true])
                ->setPrimaryKey(['config_id', 'job_title_id'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_timesheet_reminder_job_title',
                new ForeignKeyConstraint(
                    ['config_id'],
                    'ohrm_timesheet_reminder_config',
                    ['id'],
                    'ts_reminder_jt_config_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_timesheet_reminder_job_title',
                new ForeignKeyConstraint(
                    ['job_title_id'],
                    'ohrm_job_title',
                    ['id'],
                    'ts_reminder_jt_title_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_timesheet_reminder_employee'])) {
            $this->getSchemaHelper()->createTable('ohrm_timesheet_reminder_employee')
                ->addColumn('config_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->setPrimaryKey(['config_id', 'emp_number'])
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_timesheet_reminder_employee',
                new ForeignKeyConstraint(
                    ['config_id'],
                    'ohrm_timesheet_reminder_config',
                    ['id'],
                    'ts_reminder_emp_config_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_timesheet_reminder_employee',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'ts_reminder_emp_emp_fk',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
    }

    private function seedDefaultConfig(): void
    {
        $exists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_timesheet_reminder_config')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }

        $this->createQueryBuilder()
            ->insert('ohrm_timesheet_reminder_config')
            ->values([
                'enabled' => ':enabled',
                'weekday' => ':weekday',
                'send_time' => ':sendTime',
                'timezone' => ':timezone',
            ])
            ->setParameter('enabled', 0)
            ->setParameter('weekday', 5)
            ->setParameter('sendTime', '16:00')
            ->setParameter('timezone', 'UTC')
            ->executeQuery();
    }

    private function cleanupDuplicateScreens(array $actionUrls): void
    {
        foreach ($actionUrls as $actionUrl) {
            $ids = $this->createQueryBuilder()
                ->select('s.id')
                ->from('ohrm_screen', 's')
                ->where('s.action_url = :url')
                ->setParameter('url', $actionUrl)
                ->orderBy('s.id', 'ASC')
                ->executeQuery()
                ->fetchFirstColumn();
            if (count($ids) <= 1) {
                continue;
            }
            $keepId = (int) array_shift($ids);
            foreach ($ids as $duplicateId) {
                $duplicateId = (int) $duplicateId;
                $this->createQueryBuilder()
                    ->update('ohrm_menu_item')
                    ->set('screen_id', ':keepId')
                    ->where('screen_id = :duplicateId')
                    ->setParameter('keepId', $keepId)
                    ->setParameter('duplicateId', $duplicateId)
                    ->executeQuery();
                $this->createQueryBuilder()
                    ->delete('ohrm_user_role_screen')
                    ->where('screen_id = :duplicateId')
                    ->setParameter('duplicateId', $duplicateId)
                    ->executeQuery();
                $this->createQueryBuilder()
                    ->delete('ohrm_screen')
                    ->where('id = :duplicateId')
                    ->setParameter('duplicateId', $duplicateId)
                    ->executeQuery();
            }
        }
    }

    private function dataGroupExists(string $name): bool
    {
        return (bool) $this->createQueryBuilder()
            ->select('dg.id')
            ->from('ohrm_data_group', 'dg')
            ->where('dg.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function screenExistsByActionUrl(string $actionUrl): bool
    {
        return (bool) $this->createQueryBuilder()
            ->select('s.id')
            ->from('ohrm_screen', 's')
            ->where('s.action_url = :url')
            ->setParameter('url', $actionUrl)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function insertMenus(): void
    {
        $timeMenuId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Time')
            ->executeQuery()
            ->fetchOne();
        if (!$timeMenuId) {
            return;
        }

        $exists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('parent_id = :parent')
            ->setParameter('title', 'Timesheet Reminders')
            ->setParameter('parent', $timeMenuId)
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }

        $screenId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('name = :name')
            ->setParameter('name', 'Timesheet Reminders')
            ->executeQuery()
            ->fetchOne();

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
                'menu_title' => 'Timesheet Reminders',
                'screen_id' => $screenId ?: null,
                'parent_id' => $timeMenuId,
                'level' => 2,
                'order_hint' => 970,
                'status' => 1,
                'additional_params' => null,
            ])
            ->executeQuery();
    }
}
