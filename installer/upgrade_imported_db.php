<?php

/**
 * Upgrade an imported OrangeHRM DB (e.g. orangetfc @ 5.8.0) to PRODUCT_VERSION.
 *
 * Usage (inside PHP container):
 *   FT_UPGRADE_DB_NAME=orangetfc php installer/upgrade_imported_db.php
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

$dbHost = getenv('FT_UPGRADE_DB_HOST') ?: 'mariadb103';
$dbPort = (string) (getenv('FT_UPGRADE_DB_PORT') ?: '3306');
$dbName = getenv('FT_UPGRADE_DB_NAME') ?: 'orangetfc';
$dbUser = getenv('FT_UPGRADE_DB_USER') ?: 'root';
$dbPass = getenv('FT_UPGRADE_DB_PASS') ?: 'root';

new HttpKernel('prod', false);
$sessionStorage = new MemorySessionStorage();
ServiceContainer::getContainer()->set(Services::SESSION_STORAGE, $sessionStorage);
$session = new Session($sessionStorage);
$session->start();
ServiceContainer::getContainer()->set(Services::SESSION, $session);

$cred = new UserCredential($dbUser, $dbPass);
StateContainer::getInstance()->storeDbInfo($dbHost, $dbPort, $cred, $dbName, $cred, false);
DatabaseServerConnection::reset();
Connection::reset();

$appSetup = new AppSetupUtility();
$fromKey = $appSetup->getCurrentProductVersionFromDatabase();
if ($fromKey === null) {
    fwrite(STDERR, "Could not map instance.version to a MIGRATIONS_MAP key\n");
    $ver = Connection::getConnection()->fetchOne(
        "SELECT value FROM hs_hr_config WHERE name = 'instance.version'"
    );
    fwrite(STDERR, "instance.version=$ver\n");
    exit(1);
}

echo "Detected migration map key: $fromKey\n";
echo 'Target: ' . Config::PRODUCT_VERSION . "\n";

$versions = $appSetup->getVersionsInRange($fromKey, null, false);
echo 'Will run: ' . implode(', ', $versions) . "\n";

$start = microtime(true);
try {
    foreach ($versions as $version) {
        echo "Migrating $version...\n";
        $appSetup->runMigrationFor($version);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'UPGRADE FAILED at: ' . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}

$elapsed = microtime(true) - $start;
$newVersion = Connection::getConnection()->fetchOne(
    "SELECT value FROM hs_hr_config WHERE name = 'instance.version'"
);
$employees = (int) Connection::getConnection()->fetchOne('SELECT COUNT(*) FROM hs_hr_employee');
$users = (int) Connection::getConnection()->fetchOne('SELECT COUNT(*) FROM ohrm_user');
$dups = (int) Connection::getConnection()->fetchOne(
    'SELECT COUNT(*) FROM (SELECT action_url FROM ohrm_screen GROUP BY action_url HAVING COUNT(*)>1) d'
);

echo sprintf("UPGRADE OK in %.1fs\n", $elapsed);
echo "instance.version=$newVersion employees=$employees users=$users duplicate_screens=$dups\n";

if ($newVersion !== Config::PRODUCT_VERSION) {
    fwrite(STDERR, 'Version mismatch: expected ' . Config::PRODUCT_VERSION . "\n");
    exit(1);
}
if ($dups !== 0) {
    fwrite(STDERR, "Duplicate screens remain: $dups\n");
    exit(1);
}
exit(0);
