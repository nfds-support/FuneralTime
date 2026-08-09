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

namespace OrangeHRM\Installer\Migration\V5_9_7;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->addEmployeeFuelForBankedTimeFlag();
        $this->addEmployeeHourlyRate();
        $this->createFuelBankedTimeRequestTable();
        $this->seedScreens();
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        $this->insertMenus();

        foreach (['time', 'pim', 'leave'] as $group) {
            if (is_file(__DIR__ . '/lang-string/' . $group . '.yaml')) {
                $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, $group);
            }
        }
    }

    public function getVersion(): string
    {
        return '5.9.7';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function addEmployeeFuelForBankedTimeFlag(): void
    {
        if (!$this->getSchemaHelper()->columnExists('hs_hr_employee', 'fuel_for_banked_time_enabled')) {
            $this->getSchemaHelper()->addColumn(
                'hs_hr_employee',
                'fuel_for_banked_time_enabled',
                Types::BOOLEAN,
                ['Notnull' => true, 'Default' => false]
            );
        }
    }

    private function addEmployeeHourlyRate(): void
    {
        if (!$this->getSchemaHelper()->columnExists('hs_hr_employee', 'hourly_rate')) {
            $this->getSchemaHelper()->addColumn(
                'hs_hr_employee',
                'hourly_rate',
                Types::DECIMAL,
                ['Precision' => 12, 'Scale' => 2, 'Notnull' => false]
            );
        }
    }

    private function createFuelBankedTimeRequestTable(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_fuel_banked_time_request'])) {
            return;
        }

        $this->getSchemaHelper()->createTable('ohrm_fuel_banked_time_request')
            ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
            ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
            ->addColumn('amount', Types::DECIMAL, ['Precision' => 12, 'Scale' => 2, 'Notnull' => true])
            ->addColumn('hourly_rate', Types::DECIMAL, ['Precision' => 12, 'Scale' => 2, 'Notnull' => true])
            ->addColumn('hours', Types::DECIMAL, ['Precision' => 10, 'Scale' => 4, 'Notnull' => true])
            ->addColumn('status', Types::STRING, ['Length' => 20, 'Notnull' => true])
            ->addColumn('comment', Types::STRING, ['Length' => 255, 'Notnull' => false])
            ->addColumn('created_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
            ->addColumn('updated_at', Types::DATETIME_MUTABLE, ['Notnull' => false])
            ->addColumn('actioned_by', Types::INTEGER, ['Notnull' => false])
            ->addColumn('actioned_at', Types::DATETIME_MUTABLE, ['Notnull' => false])
            ->setPrimaryKey(['id'])
            ->create();

        $this->getSchemaHelper()->addForeignKey(
            'ohrm_fuel_banked_time_request',
            new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'fuel_banked_time_emp',
                ['onDelete' => 'CASCADE']
            )
        );
        $this->getSchemaHelper()->addForeignKey(
            'ohrm_fuel_banked_time_request',
            new ForeignKeyConstraint(
                ['actioned_by'],
                'ohrm_user',
                ['id'],
                'fuel_banked_time_actioned_by',
                ['onDelete' => 'SET NULL']
            )
        );
    }

    private function seedScreens(): void
    {
        $moduleId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'time')
            ->executeQuery()
            ->fetchOne();
        if (!$moduleId) {
            return;
        }

        $screens = [
            ['My Fuel for Banked Time', 'viewMyFuelBankedTime'],
            ['Employee Fuel for Banked Time', 'viewEmployeeFuelBankedTime'],
        ];
        foreach ($screens as [$name, $url]) {
            $exists = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_screen')
                ->where('action_url = :url')
                ->setParameter('url', $url)
                ->executeQuery()
                ->fetchOne();
            if ($exists) {
                continue;
            }
            $this->createQueryBuilder()
                ->insert('ohrm_screen')
                ->values([
                    'name' => ':name',
                    'module_id' => ':moduleId',
                    'action_url' => ':url',
                ])
                ->setParameter('name', $name)
                ->setParameter('moduleId', $moduleId)
                ->setParameter('url', $url)
                ->executeQuery();
        }
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

        $menus = [
            ['Fuel for Banked Time', 'My Fuel for Banked Time', 970],
            ['Employee Fuel Requests', 'Employee Fuel for Banked Time', 980],
        ];
        foreach ($menus as [$title, $screenName, $order]) {
            $exists = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_menu_item')
                ->where('menu_title = :title')
                ->andWhere('parent_id = :parent')
                ->setParameter('title', $title)
                ->setParameter('parent', $timeMenuId)
                ->executeQuery()
                ->fetchOne();
            if ($exists) {
                continue;
            }
            $screenId = $this->createQueryBuilder()
                ->select('id')
                ->from('ohrm_screen')
                ->where('name = :name')
                ->setParameter('name', $screenName)
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
                    'parent_id' => $timeMenuId,
                    'level' => 2,
                    'order_hint' => $order,
                    'status' => 1,
                    'additional_params' => null,
                ])
                ->executeQuery();
        }
    }
}
