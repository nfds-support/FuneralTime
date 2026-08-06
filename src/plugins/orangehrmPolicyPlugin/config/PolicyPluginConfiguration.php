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

use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Framework\Console\Console;
use OrangeHRM\Framework\Console\ConsoleConfigurationInterface;
use OrangeHRM\Framework\Console\Scheduling\CommandInfo;
use OrangeHRM\Framework\Console\Scheduling\Schedule;
use OrangeHRM\Framework\Console\Scheduling\SchedulerConfigurationInterface;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\PluginConfigurationInterface;
use OrangeHRM\Framework\Services;
use OrangeHRM\Policy\Command\MoodleSyncUserCommand;
use OrangeHRM\Policy\Service\MoodleSyncService;
use OrangeHRM\Policy\Service\PolicyService;

class PolicyPluginConfiguration implements
    PluginConfigurationInterface,
    ConsoleConfigurationInterface,
    SchedulerConfigurationInterface
{
    use ServiceContainerTrait;
    use ConfigServiceTrait;

    public function initialize(Request $request): void
    {
        $this->getContainer()->register(Services::POLICY_SERVICE, PolicyService::class);
        $this->getContainer()->register(Services::MOODLE_SYNC_SERVICE, MoodleSyncService::class);
    }

    public function registerCommands(Console $console): void
    {
        $console->add(new MoodleSyncUserCommand());
    }

    public function schedule(Schedule $schedule): void
    {
        if ($this->getConfigService()->getMoodleSyncEnabled()) {
            $schedule->add(new CommandInfo('orangehrm:moodle-sync-users'))
                ->hourly();
        }
    }
}
