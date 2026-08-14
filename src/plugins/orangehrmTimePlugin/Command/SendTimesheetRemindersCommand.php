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

namespace OrangeHRM\Time\Command;

use OrangeHRM\Framework\Console\Command;
use OrangeHRM\Framework\Logger\LoggerFactory;
use OrangeHRM\Time\Service\TimesheetReminderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SendTimesheetRemindersCommand extends Command
{
    public const OPT_DRY_RUN = 'dry-run';

    public function getCommandName(): string
    {
        return 'orangehrm:send-timesheet-reminders';
    }

    protected function configure(): void
    {
        $this->setDescription('Email staff whose current timesheet is not yet submitted.');
        $this->addOption(
            self::OPT_DRY_RUN,
            null,
            InputOption::VALUE_NONE,
            'List recipients without sending mail'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $logger = LoggerFactory::getLogger('timesheet');
        $logger->info('Timesheet reminder run starting' . ($dryRun ? ' (dry-run)' : ''));

        try {
            $service = new TimesheetReminderService();
            $result = $service->sendReminders($dryRun);
        } catch (Throwable $e) {
            $logger->error('Timesheet reminder run failed: ' . $e->getMessage());
            $this->getIO()->error('Timesheet reminder run failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$result['enabled']) {
            $this->getIO()->note('Timesheet reminders are disabled.');
            $logger->info('Timesheet reminder run completed: disabled');
            return self::SUCCESS;
        }

        if ($result['recipients'] !== []) {
            $rows = [];
            foreach ($result['recipients'] as $recipient) {
                $rows[] = [
                    (string)$recipient['empNumber'],
                    $recipient['name'],
                    $recipient['email'],
                ];
            }
            $this->getIO()->table(['Emp #', 'Name', 'Email'], $rows);
        }

        $summary = sprintf(
            'Period %s to %s — considered %d, sent %d, skipped %d, failed %d',
            $result['periodStart'] ?? '-',
            $result['periodEnd'] ?? '-',
            $result['considered'],
            $result['sent'],
            $result['skipped'],
            $result['failed']
        );
        if (!$result['smtpConfigured'] && !$dryRun) {
            $this->getIO()->warning('Email is not configured; no messages were sent.');
        }
        if ($result['failed'] > 0) {
            $this->getIO()->warning($summary);
        } else {
            $this->getIO()->success($summary);
        }
        $logger->info('Timesheet reminder run completed: ' . $summary);

        return self::SUCCESS;
    }
}
