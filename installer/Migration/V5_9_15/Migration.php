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

namespace OrangeHRM\Installer\Migration\V5_9_15;

use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    public function up(): void
    {
        // leave.change was seeded in 3.3.3 against legacy paths that were never
        // migrated to Twig (unlike apply/approve/assign/reject/cancel). The event
        // is unused in current Leave mail processors — remove orphan rows so the
        // Email Templates admin API can list without missing-file errors.
        $this->removeOrphanLeaveChangeTemplates();
    }

    public function getVersion(): string
    {
        return '5.9.15';
    }

    private function removeOrphanLeaveChangeTemplates(): void
    {
        $emailId = $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_email WHERE name = ?',
            ['leave.change']
        )->fetchOne();
        if (!$emailId) {
            return;
        }

        $this->createQueryBuilder()
            ->delete('ohrm_email_template')
            ->where('email_id = :emailId')
            ->setParameter('emailId', $emailId)
            ->executeQuery();

        $this->createQueryBuilder()
            ->delete('ohrm_email')
            ->where('id = :emailId')
            ->setParameter('emailId', $emailId)
            ->executeQuery();
    }
}
