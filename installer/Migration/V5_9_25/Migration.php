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

namespace OrangeHRM\Installer\Migration\V5_9_25;

use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        if (!$this->screenExistsByActionUrl('viewBankedTimePayout')) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->insertMenu();

        if (is_file(__DIR__ . '/lang-string/leave.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'leave');
        }
        $this->seedCanadianFrenchStrings();
    }

    public function getVersion(): string
    {
        return '5.9.25';
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

    private function insertMenu(): void
    {
        $leaveMenuId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Leave')
            ->executeQuery()
            ->fetchOne();
        if (!$leaveMenuId) {
            return;
        }

        $exists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('parent_id = :parent')
            ->setParameter('title', 'Banked Time Payout')
            ->setParameter('parent', $leaveMenuId)
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }

        $screenId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :url')
            ->setParameter('url', 'viewBankedTimePayout')
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
                'menu_title' => 'Banked Time Payout',
                'screen_id' => $screenId ?: null,
                'parent_id' => $leaveMenuId,
                'level' => 2,
                'order_hint' => 990,
                'status' => 1,
                'additional_params' => null,
            ])
            ->executeQuery();
    }

    private function seedCanadianFrenchStrings(): void
    {
        $frCaId = $this->getLanguageId('fr_CA');
        if ($frCaId === null) {
            return;
        }

        $translations = [
            'leave.banked_time_payout' => 'Paiement du temps mis en banque',
            'leave.banked_hours_available' => "Heures mises en banque disponibles",
            'leave.hours_to_pay_out' => 'Heures à payer',
            'leave.pay_out_banked_time' => 'Payer le temps mis en banque',
            'leave.banked_time_payout_hint' =>
                "Le paiement déduit les heures du solde de temps mis en banque de l'employé. Aucun montant en argent n'est calculé.",
            'leave.banked_time_payout_success' => 'Paiement du temps mis en banque enregistré',
            'leave.recent_banked_time_payouts' => 'Paiements récents du temps mis en banque',
            'leave.hours_deducted' => 'Heures déduites',
            'leave.balance_after_hours' => 'Solde après (heures)',
            'leave.paid_out_by' => 'Payé par',
        ];

        foreach ($translations as $unitKey => $value) {
            [$groupName, $unitId] = explode('.', $unitKey, 2);
            $langStringId = $this->getLangStringId($groupName, $unitId);
            if ($langStringId === null) {
                continue;
            }
            $this->upsertTranslation($langStringId, $frCaId, $value);
        }
    }

    private function getLanguageId(string $code): ?int
    {
        $id = $this->createQueryBuilder()
            ->select('ohrm_i18n_language.id')
            ->from('ohrm_i18n_language')
            ->where('ohrm_i18n_language.code = :code')
            ->setParameter('code', $code)
            ->executeQuery()
            ->fetchOne();
        return $id === false ? null : (int) $id;
    }

    private function getLangStringId(string $groupName, string $unitId): ?int
    {
        $groupId = $this->createQueryBuilder()
            ->select('ohrm_i18n_group.id')
            ->from('ohrm_i18n_group')
            ->where('ohrm_i18n_group.name = :name')
            ->setParameter('name', $groupName)
            ->executeQuery()
            ->fetchOne();
        if ($groupId === false) {
            return null;
        }
        $langStringId = $this->createQueryBuilder()
            ->select('ohrm_i18n_lang_string.id')
            ->from('ohrm_i18n_lang_string')
            ->where('ohrm_i18n_lang_string.group_id = :groupId')
            ->andWhere('ohrm_i18n_lang_string.unit_id = :unitId')
            ->setParameter('groupId', $groupId)
            ->setParameter('unitId', $unitId)
            ->executeQuery()
            ->fetchOne();
        return $langStringId === false ? null : (int) $langStringId;
    }

    private function upsertTranslation(int $langStringId, int $languageId, string $value): void
    {
        $existingId = $this->createQueryBuilder()
            ->select('ohrm_i18n_translate.id')
            ->from('ohrm_i18n_translate')
            ->where('ohrm_i18n_translate.lang_string_id = :langStringId')
            ->andWhere('ohrm_i18n_translate.language_id = :languageId')
            ->setParameter('langStringId', $langStringId)
            ->setParameter('languageId', $languageId)
            ->executeQuery()
            ->fetchOne();

        if ($existingId === false) {
            $this->createQueryBuilder()
                ->insert('ohrm_i18n_translate')
                ->values([
                    'lang_string_id' => ':langStringId',
                    'language_id' => ':languageId',
                    'value' => ':value',
                ])
                ->setParameter('langStringId', $langStringId)
                ->setParameter('languageId', $languageId)
                ->setParameter('value', $value)
                ->executeQuery();
            return;
        }

        $this->createQueryBuilder()
            ->update('ohrm_i18n_translate')
            ->set('ohrm_i18n_translate.value', ':value')
            ->where('ohrm_i18n_translate.id = :id')
            ->setParameter('value', $value)
            ->setParameter('id', $existingId)
            ->executeQuery();
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if (!$this->langStringHelper instanceof LangStringHelper) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }
}
