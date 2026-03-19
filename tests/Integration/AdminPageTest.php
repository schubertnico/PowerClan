<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for admin pages
 */
class AdminPageTest extends PageTestCase
{
    private string $adminPath;
    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminPath = __DIR__ . '/../../admin/';
        $this->adminId = $this->createAdmin([
            'nick' => 'TestAdmin',
            'email' => 'admin@test.com',
            'password' => password_hash('adminpass', PASSWORD_DEFAULT),
        ]);
    }

    // =========================================================================
    // admin/index.php Tests
    // =========================================================================

    #[Test]
    public function adminIndexShowsDashboardWithPermissions(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'index.php', $this->adminId);

        $this->assertStringContainsString('Willkommen im Adminbereich', $html);
        $this->assertStringContainsString('Member hinzuf', $html);
        $this->assertStringContainsString('News hinzuf', $html);
        $this->assertStringContainsString('Wars hinzuf', $html);
        $this->assertStringContainsString('Alle Rechte', $html);
    }

    #[Test]
    public function adminIndexShowsNoPermissionsForRegularMember(): void
    {
        $memberId = $this->createMember([
            'nick' => 'RegularUser',
            'email' => 'regular@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'index.php', $memberId);

        $this->assertStringContainsString('keine', $html);
    }

    // =========================================================================
    // admin/addmember.php Tests
    // =========================================================================

    #[Test]
    public function addmemberShowsForm(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'addmember.php', $this->adminId);

        $this->assertStringContainsString('Member hinzuf', $html);
        $this->assertStringContainsString('name="nickname"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('csrf_token', $html);
    }

    #[Test]
    public function addmemberCreatesNewMember(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'addmember.php',
            $this->adminId,
            ['addmember' => 'YES'],
            [
                'nickname' => 'NewMember',
                'email' => 'newmember@test.com',
                'csrf_token' => $token,
                'member_add' => 'YES',
                'news_add' => 'YES',
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        // Verify member was created in DB
        global $conn;
        $result = $conn->query("SELECT * FROM pc_members WHERE nick = 'NewMember'");
        $this->assertSame(1, $result->num_rows);
        $row = $result->fetch_assoc();
        $this->assertSame('newmember@test.com', $row['email']);
        $this->assertSame('YES', $row['member_add']);
        $this->assertSame('YES', $row['news_add']);
    }

    #[Test]
    public function addmemberDeniesAccessWithoutPermission(): void
    {
        $memberId = $this->createMember([
            'nick' => 'NoPerms',
            'email' => 'noperms@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'addmember.php', $memberId);

        $this->assertStringContainsString('keine Zugang', $html);
    }

    // =========================================================================
    // admin/addnews.php Tests
    // =========================================================================

    #[Test]
    public function addnewsShowsForm(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'addnews.php', $this->adminId);

        $this->assertStringContainsString('News hinzuf', $html);
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="text"', $html);
    }

    #[Test]
    public function addnewsCreatesNewEntry(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'addnews.php',
            $this->adminId,
            ['addnews' => 'YES'],
            [
                'title' => 'New Test News',
                'text' => 'This is news content.',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query("SELECT * FROM pc_news WHERE title = 'New Test News'");
        $this->assertSame(1, $result->num_rows);
    }

    #[Test]
    public function addnewsShowsErrorForEmptyFields(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'addnews.php',
            $this->adminId,
            ['addnews' => 'YES'],
            [
                'title' => '',
                'text' => '',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('Bitte f', $html);
    }

    // =========================================================================
    // admin/addwar.php Tests
    // =========================================================================

    #[Test]
    public function addwarShowsForm(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'addwar.php', $this->adminId);

        $this->assertStringContainsString('War hinzuf', $html);
        $this->assertStringContainsString('name="enemy"', $html);
        $this->assertStringContainsString('name="league"', $html);
    }

    #[Test]
    public function addwarCreatesNewEntry(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'addwar.php',
            $this->adminId,
            ['addwar' => 'YES'],
            [
                'enemy' => 'WarEnemy',
                'enemy_tag' => 'WE',
                'homepage' => 'https://enemy.com',
                'league' => 'ESPL',
                'map1' => 'de_dust2',
                'map2' => 'de_inferno',
                'map3' => '',
                'time_day' => '15',
                'time_month' => '6',
                'time_year' => '2026',
                'time_hour' => '20',
                'time_minute' => '0',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query("SELECT * FROM pc_wars WHERE enemy = 'WarEnemy'");
        $this->assertSame(1, $result->num_rows);
    }

    #[Test]
    public function addwarShowsErrorForEmptyFields(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'addwar.php',
            $this->adminId,
            ['addwar' => 'YES'],
            [
                'enemy' => '',
                'enemy_tag' => '',
                'homepage' => '',
                'league' => '',
                'map1' => '',
                'map2' => '',
                'time_day' => '0',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('Bitte f', $html);
    }

    // =========================================================================
    // admin/editconfig.php Tests
    // =========================================================================

    #[Test]
    public function editconfigShowsForm(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'editconfig.php', $this->adminId);

        $this->assertStringContainsString('Konfiguration editieren', $html);
        $this->assertStringContainsString('name="clanname"', $html);
        $this->assertStringContainsString('name="clantag"', $html);
        $this->assertStringContainsString('TestClan', $html);
    }

    #[Test]
    public function editconfigUpdatesConfig(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'editconfig.php',
            $this->adminId,
            ['editconfig' => 'YES'],
            [
                'clanname' => 'UpdatedClan',
                'clantag' => 'UC',
                'url' => 'https://updated.com',
                'serverpath' => '/var/www',
                'header' => 'header.html',
                'footer' => 'footer.html',
                'tablebg1' => '#111111',
                'tablebg2' => '#222222',
                'tablebg3' => '#333333',
                'clrwon' => '#00FF00',
                'clrdraw' => '#FFFF00',
                'clrlost' => '#FF0000',
                'newslimit' => '5',
                'warlimit' => '5',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query('SELECT * FROM pc_config WHERE id = 1');
        $row = $result->fetch_assoc();
        $this->assertSame('UpdatedClan', $row['clanname']);
        $this->assertSame('UC', $row['clantag']);
    }

    #[Test]
    public function editconfigDeniesAccessForNonSuperadmin(): void
    {
        $memberId = $this->createMember([
            'nick' => 'NotSuper',
            'email' => 'notsuper@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
            'news_add' => 'YES',
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'editconfig.php', $memberId);

        $this->assertStringContainsString('keine Zugang', $html);
    }

    // =========================================================================
    // admin/profile.php Tests
    // =========================================================================

    #[Test]
    public function profileShowsForm(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'profile.php', $this->adminId);

        $this->assertStringContainsString('Profil editieren', $html);
        $this->assertStringContainsString('TestAdmin', $html);
        $this->assertStringContainsString('admin@test.com', $html);
    }

    #[Test]
    public function profileUpdatesSuccessfully(): void
    {
        $token = $this->setupCsrfToken();
        $html = $this->renderAdminPage(
            $this->adminPath . 'profile.php',
            $this->adminId,
            ['editprofile' => 'YES'],
            [
                'nick' => 'UpdatedAdmin',
                'email' => 'updated@test.com',
                'password1' => '',
                'password2' => '',
                'icq' => '12345',
                'homepage' => 'https://myhome.com',
                'realname' => 'Admin Name',
                'age' => '30',
                'hardware' => 'Good PC',
                'info' => 'Some info',
                'pic' => '',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        $member = $this->getMember($this->adminId);
        $this->assertSame('UpdatedAdmin', $member['nick']);
        $this->assertSame('updated@test.com', $member['email']);
    }

    // =========================================================================
    // admin/choosemember.php Tests
    // =========================================================================

    #[Test]
    public function choosememberShowsMemberList(): void
    {
        $this->createMember([
            'nick' => 'ListMember1',
            'email' => 'list1@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'choosemember.php', $this->adminId);

        $this->assertStringContainsString('ListMember1', $html);
        $this->assertStringContainsString('editieren', $html);
        $this->assertStringContainsString('schen', $html); // löschen
    }

    #[Test]
    public function choosememberShowsEmptyMessage(): void
    {
        global $conn;
        $conn->query('DELETE FROM pc_members');

        // Re-create admin for this test
        $this->adminId = $this->createAdmin([
            'nick' => 'OnlyAdmin',
            'email' => 'only@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'choosemember.php', $this->adminId);

        $this->assertStringContainsString('Memberliste', $html);
    }

    // =========================================================================
    // admin/choosenews.php Tests
    // =========================================================================

    #[Test]
    public function choosenewsShowsNewsList(): void
    {
        $this->createNews(['title' => 'ChooseThisNews']);

        $html = $this->renderAdminPage($this->adminPath . 'choosenews.php', $this->adminId);

        $this->assertStringContainsString('ChooseThisNews', $html);
        $this->assertStringContainsString('editieren', $html);
    }

    #[Test]
    public function choosenewsShowsEmptyMessage(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'choosenews.php', $this->adminId);

        $this->assertStringContainsString('Keine News', $html);
    }

    // =========================================================================
    // admin/choosewar.php Tests
    // =========================================================================

    #[Test]
    public function choosewarShowsWarList(): void
    {
        $this->createWar([
            'enemy' => 'ChooseWarEnemy',
            'enemy_tag' => 'CW',
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'choosewar.php', $this->adminId);

        $this->assertStringContainsString('ChooseWarEnemy', $html);
        $this->assertStringContainsString('editieren', $html);
    }

    #[Test]
    public function choosewarShowsEmptyMessage(): void
    {
        $html = $this->renderAdminPage($this->adminPath . 'choosewar.php', $this->adminId);

        $this->assertStringContainsString('Keine Wars', $html);
    }

    #[Test]
    public function choosewarShowsResultHighlight(): void
    {
        $this->createWar([
            'enemy' => 'ResultEnemy',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
            'res1' => '10:5',
        ]);

        $html = $this->renderAdminPage($this->adminPath . 'choosewar.php', $this->adminId);

        $this->assertStringContainsString('green', $html);
    }

    // =========================================================================
    // admin/editmember.php Tests
    // =========================================================================

    #[Test]
    public function editmemberShowsForm(): void
    {
        $memberId = $this->createMember([
            'nick' => 'EditMe',
            'email' => 'editme@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
            'work' => 'Sniper',
        ]);

        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember.php',
            $this->adminId,
            ['memberid' => (string) $memberId]
        );

        $this->assertStringContainsString('Member editieren', $html);
        $this->assertStringContainsString('EditMe', $html);
        $this->assertStringContainsString('editme@test.com', $html);
    }

    #[Test]
    public function editmemberUpdatesSuccessfully(): void
    {
        $memberId = $this->createMember([
            'nick' => 'ToEdit',
            'email' => 'toedit@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);

        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember.php',
            $this->adminId,
            ['memberid' => (string) $memberId, 'editmember' => 'YES'],
            [
                'nick' => 'Edited',
                'email' => 'edited@test.com',
                'password1' => '',
                'password2' => '',
                'work' => 'Leader',
                'icq' => '0',
                'homepage' => '',
                'realname' => 'Real Name',
                'age' => '25',
                'hardware' => 'PC',
                'info' => 'Info',
                'pic' => '',
                'member_add' => 'YES',
                'news_add' => 'YES',
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        $member = $this->getMember($memberId);
        $this->assertSame('Edited', $member['nick']);
        $this->assertSame('edited@test.com', $member['email']);
        $this->assertSame('YES', $member['member_add']);
    }

    #[Test]
    public function editmemberShowsErrorForMissingMember(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember.php',
            $this->adminId,
            ['memberid' => '99999']
        );

        $this->assertStringContainsString('existiert nicht', $html);
    }

    #[Test]
    public function editmemberShowsErrorForEmptyMemberId(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember.php',
            $this->adminId
        );

        $this->assertStringContainsString('Member aus', $html);
    }

    // =========================================================================
    // admin/editnews.php Tests
    // =========================================================================

    #[Test]
    public function editnewsShowsForm(): void
    {
        $newsId = $this->createNews(['title' => 'EditNews', 'text' => 'News content']);

        $html = $this->renderAdminPage(
            $this->adminPath . 'editnews.php',
            $this->adminId,
            ['newsid' => (string) $newsId]
        );

        $this->assertStringContainsString('News editieren', $html);
        $this->assertStringContainsString('EditNews', $html);
    }

    #[Test]
    public function editnewsUpdatesSuccessfully(): void
    {
        $newsId = $this->createNews(['title' => 'OldTitle', 'text' => 'Old text']);
        $token = $this->setupCsrfToken();

        $html = $this->renderAdminPage(
            $this->adminPath . 'editnews.php',
            $this->adminId,
            ['newsid' => (string) $newsId, 'editnews' => 'YES'],
            [
                'title' => 'Updated Title',
                'text' => 'Updated text content',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query("SELECT * FROM pc_news WHERE id = {$newsId}");
        $row = $result->fetch_assoc();
        $this->assertSame('Updated Title', $row['title']);
    }

    #[Test]
    public function editnewsShowsErrorForMissingNews(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editnews.php',
            $this->adminId,
            ['newsid' => '99999']
        );

        $this->assertStringContainsString('existiert nicht', $html);
    }

    // =========================================================================
    // admin/editwar.php Tests
    // =========================================================================

    #[Test]
    public function editwarShowsForm(): void
    {
        $warId = $this->createWar([
            'enemy' => 'EditWarEnemy',
            'enemy_tag' => 'EW',
        ]);

        $html = $this->renderAdminPage(
            $this->adminPath . 'editwar.php',
            $this->adminId,
            ['warid' => (string) $warId]
        );

        $this->assertStringContainsString('War editieren', $html);
        $this->assertStringContainsString('EditWarEnemy', $html);
        $this->assertStringContainsString('Screenshot', $html);
    }

    #[Test]
    public function editwarUpdatesSuccessfully(): void
    {
        $warId = $this->createWar([
            'enemy' => 'OldEnemy',
            'enemy_tag' => 'OE',
        ]);
        $token = $this->setupCsrfToken();

        $html = $this->renderAdminPage(
            $this->adminPath . 'editwar.php',
            $this->adminId,
            ['warid' => (string) $warId, 'editwar' => 'YES'],
            [
                'enemy' => 'NewEnemy',
                'enemy_tag' => 'NE',
                'homepage' => 'https://new-enemy.com',
                'league' => 'Clanbase',
                'map1' => 'de_dust2',
                'map2' => 'de_inferno',
                'map3' => '',
                'time_day' => '20',
                'time_month' => '8',
                'time_year' => '2026',
                'time_hour' => '21',
                'time_minute' => '30',
                'report' => 'War report text.',
                'res1' => '10:5',
                'res2' => '8:3',
                'res3' => '',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query("SELECT * FROM pc_wars WHERE id = {$warId}");
        $row = $result->fetch_assoc();
        $this->assertSame('NewEnemy', $row['enemy']);
        $this->assertSame('10:5', $row['res1']);
    }

    #[Test]
    public function editwarShowsErrorForMissingWar(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editwar.php',
            $this->adminId,
            ['warid' => '99999']
        );

        $this->assertStringContainsString('existiert nicht', $html);
    }

    #[Test]
    public function editwarShowsErrorForEmptyWarId(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editwar.php',
            $this->adminId
        );

        $this->assertStringContainsString('Wareintrag aus', $html);
    }

    // =========================================================================
    // admin/delmember.php Tests
    // =========================================================================

    #[Test]
    public function delmemberShowsConfirmation(): void
    {
        $memberId = $this->createMember([
            'nick' => 'DeleteMe',
            'email' => 'deleteme@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
            'work' => 'Fighter',
        ]);

        $html = $this->renderAdminPage(
            $this->adminPath . 'delmember.php',
            $this->adminId,
            ['memberid' => (string) $memberId]
        );

        $this->assertStringContainsString('DeleteMe', $html);
        $this->assertStringContainsString('wirklich gel', $html);
        $this->assertStringContainsString('delmember', $html);
    }

    #[Test]
    public function delmemberDeletesSuccessfully(): void
    {
        $memberId = $this->createMember([
            'nick' => 'ToDelete',
            'email' => 'todelete@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);
        $token = $this->setupCsrfToken();

        $html = $this->renderAdminPage(
            $this->adminPath . 'delmember.php',
            $this->adminId,
            ['memberid' => (string) $memberId],
            [
                'memberid' => (string) $memberId,
                'delmember' => 'YES',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        $member = $this->getMember($memberId);
        $this->assertNull($member);
    }

    #[Test]
    public function delmemberShowsErrorForMissingMember(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'delmember.php',
            $this->adminId,
            ['memberid' => '99999']
        );

        $this->assertStringContainsString('existiert nicht', $html);
    }

    // =========================================================================
    // admin/delnews.php Tests
    // =========================================================================

    #[Test]
    public function delnewsShowsConfirmation(): void
    {
        $newsId = $this->createNews(['title' => 'DeleteThisNews']);

        $html = $this->renderAdminPage(
            $this->adminPath . 'delnews.php',
            $this->adminId,
            ['newsid' => (string) $newsId]
        );

        $this->assertStringContainsString('DeleteThisNews', $html);
        $this->assertStringContainsString('wirklich gel', $html);
    }

    #[Test]
    public function delnewsDeletesSuccessfully(): void
    {
        $newsId = $this->createNews(['title' => 'ToDeleteNews']);
        $token = $this->setupCsrfToken();

        $html = $this->renderAdminPage(
            $this->adminPath . 'delnews.php',
            $this->adminId,
            ['newsid' => (string) $newsId],
            [
                'newsid' => (string) $newsId,
                'delnews' => 'YES',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query("SELECT * FROM pc_news WHERE id = {$newsId}");
        $this->assertSame(0, $result->num_rows);
    }

    // =========================================================================
    // admin/delwar.php Tests
    // =========================================================================

    #[Test]
    public function delwarShowsConfirmation(): void
    {
        $warId = $this->createWar(['enemy' => 'DeleteWarEnemy']);

        $html = $this->renderAdminPage(
            $this->adminPath . 'delwar.php',
            $this->adminId,
            ['warid' => (string) $warId]
        );

        $this->assertStringContainsString('DeleteWarEnemy', $html);
        $this->assertStringContainsString('wirklich gel', $html);
    }

    #[Test]
    public function delwarDeletesSuccessfully(): void
    {
        $warId = $this->createWar(['enemy' => 'ToDeleteWar']);
        $token = $this->setupCsrfToken();

        $html = $this->renderAdminPage(
            $this->adminPath . 'delwar.php',
            $this->adminId,
            ['warid' => (string) $warId],
            [
                'warid' => (string) $warId,
                'delwar' => 'YES',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        global $conn;
        $result = $conn->query("SELECT * FROM pc_wars WHERE id = {$warId}");
        $this->assertSame(0, $result->num_rows);
    }

    #[Test]
    public function delwarShowsErrorForMissingWar(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'delwar.php',
            $this->adminId,
            ['warid' => '99999']
        );

        $this->assertStringContainsString('existiert nicht', $html);
    }

    // =========================================================================
    // admin/editmember2.php Tests
    // =========================================================================

    #[Test]
    public function editmember2ShowsForm(): void
    {
        $memberId = $this->createMember([
            'nick' => 'EditMe2',
            'email' => 'editme2@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
            'work' => 'Medic',
        ]);

        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember2.php',
            $this->adminId,
            ['memberid' => (string) $memberId]
        );

        $this->assertStringContainsString('Member editieren', $html);
        $this->assertStringContainsString('EditMe2', $html);
        $this->assertStringContainsString('editme2@test.com', $html);
        $this->assertStringContainsString('Medic', $html);
        $this->assertStringContainsString('Adminrechte', $html);
    }

    #[Test]
    public function editmember2UpdatesSuccessfully(): void
    {
        $memberId = $this->createMember([
            'nick' => 'ToEdit2',
            'email' => 'toedit2@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);
        $token = $this->setupCsrfToken();

        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember2.php',
            $this->adminId,
            ['memberid' => (string) $memberId, 'editmember' => 'YES'],
            [
                'memberid' => (string) $memberId,
                'nick' => 'Edited2',
                'email' => 'edited2@test.com',
                'password1' => '',
                'password2' => '',
                'work' => 'Leader',
                'icq' => '0',
                'homepage' => '',
                'realname' => 'Real Name 2',
                'age' => '28',
                'hardware' => 'Good PC',
                'info' => 'Some info',
                'pic' => '',
                'member_add' => 'YES',
                'news_add' => 'YES',
                'csrf_token' => $token,
            ]
        );

        $this->assertStringContainsString('erfolgreich', $html);

        $member = $this->getMember($memberId);
        $this->assertSame('Edited2', $member['nick']);
        $this->assertSame('edited2@test.com', $member['email']);
        $this->assertSame('YES', $member['member_add']);
    }

    #[Test]
    public function editmember2ShowsErrorForMissingMember(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember2.php',
            $this->adminId,
            ['memberid' => '99999']
        );

        $this->assertStringContainsString('existiert nicht', $html);
    }

    #[Test]
    public function editmember2ShowsErrorForEmptyMemberId(): void
    {
        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember2.php',
            $this->adminId
        );

        $this->assertStringContainsString('Member aus', $html);
    }

    #[Test]
    public function editmember2DeniesAccessWithoutPermission(): void
    {
        $memberId = $this->createMember([
            'nick' => 'NoPerm2',
            'email' => 'noperm2@test.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT),
        ]);

        $html = $this->renderAdminPage(
            $this->adminPath . 'editmember2.php',
            $memberId,
            ['memberid' => (string) $memberId]
        );

        $this->assertStringContainsString('keine Zugang', $html);
    }
}
