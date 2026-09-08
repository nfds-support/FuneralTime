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

namespace OrangeHRM\Installer\Migration\V5_9_13;

use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        foreach (['general', 'time'] as $group) {
            if (is_file(__DIR__ . '/lang-string/' . $group . '.yaml')) {
                $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, $group);
            }
        }

        $this->enableFrenchCanada();
        $this->copyFrenchTranslationsToCanadianFrench();
        $this->seedCanadianFrenchCustomTranslations();
        $this->hideOrangeHrmSocialMediaIcons();
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
    }

    public function getVersion(): string
    {
        return '5.9.13';
    }

    private function enableFrenchCanada(): void
    {
        $this->createQueryBuilder()
            ->update('ohrm_i18n_language')
            ->set('ohrm_i18n_language.enabled', ':enabled')
            ->set('ohrm_i18n_language.added', ':added')
            ->where('ohrm_i18n_language.code = :code')
            ->setParameter('enabled', true)
            ->setParameter('added', true)
            ->setParameter('code', 'fr_CA')
            ->executeQuery();

        // Ensure English remains available as a switch target
        $this->createQueryBuilder()
            ->update('ohrm_i18n_language')
            ->set('ohrm_i18n_language.enabled', ':enabled')
            ->set('ohrm_i18n_language.added', ':added')
            ->where('ohrm_i18n_language.code = :code')
            ->setParameter('enabled', true)
            ->setParameter('added', true)
            ->setParameter('code', 'en_US')
            ->executeQuery();
    }

    private function copyFrenchTranslationsToCanadianFrench(): void
    {
        $frId = $this->getLanguageId('fr');
        $frCaId = $this->getLanguageId('fr_CA');
        if ($frId === null || $frCaId === null) {
            return;
        }

        $this->getConnection()->executeStatement(
            'INSERT INTO ohrm_i18n_translate (lang_string_id, language_id, value)
             SELECT fr.lang_string_id, :frCaId, fr.value
             FROM ohrm_i18n_translate fr
             WHERE fr.language_id = :frId
               AND NOT EXISTS (
                   SELECT 1 FROM ohrm_i18n_translate existing
                   WHERE existing.lang_string_id = fr.lang_string_id
                     AND existing.language_id = :frCaId2
               )',
            [
                'frCaId' => $frCaId,
                'frId' => $frId,
                'frCaId2' => $frCaId,
            ]
        );
    }

    private function seedCanadianFrenchCustomTranslations(): void
    {
        $frCaId = $this->getLanguageId('fr_CA');
        if ($frCaId === null) {
            return;
        }

        $translations = [
            'time.apply_fuel_for_banked_time' => 'Demander le carburant pour le temps mis en banque',
            'time.fuel_amount' => 'Montant du carburant',
            'time.hourly_rate' => 'Taux horaire',
            'time.hours_to_deduct' => 'Heures à déduire',
            'time.banked_hours_available' => 'Heures mises en banque disponibles',
            'time.fuel_for_banked_time_disabled' =>
                'Le carburant pour le temps mis en banque n\'est pas activé pour votre dossier. Contactez votre administrateur.',
            'time.fuel_hourly_rate_missing' =>
                'Un taux horaire est requis sur votre dossier d\'emploi avant de pouvoir demander du carburant pour le temps mis en banque.',
            'time.ufcw_monthly_remittance' => 'Remise mensuelle UFCW',
            'time.prepared_by' => 'Préparé par',
            'time.update_initiation_balances' => 'Mettre à jour les soldes d\'initiation',
            'time.generate_ufcw_remittance' => 'Générer la remise UFCW',
            'time.download_xlsx' => 'Télécharger XLSX',
            'time.email_ufcw_remittance' => 'Envoyer la remise UFCW par courriel',
            'time.remittance_due' => 'Remise due',
            'time.regular_union_dues' => 'Cotisations syndicales régulières',
            'time.initiation_fees' => 'Frais d\'initiation',
            'time.total_remittance' => 'Remise totale',
            'general.language' => 'Langue',
            'general.english_united_states' => 'English (United States)',
            'general.french_canada' => 'Français (Canada)',
        ];

        foreach ($translations as $unitKey => $value) {
            [$groupName, $unitId] = explode('.', $unitKey, 2);
            $langStringId = $this->getLangStringId($groupName, $unitId);
            if ($langStringId === null) {
                continue;
            }
            $this->upsertTranslation((int) $langStringId, (int) $frCaId, $value);
        }
    }

    private function hideOrangeHrmSocialMediaIcons(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_theme'])) {
            return;
        }
        $this->createQueryBuilder()
            ->update('ohrm_theme')
            ->set('ohrm_theme.show_social_media_icons', ':show')
            ->setParameter('show', 0)
            ->executeQuery();
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

        $id = $this->createQueryBuilder()
            ->select('ohrm_i18n_lang_string.id')
            ->from('ohrm_i18n_lang_string')
            ->where('ohrm_i18n_lang_string.group_id = :groupId')
            ->andWhere('ohrm_i18n_lang_string.unit_id = :unitId')
            ->setParameter('groupId', $groupId)
            ->setParameter('unitId', $unitId)
            ->executeQuery()
            ->fetchOne();
        return $id === false ? null : (int) $id;
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
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }
}
