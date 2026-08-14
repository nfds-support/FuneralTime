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


use OrangeHRM\Core\Traits\EventDispatcherTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Entity\TimesheetReminderConfig;
use OrangeHRM\Framework\Console\Console;
use OrangeHRM\Framework\Console\ConsoleConfigurationInterface;
use OrangeHRM\Framework\Console\Scheduling\CommandInfo;
use OrangeHRM\Framework\Console\Scheduling\Schedule;
use OrangeHRM\Framework\Console\Scheduling\SchedulerConfigurationInterface;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Logger\LoggerFactory;
use OrangeHRM\Framework\PluginConfigurationInterface;
use OrangeHRM\Framework\Services;
use OrangeHRM\Time\Command\SendTimesheetRemindersCommand;
use OrangeHRM\Time\Service\BankedTimeService;
use OrangeHRM\Time\Service\CustomerService;
use OrangeHRM\Time\Service\FuelBankedTimeService;
use OrangeHRM\Time\Service\PayrollPeriodService;
use OrangeHRM\Time\Service\ProjectService;
use OrangeHRM\Time\Service\TimesheetReminderService;
use OrangeHRM\Time\Service\TimesheetService;
use OrangeHRM\Time\Service\UfcwRemittanceReportService;
use OrangeHRM\Time\Service\UfcwRemittanceSettingsService;
use OrangeHRM\Time\Subscriber\TimesheetPeriodSubscriber;
use Throwable;

class TimePluginConfiguration implements
    PluginConfigurationInterface,
    ConsoleConfigurationInterface,
    SchedulerConfigurationInterface
{
    use ServiceContainerTrait;
    use EventDispatcherTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $this->getContainer()->register(Services::PROJECT_SERVICE, ProjectService::class);
        $this->getContainer()->register(Services::CUSTOMER_SERVICE, CustomerService::class);
        $this->getContainer()->register(Services::TIMESHEET_SERVICE, TimesheetService::class);
        $this->getContainer()->register(Services::PAYROLL_PERIOD_SERVICE, PayrollPeriodService::class);
        $this->getContainer()->register(Services::BANKED_TIME_SERVICE, BankedTimeService::class);
        $this->getContainer()->register(Services::FUEL_BANKED_TIME_SERVICE, FuelBankedTimeService::class);
        $this->getContainer()->register(Services::UFCW_REMITTANCE_REPORT_SERVICE, UfcwRemittanceReportService::class);
        $this->getContainer()->register(Services::UFCW_REMITTANCE_SETTINGS_SERVICE, UfcwRemittanceSettingsService::class);
        $this->getContainer()->register(Services::TIMESHEET_REMINDER_SERVICE, TimesheetReminderService::class);

        $this->getEventDispatcher()->addSubscriber(new TimesheetPeriodSubscriber());
    }

    public function registerCommands(Console $console): void
    {
        $console->add(new SendTimesheetRemindersCommand());
    }

    public function schedule(Schedule $schedule): void
    {
        try {
            $config = (new TimesheetReminderService())->getConfig();
        } catch (Throwable $e) {
            LoggerFactory::getLogger('timesheet')->error(
                'Failed to load timesheet reminder config for scheduling: ' . $e->getMessage()
            );
            return;
        }

        if (!$config->isEnabled()) {
            return;
        }

        try {
            $this->scheduleReminder($schedule, $config);
        } catch (Throwable $e) {
            LoggerFactory::getLogger('timesheet')->error(
                'Failed to schedule timesheet reminders: ' . $e->getMessage()
            );
        }
    }

    private function scheduleReminder(Schedule $schedule, TimesheetReminderConfig $config): void
    {
        $sendTime = $config->getSendTime();
        $parts = explode(':', $sendTime, 2);
        if (count($parts) !== 2) {
            throw new RuntimeException("Invalid sendTime '{$sendTime}'");
        }
        $hour = (int)$parts[0];
        $minute = (int)$parts[1];
        $weekday = $config->getWeekday();
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $weekday < 0 || $weekday > 6) {
            throw new RuntimeException("Out-of-range timesheet reminder schedule");
        }

        $cron = sprintf('%d %d * * %d', $minute, $hour, $weekday);
        $schedule->add(new CommandInfo('orangehrm:send-timesheet-reminders'))
            ->cron($cron)
            ->timezone($config->getTimezone());
    }
}
