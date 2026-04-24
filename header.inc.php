<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Header Include File
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

if (file_exists(__DIR__ . '/config.inc.php') && file_exists(__DIR__ . '/mysql.inc.php') && file_exists(__DIR__ . '/functions.inc.php')) {
    require_once __DIR__ . '/config.inc.php';
    $errortablebg = $settings['tablebg1'] ?? '#000000';
    require_once __DIR__ . '/mysql.inc.php';
    require_once __DIR__ . '/functions.inc.php';
} else {
    echo '<center><b>Es fehlen wichtige Dateien!</b></center>';
    exit;
}

getsettings();

// Safely include header file if configured and exists
$headerFile = $settings['header'] ?? '';
if (!empty($headerFile) && is_string($headerFile)) {
    // Security: Only allow files from the current directory
    $headerFile = basename($headerFile);
    $headerPath = __DIR__ . '/' . $headerFile;
    if (file_exists($headerPath) && is_file($headerPath)) {
        include $headerPath;
    }
}
