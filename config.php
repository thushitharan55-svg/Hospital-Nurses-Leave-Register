<?php
declare(strict_types=1);

define('APP_NAME', 'Hospital Nurses Leave Register');
define('APP_SHORT', 'NLS');

$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . '/..') ?: '');
$root = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: __DIR__ . '/..');
$base = $docRoot !== '' ? str_replace($docRoot, '', $root) : '';
define('BASE_URL', $base === '' || $base === '/' ? '' : $base);
define('ROOT_PATH', $root);

define('LEAVE_ENTITLEMENTS', [
    'annual'  => 21,
    'casual'  => 7,
    'medical' => 14,
]);

define('SESSION_NAME', 'nls_session');
define('SESSION_LIFETIME', 60 * 60 * 8);
