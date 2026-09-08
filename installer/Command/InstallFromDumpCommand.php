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

namespace OrangeHRM\Installer\Command;

use OrangeHRM\Authentication\Dto\UserCredential;
use OrangeHRM\Config\Config;
use OrangeHRM\Installer\Framework\InstallerCommand;
use OrangeHRM\Installer\Util\AppSetupUtility;
use OrangeHRM\Installer\Util\Connection;
use OrangeHRM\Installer\Util\DatabaseUserPermissionEvaluator;
use OrangeHRM\Installer\Util\Logger;
use OrangeHRM\Installer\Util\StateContainer;
use OrangeHRM\Installer\Util\SystemCheck;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class InstallFromDumpCommand extends InstallerCommand
{
    use InstallerCommandHelperTrait;

    public const STEP_1 = 'Database creation and SQL import';
    public const STEP_2 = 'Checking database permissions';
    public const STEP_3 = 'Applying database changes';
    public const STEP_4 = 'Create OrangeHRM database user';
    public const STEP_5 = 'Creating configuration files';

    /**
     * @inheritDoc
     */
    public function getCommandName(): string
    {
        return 'install:from-dump';
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Create a new database from an OrangeHRM SQL dump and upgrade to FuneralTime')
            ->addOption('sql', null, InputOption::VALUE_REQUIRED, 'Absolute path to .sql dump')
            ->addOption('dbHost', null, InputOption::VALUE_REQUIRED)
            ->addOption('dbPort', null, InputOption::VALUE_REQUIRED)
            ->addOption('dbName', null, InputOption::VALUE_REQUIRED)
            ->addOption('dbUser', null, InputOption::VALUE_REQUIRED)
            ->addOption('dbUserPassword', null, InputOption::VALUE_REQUIRED)
            ->addOption('ohrmDbUser', null, InputOption::VALUE_REQUIRED)
            ->addOption('ohrmDbUserPassword', null, InputOption::VALUE_REQUIRED)
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Drop existing database with the same name')
            ->addOption('systemCheckAcceptRisk', null, InputOption::VALUE_NONE);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Config::isInstalled()) {
            $this->getIO()->error('This system already installed.');
            return self::FAILURE;
        }

        set_time_limit(0);
        $sqlPath = $input->getOption('sql');
        if ($input->isInteractive() && ($sqlPath === null || $sqlPath === '')) {
            $sqlPath = $this->getRequiredField('SQL dump path');
        }
        if ($sqlPath === null || $sqlPath === '' || !is_readable($sqlPath)) {
            $this->getIO()->error('SQL dump path is missing or not readable.');
            return self::INVALID;
        }
        if (strtolower(pathinfo($sqlPath, PATHINFO_EXTENSION)) !== 'sql') {
            $this->getIO()->error('Only .sql dump files are supported.');
            return self::INVALID;
        }

        if ($input->isInteractive()) {
            if (!$this->hasOption($input, 'dbHost')) {
                $input->setOption('dbHost', $this->getRequiredField('Database Host Name'));
            }
            if (!$this->hasOption($input, 'dbPort')) {
                $input->setOption(
                    'dbPort',
                    $this->getIO()->ask('Database Host Port', '3306', fn (?string $v) => $this->databasePortValidator($v))
                );
            }
            if (!$this->hasOption($input, 'dbName')) {
                $input->setOption('dbName', $this->getRequiredField('Database Name'));
            }
            if (!$this->hasOption($input, 'dbUser')) {
                $input->setOption('dbUser', $this->getRequiredField('Privileged Database Username'));
            }
            if (!$this->hasOption($input, 'dbUserPassword')) {
                $input->setOption(
                    'dbUserPassword',
                    $this->getIO()->askHidden('Privileged Database Password <comment>(hidden)</comment>')
                );
            }
        }

        foreach (['dbHost', 'dbName', 'dbUser'] as $required) {
            if (!$this->hasOption($input, $required)) {
                $this->getIO()->error("Missing required option --$required");
                return self::INVALID;
            }
        }

        $dbHost = $input->getOption('dbHost');
        $dbPort = (string) ($input->getOption('dbPort') ?? '3306');
        $dbName = $input->getOption('dbName');
        $dbUser = $input->getOption('dbUser');
        $dbPassword = $input->getOption('dbUserPassword') ?? '';
        $ohrmDbUser = $input->getOption('ohrmDbUser') ?: $dbUser;
        $ohrmDbPassword = $input->getOption('ohrmDbUserPassword') ?? $dbPassword;
        $overwrite = (bool) $input->getOption('overwrite');

        $this->getIO()->caution(
            'IMPORTANT: Use a copy of your production dump. Import runs as the privileged DB user.'
        );
        $this->getIO()->warning(
            'ENCRYPTION: If the dump used data encryption, copy lib/confs/cryptokeys/key.ohrm after install.'
        );

        StateContainer::getInstance()->setInstallationMode(StateContainer::INSTALLATION_MODE_RESTORE);
        StateContainer::getInstance()->storeDbInfo(
            $dbHost,
            $dbPort,
            new UserCredential($dbUser, $dbPassword),
            $dbName,
            new UserCredential($ohrmDbUser, $ohrmDbPassword),
            false
        );
        StateContainer::getInstance()->setDbType(AppSetupUtility::INSTALLATION_DB_TYPE_NEW);
        StateContainer::getInstance()->setSqlDumpPath(realpath($sqlPath) ?: $sqlPath, basename($sqlPath));

        $appSetupUtility = new AppSetupUtility();
        $systemCheck = new SystemCheck();
        $this->drawSystemCheckTable($systemCheck->getSystemCheckResults(true));
        if ($systemCheck->isInterruptContinue()) {
            $this->getIO()->error('System check failed');
            if (!$input->getOption('systemCheckAcceptRisk') && $input->isInteractive()) {
                $input->setOption(
                    'systemCheckAcceptRisk',
                    $this->getIO()->confirm('Do you want to accept the risk and continue?', false)
                );
            }
            if ($input->getOption('systemCheckAcceptRisk') !== true) {
                return self::INVALID;
            }
            $this->getIO()->warning('Accepted the risk, so continue.');
        }

        $step1 = $this->startSection($output, self::STEP_1);
        $step2 = $this->startSection($output, self::STEP_2);
        $step3 = $this->startSection($output, self::STEP_3);
        $step4 = $this->startSection($output, self::STEP_4);
        $step5 = $this->startSection($output, self::STEP_5, "\n");

        try {
            $this->startStep($step1, self::STEP_1);
            $result = $appSetupUtility->createDatabaseAndImportSqlDump(null, $overwrite);
            $this->getIO()->writeln(sprintf(
                ' Imported dump at <info>%s</info> (skip admin seed: %s)',
                $result['instanceVersion'],
                $result['skipAdminSeed'] ? 'yes' : 'no'
            ));
            if (!$result['skipAdminSeed']) {
                $this->getIO()->warning(
                    'Dump has no usable Admin user/organization. Re-run with fresh install, or add admin data manually.'
                );
                return self::FAILURE;
            }
            $this->completeStep($step1, self::STEP_1);

            $this->startStep($step2, self::STEP_2);
            $evaluator = new DatabaseUserPermissionEvaluator(Connection::getConnection());
            $evaluator->evalPrivilegeDatabaseUserPermission();
            $this->completeStep($step2, self::STEP_2);

            $this->startStep($step3, self::STEP_3);
            $fromVersion = $result['currentVersion'];
            $migrationVersions = $appSetupUtility->getVersionsInRange($fromVersion, null, false);
            StateContainer::getInstance()->clearMigrationCompleted();
            foreach ($migrationVersions as $version) {
                Logger::getLogger()->info(json_encode(['version' => $version]));
                $appSetupUtility->runMigrationFor($version);
            }
            $this->completeStep($step3, self::STEP_3);

            $this->startStep($step4, self::STEP_4);
            $appSetupUtility->createDBUser();
            $this->completeStep($step4, self::STEP_4);

            $this->startStep($step5, self::STEP_5, "\n");
            $appSetupUtility->writeConfFile();
            $this->completeStep($step5, self::STEP_5, "\n");
        } catch (Throwable $e) {
            Logger::getLogger()->error($e->getMessage());
            Logger::getLogger()->error($e->getTraceAsString());
            $this->getIO()->error($e->getMessage());
            return self::FAILURE;
        } finally {
            StateContainer::getInstance()->clearSqlDumpPath();
        }

        $this->getIO()->success(
            'FuneralTime installed from SQL dump and upgraded to ' . Config::PRODUCT_VERSION
        );
        return self::SUCCESS;
    }

    private function hasOption(InputInterface $input, string $name): bool
    {
        $value = $input->getOption($name);
        return !($value === null || $value === '');
    }
}
