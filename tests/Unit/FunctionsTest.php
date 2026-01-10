<?php

declare(strict_types=1);

namespace PowerClan\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for functions.inc.php
 */
class FunctionsTest extends TestCase
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
    // validate_email() Tests
    // =========================================================================

    #[Test]
    public function validateEmailWithValidEmail(): void
    {
        $this->assertTrue(validate_email('test@example.com'));
        $this->assertTrue(validate_email('user.name@domain.org'));
        $this->assertTrue(validate_email('user+tag@example.co.uk'));
    }

    #[Test]
    public function validateEmailWithInvalidEmail(): void
    {
        $this->assertFalse(validate_email('invalid'));
        $this->assertFalse(validate_email('invalid@'));
        $this->assertFalse(validate_email('@domain.com'));
        $this->assertFalse(validate_email('user@.com'));
        $this->assertFalse(validate_email('user@domain'));
    }

    #[Test]
    public function validateEmailWithEmptyString(): void
    {
        $this->assertFalse(validate_email(''));
        $this->assertFalse(validate_email('   '));
    }

    // =========================================================================
    // e() Tests - HTML Escaping
    // =========================================================================

    #[Test]
    public function eEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', e('<img src=x onerror=alert(1)>'));
    }

    #[Test]
    public function eEscapesQuotes(): void
    {
        $this->assertSame('&quot;quoted&quot;', e('"quoted"'));
        $this->assertSame('&#039;single&#039;', e("'single'"));
    }

    #[Test]
    public function eEscapesAmpersand(): void
    {
        $this->assertSame('&amp;amp;', e('&amp;'));
        $this->assertSame('foo &amp; bar', e('foo & bar'));
    }

    #[Test]
    public function eHandlesNormalStrings(): void
    {
        $this->assertSame('Hello World', e('Hello World'));
        $this->assertSame('Test 123', e('Test 123'));
        $this->assertSame('', e(''));
    }

    #[Test]
    public function eConvertsNonStrings(): void
    {
        $this->assertSame('123', e(123));
        $this->assertSame('45.67', e(45.67));
        $this->assertSame('', e(null));
    }

    // =========================================================================
    // news_replace() Tests - BBCode Conversion
    // =========================================================================

    #[Test]
    public function newsReplaceConvertsBoldTag(): void
    {
        $result = news_replace('[b]bold text[/b]');
        $this->assertStringContainsString('<b>bold text</b>', $result);
    }

    #[Test]
    public function newsReplaceConvertsUnderlineTag(): void
    {
        $result = news_replace('[u]underlined[/u]');
        $this->assertStringContainsString('<u>underlined</u>', $result);
    }

    #[Test]
    public function newsReplaceConvertsItalicTag(): void
    {
        $result = news_replace('[i]italic[/i]');
        $this->assertStringContainsString('<i>italic</i>', $result);
    }

    #[Test]
    public function newsReplaceConvertsUrlTag(): void
    {
        $result = news_replace('[url]https://example.com[/url]');
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    #[Test]
    public function newsReplaceConvertsUrlWithLabel(): void
    {
        $result = news_replace('[url=https://example.com]Click Here[/url]');
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('>Click Here</a>', $result);
    }

    #[Test]
    public function newsReplaceConvertsEmailTag(): void
    {
        $result = news_replace('[email]test@example.com[/email]');
        $this->assertStringContainsString('href="mailto:test@example.com"', $result);
    }

    #[Test]
    public function newsReplaceConvertsEmailWithLabel(): void
    {
        $result = news_replace('[email=test@example.com]Contact Us[/email]');
        $this->assertStringContainsString('href="mailto:test@example.com"', $result);
        $this->assertStringContainsString('>Contact Us</a>', $result);
    }

    #[Test]
    public function newsReplaceConvertsNewlines(): void
    {
        $result = news_replace("Line 1\nLine 2");
        $this->assertStringContainsString('<br', $result);
    }

    #[Test]
    public function newsReplaceEscapesXss(): void
    {
        $result = news_replace('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function newsReplaceHandlesNestedTags(): void
    {
        $result = news_replace('[b][u]bold and underlined[/u][/b]');
        $this->assertStringContainsString('<b><u>bold and underlined</u></b>', $result);
    }

    // =========================================================================
    // default_error() Tests
    // =========================================================================

    #[Test]
    public function defaultErrorOutputsErrorMessage(): void
    {
        global $errortablebg;
        $errortablebg = '#FF0000';

        ob_start();
        default_error('http://example.com', 'Test Error');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Error', $output);
        $this->assertStringContainsString('href="http://example.com"', $output);
    }

    #[Test]
    public function defaultErrorEscapesXssInUrl(): void
    {
        global $errortablebg;
        $errortablebg = '#FF0000';

        ob_start();
        default_error('javascript:alert(1)', 'Click me');
        $output = ob_get_clean();

        // URL should be escaped
        $this->assertStringNotContainsString('javascript:alert', $output);
    }

    #[Test]
    public function defaultErrorEscapesXssInMessage(): void
    {
        global $errortablebg;
        $errortablebg = '#FF0000';

        ob_start();
        default_error('http://example.com', '<script>alert(1)</script>');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }
}
