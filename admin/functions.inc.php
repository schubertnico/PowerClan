<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Functions
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/**
 * Check login credentials with automatic password migration
 * Supports both new password_hash() format and legacy base64 format
 */
function checklogin(string $id, string $password): void
{
    global $loggedin, $pcadmin, $conn;
    $loggedin = 'NO';

    if ($id === '' || $id === '0' || ($password === '' || $password === '0')) {
        return;
    }

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare('SELECT * FROM pc_members WHERE id = ?');
    $idInt = (int) $id;
    $stmt->bind_param('i', $idInt);
    $stmt->execute();
    $result = $stmt->get_result();
    $num = mysqli_num_rows($result);

    if ($num === 1) {
        $pcadmin = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $storedPassword = $pcadmin['password'] ?? '';

        // Check for bcrypt/argon2 hash format
        if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$argon2')) {
            // FIRST: Check if cookie-password matches stored hash (cookie-based session)
            if ($storedPassword === $password) {
                $loggedin = 'YES';
            }
            // SECOND: Try password_verify for plain-text login
            elseif (password_verify($password, $storedPassword)) {
                $loggedin = 'YES';

                // Rehash if needed (algorithm update)
                if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
                    $updateStmt->bind_param('si', $newHash, $idInt);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        }
        // Fallback: Try legacy base64 format and migrate
        elseif ($storedPassword === base64_encode($password)) {
            $loggedin = 'YES';

            // Migrate to secure password hash
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
            $updateStmt->bind_param('si', $newHash, $idInt);
            $updateStmt->execute();
            $updateStmt->close();

            error_log("Password migrated to secure hash for user ID: {$idInt}");
        }
    }

    $stmt->close();
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
            if ($dbSettings !== null) {
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
