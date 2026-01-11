<?php

declare(strict_types=1);

namespace PowerClan\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for admin/functions.inc.php
 */
class AdminFunctionsTest extends TestCase
{
    private static bool $functionsLoaded = false;

    protected function setUp(): void
    {
        if (!self::$functionsLoaded) {
            require_once __DIR__ . '/../../admin/functions.inc.php';
            self::$functionsLoaded = true;
        }
    }

    // =========================================================================
    // hash_password() Tests
    // =========================================================================

    #[Test]
    public function hashPasswordReturnsValidBcryptHash(): void
    {
        $hash = hash_password('testpassword');

        $this->assertNotEmpty($hash);
        $this->assertTrue(str_starts_with($hash, '$2y$'));
    }

    #[Test]
    public function hashPasswordCanBeVerified(): void
    {
        $password = 'SecurePassword123!';
        $hash = hash_password($password);

        $this->assertTrue(password_verify($password, $hash));
    }

    #[Test]
    public function hashPasswordIsDifferentEachTime(): void
    {
        $password = 'testpassword';
        $hash1 = hash_password($password);
        $hash2 = hash_password($password);

        // Different salts should produce different hashes
        $this->assertNotSame($hash1, $hash2);

        // But both should verify
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }

    #[Test]
    public function hashPasswordHandlesEmptyString(): void
    {
        $hash = hash_password('');

        $this->assertNotEmpty($hash);
        $this->assertTrue(password_verify('', $hash));
    }

    #[Test]
    public function hashPasswordHandlesSpecialCharacters(): void
    {
        $password = "P@ssw0rd!#$%^&*()_+-=[]{}|;':\",./<>?";
        $hash = hash_password($password);

        $this->assertTrue(password_verify($password, $hash));
    }

    #[Test]
    public function hashPasswordHandlesUnicode(): void
    {
        $password = 'Passwort_äöü_日本語';
        $hash = hash_password($password);

        $this->assertTrue(password_verify($password, $hash));
    }

    // =========================================================================
    // validate_email() Tests (Admin version)
    // =========================================================================

    #[Test]
    public function validateEmailAcceptsValidEmails(): void
    {
        $this->assertTrue(validate_email('user@example.com'));
        $this->assertTrue(validate_email('user.name@subdomain.example.com'));
        $this->assertTrue(validate_email('user+tag@example.org'));
        $this->assertTrue(validate_email('firstname.lastname@company.co.uk'));
    }

    #[Test]
    public function validateEmailRejectsInvalidEmails(): void
    {
        $this->assertFalse(validate_email('notanemail'));
        $this->assertFalse(validate_email('missing@domain'));
        $this->assertFalse(validate_email('@nodomain.com'));
        $this->assertFalse(validate_email('spaces in@email.com'));
        $this->assertFalse(validate_email('double@@at.com'));
    }

    #[Test]
    public function validateEmailTrimsWhitespace(): void
    {
        $this->assertTrue(validate_email('  user@example.com  '));
        $this->assertTrue(validate_email("\tuser@example.com\n"));
    }

    // =========================================================================
    // e() Tests (Admin version - HTML escaping)
    // =========================================================================

    #[Test]
    public function eEscapesHtmlTags(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
        $this->assertSame('&lt;/script&gt;', e('</script>'));
        $this->assertSame('&lt;img src=&quot;x&quot; onerror=&quot;alert(1)&quot;&gt;', e('<img src="x" onerror="alert(1)">'));
    }

    #[Test]
    public function eEscapesQuotesAndAmpersands(): void
    {
        $this->assertSame('&quot;double&quot;', e('"double"'));
        $this->assertSame('&#039;single&#039;', e("'single'"));
        $this->assertSame('&amp;', e('&'));
        $this->assertSame('Tom &amp; Jerry', e('Tom & Jerry'));
    }

    #[Test]
    public function eHandlesNonStringTypes(): void
    {
        $this->assertSame('42', e(42));
        $this->assertSame('3.14', e(3.14));
        $this->assertSame('1', e(true));
        $this->assertSame('', e(false));
        $this->assertSame('', e(null));
    }

    #[Test]
    public function ePreservesNormalText(): void
    {
        $this->assertSame('Hello World', e('Hello World'));
        $this->assertSame('Test 123 ABC', e('Test 123 ABC'));
        $this->assertSame('', e(''));
    }

    #[Test]
    public function eHandlesGermanUmlauts(): void
    {
        $this->assertSame('äöüÄÖÜß', e('äöüÄÖÜß'));
        $this->assertSame('Größe', e('Größe'));
    }
}
