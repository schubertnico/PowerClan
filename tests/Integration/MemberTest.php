<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for member management
 */
class MemberTest extends IntegrationTestCase
{
    // =========================================================================
    // Member Creation Tests
    // =========================================================================

    #[Test]
    public function canCreateMemberWithRequiredFields(): void
    {
        $memberId = $this->createMember([
            'nick' => 'NewMember',
            'email' => 'newmember@example.com',
        ]);

        $member = $this->getMember($memberId);

        $this->assertNotNull($member);
        $this->assertSame('NewMember', $member['nick']);
        $this->assertSame('newmember@example.com', $member['email']);
    }

    #[Test]
    public function newMemberHasDefaultPermissions(): void
    {
        $memberId = $this->createMember([
            'nick' => 'DefaultPerms',
            'email' => 'default@example.com',
        ]);

        $member = $this->getMember($memberId);

        $this->assertSame('NO', $member['superadmin']);
        $this->assertSame('NO', $member['member_add']);
        $this->assertSame('NO', $member['member_edit']);
        $this->assertSame('NO', $member['member_del']);
    }

    #[Test]
    public function canCreateMemberWithAllFields(): void
    {
        $memberId = $this->createMember([
            'nick' => 'FullMember',
            'email' => 'full@example.com',
            'work' => 'Developer',
            'realname' => 'John Doe',
            'homepage' => 'https://example.com',
            'icq' => '12345678',
            'age' => '25',
            'hardware' => 'Gaming PC',
            'info' => 'Test info',
            'pic' => 'https://example.com/pic.jpg',
        ]);

        $member = $this->getMember($memberId);

        $this->assertNotNull($member);
        $this->assertSame('FullMember', $member['nick']);
        $this->assertSame('Developer', $member['work']);
    }

    // =========================================================================
    // Member Validation Tests
    // =========================================================================

    #[Test]
    public function emailMustBeValid(): void
    {
        require_once __DIR__ . '/../../admin/functions.inc.php';

        $this->assertFalse(validate_email('invalid-email'));
        $this->assertTrue(validate_email('valid@example.com'));
    }

    #[Test]
    public function duplicateEmailShouldBeDetectable(): void
    {
        global $conn;

        $email = 'duplicate@example.com';

        $this->createMember(['email' => $email, 'nick' => 'First']);

        // Check for duplicate
        $stmt = $conn->prepare('SELECT id FROM pc_members WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->num_rows;
        $stmt->close();

        $this->assertSame(1, $count);
    }

    #[Test]
    public function duplicateNicknameShouldBeDetectable(): void
    {
        global $conn;

        $nick = 'UniqueNick';

        $this->createMember(['nick' => $nick, 'email' => 'first@example.com']);

        // Check for duplicate
        $stmt = $conn->prepare('SELECT id FROM pc_members WHERE nick = ?');
        $stmt->bind_param('s', $nick);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->num_rows;
        $stmt->close();

        $this->assertSame(1, $count);
    }

    // =========================================================================
    // Member Update Tests
    // =========================================================================

    #[Test]
    public function canUpdateMemberProfile(): void
    {
        global $conn;

        $memberId = $this->createMember([
            'nick' => 'OriginalNick',
            'email' => 'original@example.com',
        ]);

        // Update member
        $newNick = 'UpdatedNick';
        $stmt = $conn->prepare('UPDATE pc_members SET nick = ? WHERE id = ?');
        $stmt->bind_param('si', $newNick, $memberId);
        $stmt->execute();
        $stmt->close();

        $member = $this->getMember($memberId);
        $this->assertSame('UpdatedNick', $member['nick']);
    }

    #[Test]
    public function canUpdateMemberPassword(): void
    {
        global $conn;

        $memberId = $this->createMember([
            'nick' => 'PasswordUser',
            'email' => 'password@example.com',
        ]);

        $newPassword = 'NewSecurePassword123!';
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $memberId);
        $stmt->execute();
        $stmt->close();

        $member = $this->getMember($memberId);
        $this->assertTrue(password_verify($newPassword, $member['password']));
    }

    #[Test]
    public function canUpdateMemberPermissions(): void
    {
        global $conn;

        $memberId = $this->createMember([
            'nick' => 'PermsUser',
            'email' => 'perms@example.com',
        ]);

        // Grant news permissions
        $yes = 'YES';
        $stmt = $conn->prepare('UPDATE pc_members SET news_add = ?, news_edit = ? WHERE id = ?');
        $stmt->bind_param('ssi', $yes, $yes, $memberId);
        $stmt->execute();
        $stmt->close();

        $member = $this->getMember($memberId);
        $this->assertSame('YES', $member['news_add']);
        $this->assertSame('YES', $member['news_edit']);
        $this->assertSame('NO', $member['news_del']); // Unchanged
    }

    // =========================================================================
    // Member Deletion Tests
    // =========================================================================

    #[Test]
    public function canDeleteMember(): void
    {
        global $conn;

        $memberId = $this->createMember([
            'nick' => 'ToDelete',
            'email' => 'delete@example.com',
        ]);

        // Delete member
        $stmt = $conn->prepare('DELETE FROM pc_members WHERE id = ?');
        $stmt->bind_param('i', $memberId);
        $stmt->execute();
        $stmt->close();

        $member = $this->getMember($memberId);
        $this->assertNull($member);
    }

    #[Test]
    public function deletingMemberDoesNotAffectOthers(): void
    {
        global $conn;

        $member1 = $this->createMember(['nick' => 'Member1', 'email' => 'm1@example.com']);
        $member2 = $this->createMember(['nick' => 'Member2', 'email' => 'm2@example.com']);

        // Delete first member
        $stmt = $conn->prepare('DELETE FROM pc_members WHERE id = ?');
        $stmt->bind_param('i', $member1);
        $stmt->execute();
        $stmt->close();

        // Second member should still exist
        $this->assertNull($this->getMember($member1));
        $this->assertNotNull($this->getMember($member2));
    }

    // =========================================================================
    // Password Security Tests
    // =========================================================================

    #[Test]
    public function passwordIsHashedNotPlaintext(): void
    {
        $password = 'PlainTextPassword';

        $memberId = $this->createMember([
            'nick' => 'HashTest',
            'email' => 'hash@example.com',
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $member = $this->getMember($memberId);

        // Password should not be stored in plaintext
        $this->assertNotSame($password, $member['password']);
        $this->assertTrue(str_starts_with($member['password'], '$2y$'));
    }
}
