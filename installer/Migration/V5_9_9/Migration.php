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

namespace OrangeHRM\Installer\Migration\V5_9_9;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->enhanceDisciplineSchema();
        $this->createMonthlyAssessmentSchema();
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        $this->insertMonthlyAssessmentMenus();

        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'discipline');
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'performance');
    }

    public function getVersion(): string
    {
        return '5.9.9';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function enhanceDisciplineSchema(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_discipline_case'])) {
            return;
        }

        if (!$this->getSchemaHelper()->columnExists('ohrm_discipline_case', 'complaint_source')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_discipline_case',
                'complaint_source',
                Types::STRING,
                ['Length' => 40, 'Notnull' => false, 'Default' => null]
            );
        }
        if (!$this->getSchemaHelper()->columnExists('ohrm_discipline_case', 'details')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_discipline_case',
                'details',
                Types::TEXT,
                ['Notnull' => false, 'Default' => null]
            );
        }
        if (!$this->getSchemaHelper()->columnExists('ohrm_discipline_case', 'manager_notes')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_discipline_case',
                'manager_notes',
                Types::TEXT,
                ['Notnull' => false, 'Default' => null]
            );
        }
        if (!$this->getSchemaHelper()->columnExists('ohrm_discipline_case', 'action_plan')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_discipline_case',
                'action_plan',
                Types::TEXT,
                ['Notnull' => false, 'Default' => null]
            );
        }
    }

    private function createMonthlyAssessmentSchema(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_monthly_assessment'])) {
            return;
        }

        $this->getSchemaHelper()->createTable('ohrm_monthly_assessment')
            ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
            ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
            ->addColumn('manager_emp_number', Types::INTEGER, ['Notnull' => false, 'Default' => null])
            ->addColumn('period_year', Types::INTEGER, ['Notnull' => true])
            ->addColumn('period_month', Types::INTEGER, ['Notnull' => true])
            ->addColumn('status', Types::STRING, ['Length' => 40, 'Notnull' => true, 'Default' => 'DRAFT'])
            ->addColumn('employee_overall_rating', Types::INTEGER, ['Notnull' => false, 'Default' => null])
            ->addColumn('employee_engagement_rating', Types::INTEGER, ['Notnull' => false, 'Default' => null])
            ->addColumn('employee_accomplishments', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('employee_improvements', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('employee_goals', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('employee_support_needed', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('employee_submitted_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
            ->addColumn('manager_overall_rating', Types::INTEGER, ['Notnull' => false, 'Default' => null])
            ->addColumn('manager_strengths', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('manager_improvements', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('manager_goals_support', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('manager_follow_up_notes', Types::TEXT, ['Notnull' => false, 'Default' => null])
            ->addColumn('manager_submitted_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
            ->addColumn('created_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
            ->addColumn('updated_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['emp_number', 'period_year', 'period_month'], 'monthly_assessment_emp_period')
            ->create();

        $this->getSchemaHelper()->addForeignKey(
            'ohrm_monthly_assessment',
            new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'monthly_assessment_employee',
                ['onDelete' => 'CASCADE']
            )
        );
        $this->getSchemaHelper()->addForeignKey(
            'ohrm_monthly_assessment',
            new ForeignKeyConstraint(
                ['manager_emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'monthly_assessment_manager',
                ['onDelete' => 'SET NULL']
            )
        );
    }

    private function insertMonthlyAssessmentMenus(): void
    {
        $performanceId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->setParameter('menuTitle', 'Manage Reviews')
            ->andWhere('level = :level')
            ->setParameter('level', 2)
            ->executeQuery()
            ->fetchOne();

        if (!$performanceId) {
            $performanceId = $this->createQueryBuilder()
                ->select('menu_item.id')
                ->from('ohrm_menu_item', 'menu_item')
                ->where('menu_item.menu_title = :menuTitle')
                ->setParameter('menuTitle', 'Performance')
                ->andWhere('level = :level')
                ->setParameter('level', 1)
                ->executeQuery()
                ->fetchOne();
        }

        if (!$performanceId) {
            return;
        }

        $myExists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('parent_id = :parent')
            ->setParameter('title', 'My Monthly Assessments')
            ->setParameter('parent', $performanceId)
            ->executeQuery()
            ->fetchOne();

        if (!$myExists) {
            $myScreenId = $this->getScreenId('My Monthly Assessments');
            $this->insertMenuItems(
                'My Monthly Assessments',
                $myScreenId,
                (int) $performanceId,
                3,
                400,
                1,
                null
            );
        }

        $teamExists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('parent_id = :parent')
            ->setParameter('title', 'Team Monthly Assessments')
            ->setParameter('parent', $performanceId)
            ->executeQuery()
            ->fetchOne();

        if (!$teamExists) {
            $teamScreenId = $this->getScreenId('Team Monthly Assessments');
            $this->insertMenuItems(
                'Team Monthly Assessments',
                $teamScreenId,
                (int) $performanceId,
                3,
                500,
                1,
                null
            );
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
