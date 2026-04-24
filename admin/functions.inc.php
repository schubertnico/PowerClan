<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Functions
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

require_once __DIR__ . '/session.inc.php';

/**
 * Load current admin from serverside session.
 * Sets $loggedin and $pcadmin globals.
 */
function checklogin(): void
{
    global $loggedin, $pcadmin, $conn;
    $loggedin = 'NO';
    $pcadmin = [];

    $memberId = pc_session_current_member_id();
    if ($memberId <= 0) {
        return;
    }

    $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE id = ?');
    $stmt->bind_param('i', $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result instanceof mysqli_result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if (is_array($row)) {
            $pcadmin = $row;
            $loggedin = 'YES';
        }
    }
    $stmt->close();
}

/**
 * Check whether the current admin has a specific permission.
 * Superadmins always return true.
 */
function pc_can(string $perm): bool
{
    global $pcadmin;
    if (($pcadmin['superadmin'] ?? 'NO') === 'YES') {
        return true;
    }
    return ($pcadmin[$perm] ?? 'NO') === 'YES';
}

/**
 * Load settings from database (admin version)
 */
if (!function_exists('getsettings')) {
    function getsettings(): void
    {
        global $conn, $settings;

        $query = 'SELECT * FROM pc_config WHERE id = 1';
        $result = $conn->query($query);

        if ($result === false) {
            echo '<center><b>Die Konfiguration konnte nicht geladen werden!</b></center>';
            exit;
        }

        $num = mysqli_num_rows($result);
        if ($num === 1) {
            $dbSettings = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if (is_array($dbSettings)) {
                $settings = array_merge($settings ?? [], $dbSettings);
            }
        } else {
            echo '<center><b>Die Konfiguration konnte nicht geladen werden!</b></center>';
            exit;
        }
    }
} // end function_exists('getsettings')

/**
 * Hash a password securely
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Safely escape output for HTML
 */
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate email address using filter_var (replaces deprecated eregi)
 */
if (!function_exists('validate_email')) {
    function validate_email(string $email): bool
    {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }
}
