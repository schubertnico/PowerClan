<?php

declare(strict_types=1);

namespace PowerClan\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Tests for CSRF protection functions
 */
class CSRFProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear session before each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        parent::tearDown();
    }

    public function testCsrfTokenGeneratesToken(): void
    {
        $token = csrf_token();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testCsrfTokenReturnsSameTokenInSession(): void
    {
        $token1 = csrf_token();
        $token2 = csrf_token();

        $this->assertEquals($token1, $token2);
    }

    public function testCsrfFieldGeneratesHiddenInput(): void
    {
        $field = csrf_field();

        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    public function testCsrfValidateReturnsFalseWithoutToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = '';

        $this->assertFalse(csrf_validate());
    }

    public function testCsrfValidateReturnsFalseWithWrongToken(): void
    {
        // Generate a valid token first
        $validToken = csrf_token();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = 'invalid_token_12345';

        $this->assertFalse(csrf_validate());
    }

    public function testCsrfValidateReturnsTrueWithCorrectToken(): void
    {
        // Generate token and store in session
        $token = csrf_token();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = $token;

        $this->assertTrue(csrf_validate());
    }

    public function testCsrfRegenerateCreatesNewToken(): void
    {
        $token1 = csrf_token();
        csrf_regenerate();
        $token2 = csrf_token();

        $this->assertNotEquals($token1, $token2);
    }

    public function testCsrfTokenIsSecureRandom(): void
    {
        // Generate multiple tokens and ensure they're all different
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            csrf_regenerate();
            $tokens[] = csrf_token();
        }

        $uniqueTokens = array_unique($tokens);
        $this->assertCount(10, $uniqueTokens, 'All generated tokens should be unique');
    }

    public function testCsrfFieldEscapesHtmlSpecialChars(): void
    {
        $field = csrf_field();

        // The field should be a valid hidden input
        $this->assertStringNotContainsString('<script>', $field);

        // Verify it's a properly formed input tag
        $this->assertMatchesRegularExpression(
            '/<input type="hidden" name="csrf_token" value="[a-f0-9]{64}">/',
            $field
        );
    }
}
