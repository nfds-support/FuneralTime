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

namespace OrangeHRM\Installer\Migration\V5_9_16;

use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    public function up(): void
    {
        $subjectPath = '/orangehrmAuthenticationPlugin/Mail/templates/en_US/auth.welcome/welcomeSubject.txt.twig';
        $bodyPath = '/orangehrmAuthenticationPlugin/Mail/templates/en_US/auth.welcome/welcomeBody.html.twig';

        $this->ensureEmailAndTemplate(
            'auth.welcome',
            'en_US',
            null,
            'ess',
            $subjectPath,
            $bodyPath,
            'Welcome to FuneralTime, {{ employeeName }}',
            '<p>Hi {{ employeeName }},</p>'
            . '<p>Welcome to FuneralTime. Your account (<strong>{{ userName }}</strong>) is ready.</p>'
            . '<p>You can sign in here: <a href="{{ loginUrl }}">{{ loginUrl }}</a></p>'
            . '<p>If you have any questions, contact your administrator.</p>'
            . '<p>— FuneralTime</p>'
        );

        $this->ensureEmailAndTemplate(
            'auth.welcome',
            'fr_CA',
            null,
            'ess',
            $subjectPath,
            $bodyPath,
            'Bienvenue sur FuneralTime, {{ employeeName }}',
            '<p>Bonjour {{ employeeName }},</p>'
            . '<p>Bienvenue sur FuneralTime. Votre compte (<strong>{{ userName }}</strong>) est prêt.</p>'
            . '<p>Vous pouvez vous connecter ici : <a href="{{ loginUrl }}">{{ loginUrl }}</a></p>'
            . '<p>Pour toute question, communiquez avec votre administrateur.</p>'
            . '<p>— FuneralTime</p>'
        );
    }

    public function getVersion(): string
    {
        return '5.9.16';
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
}
