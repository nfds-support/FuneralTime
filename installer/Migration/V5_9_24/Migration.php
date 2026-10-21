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

namespace OrangeHRM\Installer\Migration\V5_9_24;

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        if (
            $this->getSchemaHelper()->tableExists(['ohrm_timesheet_day'])
            && !$this->getSchemaHelper()->columnExists('ohrm_timesheet_day', 'removal_hours')
        ) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_timesheet_day',
                'removal_hours',
                Types::INTEGER,
                ['Notnull' => true, 'Default' => 0]
            );
        }

        if (is_file(__DIR__ . '/lang-string/time.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'time');
        }
        $this->seedCanadianFrenchStrings();
    }

    public function getVersion(): string
    {
        return '5.9.24';
    }

    private function seedCanadianFrenchStrings(): void
    {
        $frCaId = $this->getLanguageId('fr_CA');
        if ($frCaId === null) {
            return;
        }

        $translations = [
            'time.removal_hours' => "Heures d'enlèvement",
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
