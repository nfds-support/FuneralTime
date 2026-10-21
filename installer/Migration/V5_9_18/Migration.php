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

namespace OrangeHRM\Installer\Migration\V5_9_18;

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->addVisibleToEmployeeColumn();
        $this->grantEmployeeSharedCaseAccess();
        if (is_file(__DIR__ . '/lang-string/discipline.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'discipline');
        }
        $this->seedCanadianFrenchStrings();
    }

    public function getVersion(): string
    {
        return '5.9.18';
    }

    private function addVisibleToEmployeeColumn(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_discipline_case'])) {
            return;
        }
        if ($this->getSchemaHelper()->columnExists('ohrm_discipline_case', 'visible_to_employee')) {
            return;
        }
        $this->getSchemaHelper()->addColumn(
            'ohrm_discipline_case',
            'visible_to_employee',
            Types::BOOLEAN,
            ['Notnull' => true, 'Default' => false]
        );
    }

    private function grantEmployeeSharedCaseAccess(): void
    {
        // Employees may open Discipline → My Cases (read only) to see shared records.
        $this->setScreenPermission('viewDisciplineModule', 'ESS', true, false, false, false);
        $this->setScreenPermission('viewMyDisciplineCases', 'ESS', true, false, false, false);
        $this->setScreenPermission('saveMyDisciplineCase', 'ESS', false, false, false, false);

        $this->setDataGroupPermission(
            'apiv2_discipline_cases',
            'ESS',
            true,
            false,
            false,
            false,
            true
        );

        $moduleId = $this->createQueryBuilder()
            ->select('ohrm_module.id')
            ->from('ohrm_module')
            ->where('ohrm_module.name = :name')
            ->setParameter('name', 'discipline')
            ->executeQuery()
            ->fetchOne();
        $essRoleId = $this->getDataGroupHelper()->getUserRoleIdByName('ESS');
        if ($moduleId === false || !$essRoleId) {
            return;
        }

        $exists = $this->createQueryBuilder()
            ->select('ohrm_module_default_page.id')
            ->from('ohrm_module_default_page')
            ->where('module_id = :moduleId')
            ->andWhere('user_role_id = :roleId')
            ->setParameter('moduleId', $moduleId)
            ->setParameter('roleId', $essRoleId)
            ->executeQuery()
            ->fetchOne();
        if ($exists === false) {
            $this->createQueryBuilder()
                ->insert('ohrm_module_default_page')
                ->values([
                    'module_id' => ':moduleId',
                    'user_role_id' => ':roleId',
                    'action' => ':action',
                    'priority' => ':priority',
                ])
                ->setParameter('moduleId', $moduleId)
                ->setParameter('roleId', $essRoleId)
                ->setParameter('action', 'discipline/viewMyDisciplineCases')
                ->setParameter('priority', 20)
                ->executeQuery();
        }
    }

    private function seedCanadianFrenchStrings(): void
    {
        $frCaId = $this->getLanguageId('fr_CA');
        if ($frCaId === null) {
            return;
        }
        $translations = [
            'discipline.visible_to_employee' => 'Visible pour l\'employé',
            'discipline.visible_to_employee_hint' =>
                'Lorsque cette option est activée, l\'employé concerné peut consulter une version limitée du dossier (objet, statut, détails et mesures). Les notes du gestionnaire restent privées.',
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

    private function setScreenPermission(
        string $actionUrl,
        string $roleName,
        bool $canRead,
        bool $canCreate,
        bool $canUpdate,
        bool $canDelete
    ): void {
        $screenId = $this->createQueryBuilder()
            ->select('ohrm_screen.id')
            ->from('ohrm_screen')
            ->where('ohrm_screen.action_url = :url')
            ->setParameter('url', $actionUrl)
            ->executeQuery()
            ->fetchOne();
        if ($screenId === false) {
            return;
        }
        $roleId = $this->getDataGroupHelper()->getUserRoleIdByName($roleName);
        if (!$roleId) {
            return;
        }

        $existingId = $this->createQueryBuilder()
            ->select('ohrm_user_role_screen.id')
            ->from('ohrm_user_role_screen')
            ->where('ohrm_user_role_screen.screen_id = :screenId')
            ->andWhere('ohrm_user_role_screen.user_role_id = :roleId')
            ->setParameter('screenId', $screenId)
            ->setParameter('roleId', $roleId)
            ->executeQuery()
            ->fetchOne();

        if ($existingId === false) {
            $this->createQueryBuilder()
                ->insert('ohrm_user_role_screen')
                ->values([
                    'user_role_id' => ':roleId',
                    'screen_id' => ':screenId',
                    'can_read' => ':read',
                    'can_create' => ':create',
                    'can_update' => ':update',
                    'can_delete' => ':delete',
                ])
                ->setParameter('roleId', $roleId)
                ->setParameter('screenId', $screenId)
                ->setParameter('read', $canRead ? 1 : 0)
                ->setParameter('create', $canCreate ? 1 : 0)
                ->setParameter('update', $canUpdate ? 1 : 0)
                ->setParameter('delete', $canDelete ? 1 : 0)
                ->executeQuery();
            return;
        }

        $this->createQueryBuilder()
            ->update('ohrm_user_role_screen')
            ->set('ohrm_user_role_screen.can_read', ':read')
            ->set('ohrm_user_role_screen.can_create', ':create')
            ->set('ohrm_user_role_screen.can_update', ':update')
            ->set('ohrm_user_role_screen.can_delete', ':delete')
            ->where('ohrm_user_role_screen.id = :id')
            ->setParameter('read', $canRead ? 1 : 0)
            ->setParameter('create', $canCreate ? 1 : 0)
            ->setParameter('update', $canUpdate ? 1 : 0)
            ->setParameter('delete', $canDelete ? 1 : 0)
            ->setParameter('id', $existingId)
            ->executeQuery();
    }

    private function setDataGroupPermission(
        string $dataGroupName,
        string $roleName,
        bool $canRead,
        bool $canCreate,
        bool $canUpdate,
        bool $canDelete,
        bool $self = false
    ): void {
        $dataGroupId = $this->createQueryBuilder()
            ->select('ohrm_data_group.id')
            ->from('ohrm_data_group')
            ->where('ohrm_data_group.name = :name')
            ->setParameter('name', $dataGroupName)
            ->executeQuery()
            ->fetchOne();
        if ($dataGroupId === false) {
            return;
        }
        $roleId = $this->getDataGroupHelper()->getUserRoleIdByName($roleName);
        if (!$roleId) {
            return;
        }

        $existingId = $this->createQueryBuilder()
            ->select('ohrm_user_role_data_group.id')
            ->from('ohrm_user_role_data_group')
            ->where('ohrm_user_role_data_group.data_group_id = :dataGroupId')
            ->andWhere('ohrm_user_role_data_group.user_role_id = :roleId')
            ->andWhere('ohrm_user_role_data_group.self = :self')
            ->setParameter('dataGroupId', $dataGroupId)
            ->setParameter('roleId', $roleId)
            ->setParameter('self', $self ? 1 : 0)
            ->executeQuery()
            ->fetchOne();

        if ($existingId === false) {
            $this->createQueryBuilder()
                ->insert('ohrm_user_role_data_group')
                ->values([
                    'user_role_id' => ':roleId',
                    'data_group_id' => ':dataGroupId',
                    'can_read' => ':read',
                    'can_create' => ':create',
                    'can_update' => ':update',
                    'can_delete' => ':delete',
                    'self' => ':self',
                ])
                ->setParameter('roleId', $roleId)
                ->setParameter('dataGroupId', $dataGroupId)
                ->setParameter('read', $canRead ? 1 : 0)
                ->setParameter('create', $canCreate ? 1 : 0)
                ->setParameter('update', $canUpdate ? 1 : 0)
                ->setParameter('delete', $canDelete ? 1 : 0)
                ->setParameter('self', $self ? 1 : 0)
                ->executeQuery();
            return;
        }

        $this->createQueryBuilder()
            ->update('ohrm_user_role_data_group')
            ->set('ohrm_user_role_data_group.can_read', ':read')
            ->set('ohrm_user_role_data_group.can_create', ':create')
            ->set('ohrm_user_role_data_group.can_update', ':update')
            ->set('ohrm_user_role_data_group.can_delete', ':delete')
            ->where('ohrm_user_role_data_group.id = :id')
            ->setParameter('read', $canRead ? 1 : 0)
            ->setParameter('create', $canCreate ? 1 : 0)
            ->setParameter('update', $canUpdate ? 1 : 0)
            ->setParameter('delete', $canDelete ? 1 : 0)
            ->setParameter('id', $existingId)
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
