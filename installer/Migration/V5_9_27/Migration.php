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

namespace OrangeHRM\Installer\Migration\V5_9_27;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    public function up(): void
    {
        $this->createScheduleTables();
        $this->seedShiftTypes();
        if (!$this->dataGroupExists('time_schedule')) {
            $this->getDataGroupHelper()->insertDataGroupPermissions(__DIR__ . '/permission/data_group.yaml');
        }
        if (!$this->dataGroupExists('apiv2_time_schedule_week')) {
            $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        }
    }

    public function getVersion(): string
    {
        return '5.9.27';
    }

    private function createScheduleTables(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_schedule_shift_type'])) {
            $this->getSchemaHelper()->createTable('ohrm_schedule_shift_type')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('name', Types::STRING, ['Length' => 100, 'Notnull' => true])
                ->addColumn('color', Types::STRING, ['Length' => 20, 'Notnull' => true])
                ->addColumn('sort_order', Types::INTEGER, ['Notnull' => true, 'Default' => 0])
                ->setPrimaryKey(['id'])
                ->create();
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_schedule_assignment'])) {
            $this->getSchemaHelper()->createTable('ohrm_schedule_assignment')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->addColumn('date', Types::DATE_MUTABLE, ['Notnull' => true])
                ->addColumn('shift_type_id', Types::INTEGER, ['Notnull' => true])
                ->setPrimaryKey(['id'])
                ->addUniqueIndex(['emp_number', 'date'], 'schedule_assignment_emp_date')
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_schedule_assignment',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'schedule_assignment_emp',
                    ['onDelete' => 'CASCADE']
                )
            );
            $this->getSchemaHelper()->addForeignKey(
                'ohrm_schedule_assignment',
                new ForeignKeyConstraint(
                    ['shift_type_id'],
                    'ohrm_schedule_shift_type',
                    ['id'],
                    'schedule_assignment_shift_type',
                    ['onDelete' => 'RESTRICT']
                )
            );
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_schedule_on_call'])) {
            $this->getSchemaHelper()->createTable('ohrm_schedule_on_call')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
                ->addColumn('date', Types::DATE_MUTABLE, ['Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->setPrimaryKey(['id'])
                ->addUniqueIndex(['date'], 'schedule_on_call_date')
                ->create();

            $this->getSchemaHelper()->addForeignKey(
                'ohrm_schedule_on_call',
                new ForeignKeyConstraint(
                    ['emp_number'],
                    'hs_hr_employee',
                    ['emp_number'],
                    'schedule_on_call_emp',
                    ['onDelete' => 'CASCADE']
                )
            );
        }
    }

    private function seedShiftTypes(): void
    {
        $count = (int) $this->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from('ohrm_schedule_shift_type', 't')
            ->executeQuery()
            ->fetchOne();
        if ($count > 0) {
            return;
        }

        $seeds = [
            ['Office', '#90CAF9', 1],
            ['Funeral', '#EF9A9A', 2],
            ['Removal', '#FFE082', 3],
            ['Off', '#E0E0E0', 4],
        ];
        foreach ($seeds as [$name, $color, $sortOrder]) {
            $this->createQueryBuilder()
                ->insert('ohrm_schedule_shift_type')
                ->values([
                    'name' => ':name',
                    'color' => ':color',
                    'sort_order' => ':sortOrder',
                ])
                ->setParameter('name', $name)
                ->setParameter('color', $color)
                ->setParameter('sortOrder', $sortOrder)
                ->executeQuery();
        }
    }

    private function dataGroupExists(string $name): bool
    {
        $id = $this->createQueryBuilder()
            ->select('ohrm_data_group.id')
            ->from('ohrm_data_group')
            ->where('ohrm_data_group.name = :name')
            ->setParameter('name', $name)
            ->executeQuery()
            ->fetchOne();
        return $id !== false;
    }
}
