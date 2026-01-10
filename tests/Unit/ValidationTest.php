<?php

declare(strict_types=1);

namespace PowerClan\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Comprehensive validation tests
 */
class ValidationTest extends TestCase
{
    protected static bool $functionsLoaded = false;

    protected function setUp(): void
    {
        if (!self::$functionsLoaded) {
            require_once __DIR__ . '/../../functions.inc.php';
            self::$functionsLoaded = true;
        }
    }

    // =========================================================================
    // Email Validation - Edge Cases
    // =========================================================================

    #[Test]
    #[DataProvider('validEmailProvider')]
    public function validateEmailAcceptsValidFormat(string $email): void
    {
        $this->assertTrue(validate_email($email), "Expected '{$email}' to be valid");
    }

    public static function validEmailProvider(): array
    {
        return [
            'simple' => ['user@example.com'],
            'with dots' => ['user.name@example.com'],
            'with plus' => ['user+tag@example.com'],
            'subdomain' => ['user@mail.example.com'],
            'country TLD' => ['user@example.co.uk'],
            'numeric domain' => ['user@123.com'],
            'hyphen domain' => ['user@my-domain.com'],
            'numbers in local' => ['user123@example.com'],
            'underscores' => ['user_name@example.com'],
        ];
    }

    #[Test]
    #[DataProvider('invalidEmailProvider')]
    public function validateEmailRejectsInvalidFormat(string $email): void
    {
        $this->assertFalse(validate_email($email), "Expected '{$email}' to be invalid");
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'no at sign' => ['userexample.com'],
            'no domain' => ['user@'],
            'no local part' => ['@example.com'],
            'double at' => ['user@@example.com'],
            'spaces' => ['user @example.com'],
            'no TLD' => ['user@example'],
            'special chars' => ['user<>@example.com'],
            'empty string' => [''],
            'only whitespace' => ['   '],
            'dot at start' => ['.user@example.com'],
            'dot at end local' => ['user.@example.com'],
            'double dots' => ['user..name@example.com'],
        ];
    }

    // =========================================================================
    // XSS Prevention - e() Function
    // =========================================================================

    #[Test]
    #[DataProvider('xssAttackVectorProvider')]
    public function eBlocksXssAttackVectors(string $input, string $description): void
    {
        $output = e($input);

        // e() escapes HTML special chars - should never contain unescaped < or >
        $this->assertStringNotContainsString('<', $output, "HTML tag not escaped: {$description}");
        $this->assertStringNotContainsString('>', $output, "HTML tag not escaped: {$description}");
    }

    public static function xssAttackVectorProvider(): array
    {
        // Note: e() is for HTML escaping only, not URL sanitization
        // javascript: protocol without HTML tags is NOT blocked by e()
        return [
            'basic script' => ['<script>alert(1)</script>', 'basic script tag'],
            'img onerror' => ['<img src=x onerror=alert(1)>', 'img onerror'],
            'svg onload' => ['<svg onload=alert(1)>', 'svg onload'],
            'body onload' => ['<body onload=alert(1)>', 'body onload'],
            'encoded script' => ['<script>alert(String.fromCharCode(88,83,83))</script>', 'encoded script'],
            'event handler' => ['<div onclick="alert(1)">', 'onclick handler'],
            'style expression' => ['<div style="background:url(javascript:alert(1))">', 'style expression'],
        ];
    }

    // =========================================================================
    // BBCode Security - news_replace() Function
    // =========================================================================

    #[Test]
    public function newsReplaceDoesNotAllowScriptInjection(): void
    {
        $maliciousInputs = [
            '[url]javascript:alert(1)[/url]',
            '[url=javascript:alert(1)]Click[/url]',
            '[email]<script>alert(1)</script>[/email]',
            '<script>[b]bold[/b]</script>',
            '[b]<script>alert(1)</script>[/b]',
        ];

        foreach ($maliciousInputs as $input) {
            $output = news_replace($input);
            $this->assertStringNotContainsString('<script>', $output, "Script tag not escaped in: {$input}");
        }
    }

    #[Test]
    public function newsReplacePreservesSafeBbcode(): void
    {
        $safeInputs = [
            ['[b]Bold[/b]', '<b>Bold</b>'],
            ['[u]Underline[/u]', '<u>Underline</u>'],
            ['[i]Italic[/i]', '<i>Italic</i>'],
        ];

        foreach ($safeInputs as [$input, $expected]) {
            $output = news_replace($input);
            $this->assertStringContainsString($expected, $output, "BBCode not converted: {$input}");
        }
    }

    // =========================================================================
    // Password Validation
    // =========================================================================

    #[Test]
    public function passwordHashIsSecure(): void
    {
        require_once __DIR__ . '/../../admin/functions.inc.php';

        $password = 'MySecurePassword123!';
        $hash = hash_password($password);

        // Should use bcrypt
        $this->assertTrue(str_starts_with($hash, '$2y$'), 'Should use bcrypt algorithm');

        // Should have sufficient cost
        $info = password_get_info($hash);
        $this->assertGreaterThanOrEqual(10, $info['options']['cost'] ?? 0, 'Cost should be at least 10');
    }

    #[Test]
    public function passwordHashDoesNotLeakPassword(): void
    {
        require_once __DIR__ . '/../../admin/functions.inc.php';

        $password = 'SecretPassword';
        $hash = hash_password($password);

        $this->assertStringNotContainsString($password, $hash);
        $this->assertStringNotContainsString(base64_encode($password), $hash);
    }
}
