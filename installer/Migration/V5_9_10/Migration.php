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

namespace OrangeHRM\Installer\Migration\V5_9_10;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->createEmployeeCommissionTable();

        if (!$this->dataGroupExists('apiv2_claim_employee_commissions')) {
            $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        }
        if (!$this->screenExistsByActionUrl('viewAssignCommissions')) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->insertMenus();
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'claim');
    }

    public function getVersion(): string
    {
        return '5.9.10';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function createEmployeeCommissionTable(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_employee_commission'])) {
            return;
        }

        $this->getSchemaHelper()->createTable('ohrm_employee_commission')
            ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
            ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
            ->addColumn('sale_date', Types::DATE_MUTABLE, ['Notnull' => true])
            ->addColumn('amount', Types::DECIMAL, ['Precision' => 12, 'Scale' => 2, 'Notnull' => true])
            ->addColumn('description', Types::STRING, ['Length' => 1000, 'Notnull' => false, 'Default' => null])
            ->addColumn('assigned_by', Types::INTEGER, ['Notnull' => false, 'Default' => null])
            ->addColumn('created_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
            ->setPrimaryKey(['id'])
            ->addIndex(['emp_number', 'sale_date'], 'employee_commission_emp_date')
            ->create();

        $this->getSchemaHelper()->addForeignKey(
            'ohrm_employee_commission',
            new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'employee_commission_emp',
                ['onDelete' => 'CASCADE']
            )
        );
        $this->getSchemaHelper()->addForeignKey(
            'ohrm_employee_commission',
            new ForeignKeyConstraint(
                ['assigned_by'],
                'ohrm_user',
                ['id'],
                'employee_commission_assigned_by',
                ['onDelete' => 'SET NULL']
            )
        );
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
            ['Assign Commissions', 'viewAssignCommissions', 800],
            ['My Commissions', 'viewMyCommissions', 810],
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
