<?php

/**
 * One-off validation: apply the full migration chain on an empty database and
 * assert FuneralTime install artefacts exist without duplicate screens.
 *
 * Usage (inside PHP container):
 *   php installer/validate_fresh_install.php
 */

use OrangeHRM\Authentication\Dto\UserCredential;
use OrangeHRM\Config\Config;
use OrangeHRM\Framework\Http\Session\MemorySessionStorage;
use OrangeHRM\Framework\Http\Session\Session;
use OrangeHRM\Framework\ServiceContainer;
use OrangeHRM\Framework\Services;
use OrangeHRM\Installer\Framework\HttpKernel;
use OrangeHRM\Installer\Util\AppSetupUtility;
use OrangeHRM\Installer\Util\Connection;
use OrangeHRM\Installer\Util\DatabaseServerConnection;
use OrangeHRM\Installer\Util\StateContainer;

require dirname(__DIR__) . '/src/vendor/autoload.php';

$dbHost = getenv('FT_VALIDATE_DB_HOST') ?: 'mariadb103';
$dbPort = (string) (getenv('FT_VALIDATE_DB_PORT') ?: '3306');
$dbName = getenv('FT_VALIDATE_DB_NAME') ?: 'funeraltime_install_test';
$dbUser = getenv('FT_VALIDATE_DB_USER') ?: 'root';
$dbPass = getenv('FT_VALIDATE_DB_PASS') ?: 'root';

new HttpKernel('prod', false);
$sessionStorage = new MemorySessionStorage();
ServiceContainer::getContainer()->set(Services::SESSION_STORAGE, $sessionStorage);
$session = new Session($sessionStorage);
$session->start();
ServiceContainer::getContainer()->set(Services::SESSION, $session);

$appSetup = new AppSetupUtility();
$cred = new UserCredential($dbUser, $dbPass);

StateContainer::getInstance()->storeDbInfo($dbHost, $dbPort, $cred, $dbName, $cred, false);
StateContainer::getInstance()->setDbType(AppSetupUtility::INSTALLATION_DB_TYPE_NEW);

DatabaseServerConnection::reset();
Connection::reset();

echo "Creating database `$dbName` on $dbHost:$dbPort...\n";
try {
    DatabaseServerConnection::getConnection()->executeStatement("DROP DATABASE IF EXISTS `$dbName`");
} catch (Throwable $e) {
    // ignore
}
$appSetup->createNewDatabase($dbName);

DatabaseServerConnection::reset();
Connection::reset();
StateContainer::getInstance()->storeDbInfo($dbHost, $dbPort, $cred, $dbName, $cred, false);

echo 'Running migrations 3.3.3 -> ' . Config::PRODUCT_VERSION . "...\n";
$start = microtime(true);
try {
    $appSetup->runMigrations('3.3.3', Config::PRODUCT_VERSION);
} catch (Throwable $e) {
    fwrite(STDERR, 'MIGRATION FAILED: ' . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}
echo sprintf("Migrations finished in %.1fs\n", microtime(true) - $start);

$conn = Connection::getConnection();
$failures = [];

$version = $conn->fetchOne("SELECT value FROM hs_hr_config WHERE name = 'instance.version'");
if ($version !== Config::PRODUCT_VERSION) {
    $failures[] = "instance.version=$version expected " . Config::PRODUCT_VERSION;
}

$requiredScreens = [
    'viewEmailTemplates',
    'saveEmailTemplate',
    'viewMyFuelBankedTime',
    'viewEmployeeFuelBankedTime',
    'viewUfcwRemittanceReport',
    'viewUfcwRemittanceConfig',
    'viewPayrollFillSheetReport',
    'viewTimesheetReminderConfig',
];
foreach ($requiredScreens as $url) {
    $count = (int) $conn->fetchOne(
        'SELECT COUNT(*) FROM ohrm_screen WHERE action_url = ?',
        [$url]
    );
    if ($count !== 1) {
        $failures[] = "screen `$url` count=$count (expected 1)";
    }
}

$dupGroups = (int) $conn->fetchOne(
    'SELECT COUNT(*) FROM (
        SELECT module_id, action_url FROM ohrm_screen
        GROUP BY module_id, action_url HAVING COUNT(*) > 1
     ) d'
);
if ($dupGroups !== 0) {
    $failures[] = "duplicate screen groups=$dupGroups";
}

foreach (['subject_content', 'body_content'] as $col) {
    $exists = $conn->fetchOne(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'ohrm_email_template' AND COLUMN_NAME = ?",
        [$dbName, $col]
    );
    if ((int) $exists !== 1) {
        $failures[] = "missing ohrm_email_template.$col";
    }
}

$templateCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM ohrm_email_template');
if ($templateCount < 15) {
    $failures[] = "email templates=$templateCount (expected >= 15)";
}

foreach (['time.ufcw_remittance', 'time.timesheet_reminder'] as $emailName) {
    $id = $conn->fetchOne('SELECT id FROM ohrm_email WHERE name = ?', [$emailName]);
    if (!$id) {
        $failures[] = "missing email type `$emailName`";
    }
}

$frCa = $conn->fetchOne(
    "SELECT enabled FROM ohrm_i18n_language WHERE code = 'fr_CA'"
);
if (!(int) $frCa) {
    $failures[] = 'fr_CA language not enabled';
}

$emailMenu = $conn->fetchOne(
    "SELECT m.id FROM ohrm_menu_item m
     INNER JOIN ohrm_screen s ON s.id = m.screen_id
     WHERE s.action_url = 'viewEmailTemplates'"
);
if (!$emailMenu) {
    $failures[] = 'Email Templates menu missing';
}

$fuelCol = $conn->fetchOne(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'hs_hr_employee'
       AND COLUMN_NAME = 'fuel_for_banked_time_enabled'",
    [$dbName]
);
if ((int) $fuelCol !== 1) {
    $failures[] = 'missing hs_hr_employee.fuel_for_banked_time_enabled';
}

$tables = [
    'ohrm_fuel_banked_time_request',
    'ohrm_ufcw_initiation_fee',
    'ohrm_timesheet_reminder_config',
];
foreach ($tables as $table) {
    $exists = $conn->fetchOne(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        [$dbName, $table]
    );
    if ((int) $exists !== 1) {
        $failures[] = "missing table `$table`";
    }
}

if ($failures) {
    echo "VALIDATION FAILED:\n - " . implode("\n - ", $failures) . "\n";
    exit(1);
}

echo "VALIDATION OK — fresh install DB `$dbName` reaches "
    . Config::PRODUCT_VERSION
    . " with FuneralTime screens/templates/schema.\n";
exit(0);
