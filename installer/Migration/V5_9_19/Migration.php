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

namespace OrangeHRM\Installer\Migration\V5_9_19;

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_user'])) {
            return;
        }
        if ($this->getSchemaHelper()->columnExists('ohrm_user', 'ui_language')) {
            return;
        }
        $this->getSchemaHelper()->addColumn(
            'ohrm_user',
            'ui_language',
            Types::STRING,
            ['Length' => 20, 'Notnull' => false, 'Default' => null]
        );
    }

    public function getVersion(): string
    {
        return '5.9.19';
    }
}
