<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use mysqli;
use PHPUnit\Framework\TestCase;
use PowerClan\Tests\Fixtures\TestDatabase;

/**
 * Base class for integration tests
 *
 * Provides database connection and helper methods.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static ?mysqli $conn = null;
    protected static bool $initialized = false;

    public static function setUpBeforeClass(): void
    {
        if (!self::$initialized) {
            self::$conn = TestDatabase::getConnection();
            TestDatabase::initialize();
            self::$initialized = true;
        }
    }

    protected function setUp(): void
    {
        // Clear test data before each test
        TestDatabase::clearAll();

        // Initialize global connection for tested code
        $GLOBALS['conn'] = self::$conn;
        $GLOBALS['settings'] = [];
        $GLOBALS['pcadmin'] = [];
        $GLOBALS['loggedin'] = 'NO';
    }

    public static function tearDownAfterClass(): void
    {
        TestDatabase::close();
        self::$conn = null;
        self::$initialized = false;
    }

    /**
     * Create a test member and return ID
     */
    protected function createMember(array $data = []): int
    {
        return TestDatabase::createTestMember($data);
    }

    /**
     * Create a test admin and return ID
     */
    protected function createAdmin(array $data = []): int
    {
        return TestDatabase::createTestAdmin($data);
    }

    /**
     * Create test news and return ID
     */
    protected function createNews(array $data = []): int
    {
        return TestDatabase::createTestNews($data);
    }

    /**
     * Create test war and return ID
     */
    protected function createWar(array $data = []): int
    {
        return TestDatabase::createTestWar($data);
    }

    /**
     * Get member by ID
     */
    protected function getMember(int $id): ?array
    {
        return TestDatabase::getMember($id);
    }

    /**
     * Simulate a logged-in admin session
     */
    protected function loginAsAdmin(int $memberId): void
    {
        $member = $this->getMember($memberId);
        if ($member !== null) {
            $GLOBALS['pcadmin'] = $member;
            $GLOBALS['loggedin'] = 'YES';
        }
    }

    /**
     * Simulate form POST data
     */
    protected function setPostData(array $data): void
    {
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    /**
     * Simulate GET parameters
     */
    protected function setGetData(array $data): void
    {
        $_GET = $data;
    }

    /**
     * Clear superglobals
     */
    protected function clearRequestData(): void
    {
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
}
