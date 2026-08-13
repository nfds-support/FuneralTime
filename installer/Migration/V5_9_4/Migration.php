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

namespace OrangeHRM\Installer\Migration\V5_9_4;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->extendEmployeeMileageRate();
        $this->extendExpenseTypeReportColumn();
        $this->extendExpenseQuantityKm();
        $this->createClaimExpenseLimitTable();
        $this->seedExpenseTypes();
        // insertScreenPermissions creates ohrm_screen rows — do not also seedScreens()
        // or ScreenDao::getScreen() throws NonUniqueResultException (HTTP 500).
        $this->cleanupDuplicateScreens([
            'viewEmployeeExpenseLimits',
            'viewMonthlyExpenseReport',
        ]);

        if (!$this->dataGroupExists('apiv2_claim_employee_expense_limits')) {
            $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        }
        if (!$this->screenExistsByActionUrl('viewEmployeeExpenseLimits')) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->insertMenus();

        foreach (['claim', 'pim'] as $group) {
            if (is_file(__DIR__ . '/lang-string/' . $group . '.yaml')) {
                $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, $group);
            }
        }
    }

    public function getVersion(): string
    {
        return '5.9.4';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function extendEmployeeMileageRate(): void
    {
        if (!$this->getSchemaHelper()->columnExists('hs_hr_employee', 'mileage_reimbursement_rate')) {
            $this->getSchemaHelper()->addColumn(
                'hs_hr_employee',
                'mileage_reimbursement_rate',
                Types::DECIMAL,
                ['Precision' => 8, 'Scale' => 4, 'Notnull' => false, 'Default' => 0.55]
            );
        }
    }

    private function extendExpenseTypeReportColumn(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_expense_type', 'report_column')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_expense_type',
                'report_column',
                Types::STRING,
                ['Length' => 20, 'Notnull' => false, 'Default' => null]
            );
        }
    }

    private function extendExpenseQuantityKm(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_expense', 'quantity_km')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_expense',
                'quantity_km',
                Types::DECIMAL,
                ['Precision' => 10, 'Scale' => 2, 'Notnull' => false, 'Default' => null]
            );
        }
    }

    private function createClaimExpenseLimitTable(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_claim_expense_limit'])) {
            return;
        }

        $this->getSchemaHelper()->createTable('ohrm_claim_expense_limit')
            ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
            ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
            ->addColumn('expense_type_id', Types::INTEGER, ['Notnull' => true])
            ->addColumn('monthly_limit', Types::DECIMAL, ['Precision' => 12, 'Scale' => 2, 'Notnull' => true])
            ->setPrimaryKey(['id'])
            ->create();

        $this->getSchemaHelper()->addForeignKey(
            'ohrm_claim_expense_limit',
            new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'claim_expense_limit_emp',
                ['onDelete' => 'CASCADE']
            )
        );
        $this->getSchemaHelper()->addForeignKey(
            'ohrm_claim_expense_limit',
            new ForeignKeyConstraint(
                ['expense_type_id'],
                'ohrm_expense_type',
                ['id'],
                'claim_expense_limit_type',
                ['onDelete' => 'CASCADE']
            )
        );
    }

    private function seedExpenseTypes(): void
    {
        $types = [
            ['Mileage', 'mileage', 'Mileage reimbursement (KM × employee rate)'],
            ['Gas / Fuel', 'gas', '55300 - Gas fuel'],
            ['Vehicle Expense', 'vehicle', '55700 - Vehicle Expense'],
            ['Wellness Allowance', 'wellness', '61300 - Wellness Allowance'],
            ['Cellulaire', 'cellular', '63310 - Cellulaire'],
            ['Office Expense', 'office', '63600 - Office Expense'],
            ['Meal', 'meal', '69100 - Meal'],
            ['Travelling Expense', 'travelling', '69120 - Travelling Expense'],
            ['Other', 'other', 'Other expense (justify in note)'],
        ];

        $adminUserId = $this->createQueryBuilder()
            ->select('u.id')
            ->from('ohrm_user', 'u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        foreach ($types as [$name, $column, $description]) {
            $existingId = $this->createQueryBuilder()
                ->select('t.id')
                ->from('ohrm_expense_type', 't')
                ->where('t.name = :name')
                ->orWhere('t.report_column = :column')
                ->setParameter('name', $name)
                ->setParameter('column', $column)
                ->executeQuery()
                ->fetchOne();

            if ($existingId) {
                $this->createQueryBuilder()
                    ->update('ohrm_expense_type')
                    ->set('report_column', ':column')
                    ->set('status', ':status')
                    ->where('id = :id')
                    ->setParameter('column', $column)
                    ->setParameter('status', 1)
                    ->setParameter('id', $existingId)
                    ->executeQuery();
                continue;
            }

            $values = [
                'name' => ':name',
                'description' => ':description',
                'status' => ':status',
                'is_deleted' => ':deleted',
                'report_column' => ':column',
            ];
            $qb = $this->createQueryBuilder()->insert('ohrm_expense_type')->values($values)
                ->setParameter('name', $name)
                ->setParameter('description', $description)
                ->setParameter('status', 1)
                ->setParameter('deleted', 0)
                ->setParameter('column', $column);

            if ($adminUserId) {
                $qb->setValue('added_by', ':addedBy')->setParameter('addedBy', $adminUserId);
            }
            $qb->executeQuery();
        }
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
        $claimMenuId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Claim')
            ->executeQuery()
            ->fetchOne();
        if (!$claimMenuId) {
            return;
        }

        $menus = [
            ['Expense Limits', 'viewEmployeeExpenseLimits', 850],
            ['Monthly Expense Report', 'viewMonthlyExpenseReport', 900],
        ];
        foreach ($menus as [$title, $actionUrl, $order]) {
            $exists = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :title')
                ->andWhere('parent_id = :parent')
                ->setParameter('title', $title)
                ->setParameter('parent', $claimMenuId)
                ->executeQuery()
                ->fetchOne();
            if ($exists) {
                continue;
            }
            $screenId = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_screen')
                ->where('action_url = :url')
                ->setParameter('url', $actionUrl)
                ->orderBy('id', 'ASC')
                ->setMaxResults(1)
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
                    'menu_title' => $title,
                    'screen_id' => $screenId ?: null,
                    'parent_id' => $claimMenuId,
                    'level' => 2,
                    'order_hint' => $order,
                    'status' => 1,
                    'additional_params' => null,
                ])
                ->executeQuery();
        }
    }
}
