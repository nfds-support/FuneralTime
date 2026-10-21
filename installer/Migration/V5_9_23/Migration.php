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

namespace OrangeHRM\Installer\Migration\V5_9_23;

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->extendMembershipSchema();
        $this->seedUfcwMembershipDefaults();
        if (is_file(__DIR__ . '/lang-string/admin.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'admin');
        }
        if (is_file(__DIR__ . '/lang-string/pim.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'pim');
        }
        if (is_file(__DIR__ . '/lang-string/time.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'time');
        }
        $this->seedCanadianFrenchStrings();
    }

    public function getVersion(): string
    {
        return '5.9.23';
    }

    private function extendMembershipSchema(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_membership'])) {
            return;
        }

        $columns = [
            'dues_mode' => [
                'type' => Types::STRING,
                'options' => ['Length' => 20, 'Notnull' => true, 'Default' => 'flat'],
            ],
            'dues_hourly_multiplier' => [
                'type' => Types::DECIMAL,
                'options' => ['Precision' => 8, 'Scale' => 4, 'Notnull' => false, 'Default' => null],
            ],
            'dues_weekly_flat_fee' => [
                'type' => Types::DECIMAL,
                'options' => ['Precision' => 12, 'Scale' => 2, 'Notnull' => false, 'Default' => null],
            ],
            'initiation_fee_full_time' => [
                'type' => Types::DECIMAL,
                'options' => ['Precision' => 12, 'Scale' => 2, 'Notnull' => false, 'Default' => null],
            ],
            'initiation_fee_part_time' => [
                'type' => Types::DECIMAL,
                'options' => ['Precision' => 12, 'Scale' => 2, 'Notnull' => false, 'Default' => null],
            ],
            'initiation_weekly_max_full_time' => [
                'type' => Types::DECIMAL,
                'options' => ['Precision' => 12, 'Scale' => 2, 'Notnull' => false, 'Default' => null],
            ],
            'initiation_weekly_max_part_time' => [
                'type' => Types::DECIMAL,
                'options' => ['Precision' => 12, 'Scale' => 2, 'Notnull' => false, 'Default' => null],
            ],
        ];

        foreach ($columns as $name => $definition) {
            if ($this->getSchemaHelper()->columnExists('ohrm_membership', $name)) {
                continue;
            }
            $this->getSchemaHelper()->addColumn(
                'ohrm_membership',
                $name,
                $definition['type'],
                $definition['options']
            );
        }
    }

    private function seedUfcwMembershipDefaults(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_membership', 'dues_mode')) {
            return;
        }

        $membershipName = $this->createQueryBuilder()
            ->select('c.value')
            ->from('hs_hr_config', 'c')
            ->where('c.name = :name')
            ->setParameter('name', 'time.ufcw.membership_name')
            ->executeQuery()
            ->fetchOne();
        if ($membershipName === false || $membershipName === null || trim((string) $membershipName) === '') {
            $membershipName = 'UFCW Local 175';
        }

        $id = $this->createQueryBuilder()
            ->select('m.id')
            ->from('ohrm_membership', 'm')
            ->where('m.name = :name')
            ->setParameter('name', $membershipName)
            ->executeQuery()
            ->fetchOne();

        if ($id === false) {
            $this->createQueryBuilder()
                ->insert('ohrm_membership')
                ->values([
                    'name' => ':name',
                    'dues_mode' => ':duesMode',
                    'dues_hourly_multiplier' => ':multiplier',
                    'dues_weekly_flat_fee' => ':flatFee',
                    'initiation_fee_full_time' => ':initFt',
                    'initiation_fee_part_time' => ':initPt',
                    'initiation_weekly_max_full_time' => ':maxFt',
                    'initiation_weekly_max_part_time' => ':maxPt',
                ])
                ->setParameters([
                    'name' => $membershipName,
                    'duesMode' => 'rate_weekly',
                    'multiplier' => '0.6000',
                    'flatFee' => '0.25',
                    'initFt' => '40.00',
                    'initPt' => '25.00',
                    'maxFt' => '10.00',
                    'maxPt' => '5.00',
                ])
                ->executeQuery();
            return;
        }

        $this->createQueryBuilder()
            ->update('ohrm_membership')
            ->set('dues_mode', ':duesMode')
            ->set('dues_hourly_multiplier', ':multiplier')
            ->set('dues_weekly_flat_fee', ':flatFee')
            ->set('initiation_fee_full_time', ':initFt')
            ->set('initiation_fee_part_time', ':initPt')
            ->set('initiation_weekly_max_full_time', ':maxFt')
            ->set('initiation_weekly_max_part_time', ':maxPt')
            ->where('id = :id')
            ->andWhere("(dues_mode = 'flat' OR dues_hourly_multiplier IS NULL)")
            ->setParameters([
                'duesMode' => 'rate_weekly',
                'multiplier' => '0.6000',
                'flatFee' => '0.25',
                'initFt' => '40.00',
                'initPt' => '25.00',
                'maxFt' => '10.00',
                'maxPt' => '5.00',
                'id' => $id,
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
            'admin.dues_mode' => 'Mode de cotisation',
            'admin.dues_mode_flat' => 'Montant forfaitaire',
            'admin.dues_mode_rate_weekly' => 'Hebdomadaire selon le taux',
            'admin.dues_hourly_multiplier' => 'Multiplicateur du taux horaire',
            'admin.dues_weekly_flat_fee' => 'Frais hebdomadaires fixes',
            'admin.initiation_fee_full_time' => 'Frais d\'initiation (temps plein)',
            'admin.initiation_fee_part_time' => 'Frais d\'initiation (temps partiel)',
            'admin.initiation_weekly_max_full_time' => 'Max. hebdomadaire initiation (temps plein)',
            'admin.initiation_weekly_max_part_time' => 'Max. hebdomadaire initiation (temps partiel)',
            'admin.help_membership_dues_formula' =>
                'Cotisation hebdomadaire = (taux horaire × multiplicateur) + frais fixes, pour chaque semaine avec heures approuvées. L\'initiation est déduite sur les premières paies selon les plafonds ci-dessous.',
            'pim.membership_rate_weekly_hint' =>
                'Cotisations hebdomadaires = (taux horaire × multiplicateur) + frais fixes par semaine avec heures; initiation déduite sur les premières paies selon les paramètres de l\'adhésion.',
            'time.dues_fallback_hint' =>
                'Ces montants s\'appliquent seulement si l\'adhésion de l\'unité de négociation n\'a pas de formule (mode forfaitaire ou champs vides). La source principale est Admin → Adhésions.',
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
