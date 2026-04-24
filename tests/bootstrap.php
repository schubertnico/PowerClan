<?php

declare(strict_types=1);

/**
 * PowerClan - PHPUnit Bootstrap
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 */

// Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Test-Datenbank-Konfiguration
// Detect if running in Docker (db host resolves) or locally
$isDocker = gethostbyname('db') !== 'db';
$GLOBALS['test_mysql'] = [
    'host' => getenv('DB_HOST') ?: ($isDocker ? 'db' : 'localhost'),
    'user' => getenv('DB_USER') ?: 'powerclan',
    'password' => getenv('DB_PASSWORD') ?: 'powerclan_secure_2024',
    'database' => getenv('DB_DATABASE') ?: 'powerclan_v2.0',
    'port' => (int) (getenv('DB_PORT') ?: ($isDocker ? 3306 : 3316)),
];

// Globale Variablen für Tests initialisieren
$settings = [];
$pcadmin = [];
$loggedin = 'NO';
