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

namespace OrangeHRM\Tests\Time\Command;

use DateTime;
use OrangeHRM\Config\Config;
use OrangeHRM\Core\Service\ConfigService;
use OrangeHRM\Core\Service\DateTimeHelperService;
use OrangeHRM\Framework\Services;
use OrangeHRM\Tests\Util\KernelTestCase;
use OrangeHRM\Tests\Util\TestDataService;
use OrangeHRM\Time\Command\SendTimesheetRemindersCommand;
use OrangeHRM\Time\Service\TimesheetReminderService;
use OrangeHRM\Time\Service\TimesheetService;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group Time
 * @group Command
 */
class SendTimesheetRemindersCommandTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $fixture = Config::get(Config::PLUGINS_DIR)
            . '/orangehrmTimePlugin/test/fixtures/TimesheetReminder.yaml';
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_job_title');
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_employee');
        TestDataService::populate($fixture, true);

        $dateTimeHelper = $this->getMockBuilder(DateTimeHelperService::class)
            ->onlyMethods(['getNow'])
            ->getMock();
        $dateTimeHelper->method('getNow')->willReturn(new DateTime('2026-08-14'));

        $this->createKernelWithMockServices([
            Services::TIMESHEET_SERVICE => new TimesheetService(),
            Services::CONFIG_SERVICE => new ConfigService(),
            Services::DATETIME_HELPER_SERVICE => $dateTimeHelper,
            Services::TIMESHEET_REMINDER_SERVICE => new TimesheetReminderService(),
        ]);
    }

    public function testCommandNameMatchesSchedulerEntryPoint(): void
    {
        $this->assertSame(
            'orangehrm:send-timesheet-reminders',
            (new SendTimesheetRemindersCommand())->getCommandName()
        );
    }

    public function testRunWhenDisabledPrintsNote(): void
    {
        $service = new TimesheetReminderService();
        $config = $service->getConfig();
        $config->setEnabled(false);
        $service->saveConfig($config);

        $tester = new CommandTester(new SendTimesheetRemindersCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Timesheet reminders are disabled.', $tester->getDisplay());
    }

    public function testDryRunListsDueRecipients(): void
    {
        $service = new TimesheetReminderService();
        $config = $service->getConfig();
        $config->setEnabled(true);
        $config->getDecorator()->setJobTitlesByIds([1, 2]);
        $config->getDecorator()->setEmployeesByEmpNumbers([5]);
        $service->saveConfig($config);

        $tester = new CommandTester(new SendTimesheetRemindersCommand());
        $exitCode = $tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Period 2026-08-10 to 2026-08-16', $display);
        $this->assertMatchesRegularExpression('/considered\s+7,\s+sent\s+5,\s+skipped\s+2,\s+failed\s+0/s', $display);
        $this->assertStringContainsString('kayla@xample.com', $display);
        $this->assertStringContainsString('sandeepa@xample.com', $display);
        $this->assertStringNotContainsString('ashley@xample.com', $display);
        $this->assertStringNotContainsString('linda@xample.com', $display);
    }
}
