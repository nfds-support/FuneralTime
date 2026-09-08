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

namespace OrangeHRM\Installer\Migration\V5_9_14;

use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->addTemplateContentColumns();
        $this->seedExtraEmailTemplates();

        if (!$this->dataGroupExists('apiv2_admin_email_templates')) {
            $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        }
        if (!$this->screenExistsByActionUrl('viewEmailTemplates')) {
            $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screen.yaml');
        }
        $this->insertMenus();

        if (is_file(__DIR__ . '/lang-string/admin.yaml')) {
            $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'admin');
        }
    }

    public function getVersion(): string
    {
        return '5.9.14';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if ($this->langStringHelper === null) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function addTemplateContentColumns(): void
    {
        if (!$this->getSchemaHelper()->columnExists('ohrm_email_template', 'subject_content')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_email_template',
                'subject_content',
                Types::TEXT,
                ['Notnull' => false, 'Default' => null]
            );
        }
        if (!$this->getSchemaHelper()->columnExists('ohrm_email_template', 'body_content')) {
            $this->getSchemaHelper()->addColumn(
                'ohrm_email_template',
                'body_content',
                Types::TEXT,
                ['Notnull' => false, 'Default' => null]
            );
        }
    }

    private function seedExtraEmailTemplates(): void
    {
        $this->ensureEmailAndTemplate(
            'time.ufcw_remittance',
            'en_US',
            null,
            'subscriber',
            '',
            '',
            'UFCW Monthly Remittance – {{ employerName }} – {{ reportMonthLabel }}',
            '<p>Please find attached the UFCW monthly dues &amp; membership remittance report for '
            . '{{ reportMonthLabel }}.</p>'
            . '<p>Regular union dues: ${{ unionDues }}'
            . '<br/>Initiation fees: ${{ initiationFees }}'
            . '<br/>Total remittance: ${{ remittance }}'
            . '<br/>Remittance due: {{ remittanceDueDate }}</p>'
            . '<p>Cheque payable to: {{ chequePayableTo }}'
            . '<br/>Attention: {{ chequeAttention }}</p>'
            . '<p>A hard copy of the detailed dues report should accompany the cheque. '
            . 'Do not offset prior overpayments.</p>'
        );

        $this->ensureEmailAndTemplate(
            'time.timesheet_reminder',
            'en_US',
            null,
            'ess',
            '/orangehrmTimePlugin/Mail/templates/en_US/timesheet.reminder/timesheetReminderSubject.txt.twig',
            '/orangehrmTimePlugin/Mail/templates/en_US/timesheet.reminder/timesheetReminderBody.html.twig',
            null,
            null
        );
    }

    private function ensureEmailAndTemplate(
        string $emailName,
        string $locale,
        ?string $performerRole,
        string $recipientRole,
        string $subjectPath,
        string $bodyPath,
        ?string $subjectContent,
        ?string $bodyContent
    ): void {
        $emailId = $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_email WHERE name = ?',
            [$emailName]
        )->fetchOne();
        if (!$emailId) {
            $this->createQueryBuilder()
                ->insert('ohrm_email')
                ->values(['name' => ':name'])
                ->setParameter('name', $emailName)
                ->executeQuery();
            $emailId = $this->getConnection()->lastInsertId();
        }

        $existing = $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_email_template WHERE email_id = ? AND locale = ? AND recipient_role = ?'
            . ' AND (performer_role IS NULL OR performer_role = ?)',
            [$emailId, $locale, $recipientRole, $performerRole]
        )->fetchOne();
        if ($existing) {
            return;
        }

        $this->createQueryBuilder()
            ->insert('ohrm_email_template')
            ->values([
                'email_id' => ':emailId',
                'locale' => ':locale',
                'performer_role' => ':performerRole',
                'recipient_role' => ':recipientRole',
                'subject' => ':subject',
                'body' => ':body',
                'subject_content' => ':subjectContent',
                'body_content' => ':bodyContent',
            ])
            ->setParameter('emailId', $emailId)
            ->setParameter('locale', $locale)
            ->setParameter('performerRole', $performerRole)
            ->setParameter('recipientRole', $recipientRole)
            ->setParameter('subject', $subjectPath)
            ->setParameter('body', $bodyPath)
            ->setParameter('subjectContent', $subjectContent)
            ->setParameter('bodyContent', $bodyContent)
            ->executeQuery();
    }

    private function insertMenus(): void
    {
        $screenId = $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_screen WHERE action_url = ?',
            ['viewEmailTemplates']
        )->fetchOne();
        if (!$screenId) {
            return;
        }
        $exists = $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_menu_item WHERE screen_id = ?',
            [$screenId]
        )->fetchOne();
        if ($exists) {
            return;
        }

        $adminId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->andWhere('level = :level')
            ->setParameter('menuTitle', 'Admin')
            ->setParameter('level', 1)
            ->executeQuery()
            ->fetchOne();
        if (!$adminId) {
            return;
        }

        $parentId = $this->createQueryBuilder()
            ->select('menu_item.id')
            ->from('ohrm_menu_item', 'menu_item')
            ->where('menu_item.menu_title = :menuTitle')
            ->andWhere('level = :level')
            ->andWhere('parent_id = :parentId')
            ->setParameter('menuTitle', 'Configuration')
            ->setParameter('level', 2)
            ->setParameter('parentId', $adminId)
            ->executeQuery()
            ->fetchOne();
        if (!$parentId) {
            return;
        }

        $this->createQueryBuilder()
            ->insert('ohrm_menu_item')
            ->values([
                'menu_title' => ':title',
                'screen_id' => ':screenId',
                'parent_id' => ':parentId',
                'level' => ':level',
                'order_hint' => ':orderHint',
                'status' => ':status',
            ])
            ->setParameter('title', 'Email Templates')
            ->setParameter('screenId', $screenId)
            ->setParameter('parentId', $parentId)
            ->setParameter('level', 3)
            ->setParameter('orderHint', 250)
            ->setParameter('status', 1)
            ->executeQuery();
    }

    private function dataGroupExists(string $name): bool
    {
        return (bool) $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_data_group WHERE name = ?',
            [$name]
        )->fetchOne();
    }

    private function screenExistsByActionUrl(string $actionUrl): bool
    {
        return (bool) $this->getConnection()->executeQuery(
            'SELECT id FROM ohrm_screen WHERE action_url = ?',
            [$actionUrl]
        )->fetchOne();
    }
}
