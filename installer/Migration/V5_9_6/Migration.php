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

namespace OrangeHRM\Installer\Migration\V5_9_6;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->createInitiationFeeTable();
        $this->seedConfigValues();
        $this->seedMembership();
        // insertScreenPermissions creates ohrm_screen rows — do not also seedScreens()
        // or ScreenDao::getScreen() throws NonUniqueResultException (HTTP 500).
        $this->cleanupDuplicateScreens([
            'viewUfcwRemittanceReport',
            'viewUfcwRemittanceConfig',
        ]);

        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        if (
            !$this->screenExistsByActionUrl('viewUfcwRemittanceReport')
            || !$this->screenExistsByActionUrl('viewUfcwRemittanceConfig')
        ) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->cleanupDuplicateScreens([
            'viewUfcwRemittanceReport',
            'viewUfcwRemittanceConfig',
        ]);
        $this->insertMenus();

        if (is_file(__DIR__ . '/lang-string/time.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'time');
        }
    }

    public function getVersion(): string
    {
        return '5.9.6';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function createInitiationFeeTable(): void
    {
        if ($this->getSchemaHelper()->tableExists(['ohrm_ufcw_initiation_fee'])) {
            return;
        }

        $this->getSchemaHelper()->createTable('ohrm_ufcw_initiation_fee')
            ->addColumn('id', Types::INTEGER, ['Autoincrement' => true, 'Notnull' => true])
            ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
            ->addColumn('fee_required', Types::DECIMAL, ['Precision' => 12, 'Scale' => 2, 'Notnull' => true, 'Default' => 0])
            ->addColumn('amount_paid', Types::DECIMAL, ['Precision' => 12, 'Scale' => 2, 'Notnull' => true, 'Default' => 0])
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['emp_number'], 'ufcw_initiation_fee_emp_unique')
            ->create();

        $this->getSchemaHelper()->addForeignKey(
            'ohrm_ufcw_initiation_fee',
            new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'ufcw_initiation_fee_emp',
                ['onDelete' => 'CASCADE']
            )
        );
    }

    private function seedConfigValues(): void
    {
        $defaults = [
            'time.ufcw.dues_hourly_multiplier' => '0.6',
            'time.ufcw.dues_weekly_flat_fee' => '0.25',
            'time.ufcw.initiation_fee_full_time' => '40',
            'time.ufcw.initiation_fee_part_time' => '25',
            'time.ufcw.initiation_weekly_max_full_time' => '10',
            'time.ufcw.initiation_weekly_max_part_time' => '5',
            'time.ufcw.employer_name' => 'Timiskaming Funeral Cooperative',
            'time.ufcw.work_location' => 'Timiskaming Funeral Cooperative',
            'time.ufcw.work_location_code' => '7297',
            'time.ufcw.union_contacts' => 'Michael Bernier / Sabrina Qadir',
            'time.ufcw.membership_name' => 'UFCW Local 175',
            'time.ufcw.remittance_email' => 'remit@ufcw175.com',
            'time.ufcw.payroll_email' => '',
            'time.ufcw.cheque_payable_to' => 'UFCW Local 175',
            'time.ufcw.cheque_attention' => 'Secretary-Treasurer',
        ];

        foreach ($defaults as $name => $value) {
            $existing = $this->getConfigHelper()->getConfigValue($name);
            if ($existing === null || $existing === '') {
                $this->getConfigHelper()->setConfigValue($name, $value);
            }
        }
    }

    private function seedMembership(): void
    {
        $name = 'UFCW Local 175';
        $exists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_membership')
            ->where('name = :name')
            ->setParameter('name', $name)
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }
        $this->createQueryBuilder()
            ->insert('ohrm_membership')
            ->values(['name' => ':name'])
            ->setParameter('name', $name)
            ->executeQuery();
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
            ['UFCW Remittance', 'UFCW Monthly Remittance', 950],
            ['UFCW Remittance Settings', 'UFCW Remittance Settings', 960],
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
