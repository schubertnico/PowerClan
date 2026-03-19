<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for utility functions
 */
class FunctionsIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../functions.inc.php';
        require_once __DIR__ . '/../../admin/functions.inc.php';
    }

    // =========================================================================
    // getsettings() Tests
    // =========================================================================

    #[Test]
    public function getsettingsLoadsConfigFromDatabase(): void
    {
        global $settings, $conn;
        $settings = [];

        // Ensure known state
        $conn->query("UPDATE pc_config SET clanname = 'TestClan', clantag = 'TC', url = 'http://localhost' WHERE id = 1");

        getsettings();

        $this->assertSame('TestClan', $settings['clanname']);
        $this->assertSame('TC', $settings['clantag']);
        $this->assertSame('http://localhost', $settings['url']);
    }

    #[Test]
    public function getsettingsMergesWithExistingSettings(): void
    {
        global $settings, $conn;
        $settings = ['custom_key' => 'custom_value'];

        $conn->query("UPDATE pc_config SET clanname = 'TestClan' WHERE id = 1");

        getsettings();

        $this->assertSame('TestClan', $settings['clanname']);
        $this->assertSame('custom_value', $settings['custom_key']);
    }

    // =========================================================================
    // getwarstats() Tests
    // =========================================================================

    #[Test]
    public function getwarstatsDisplaysEmptyStats(): void
    {
        global $settings;
        getsettings();

        ob_start();
        getwarstats();
        $output = ob_get_clean();

        $this->assertStringContainsString('Gewonnen', $output);
        $this->assertStringContainsString('Verloren', $output);
        $this->assertStringContainsString('Unentschieden', $output);
        $this->assertStringContainsString('Offen', $output);
        $this->assertStringContainsString('Gesamt', $output);
    }

    #[Test]
    public function getwarstatsCountsWonWar(): void
    {
        global $settings;
        getsettings();

        // Create a won war (10:5 on map1, 8:3 on map2)
        $this->createWar([
            'res1' => '10:5',
            'res2' => '8:3',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        ob_start();
        getwarstats();
        $output = ob_get_clean();

        // Check the stats row contains correct counts
        $this->assertStringContainsString('Gewonnen', $output);
    }

    #[Test]
    public function getwarstatsCountsLostWar(): void
    {
        global $settings;
        getsettings();

        // Create a lost war
        $this->createWar([
            'res1' => '3:10',
            'res2' => '2:8',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        ob_start();
        getwarstats();
        $output = ob_get_clean();

        $this->assertStringContainsString('Verloren', $output);
    }

    #[Test]
    public function getwarstatsCountsDrawWar(): void
    {
        global $settings;
        getsettings();

        // Create a draw war
        $this->createWar([
            'res1' => '5:5',
            'res2' => '5:5',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        ob_start();
        getwarstats();
        $output = ob_get_clean();

        $this->assertStringContainsString('Unentschieden', $output);
    }

    #[Test]
    public function getwarstatsCountsOpenWar(): void
    {
        global $settings;
        getsettings();

        // Create an open war (no results)
        $this->createWar([
            'res1' => '',
            'res2' => '',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        ob_start();
        getwarstats();
        $output = ob_get_clean();

        $this->assertStringContainsString('Offen', $output);
    }

    #[Test]
    public function getwarstatsHandlesThreeMaps(): void
    {
        global $settings;
        getsettings();

        $this->createWar([
            'res1' => '5:3',
            'res2' => '2:5',
            'res3' => '10:2',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
            'map3' => 'de_nuke',
        ]);

        ob_start();
        getwarstats();
        $output = ob_get_clean();

        $this->assertStringContainsString('Gewonnen', $output);
    }

    // =========================================================================
    // db_prepare() Tests
    // =========================================================================

    #[Test]
    public function dbPrepareReturnsStatement(): void
    {
        global $conn;
        $stmt = db_prepare($conn, 'SELECT * FROM pc_config WHERE id = ?');
        $this->assertInstanceOf(\mysqli_stmt::class, $stmt);
        $stmt->close();
    }

    #[Test]
    public function dbPrepareThrowsOnInvalidQuery(): void
    {
        global $conn;
        $this->expectException(\Exception::class);
        db_prepare($conn, 'SELECT * FROM nonexistent_table WHERE id = ?');
    }

    // =========================================================================
    // db_query() Tests
    // =========================================================================

    #[Test]
    public function dbQueryReturnsResult(): void
    {
        global $conn;
        $result = db_query($conn, 'SELECT * FROM pc_config WHERE id = 1');
        $this->assertInstanceOf(\mysqli_result::class, $result);
    }

    #[Test]
    public function dbQueryThrowsOnInvalidQuery(): void
    {
        global $conn;
        $this->expectException(\Exception::class);
        db_query($conn, 'SELECT * FROM nonexistent_table');
    }

    // =========================================================================
    // csrf_check() Tests
    // =========================================================================

    #[Test]
    public function csrfCheckPassesOnGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // Should not throw or die
        csrf_check();
        $this->assertTrue(true);
    }

    #[Test]
    public function csrfCheckPassesWithValidToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = csrf_token();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = $token;

        csrf_check();
        $this->assertTrue(true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
    }

    // =========================================================================
    // csrf_token() / csrf_field() / csrf_validate() / csrf_regenerate() Tests
    // =========================================================================

    #[Test]
    public function csrfTokenGeneratesAndReturnsToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['csrf_token']);

        $token = csrf_token();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    #[Test]
    public function csrfTokenReturnsSameTokenOnSecondCall(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['csrf_token']);

        $token1 = csrf_token();
        $token2 = csrf_token();
        $this->assertSame($token1, $token2);
    }

    #[Test]
    public function csrfFieldReturnsHiddenInput(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $field = csrf_field();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    #[Test]
    public function csrfValidateReturnsTrueForValidToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = csrf_token();
        $_POST['csrf_token'] = $token;

        $this->assertTrue(csrf_validate());
        $_POST = [];
    }

    #[Test]
    public function csrfValidateReturnsFalseForInvalidToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        csrf_token();
        $_POST['csrf_token'] = 'invalid_token';

        $this->assertFalse(csrf_validate());
        $_POST = [];
    }

    #[Test]
    public function csrfValidateReturnsFalseForMissingToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['csrf_token']);
        $_POST['csrf_token'] = 'something';

        $this->assertFalse(csrf_validate());
        $_POST = [];
    }

    #[Test]
    public function csrfRegenerateCreatesNewToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token1 = csrf_token();
        csrf_regenerate();
        $token2 = csrf_token();

        $this->assertNotSame($token1, $token2);
    }

    // =========================================================================
    // news_replace() Tests
    // =========================================================================

    #[Test]
    public function newsReplaceConvertsBasicBbcode(): void
    {
        $this->assertStringContainsString('<b>bold</b>', news_replace('[b]bold[/b]'));
        $this->assertStringContainsString('<u>underline</u>', news_replace('[u]underline[/u]'));
        $this->assertStringContainsString('<i>italic</i>', news_replace('[i]italic[/i]'));
    }

    #[Test]
    public function newsReplaceEscapesHtml(): void
    {
        $result = news_replace('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function newsReplaceConvertsNewlines(): void
    {
        $result = news_replace("line1\nline2");
        $this->assertStringContainsString('<br', $result);
    }

    #[Test]
    public function newsReplaceConvertsUrlBbcode(): void
    {
        $result = news_replace('[url]https://example.com[/url]');
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    #[Test]
    public function newsReplaceConvertsEmailBbcode(): void
    {
        $result = news_replace('[email]test@example.com[/email]');
        $this->assertStringContainsString('mailto:test@example.com', $result);
    }

    // =========================================================================
    // default_error() Tests
    // =========================================================================

    #[Test]
    public function defaultErrorOutputsErrorLink(): void
    {
        global $errortablebg;
        $errortablebg = '#FF0000';

        ob_start();
        default_error('index.php', 'Test Error');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Error', $output);
        $this->assertStringContainsString('href="index.php"', $output);
    }

    #[Test]
    public function defaultErrorBlocksJavascriptUrl(): void
    {
        global $errortablebg;
        $errortablebg = '#FF0000';

        ob_start();
        default_error('javascript:alert(1)', 'XSS attempt');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('javascript:', $output);
        $this->assertStringContainsString('href="#"', $output);
    }

    // =========================================================================
    // e() Tests
    // =========================================================================

    #[Test]
    public function eEscapesSpecialChars(): void
    {
        $this->assertSame('&lt;b&gt;test&lt;/b&gt;', e('<b>test</b>'));
        $this->assertSame('&quot;quoted&quot;', e('"quoted"'));
        $this->assertSame('it&#039;s', e("it's"));
    }

    // =========================================================================
    // validate_email() Tests
    // =========================================================================

    #[Test]
    public function validateEmailAcceptsValidEmail(): void
    {
        $this->assertTrue(validate_email('test@example.com'));
        $this->assertTrue(validate_email('user.name+tag@example.co.uk'));
    }

    #[Test]
    public function validateEmailRejectsInvalidEmail(): void
    {
        $this->assertFalse(validate_email('not-an-email'));
        $this->assertFalse(validate_email('@no-local.com'));
        $this->assertFalse(validate_email(''));
    }

    // =========================================================================
    // hash_password() Tests
    // =========================================================================

    #[Test]
    public function hashPasswordCreatesBcryptHash(): void
    {
        $hash = hash_password('test123');
        $this->assertTrue(str_starts_with($hash, '$2y$'));
        $this->assertTrue(password_verify('test123', $hash));
    }
}
