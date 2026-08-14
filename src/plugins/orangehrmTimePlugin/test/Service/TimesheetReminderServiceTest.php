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

namespace OrangeHRM\Tests\Time\Service;

use DateTime;
use OrangeHRM\Config\Config;
use OrangeHRM\Core\Service\ConfigService;
use OrangeHRM\Core\Service\EmailService;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\TimesheetReminderConfig;
use OrangeHRM\Framework\Services;
use OrangeHRM\Tests\Util\KernelTestCase;
use OrangeHRM\Tests\Util\TestDataService;
use OrangeHRM\Time\Service\TimesheetReminderService;
use OrangeHRM\Time\Service\TimesheetService;

/**
 * @group Time
 * @group Service
 */
class TimesheetReminderServiceTest extends KernelTestCase
{
    private const AS_OF = '2026-08-14';

    protected function setUp(): void
    {
        $fixture = Config::get(Config::PLUGINS_DIR)
            . '/orangehrmTimePlugin/test/fixtures/TimesheetReminder.yaml';
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_job_title');
        $connection->executeStatement('DELETE FROM ohrm_timesheet_reminder_employee');
        TestDataService::populate($fixture, true);

        $this->createKernelWithMockServices([
            Services::TIMESHEET_SERVICE => new TimesheetService(),
            Services::CONFIG_SERVICE => new ConfigService(),
            Services::TIMESHEET_REMINDER_SERVICE => new TimesheetReminderService(),
        ]);
    }

    public function testGetCurrentPeriodUsesWeeklyStartDate(): void
    {
        $period = $this->makeService()->getCurrentPeriod(new DateTime(self::AS_OF));
        $this->assertSame('2026-08-10', $period['start']->format('Y-m-d'));
        $this->assertSame('2026-08-16', $period['end']->format('Y-m-d'));
    }

    public function testGetEmployeesDueForReminderUnionsJobTitlesAndEmployees(): void
    {
        $this->saveRecipients([1, 2], [5]);
        $due = $this->makeService()->getEmployeesDueForReminder(null, new DateTime(self::AS_OF));
        $empNumbers = $this->empNumbers($due);
        sort($empNumbers);

        $this->assertSame([1, 3, 5, 9, 10], $empNumbers);
    }

    public function testGetEmployeesDueForReminderSkipsSubmittedAndApproved(): void
    {
        $this->saveRecipients([1], []);
        $due = $this->makeService()->getEmployeesDueForReminder(null, new DateTime(self::AS_OF));
        $empNumbers = $this->empNumbers($due);

        $this->assertNotContains(2, $empNumbers);
        $this->assertNotContains(7, $empNumbers);
        $this->assertContains(1, $empNumbers);
        $this->assertContains(9, $empNumbers);
        $this->assertContains(10, $empNumbers);
    }

    public function testSendRemindersReturnsEmptyWhenDisabled(): void
    {
        $service = $this->makeService();
        $config = $service->getConfig();
        $config->setEnabled(false);
        $service->saveConfig($config);

        $result = $service->sendReminders(true, new DateTime(self::AS_OF));
        $this->assertFalse($result['enabled']);
        $this->assertSame(0, $result['sent']);
        $this->assertSame([], $result['recipients']);
    }

    public function testSendRemindersDryRunCountsDueEmployees(): void
    {
        $this->saveRecipients([1, 2], [5]);
        $result = $this->makeService()->sendReminders(true, new DateTime(self::AS_OF));

        $this->assertTrue($result['enabled']);
        $this->assertSame('2026-08-10', $result['periodStart']);
        $this->assertSame('2026-08-16', $result['periodEnd']);
        $this->assertSame(7, $result['considered']);
        $this->assertSame(5, $result['sent']);
        $this->assertSame(2, $result['skipped']);
        $this->assertSame(0, $result['failed']);

        $empNumbers = array_column($result['recipients'], 'empNumber');
        sort($empNumbers);
        $this->assertSame([1, 3, 5, 9, 10], $empNumbers);
    }

    public function testSendRemindersUsesEmailServiceWhenConfigured(): void
    {
        $this->saveRecipients([1, 2], [5]);
        $emailService = $this->createMock(EmailService::class);
        $emailService->method('isConfigSet')->willReturn(true);
        $emailService->expects($this->exactly(5))->method('sendEmail')->willReturn(true);

        $service = $this->makeService();
        $service->setEmailService($emailService);
        $result = $service->sendReminders(false, new DateTime(self::AS_OF));

        $this->assertSame(5, $result['sent']);
        $this->assertSame(2, $result['skipped']);
        $this->assertSame(0, $result['failed']);
    }

    /**
     * @param int[] $jobTitleIds
     * @param int[] $empNumbers
     */
    private function saveRecipients(array $jobTitleIds, array $empNumbers): TimesheetReminderConfig
    {
        $service = $this->makeService();
        $config = $service->getConfig();
        $config->setEnabled(true);
        $config->getDecorator()->setJobTitlesByIds($jobTitleIds);
        $config->getDecorator()->setEmployeesByEmpNumbers($empNumbers);
        return $service->saveConfig($config);
    }

    private function makeService(): TimesheetReminderService
    {
        return new TimesheetReminderService();
    }

    /**
     * @param Employee[] $employees
     * @return int[]
     */
    private function empNumbers(array $employees): array
    {
        return array_map(static fn (Employee $employee) => $employee->getEmpNumber(), $employees);
    }
}
