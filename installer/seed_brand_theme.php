<?php

/**
 * Seed Timiskaming FC brand images into ohrm_theme.custom.
 *
 * Usage: php installer/seed_brand_theme.php
 */

declare(strict_types=1);

$dbHost = getenv('FT_DB_HOST') ?: 'mariadb103';
$dbName = getenv('FT_DB_NAME') ?: 'orangetfc';
$dbUser = getenv('FT_DB_USER') ?: 'root';
$dbPass = getenv('FT_DB_PASS') ?: 'root';

$markPath = dirname(__DIR__) . '/web/images/brand/timiskaming-fc-mark.png';
$bannerPath = dirname(__DIR__) . '/web/images/brand/timiskaming-fc-banner.png';

foreach ([$markPath, $bannerPath] as $path) {
    if (!is_readable($path)) {
        fwrite(STDERR, "Missing $path\n");
        exit(1);
    }
}

$mark = file_get_contents($markPath);
$banner = file_get_contents($bannerPath);

$pdo = new PDO(
    "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare(
    'UPDATE ohrm_theme SET
        client_logo = :mark,
        client_logo_filename = :markName,
        client_logo_file_type = :png,
        client_logo_file_size = :markSize,
        client_banner = :banner,
        client_banner_filename = :bannerName,
        client_banner_file_type = :png2,
        client_banner_file_size = :bannerSize,
        login_banner = :loginBanner,
        login_banner_filename = :loginName,
        login_banner_file_type = :png3,
        login_banner_file_size = :loginSize
     WHERE theme_name = :theme'
);
$stmt->execute([
    'mark' => $mark,
    'markName' => 'timiskaming-fc-mark.png',
    'png' => 'image/png',
    'markSize' => strlen($mark),
    'banner' => $banner,
    'bannerName' => 'timiskaming-fc-banner.png',
    'png2' => 'image/png',
    'bannerSize' => strlen($banner),
    'loginBanner' => $banner,
    'loginName' => 'timiskaming-fc-banner.png',
    'png3' => 'image/png',
    'loginSize' => strlen($banner),
    'theme' => 'custom',
]);

$row = $pdo->query(
    "SELECT theme_name, client_logo_filename, client_banner_filename, login_banner_filename,
            LENGTH(client_logo) AS cl, LENGTH(client_banner) AS cb, LENGTH(login_banner) AS lb
     FROM ohrm_theme WHERE theme_name='custom'"
)->fetch(PDO::FETCH_ASSOC);

echo "THEME_SEEDED " . json_encode($row) . "\n";
