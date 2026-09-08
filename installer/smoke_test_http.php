<?php

/**
 * Authenticated HTTP smoke test against the running FuneralTime instance.
 *
 * Usage (inside PHP container):
 *   php installer/smoke_test_http.php
 */

declare(strict_types=1);

$hostHeader = getenv('FT_SMOKE_HOST') ?: 'php83';
$baseHost = getenv('FT_SMOKE_BASE_HOST') ?: 'os_dev_nginx';
$base = rtrim(getenv('FT_SMOKE_BASE') ?: ('http://' . $baseHost . '/FuneralTime/web/index.php'), '/');
$user = getenv('FT_SMOKE_USER') ?: 'FTAdmin';
$pass = getenv('FT_SMOKE_PASS') ?: 'PTWorks55%';
$cookieFile = sys_get_temp_dir() . '/ft_smoke_cookies.txt';
@unlink($cookieFile);

$fail = 0;
$nginxIp = gethostbyname($baseHost);
if ($nginxIp === $baseHost) {
    fwrite(STDERR, "Cannot resolve $baseHost\n");
    exit(1);
}

function rewriteAppUrl(string $url, string $baseHost): string
{
    $parts = parse_url($url);
    if ($parts === false || empty($parts['path'])) {
        return $url;
    }
    $path = $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
    return 'http://' . $baseHost . $path;
}

function req(
    string $method,
    string $url,
    string $hostHeader,
    string $cookieFile,
    string $nginxIp,
    string $baseHost,
    array $opts = []
): array {
    $maxHops = 8;
    $body = '';
    $code = 0;
    for ($i = 0; $i < $maxHops; $i++) {
        $ch = curl_init($url);
        $headers = ['Host: ' . $hostHeader];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RESOLVE => [
                $hostHeader . ':80:' . $nginxIp,
                $baseHost . ':80:' . $nginxIp,
            ],
        ]);
        if (!empty($opts['postFields']) && $method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['postFields']);
        }
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('curl failed: ' . $err . ' url=' . $url);
        }
        $header = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $follow = $opts['follow'] ?? false;
        if ($follow && in_array($code, [301, 302, 303, 307, 308], true)
            && preg_match('/^Location:\s*(.+)$/mi', $header, $m)
        ) {
            $location = trim($m[1]);
            if (!preg_match('#^https?://#i', $location)) {
                $location = 'http://' . $baseHost . $location;
            }
            $url = rewriteAppUrl($location, $baseHost);
            $method = 'GET';
            unset($opts['postFields']);
            continue;
        }
        break;
    }
    return [$code, $body];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $fail;
    if ($ok) {
        echo "OK  $label" . ($detail !== '' ? " ($detail)" : '') . "\n";
    } else {
        echo "FAIL $label" . ($detail !== '' ? " ($detail)" : '') . "\n";
        $fail = 1;
    }
}

echo "== Login page ==\n";
[$code, $html] = req('GET', $base . '/auth/login', $hostHeader, $cookieFile, $nginxIp, $baseHost);
check('login page', $code === 200, (string) $code);

$token = null;
if (preg_match('/:token="&quot;([^&]+)&quot;"/', $html, $m)) {
    $token = html_entity_decode($m[1], ENT_QUOTES);
} elseif (preg_match('/:token="\\"([^\\\\"]+)\\""/', $html, $m)) {
    $token = $m[1];
}
check('csrf token present', is_string($token) && $token !== '', $token ? 'len=' . strlen($token) : 'missing');
if (!$token) {
    fwrite(STDERR, "Cannot continue without CSRF token\n");
    exit(1);
}

echo "== Authenticate ==\n";
[$code, $body] = req('POST', $base . '/auth/validate', $hostHeader, $cookieFile, $nginxIp, $baseHost, [
    'follow' => true,
    'postFields' => http_build_query([
        'username' => $user,
        'password' => $pass,
        '_token' => $token,
    ]),
]);
$loggedIn = $code === 200 && !str_contains($body, 'auth-login');
check('login session', $loggedIn, (string) $code);

$pages = [
    'time/viewMyFuelBankedTime',
    'time/viewEmployeeFuelBankedTime',
    'time/viewUfcwRemittanceReport',
    'time/viewUfcwRemittanceConfig',
    'time/viewPayrollFillSheetReport',
    'time/viewTimesheetReminderConfig',
    'admin/viewEmailTemplates',
    'admin/saveEmailTemplate',
    'leave/viewLeaveEntitlementHistoryReport',
    'leave/viewMyLeaveEntitlementHistoryReport',
];

echo "== Authenticated pages ==\n";
foreach ($pages as $path) {
    [$code, $body] = req('GET', $base . '/' . $path, $hostHeader, $cookieFile, $nginxIp, $baseHost, ['follow' => true]);
    $size = strlen($body);
    $hasError = (bool) preg_match('/NonUniqueResultException|Fatal error|Whoops!|Uncaught /i', $body);
    $ok = $code === 200 && $size >= 500 && !$hasError && !str_contains($body, 'auth-login');
    check($path, $ok, "$code / {$size}b" . ($hasError ? ' ERROR_IN_BODY' : ''));
}

$apis = [
    'api/v2/admin/emails',
    'api/v2/admin/email-templates',
    'api/v2/time/ufcw-remittance/config',
    'api/v2/time/fuel-banked-time/requests',
    'api/v2/leave/leave-types',
];

echo "== API smoke ==\n";
foreach ($apis as $api) {
    [$code, $body] = req('GET', $base . '/' . $api, $hostHeader, $cookieFile, $nginxIp, $baseHost);
    $ok = $code === 200;
    check($api, $ok, (string) $code . ($ok ? '' : ' body=' . substr($body, 0, 160)));
}

echo "== Live DB ==\n";
$pdo = new PDO('mysql:host=mariadb103;dbname=funeraltime', 'root', 'root');
$dups = (int) $pdo->query(
    'SELECT COUNT(*) FROM (SELECT action_url FROM ohrm_screen GROUP BY action_url HAVING COUNT(*)>1) d'
)->fetchColumn();
$version = (string) $pdo->query("SELECT value FROM hs_hr_config WHERE name='instance.version'")->fetchColumn();
$templates = (int) $pdo->query('SELECT COUNT(*) FROM ohrm_email_template')->fetchColumn();
check('duplicate screens', $dups === 0, (string) $dups);
$expectedVersion = '5.9.15';
$okVersion = version_compare($version, '5.9.14', '>=');
check('instance.version', $okVersion, "$version (product $expectedVersion)");
check('email templates', $templates >= 15, (string) $templates);

if ($fail !== 0) {
    echo "SMOKE FAILED\n";
    exit(1);
}
echo "SMOKE OK\n";
exit(0);
