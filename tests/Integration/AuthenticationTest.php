<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for authentication system
 */
class AuthenticationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../admin/functions.inc.php';
    }

    // =========================================================================
    // checklogin() Tests
    // =========================================================================

    #[Test]
    public function checkloginSucceedsWithValidBcryptPassword(): void
    {
        global $loggedin, $pcadmin;

        $password = 'TestPassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $memberId = $this->createMember([
            'nick' => 'TestUser',
            'email' => 'test@example.com',
            'password' => $hash,
        ]);

        checklogin((string)$memberId, $password);

        $this->assertSame('YES', $loggedin);
        $this->assertSame('TestUser', $pcadmin['nick']);
    }

    #[Test]
    public function checkloginSucceedsWithStoredHash(): void
    {
        global $loggedin, $pcadmin;

        $password = 'TestPassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $memberId = $this->createMember([
            'nick' => 'CookieUser',
            'email' => 'cookie@example.com',
            'password' => $hash,
        ]);

        // Simulate cookie-based login (hash as password)
        checklogin((string)$memberId, $hash);

        $this->assertSame('YES', $loggedin);
        $this->assertSame('CookieUser', $pcadmin['nick']);
    }

    #[Test]
    public function checkloginFailsWithWrongPassword(): void
    {
        global $loggedin;

        $memberId = $this->createMember([
            'nick' => 'WrongPassUser',
            'email' => 'wrong@example.com',
            'password' => password_hash('correct', PASSWORD_DEFAULT),
        ]);

        checklogin((string)$memberId, 'incorrect');

        $this->assertSame('NO', $loggedin);
    }

    #[Test]
    public function checkloginFailsWithEmptyId(): void
    {
        global $loggedin;

        checklogin('', 'anypassword');

        $this->assertSame('NO', $loggedin);
    }

    #[Test]
    public function checkloginFailsWithEmptyPassword(): void
    {
        global $loggedin;

        $memberId = $this->createMember([
            'nick' => 'EmptyPassUser',
            'email' => 'empty@example.com',
        ]);

        checklogin((string)$memberId, '');

        $this->assertSame('NO', $loggedin);
    }

    #[Test]
    public function checkloginFailsWithNonExistentUser(): void
    {
        global $loggedin;

        checklogin('99999', 'anypassword');

        $this->assertSame('NO', $loggedin);
    }

    #[Test]
    public function checkloginMigratesLegacyBase64Password(): void
    {
        global $loggedin, $conn;

        $password = 'LegacyPassword';
        $legacyHash = base64_encode($password);

        $memberId = $this->createMember([
            'nick' => 'LegacyUser',
            'email' => 'legacy@example.com',
            'password' => $legacyHash,
        ]);

        checklogin((string)$memberId, $password);

        $this->assertSame('YES', $loggedin);

        // Check that password was migrated
        $stmt = $conn->prepare("SELECT password FROM pc_members WHERE id = ?");
        $stmt->bind_param('i', $memberId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        // Should now be bcrypt
        $this->assertTrue(str_starts_with($row['password'], '$2y$'));
        $this->assertTrue(password_verify($password, $row['password']));
    }

    // =========================================================================
    // Permission Tests
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
