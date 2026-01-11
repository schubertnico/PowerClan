<?php

declare(strict_types=1);

namespace PowerClan\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Tests for XSS protection via e() helper function
 */
class XSSProtectionTest extends TestCase
{
    public function testEscapesHtmlTags(): void
    {
        $input = '<script>alert("XSS")</script>';
        $output = e($input);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testEscapesDoubleQuotes(): void
    {
        $input = '" onmouseover="alert(1)"';
        $output = e($input);

        $this->assertStringNotContainsString('"', $output);
        $this->assertStringContainsString('&quot;', $output);
    }

    public function testEscapesSingleQuotes(): void
    {
        $input = "' onclick='alert(1)'";
        $output = e($input);

        $this->assertStringNotContainsString("'", $output);
        $this->assertStringContainsString('&#039;', $output);
    }

    public function testEscapesAmpersand(): void
    {
        $input = '&amp; test & more';
        $output = e($input);

        // Should escape & but not double-escape &amp;
        $this->assertStringContainsString('&amp;', $output);
    }

    public function testEscapesLessThanGreaterThan(): void
    {
        $input = '<img src=x onerror=alert(1)>';
        $output = e($input);

        $this->assertStringNotContainsString('<', $output);
        $this->assertStringNotContainsString('>', $output);
        $this->assertStringContainsString('&lt;', $output);
        $this->assertStringContainsString('&gt;', $output);
    }

    public function testHandlesNullInput(): void
    {
        $output = e(null);

        $this->assertEquals('', $output);
    }

    public function testHandlesNumericInput(): void
    {
        $output = e(12345);

        $this->assertEquals('12345', $output);
    }

    public function testHandlesFloatInput(): void
    {
        $output = e(123.45);

        $this->assertEquals('123.45', $output);
    }

    public function testHandlesEmptyString(): void
    {
        $output = e('');

        $this->assertEquals('', $output);
    }

    public function testPreservesNormalText(): void
    {
        $input = 'Hello World! This is normal text.';
        $output = e($input);

        $this->assertEquals($input, $output);
    }

    public function testEscapesEventHandlers(): void
    {
        $attacks = [
            '<div onmouseover="alert(1)">',
            '<a href="javascript:alert(1)">',
            '<img src="x" onerror="alert(1)">',
            '<body onload="alert(1)">',
            '<svg onload="alert(1)">',
        ];

        foreach ($attacks as $attack) {
            $output = e($attack);
            $this->assertStringNotContainsString('<', $output, "Failed to escape: $attack");
        }
    }

    public function testEscapesScriptVariations(): void
    {
        $attacks = [
            '<SCRIPT>alert(1)</SCRIPT>',
            '<ScRiPt>alert(1)</ScRiPt>',
            '<script src="evil.js"></script>',
            '<script type="text/javascript">alert(1)</script>',
        ];

        foreach ($attacks as $attack) {
            $output = e($attack);
            $this->assertStringNotContainsString('<', $output, "Failed to escape: $attack");
        }
    }

    public function testEscapesHtmlEntitiesInAttributes(): void
    {
        $input = 'value="test" onclick="alert(1)"';
        $output = e($input);

        // Quotes should be escaped
        $this->assertStringNotContainsString('"', $output);
    }

    public function testOutputIsSafeForHtmlContext(): void
    {
        $malicious = '<script>document.cookie</script>';
        $output = e($malicious);

        // When output is inserted into HTML, it should be safe
        $html = "<div>$output</div>";

        // The output should not contain executable script tags
        $this->assertStringNotContainsString('<script>', $html);
    }
}
