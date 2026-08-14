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

namespace OrangeHRM\Time\Service;

use DateTime;
use OrangeHRM\Config\Config;
use OrangeHRM\Core\Mail\TemplateHelper;
use OrangeHRM\Core\Service\EmailService;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Timesheet;
use OrangeHRM\Entity\TimesheetReminderConfig;
use OrangeHRM\Framework\Logger\LoggerFactory;
use OrangeHRM\Time\Dao\TimesheetReminderDao;
use OrangeHRM\Time\Traits\Service\TimesheetServiceTrait;
use Throwable;

class TimesheetReminderService
{
    use DateTimeHelperTrait;
    use TimesheetServiceTrait;

    public const MY_TIMESHEET_PATH = '/time/viewMyTimesheet';

    private ?TimesheetReminderDao $timesheetReminderDao = null;
    private ?EmailService $emailService = null;
    private ?TemplateHelper $templateHelper = null;

    public function getTimesheetReminderDao(): TimesheetReminderDao
    {
        return $this->timesheetReminderDao ??= new TimesheetReminderDao();
    }

    public function setTimesheetReminderDao(TimesheetReminderDao $timesheetReminderDao): void
    {
        $this->timesheetReminderDao = $timesheetReminderDao;
    }

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

    public function getConfig(): TimesheetReminderConfig
    {
        $config = $this->getTimesheetReminderDao()->getConfig();
        if ($config instanceof TimesheetReminderConfig) {
            return $config;
        }

        $config = new TimesheetReminderConfig();
        $config->setEnabled(false);
        $config->setWeekday(TimesheetReminderConfig::WEEKDAY_FRIDAY);
        $config->setSendTime('16:00');
        $config->setTimezone('UTC');
        return $this->getTimesheetReminderDao()->saveConfig($config);
    }

    public function saveConfig(TimesheetReminderConfig $config): TimesheetReminderConfig
    {
        return $this->getTimesheetReminderDao()->saveConfig($config);
    }

    /**
     * @return array{start: DateTime, end: DateTime}
     */
    public function getCurrentPeriod(?DateTime $asOf = null): array
    {
        $asOf ??= $this->getDateTimeHelper()->getNow();
        [$start, $end] = $this->getTimesheetService()->extractStartDateAndEndDateFromDate($asOf);
        return [
            'start' => new DateTime($start),
            'end' => new DateTime($end),
        ];
    }

    /**
     * @return Employee[]
     */
    public function getEmployeesDueForReminder(?TimesheetReminderConfig $config = null, ?DateTime $asOf = null): array
    {
        $config ??= $this->getConfig();
        $employees = $this->getTimesheetReminderDao()->getEligibleEmployees(
            $config->getDecorator()->getJobTitleIds(),
            $config->getDecorator()->getEmpNumbers()
        );
        $period = $this->getCurrentPeriod($asOf);
        $due = [];
        foreach ($employees as $employee) {
            if ($this->shouldRemindEmployee($employee, $period['start'])) {
                $due[] = $employee;
            }
        }
        return $due;
    }

    public function shouldRemindEmployee(Employee $employee, DateTime $periodStart): bool
    {
        $timesheet = $this->getTimesheetService()
            ->getTimesheetDao()
            ->getTimesheetByEmployeeAndStartDate($employee->getEmpNumber(), $periodStart);
        if ($timesheet === null) {
            return true;
        }
        return !in_array($timesheet->getState(), Timesheet::STATES_ALREADY_SUBMITTED, true);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     smtpConfigured: bool,
     *     periodStart: string|null,
     *     periodEnd: string|null,
     *     considered: int,
     *     sent: int,
     *     skipped: int,
     *     failed: int,
     *     recipients: array<int, array{empNumber: int, name: string, email: string}>
     * }
     */
    public function sendReminders(bool $dryRun = false, ?DateTime $asOf = null): array
    {
        $config = $this->getConfig();
        $smtpConfigured = $this->getEmailService()->isConfigSet();
        $result = [
            'enabled' => $config->isEnabled(),
            'smtpConfigured' => $smtpConfigured,
            'periodStart' => null,
            'periodEnd' => null,
            'considered' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'recipients' => [],
        ];

        if (!$config->isEnabled()) {
            return $result;
        }

        $period = $this->getCurrentPeriod($asOf);
        $result['periodStart'] = $period['start']->format('Y-m-d');
        $result['periodEnd'] = $period['end']->format('Y-m-d');

        $employees = $this->getTimesheetReminderDao()->getEligibleEmployees(
            $config->getDecorator()->getJobTitleIds(),
            $config->getDecorator()->getEmpNumbers()
        );
        $result['considered'] = count($employees);

        $logger = LoggerFactory::getLogger('timesheet');
        foreach ($employees as $employee) {
            if (!$this->shouldRemindEmployee($employee, $period['start'])) {
                $result['skipped']++;
                continue;
            }

            $email = trim((string)$employee->getWorkEmail());
            $recipient = [
                'empNumber' => $employee->getEmpNumber(),
                'name' => $employee->getDecorator()->getFirstAndLastNames(),
                'email' => $email,
            ];
            $result['recipients'][] = $recipient;

            if ($dryRun) {
                $result['sent']++;
                continue;
            }

            if (!$smtpConfigured) {
                $logger->warning('Timesheet reminder skipped: email is not configured');
                $result['skipped']++;
                continue;
            }

            try {
                if ($this->sendReminderEmail($employee, $period['start'], $period['end'])) {
                    $result['sent']++;
                } else {
                    $result['failed']++;
                }
            } catch (Throwable $e) {
                $result['failed']++;
                $logger->error('Timesheet reminder failed for emp ' . $employee->getEmpNumber() . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    public function sendReminderEmail(Employee $employee, DateTime $periodStart, DateTime $periodEnd): bool
    {
        $email = trim((string)$employee->getWorkEmail());
        if ($email === '') {
            return false;
        }

        $replacements = [
            'employeeName' => $employee->getDecorator()->getFirstAndLastNames(),
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
            'timesheetPath' => self::MY_TIMESHEET_PATH,
        ];

        $subject = $this->getTemplateHelper()->renderTemplate(
            $this->readTemplate('timesheetReminderSubject.txt.twig'),
            $replacements
        );
        $body = $this->getTemplateHelper()->renderTemplate(
            $this->readTemplate('timesheetReminderBody.html.twig'),
            $replacements
        );

        $emailService = $this->getEmailService();
        $emailService->setMessageTo([$email]);
        $emailService->setMessageSubject(trim($subject));
        $emailService->setMessageBody($body);
        $emailService->setMessageCc([]);
        $emailService->setMessageBcc([]);
        return $emailService->sendEmail();
    }

    private function readTemplate(string $filename): string
    {
        $path = Config::get(Config::PLUGINS_DIR)
            . '/orangehrmTimePlugin/Mail/templates/en_US/timesheet.reminder/'
            . $filename;
        return (string)file_get_contents($path);
    }
}
