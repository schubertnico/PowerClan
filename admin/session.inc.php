<?php

declare(strict_types=1);

/**
 * PowerClan - Admin Session Helper
 *
 * Serverseitige PHP-Session statt Passwort-Hash im Cookie.
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 */

function pc_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_name('pc_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function pc_session_login(int $memberId): void
{
    pc_session_start();
    session_regenerate_id(true);
    $_SESSION['member_id'] = $memberId;
    $_SESSION['logged_in_at'] = time();
}

function pc_session_logout(): void
{
    pc_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $name = session_name();
        if ($name !== false) {
            setcookie(
                $name,
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]
            );
        }
    }
    session_destroy();
}

function pc_session_current_member_id(): int
{
    pc_session_start();
    return (int) ($_SESSION['member_id'] ?? 0);
}

function pc_session_rotate_after_password_change(): void
{
    pc_session_start();
    session_regenerate_id(true);
    $_SESSION['password_changed_at'] = time();
}
