<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for authentication system (session-based, BUG-011 fix)
 */
class AuthenticationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../admin/functions.inc.php';
        // Ensure a session is already active so pc_session_start() becomes a no-op
        // and does not overwrite the $_SESSION values set by the test.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    // =========================================================================
    // checklogin() – session-based
    // =========================================================================

    #[Test]
    public function checkloginSucceedsWithValidSession(): void
    {
        global $loggedin, $pcadmin;

        $memberId = $this->createMember([
            'nick' => 'TestUser',
            'email' => 'test@example.com',
            'password' => password_hash('TestPassword123', PASSWORD_DEFAULT),
        ]);

        $_SESSION['member_id'] = $memberId;

        checklogin();

        $this->assertSame('YES', $loggedin);
        $this->assertSame('TestUser', $pcadmin['nick']);
    }

    #[Test]
    public function checkloginFailsWithoutSession(): void
    {
        global $loggedin;

        $_SESSION = [];

        checklogin();

        $this->assertSame('NO', $loggedin);
    }

    #[Test]
    public function checkloginFailsWithNonExistentMember(): void
    {
        global $loggedin;

        $_SESSION['member_id'] = 99999;

        checklogin();

        $this->assertSame('NO', $loggedin);
    }

    #[Test]
    public function checkloginFailsWithZeroMemberId(): void
    {
        global $loggedin;

        $_SESSION['member_id'] = 0;

        checklogin();

        $this->assertSame('NO', $loggedin);
    }

    // =========================================================================
    // pc_can() permission helper
    // =========================================================================

    #[Test]
    public function pcCanReturnsTrueForSuperadmin(): void
    {
        global $pcadmin;

        $pcadmin = ['superadmin' => 'YES'];

        $this->assertTrue(pc_can('member_add'));
        $this->assertTrue(pc_can('news_del'));
        $this->assertTrue(pc_can('wars_edit'));
    }

    #[Test]
    public function pcCanReturnsTrueOnlyForGrantedPermissions(): void
    {
        global $pcadmin;

        $pcadmin = [
            'superadmin' => 'NO',
            'news_add' => 'YES',
            'news_edit' => 'NO',
        ];

        $this->assertTrue(pc_can('news_add'));
        $this->assertFalse(pc_can('news_edit'));
        $this->assertFalse(pc_can('wars_del'));
    }

    // =========================================================================
    // Permission stored values
    // =========================================================================

    #[Test]
    public function superadminHasAllPermissions(): void
    {
        $adminId = $this->createAdmin(['superadmin' => 'YES']);
        $admin = $this->getMember($adminId);

        $this->assertSame('YES', $admin['superadmin']);
        $this->assertSame('YES', $admin['member_add']);
        $this->assertSame('YES', $admin['news_add']);
        $this->assertSame('YES', $admin['wars_add']);
    }

    #[Test]
    public function regularMemberHasNoAdminPermissions(): void
    {
        $memberId = $this->createMember();
        $member = $this->getMember($memberId);

        $this->assertSame('NO', $member['superadmin']);
        $this->assertSame('NO', $member['member_add']);
        $this->assertSame('NO', $member['news_add']);
        $this->assertSame('NO', $member['wars_add']);
    }

    #[Test]
    public function memberCanHaveSelectivePermissions(): void
    {
        $memberId = $this->createMember([
            'news_add' => 'YES',
            'news_edit' => 'YES',
            'news_del' => 'NO',
        ]);
        $member = $this->getMember($memberId);

        $this->assertSame('YES', $member['news_add']);
        $this->assertSame('YES', $member['news_edit']);
        $this->assertSame('NO', $member['news_del']);
    }
}
