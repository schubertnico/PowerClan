<?php

declare(strict_types=1);

/**
 * PHPStan Bootstrap - Deklariert globale Variablen für statische Analyse
 *
 * Diese Datei wird nur von PHPStan geladen und definiert Typen für
 * globale Variablen, die durch Include-Dateien zur Laufzeit verfügbar sind.
 */

/** @var mysqli $conn Database connection from mysql.inc.php */
$conn = new mysqli();

/** @var array{host: string, user: string, password: string, database: string, port: int} $mysql */
$mysql = [
    'host' => '',
    'user' => '',
    'password' => '',
    'database' => '',
    'port' => 3306,
];

/** @var array<string, mixed> $pcadmin Current admin user data from header.inc.php */
$pcadmin = [];

/** @var array<string, mixed> $settings Site settings from getsettings() */
$settings = [];

/** @var string $admin_tbl1 Table background color 1 */
$admin_tbl1 = '';

/** @var string $admin_tbl2 Table background color 2 */
$admin_tbl2 = '';

/** @var string $admin_tbl3 Table background color 3 */
$admin_tbl3 = '';

/** @var array<int, string> $leagues Available leagues */
$leagues = [];

/** @var string $bgcolor Row background color for alternating rows */
$bgcolor = '';

/** @var float $version PowerClan version number */
$version = 2.00;
