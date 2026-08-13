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

namespace OrangeHRM\Installer\Migration\V5_9_8;

use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        $this->insertMenus();

        if (is_file(__DIR__ . '/lang-string/admin.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'admin');
        }
    }

    public function getVersion(): string
    {
        return '5.9.8';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function insertMenus(): void
    {
        $adminId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->setParameter('menuTitle', 'Admin')
            ->andWhere('level = :level')
            ->setParameter('level', 1)
            ->executeQuery()
            ->fetchOne();
        if (!$adminId) {
            return;
        }

        $configurationId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->setParameter('menuTitle', 'Configuration')
            ->andWhere('level = :level')
            ->setParameter('level', 2)
            ->andWhere('parent_id = :parentId')
            ->setParameter('parentId', $adminId)
            ->executeQuery()
            ->fetchOne();
        if (!$configurationId) {
            return;
        }

        $exists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('parent_id = :parent')
            ->setParameter('title', 'Import from OrangeHRM')
            ->setParameter('parent', $configurationId)
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }

        $screenId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :url')
            ->setParameter('url', 'viewOrangeHRMDataImport')
            ->orderBy('id', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        $this->createQueryBuilder()
            ->insert('ohrm_menu_item')
            ->values([
                'menu_title' => ':menuTitle',
                'screen_id' => ':screenId',
                'parent_id' => ':parentId',
                'level' => ':level',
                'order_hint' => ':orderHint',
                'status' => ':status',
            ])
            ->setParameter('menuTitle', 'Import from OrangeHRM')
            ->setParameter('screenId', $screenId ?: null)
            ->setParameter('parentId', $configurationId)
            ->setParameter('level', 3)
            ->setParameter('orderHint', 1100)
            ->setParameter('status', 1)
            ->executeQuery();
    }
}
