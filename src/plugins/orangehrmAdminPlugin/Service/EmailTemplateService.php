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

namespace OrangeHRM\Admin\Service;

use OrangeHRM\Core\Dao\EmailDao;
use OrangeHRM\Core\Dto\EmailTemplateSearchFilterParams;
use OrangeHRM\Core\Service\EmailService;
use OrangeHRM\Entity\Email;
use OrangeHRM\Entity\EmailTemplate;

class EmailTemplateService
{
    private ?EmailDao $emailDao = null;
    private ?EmailService $emailService = null;

    public function getEmailDao(): EmailDao
    {
        return $this->emailDao ??= new EmailDao();
    }

    public function setEmailDao(EmailDao $emailDao): void
    {
        $this->emailDao = $emailDao;
    }

    public function getEmailService(): EmailService
    {
        return $this->emailService ??= new EmailService();
    }

    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    /**
     * @return Email[]
     */
    public function getEmails(): array
    {
        return $this->getEmailDao()->getEmails();
    }

    /**
     * @param string $name
     * @return Email
     */
    public function getOrCreateEmailByName(string $name): Email
    {
        $email = $this->getEmailDao()->getEmailByName($name);
        if ($email instanceof Email) {
            return $email;
        }
        $email = new Email();
        $email->setName($name);
        return $this->getEmailDao()->saveEmail($email);
    }

    /**
     * @param EmailTemplateSearchFilterParams $filterParams
     * @return EmailTemplate[]
     */
    public function searchEmailTemplates(EmailTemplateSearchFilterParams $filterParams): array
    {
        return $this->getEmailDao()->searchEmailTemplates($filterParams);
    }

    public function getEmailTemplatesCount(EmailTemplateSearchFilterParams $filterParams): int
    {
        return $this->getEmailDao()->getEmailTemplatesCount($filterParams);
    }

    public function getEmailTemplateById(int $id): ?EmailTemplate
    {
        return $this->getEmailDao()->getEmailTemplateById($id);
    }

    public function saveEmailTemplate(EmailTemplate $template): EmailTemplate
    {
        return $this->getEmailDao()->saveEmailTemplate($template);
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteEmailTemplates(array $ids): int
    {
        return $this->getEmailDao()->deleteEmailTemplates($ids);
    }

    /**
     * @return array{subject: string, body: string, hasCustomContent: bool}
     */
    public function resolveTemplateContent(EmailTemplate $template): array
    {
        $hasCustom = ($template->getSubjectContent() !== null && $template->getSubjectContent() !== '')
            || ($template->getBodyContent() !== null && $template->getBodyContent() !== '');

        // Legacy seed rows may point at removed filesystem templates (e.g. leave.change).
        // Admin list/edit must still succeed — fall back to empty/custom content.
        try {
            $subject = $this->getEmailService()->resolveTemplateSubject($template);
        } catch (\Throwable) {
            $subject = (string) ($template->getSubjectContent() ?? '');
        }
        try {
            $body = $this->getEmailService()->resolveTemplateBody($template);
        } catch (\Throwable) {
            $body = (string) ($template->getBodyContent() ?? '');
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'hasCustomContent' => $hasCustom,
        ];
    }

    public function resetTemplateToDefault(EmailTemplate $template): EmailTemplate
    {
        $template->setSubjectContent(null);
        $template->setBodyContent(null);
        return $this->saveEmailTemplate($template);
    }
}
