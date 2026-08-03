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

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->addEmployeeOnCallFlag();
        $this->addTimesheetDayBreakDuration();
        $this->seedDefaultTimesheetProject();

        $groups = ['time', 'pim'];
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

    private function addEmployeeOnCallFlag(): void
    {
        if (!$this->getSchemaHelper()->columnExists('hs_hr_employee', 'on_call')) {
            $this->getSchemaHelper()->addColumn(
                'hs_hr_employee',
                'on_call',
                Types::BOOLEAN,
                ['Notnull' => true, 'Default' => false]
            );
        }
    }

    private function addTimesheetDayBreakDuration(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_timesheet_day'])
            && !$this->getSchemaHelper()->columnExists('ohrm_timesheet_day', 'break_duration')
        ) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_timesheet_day',
                'break_duration',
                Types::INTEGER,
                ['Notnull' => true, 'Default' => 0]
            );
        }
    }

    private function seedDefaultTimesheetProject(): void
    {
        $existingProjectId = $this->getConfigHelper()->getConfigValue('timesheet.default_project_id');
        if (!empty($existingProjectId)) {
            return;
        }

        $customerId = $this->getConnection()->createQueryBuilder()
            ->select('customer_id')
            ->from('ohrm_customer')
            ->where('name = :name')
            ->andWhere('is_deleted = :deleted')
            ->setParameter('name', 'Internal')
            ->setParameter('deleted', 0)
            ->executeQuery()
            ->fetchOne();

        if (!$customerId) {
            $this->getConnection()->createQueryBuilder()
                ->insert('ohrm_customer')
                ->values([
                    'name' => ':name',
                    'description' => ':description',
                    'is_deleted' => ':deleted',
                ])
                ->setParameter('name', 'Internal')
                ->setParameter('description', 'System customer for clock-based timesheets')
                ->setParameter('deleted', 0)
                ->executeQuery();

            $customerId = (int) $this->getConnection()->lastInsertId();
        }

        $projectId = $this->getConnection()->createQueryBuilder()
            ->select('project_id')
            ->from('ohrm_project')
            ->where('name = :name')
            ->andWhere('customer_id = :customer_id')
            ->andWhere('is_deleted = :deleted')
            ->setParameter('name', 'General Time')
            ->setParameter('customer_id', $customerId)
            ->setParameter('deleted', 0)
            ->executeQuery()
            ->fetchOne();

        if (!$projectId) {
            $this->getConnection()->createQueryBuilder()
                ->insert('ohrm_project')
                ->values([
                    'customer_id' => ':customer_id',
                    'name' => ':name',
                    'description' => ':description',
                    'is_deleted' => ':deleted',
                ])
                ->setParameter('customer_id', $customerId)
                ->setParameter('name', 'General Time')
                ->setParameter('description', 'Default project for weekly clock-based timesheets')
                ->setParameter('deleted', 0)
                ->executeQuery();

            $projectId = (int) $this->getConnection()->lastInsertId();
        }

        $activityId = $this->getConnection()->createQueryBuilder()
            ->select('activity_id')
            ->from('ohrm_project_activity')
            ->where('name = :name')
            ->andWhere('project_id = :project_id')
            ->andWhere('is_deleted = :deleted')
            ->setParameter('name', 'Regular Hours')
            ->setParameter('project_id', $projectId)
            ->setParameter('deleted', 0)
            ->executeQuery()
            ->fetchOne();

        if (!$activityId) {
            $this->getConnection()->createQueryBuilder()
                ->insert('ohrm_project_activity')
                ->values([
                    'project_id' => ':project_id',
                    'name' => ':name',
                    'is_deleted' => ':deleted',
                ])
                ->setParameter('project_id', $projectId)
                ->setParameter('name', 'Regular Hours')
                ->setParameter('deleted', 0)
                ->executeQuery();

            $activityId = (int) $this->getConnection()->lastInsertId();
        }

        $this->getConfigHelper()->setConfigValue('timesheet.default_project_id', (string) $projectId);
        $this->getConfigHelper()->setConfigValue('timesheet.default_activity_id', (string) $activityId);
    }
}
