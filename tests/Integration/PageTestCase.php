<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

/**
 * Base class for page rendering integration tests
 *
 * Provides methods to render pages via output buffering and include.
 */
abstract class PageTestCase extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load functions needed by all pages
        require_once __DIR__ . '/../../functions.inc.php';
        require_once __DIR__ . '/../../admin/functions.inc.php';

        // Set default SERVER vars
        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = [];
        $_GET = [];
        $_POST = [];

        // Load settings from DB
        $this->loadSettings();
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        parent::tearDown();
    }

    /**
     * Load settings from the database into global $settings
     */
    protected function loadSettings(): void
    {
        global $conn, $settings;

        // Reset config to known state
        $conn->query("UPDATE pc_config SET clanname = 'TestClan', clantag = 'TC', url = 'http://localhost', "
            . "serverpath = '/var/www/html', header = '', footer = '', "
            . "tablebg1 = '#000000', tablebg2 = '#111111', tablebg3 = '#222222', "
            . "clrwon = '#00FF00', clrdraw = '#FFFF00', clrlost = '#FF0000', "
            . "newslimit = 10, warlimit = 10 WHERE id = 1");

        $result = $conn->query('SELECT * FROM pc_config WHERE id = 1');
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (is_array($row)) {
                $settings = $row;
            }
        }
    }

    /**
     * Render a frontend page and return its HTML output
     */
    protected function renderPage(string $path, array $get = [], array $post = []): string
    {
        global $conn, $settings, $errortablebg;

        $_GET = $get;
        $_POST = $post;
        if ($post !== []) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
        }

        $errortablebg = $settings['tablebg1'] ?? '#000000';

        ob_start();
        try {
            include $path;
        } finally {
            $output = ob_get_clean();
        }

        return $output !== false ? $output : '';
    }

    /**
     * Render an admin page with a logged-in admin session
     */
    protected function renderAdminPage(string $path, int $adminId, array $get = [], array $post = []): string
    {
        global $conn, $settings, $pcadmin, $loggedin, $errortablebg;

        // Set up admin login state
        $admin = $this->getMember($adminId);
        if ($admin !== null) {
            $pcadmin = $admin;
            $loggedin = 'YES';

            // Set up server-side session so checklogin() finds the member
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['member_id'] = (int) $admin['id'];
            $_SESSION['logged_in_at'] = time();
        }

        $_GET = $get;
        $_POST = $post;
        $_SERVER['PHP_SELF'] = '/' . basename($path);
        if ($post !== []) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
        }

        $errortablebg = $settings['tablebg1'] ?? '#000000';

        // Set CSRF token in session for POST requests
        if ($post !== [] && !isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = 'test_csrf_token_123';
        }

        ob_start();
        try {
            include $path;
        } finally {
            $output = ob_get_clean();
        }

        return $output !== false ? $output : '';
    }

    /**
     * Set up CSRF token for form submissions
     */
    protected function setupCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = 'test_csrf_token_' . bin2hex(random_bytes(8));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}
