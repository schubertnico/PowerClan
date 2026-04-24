<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Footer Include File
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

// Safely include footer file if configured and exists
$footerFile = $settings['footer'] ?? '';
if (!empty($footerFile) && is_string($footerFile)) {
    // Security: Only allow files from the current directory
    $footerFile = basename($footerFile);
    $footerPath = __DIR__ . '/' . $footerFile;
    if (file_exists($footerPath) && is_file($footerPath)) {
        include $footerPath;
    }
}
