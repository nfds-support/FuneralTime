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

namespace OrangeHRM\Authentication\Service;

use OrangeHRM\Config\Config;
use OrangeHRM\Core\Mail\TemplateHelper;
use OrangeHRM\Core\Service\EmailService;
use OrangeHRM\Core\Traits\LoggerTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\User;
use OrangeHRM\Framework\Services;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

class WelcomeEmailService
{
    use LoggerTrait;
    use ServiceContainerTrait;

    private ?EmailService $emailService = null;
    private ?TemplateHelper $templateHelper = null;

    public function getEmailService(): EmailService
    {
        return $this->emailService ??= new EmailService();
    }

    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    public function getTemplateHelper(): TemplateHelper
    {
        return $this->templateHelper ??= new TemplateHelper();
    }

    public function setTemplateHelper(TemplateHelper $templateHelper): void
    {
        $this->templateHelper = $templateHelper;
    }

    /**
     * Send welcome email if the user has a work email. Never throws — login must proceed.
     */
    public function sendIfEligible(User $user): bool
    {
        try {
            $employee = $user->getEmployee();
            if (!$employee instanceof Employee) {
                return false;
            }

            $email = trim((string) $employee->getWorkEmail());
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->getLogger()->info(
                    sprintf('Welcome email skipped for user %s: no valid work email', $user->getUserName())
                );
                return false;
            }

            $replacements = [
                'employeeName' => $employee->getDecorator()->getFirstAndLastNames(),
                'userName' => $user->getUserName(),
                'loginUrl' => $this->resolveLoginUrl(),
            ];

            $emailService = $this->getEmailService();
            $template = $emailService->findTemplate('auth.welcome', 'ess');
            if ($template !== null) {
                $subject = $this->getTemplateHelper()->renderTemplate(
                    $emailService->resolveTemplateSubject($template),
                    $replacements
                );
                $body = $this->getTemplateHelper()->renderTemplate(
                    $emailService->resolveTemplateBody($template),
                    $replacements
                );
            } else {
                $subject = $this->getTemplateHelper()->renderTemplate(
                    $this->readFallback('welcomeSubject.txt.twig'),
                    $replacements
                );
                $body = $this->getTemplateHelper()->renderTemplate(
                    $this->readFallback('welcomeBody.html.twig'),
                    $replacements
                );
            }

            $emailService->setMessageTo([$email]);
            $emailService->setMessageSubject(trim($subject));
            $emailService->setMessageBody($body);
            $emailService->setMessageCc([]);
            $emailService->setMessageBcc([]);
            return $emailService->sendEmail();
        } catch (Throwable $e) {
            $this->getLogger()->error('Failed to send first-login welcome email: ' . $e->getMessage());
            return false;
        }
    }

    private function resolveLoginUrl(): string
    {
        try {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->getContainer()->get(Services::URL_GENERATOR);
            return $urlGenerator->generate('auth_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (Throwable) {
            return '/web/index.php/auth/login';
        }
    }

    private function readFallback(string $filename): string
    {
        $path = Config::get(Config::PLUGINS_DIR)
            . '/orangehrmAuthenticationPlugin/Mail/templates/en_US/auth.welcome/'
            . $filename;
        return (string) file_get_contents($path);
    }
}
