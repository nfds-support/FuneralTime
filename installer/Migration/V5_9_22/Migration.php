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

namespace OrangeHRM\Installer\Migration\V5_9_22;

use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->cleanupDuplicateScreens(['viewPayrollPeriods']);
        if (!$this->screenExistsByActionUrl('viewPayrollPeriods')) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->insertMenu();
        if (is_file(__DIR__ . '/lang-string/time.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'time');
        }
        $this->seedCanadianFrenchStrings();
        $this->backfillMissingTitles();
    }

    public function getVersion(): string
    {
        return '5.9.22';
    }

    private function backfillMissingTitles(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_payroll_period'])) {
            return;
        }
        $this->getConnection()->executeStatement(
            "UPDATE ohrm_payroll_period SET label = CONCAT('P', period_number) WHERE label IS NULL OR label = ''"
        );
    }

    private function insertMenu(): void
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

        $exists = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('parent_id = :parent')
            ->setParameter('title', 'Payroll Periods')
            ->setParameter('parent', $timeMenuId)
            ->executeQuery()
            ->fetchOne();
        if ($exists) {
            return;
        }

        $screenId = $this->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :url')
            ->setParameter('url', 'viewPayrollPeriods')
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
                'menu_title' => 'Payroll Periods',
                'screen_id' => $screenId ?: null,
                'parent_id' => $timeMenuId,
                'level' => 2,
                'order_hint' => 960,
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
            'time.payroll_periods' => 'Périodes de paie',
            'time.period_title' => 'Titre de la période',
            'time.add_payroll_period' => 'Ajouter une période de paie',
            'time.edit_payroll_period' => 'Modifier la période de paie',
            'time.payroll_period_fiscal_hint' =>
                'Ajoutez les périodes à l\'avance (généralement 2 semaines). L\'exercice va du 1er octobre au 30 septembre. Exemples de titres : P1, P20.',
            'time.payroll_period_manage_hint' =>
                'Sélectionnez une période enregistrée pour générer le rapport. Gérez les périodes sous',
            'time.period_title_number_required' =>
                'Le titre doit inclure un numéro (ex. P20)',
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

    private function cleanupDuplicateScreens(array $actionUrls): void
    {
        foreach ($actionUrls as $actionUrl) {
            $ids = $this->createQueryBuilder()
                ->select('ohrm_screen.id')
                ->from('ohrm_screen')
                ->where('ohrm_screen.action_url = :url')
                ->setParameter('url', $actionUrl)
                ->orderBy('ohrm_screen.id', 'ASC')
                ->executeQuery()
                ->fetchFirstColumn();
            if (count($ids) < 2) {
                continue;
            }
            $keepId = array_shift($ids);
            foreach ($ids as $dupId) {
                $this->createQueryBuilder()
                    ->update('ohrm_menu_item')
                    ->set('ohrm_menu_item.screen_id', ':keep')
                    ->where('ohrm_menu_item.screen_id = :dup')
                    ->setParameter('keep', $keepId)
                    ->setParameter('dup', $dupId)
                    ->executeQuery();
                $this->createQueryBuilder()
                    ->delete('ohrm_user_role_screen')
                    ->where('ohrm_user_role_screen.screen_id = :dup')
                    ->setParameter('dup', $dupId)
                    ->executeQuery();
                $this->createQueryBuilder()
                    ->delete('ohrm_screen')
                    ->where('ohrm_screen.id = :dup')
                    ->setParameter('dup', $dupId)
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
