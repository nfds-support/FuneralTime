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

namespace OrangeHRM\Installer\Migration\V5_9_26;

use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    public const READ_ONLY_ROLE_NAME = 'ReadOnly';

    public function up(): void
    {
        $this->seedReadOnlyRole();
    }

    public function getVersion(): string
    {
        return '5.9.26';
    }

    /**
     * Create the ReadOnly system role with Admin's read visibility and no
     * create/update/delete grants on any screen or data group (API).
     */
    private function seedReadOnlyRole(): void
    {
        $adminId = $this->createQueryBuilder()
            ->select('r.id')
            ->from('ohrm_user_role', 'r')
            ->where('r.name = :name')
            ->setParameter('name', 'Admin')
            ->executeQuery()
            ->fetchOne();
        if ($adminId === false) {
            return;
        }
        $adminId = (int) $adminId;

        $roleId = $this->createQueryBuilder()
            ->select('r.id')
            ->from('ohrm_user_role', 'r')
            ->where('r.name = :name')
            ->setParameter('name', self::READ_ONLY_ROLE_NAME)
            ->executeQuery()
            ->fetchOne();

        if ($roleId === false) {
            $this->createQueryBuilder()
                ->insert('ohrm_user_role')
                ->values([
                    'name' => ':name',
                    'display_name' => ':displayName',
                    'is_assignable' => ':isAssignable',
                    'is_predefined' => ':isPredefined',
                ])
                ->setParameters([
                    'name' => self::READ_ONLY_ROLE_NAME,
                    'displayName' => 'Read Only',
                    'isAssignable' => 1,
                    'isPredefined' => 1,
                ])
                ->executeQuery();
            $roleId = (int) $this->getConnection()->lastInsertId();
        } else {
            $roleId = (int) $roleId;
            $this->createQueryBuilder()
                ->update('ohrm_user_role')
                ->set('display_name', ':displayName')
                ->set('is_assignable', ':isAssignable')
                ->set('is_predefined', ':isPredefined')
                ->where('id = :id')
                ->setParameters([
                    'displayName' => 'Read Only',
                    'isAssignable' => 1,
                    'isPredefined' => 1,
                    'id' => $roleId,
                ])
                ->executeQuery();
        }

        // Screens: same read visibility as Admin, never any write bit.
        $this->getConnection()->executeStatement(
            'DELETE FROM ohrm_user_role_screen WHERE user_role_id = ?',
            [$roleId]
        );
        $this->getConnection()->executeStatement(
            'INSERT INTO ohrm_user_role_screen (user_role_id, screen_id, can_read, can_create, can_update, can_delete) '
            . 'SELECT ?, screen_id, can_read, 0, 0, 0 FROM ohrm_user_role_screen WHERE user_role_id = ?',
            [$roleId, $adminId]
        );

        // Data groups (REST APIs): same read visibility as Admin, never any write bit.
        $this->getConnection()->executeStatement(
            'DELETE FROM ohrm_user_role_data_group WHERE user_role_id = ?',
            [$roleId]
        );
        $this->getConnection()->executeStatement(
            'INSERT INTO ohrm_user_role_data_group (user_role_id, data_group_id, can_read, can_create, can_update, can_delete, self) '
            . 'SELECT ?, data_group_id, can_read, 0, 0, 0, self FROM ohrm_user_role_data_group WHERE user_role_id = ?',
            [$roleId, $adminId]
        );
    }
}
