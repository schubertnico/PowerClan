<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Database Connection Handler
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

if (!isset($conn)) {
    try {
        $conn = mysqli_connect(
            $mysql['host'],
            $mysql['user'],
            $mysql['password'],
            $mysql['database'],
            $mysql['port']
        );

        if (!$conn) {
            throw new Exception('PowerClan: MySQL connection failed! Error: ' . mysqli_connect_error());
        }

        // Set charset to UTF-8 for security
        mysqli_set_charset($conn, 'utf8mb4');
    } catch (Exception $e) {
        error_log($e->getMessage());
        die('<center><b>Database connection error. Please check logs.</b></center>');
    }
}
