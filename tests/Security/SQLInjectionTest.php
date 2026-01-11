<?php

declare(strict_types=1);

namespace PowerClan\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Tests to verify SQL injection protection
 *
 * These tests verify that the codebase uses prepared statements
 * and doesn't have SQL injection vulnerabilities.
 */
class SQLInjectionTest extends TestCase
{
    /**
     * @var array<string> Files that should use prepared statements
     */
    private array $phpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Get all PHP files (excluding vendor and tests)
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../..', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();
                // Skip vendor, tests, and config files
                if (
                    strpos($path, 'vendor') === false &&
                    strpos($path, 'tests') === false &&
                    strpos($path, 'phpstan') === false &&
                    strpos($path, 'rector') === false
                ) {
                    $this->phpFiles[] = $path;
                }
            }
        }
    }

    public function testNoDirectVariableInSqlQueries(): void
    {
        $dangerousPatterns = [
            // Direct variable in WHERE clause
            '/WHERE\s+\w+\s*=\s*[\'"]?\$\w+[\'"]?/i',
            // Direct variable in INSERT VALUES
            '/VALUES\s*\([^)]*\$\w+[^)]*\)/i',
            // Direct variable in UPDATE SET
            '/SET\s+\w+\s*=\s*[\'"]?\$\w+[\'"]?/i',
        ];

        $violations = [];

        foreach ($this->phpFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            // Skip files that don't contain SQL
            if (stripos($content, 'SELECT') === false &&
                stripos($content, 'INSERT') === false &&
                stripos($content, 'UPDATE') === false &&
                stripos($content, 'DELETE') === false) {
                continue;
            }

            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    // Check if it's inside a prepare() call (which is safe)
                    $beforeMatch = substr($content, 0, strpos($content, $matches[0]));
                    $lastPrepare = strrpos($beforeMatch, 'prepare(');
                    $lastQuery = strrpos($beforeMatch, 'query(');

                    // If the last SQL function before this match is query(), it's potentially dangerous
                    if ($lastQuery !== false && ($lastPrepare === false || $lastQuery > $lastPrepare)) {
                        $violations[] = [
                            'file' => basename($file),
                            'pattern' => $matches[0],
                        ];
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Found potential SQL injection vulnerabilities: ' . print_r($violations, true)
        );
    }

    public function testAllSqlQueriesUsePreparedStatements(): void
    {
        $queryPattern = '/\$\w+->query\s*\(\s*["\'][^"\']*\$/i';
        $violations = [];

        foreach ($this->phpFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            // Look for query() with variable interpolation
            if (preg_match($queryPattern, $content, $matches)) {
                $violations[] = [
                    'file' => basename($file),
                    'match' => $matches[0],
                ];
            }
        }

        $this->assertEmpty(
            $violations,
            'Found query() calls with variable interpolation (use prepare() instead): ' . print_r($violations, true)
        );
    }

    public function testPreparedStatementsUseBindParam(): void
    {
        $prepareWithoutBind = [];

        foreach ($this->phpFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            // Count prepare() calls
            preg_match_all('/->prepare\s*\(/i', $content, $prepareMatches);
            $prepareCount = count($prepareMatches[0]);

            if ($prepareCount === 0) {
                continue;
            }

            // Count bind_param() calls
            preg_match_all('/->bind_param\s*\(/i', $content, $bindMatches);
            $bindCount = count($bindMatches[0]);

            // Also count execute() with array parameter (alternative binding)
            preg_match_all('/->execute\s*\(\s*\[/i', $content, $executeWithArrayMatches);
            $executeWithArrayCount = count($executeWithArrayMatches[0]);

            $totalBinds = $bindCount + $executeWithArrayCount;

            // Every prepare should have a corresponding bind
            if ($prepareCount > $totalBinds) {
                $prepareWithoutBind[] = [
                    'file' => basename($file),
                    'prepares' => $prepareCount,
                    'binds' => $totalBinds,
                ];
            }
        }

        // This is a soft check - some prepares might not need binds (no parameters)
        // We verify that most files have proper binding
        $this->assertEmpty(
            $prepareWithoutBind,
            'Some files have significantly more prepare() than bind_param() calls: ' .
            print_r($prepareWithoutBind, true)
        );
    }

    public function testNoStringConcatenationInSql(): void
    {
        // Pattern for SQL with string concatenation
        $dangerousPatterns = [
            '/["\']SELECT[^"\']*["\']\s*\.\s*\$/i',
            '/["\']INSERT[^"\']*["\']\s*\.\s*\$/i',
            '/["\']UPDATE[^"\']*["\']\s*\.\s*\$/i',
            '/["\']DELETE[^"\']*["\']\s*\.\s*\$/i',
        ];

        $violations = [];

        foreach ($this->phpFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $violations[] = [
                        'file' => basename($file),
                        'match' => substr($matches[0], 0, 50) . '...',
                    ];
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Found SQL queries with string concatenation: ' . print_r($violations, true)
        );
    }

    /**
     * Test that common SQL injection payloads would be escaped
     */
    public function testSqlInjectionPayloadsAreNeutralized(): void
    {
        $payloads = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "1; DELETE FROM members WHERE '1'='1",
            "admin'--",
            "' UNION SELECT * FROM pc_members --",
            "1' AND (SELECT COUNT(*) FROM pc_members) > 0 --",
        ];

        foreach ($payloads as $payload) {
            // When properly escaped, these should be safe strings
            $escaped = e($payload);

            // The escaped version should not contain unescaped quotes
            $this->assertStringNotContainsString("'", $escaped, "Payload not properly escaped: $payload");
        }
    }
}
