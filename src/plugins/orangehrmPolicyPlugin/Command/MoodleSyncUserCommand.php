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

namespace OrangeHRM\Policy\Command;

use OrangeHRM\Framework\Console\Command;
use OrangeHRM\Policy\Traits\Service\MoodleSyncServiceTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MoodleSyncUserCommand extends Command
{
    use MoodleSyncServiceTrait;

    public function getCommandName(): string
    {
        return 'orangehrm:moodle-sync-users';
    }

    protected function configure()
    {
        $this->setDescription('Sync OrangeHRM employees to Moodle users and job-title cohorts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->getMoodleSyncService()->sync();
        $this->getIO()->writeln(sprintf(
            'Moodle sync finished: created/updated processed, cohort members=%d, skipped=%d',
            $result['cohortMembers'],
            $result['skipped']
        ));
        foreach ($result['errors'] as $error) {
            $this->getIO()->error($error);
        }
        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
