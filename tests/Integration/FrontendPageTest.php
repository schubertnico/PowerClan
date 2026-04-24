<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for public-facing pages
 */
class FrontendPageTest extends PageTestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = __DIR__ . '/../../';
    }

    // =========================================================================
    // index.php Tests
    // =========================================================================

    #[Test]
    public function indexPageRendersWithNewsAndWars(): void
    {
        // Create test data
        $this->createNews(['title' => 'Test News Title', 'text' => 'News body text']);
        $this->createWar([
            'enemy' => 'TestEnemy',
            'enemy_tag' => 'TE',
            'res1' => '10:5',
            'res2' => '8:3',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        $html = $this->renderPage($this->basePath . 'index.php');

        $this->assertStringContainsString('latest news', $html);
        $this->assertStringContainsString('latest wars', $html);
        $this->assertStringContainsString('Test News Title', $html);
        $this->assertStringContainsString('News body text', $html);
        $this->assertStringContainsString('TE', $html);
    }

    #[Test]
    public function indexPageRendersEmptyLists(): void
    {
        $html = $this->renderPage($this->basePath . 'index.php');

        $this->assertStringContainsString('latest news', $html);
        $this->assertStringContainsString('Keine Wars vorhanden', $html);
    }

    #[Test]
    public function indexPageShowsMultipleNews(): void
    {
        $this->createNews(['title' => 'First News']);
        $this->createNews(['title' => 'Second News']);

        $html = $this->renderPage($this->basePath . 'index.php');

        $this->assertStringContainsString('First News', $html);
        $this->assertStringContainsString('Second News', $html);
    }

    // =========================================================================
    // member.php Tests
    // =========================================================================

    #[Test]
    public function memberPageShowsMemberList(): void
    {
        $this->createMember(['nick' => 'Player1', 'work' => 'Sniper']);
        $this->createMember(['nick' => 'Player2', 'work' => 'Medic']);

        $html = $this->renderPage($this->basePath . 'member.php');

        $this->assertStringContainsString('Player1', $html);
        $this->assertStringContainsString('Player2', $html);
        $this->assertStringContainsString('Sniper', $html);
    }

    #[Test]
    public function memberPageShowsEmptyList(): void
    {
        // Clear all members including the default one
        global $conn;
        $conn->query('DELETE FROM pc_members');

        $html = $this->renderPage($this->basePath . 'member.php');

        $this->assertStringContainsString('Es sind keine Member vorhanden', $html);
    }

    #[Test]
    public function memberPageShowsSingleMember(): void
    {
        $memberId = $this->createMember([
            'nick' => 'DetailPlayer',
            'email' => 'detail@example.com',
            'work' => 'Leader',
            'realname' => 'John Doe',
            'age' => 30,
            'info' => 'Some personal info',
            'hardware' => 'RTX 4090',
        ]);

        $html = $this->renderPage($this->basePath . 'member.php', [
            'pcpage' => 'showmember',
            'memberid' => (string) $memberId,
        ]);

        $this->assertStringContainsString('DetailPlayer', $html);
        $this->assertStringContainsString('detail@example.com', $html);
        $this->assertStringContainsString('Leader', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('30 Jahre', $html);
        $this->assertStringContainsString('Some personal info', $html);
        $this->assertStringContainsString('RTX 4090', $html);
    }

    #[Test]
    public function memberPageShowsErrorForMissingMember(): void
    {
        $html = $this->renderPage($this->basePath . 'member.php', [
            'pcpage' => 'showmember',
            'memberid' => '99999',
        ]);

        $this->assertStringContainsString('existierenden Member', $html);
    }

    #[Test]
    public function memberPageShowsErrorForEmptyMemberId(): void
    {
        $html = $this->renderPage($this->basePath . 'member.php', [
            'pcpage' => 'showmember',
            'memberid' => '',
        ]);

        $this->assertStringContainsString('Member aus', $html);
    }

    #[Test]
    public function memberPageShowsMemberWithPicture(): void
    {
        $memberId = $this->createMember([
            'nick' => 'PicPlayer',
            'pic' => 'images/member1.jpg',
        ]);

        $html = $this->renderPage($this->basePath . 'member.php', [
            'pcpage' => 'showmember',
            'memberid' => (string) $memberId,
        ]);

        $this->assertStringContainsString('images/member1.jpg', $html);
        $this->assertStringContainsString('showpic.php', $html);
    }

    #[Test]
    public function memberPageShowsMemberWithoutOptionalFields(): void
    {
        $memberId = $this->createMember([
            'nick' => 'MinimalPlayer',
            'icq' => 0,
            'homepage' => '',
            'realname' => '',
            'age' => 0,
            'info' => '',
            'hardware' => '',
            'pic' => '',
        ]);

        $html = $this->renderPage($this->basePath . 'member.php', [
            'pcpage' => 'showmember',
            'memberid' => (string) $memberId,
        ]);

        $this->assertStringContainsString('N/A', $html);
        $this->assertStringContainsString('Keine Homepage', $html);
        $this->assertStringContainsString('Kein Bild vorhanden', $html);
    }

    // =========================================================================
    // wars.php Tests
    // =========================================================================

    #[Test]
    public function warsPageShowsWarList(): void
    {
        $this->createWar([
            'enemy' => 'EnemyClan',
            'enemy_tag' => 'EC',
            'league' => 'ESPL',
            'res1' => '10:5',
            'res2' => '8:3',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        $html = $this->renderPage($this->basePath . 'wars.php');

        $this->assertStringContainsString('EC', $html);
        $this->assertStringContainsString('ESPL', $html);
        $this->assertStringContainsString('de_dust2', $html);
        $this->assertStringContainsString('de_inferno', $html);
    }

    #[Test]
    public function warsPageShowsEmptyList(): void
    {
        $html = $this->renderPage($this->basePath . 'wars.php');

        $this->assertStringContainsString('Keine Wars vorhanden', $html);
    }

    #[Test]
    public function warsPageShowsReport(): void
    {
        $warId = $this->createWar([
            'enemy' => 'ReportEnemy',
            'enemy_tag' => 'RE',
            'report' => 'This is the war report text.',
            'res1' => '10:5',
            'res2' => '8:3',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        $html = $this->renderPage($this->basePath . 'wars.php', [
            'pcpage' => 'showreport',
            'warid' => (string) $warId,
        ]);

        $this->assertStringContainsString('This is the war report text.', $html);
        $this->assertStringContainsString('ReportEnemy', $html);
        $this->assertStringContainsString('TestClan', $html);
    }

    #[Test]
    public function warsPageShowsErrorForMissingReport(): void
    {
        $warId = $this->createWar([
            'enemy' => 'NoReportEnemy',
            'report' => '',
        ]);

        $html = $this->renderPage($this->basePath . 'wars.php', [
            'pcpage' => 'showreport',
            'warid' => (string) $warId,
        ]);

        $this->assertStringContainsString('kein Bericht', $html);
    }

    #[Test]
    public function warsPageShowsErrorForNonExistentWar(): void
    {
        $html = $this->renderPage($this->basePath . 'wars.php', [
            'pcpage' => 'showreport',
            'warid' => '99999',
        ]);

        $this->assertStringContainsString('existiert nicht', $html);
    }

    #[Test]
    public function warsPageShowsErrorForEmptyWarId(): void
    {
        $html = $this->renderPage($this->basePath . 'wars.php', [
            'pcpage' => 'showreport',
            'warid' => '',
        ]);

        $this->assertStringContainsString('War ausw', $html);
    }

    #[Test]
    public function warsPageShowsWarWithThreeMaps(): void
    {
        $this->createWar([
            'enemy_tag' => 'TM',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
            'map3' => 'de_nuke',
            'res1' => '10:5',
            'res2' => '3:8',
            'res3' => '7:4',
        ]);

        $html = $this->renderPage($this->basePath . 'wars.php');

        $this->assertStringContainsString('de_nuke', $html);
        $this->assertStringContainsString('TM', $html);
    }

    #[Test]
    public function warsPageShowsWarWithScreenshots(): void
    {
        $this->createWar([
            'enemy_tag' => 'SS',
            'map1' => 'de_dust2',
            'res1' => '10:5',
            'res2' => '8:3',
            'screen1' => 'screen1.jpg',
        ]);

        $html = $this->renderPage($this->basePath . 'wars.php');

        $this->assertStringContainsString('showpic.php', $html);
        $this->assertStringContainsString('screen1.jpg', $html);
    }

    #[Test]
    public function warsPageShowsWarWithHomepage(): void
    {
        $this->createWar([
            'enemy_tag' => 'HP',
            'homepage' => 'https://enemy-clan.com',
            'res1' => '10:5',
            'res2' => '8:3',
        ]);

        $html = $this->renderPage($this->basePath . 'wars.php');

        $this->assertStringContainsString('https://enemy-clan.com', $html);
    }

    // =========================================================================
    // showpic.php Tests
    // =========================================================================

    #[Test]
    public function showpicPageRendersValidImagePath(): void
    {
        $html = $this->renderPage($this->basePath . 'showpic.php', [
            'path' => 'images/test.jpg',
        ]);

        $this->assertStringContainsString('images/test.jpg', $html);
        $this->assertStringContainsString('<img', $html);
    }

    #[Test]
    public function showpicPageBlocksPathTraversal(): void
    {
        $html = $this->renderPage($this->basePath . 'showpic.php', [
            'path' => '../../../etc/passwd',
        ]);

        $this->assertStringContainsString('Ung', $html); // Ungültiger Bildpfad
        $this->assertStringNotContainsString('passwd', $html);
    }

    #[Test]
    public function showpicPageAllowsExternalUrls(): void
    {
        $html = $this->renderPage($this->basePath . 'showpic.php', [
            'path' => 'https://example.com/image.jpg',
        ]);

        $this->assertStringContainsString('https://example.com/image.jpg', $html);
    }

    #[Test]
    public function showpicPageRejectsInvalidExtension(): void
    {
        $html = $this->renderPage($this->basePath . 'showpic.php', [
            'path' => 'images/test.php',
        ]);

        $this->assertStringContainsString('Ung', $html); // Ungültiger Bildpfad
    }

    #[Test]
    public function showpicPageHandlesEmptyPath(): void
    {
        $html = $this->renderPage($this->basePath . 'showpic.php', [
            'path' => '',
        ]);

        $this->assertStringContainsString('kein Pfad', $html);
    }

    #[Test]
    public function showpicPageAllowsWarSubdirectory(): void
    {
        $html = $this->renderPage($this->basePath . 'showpic.php', [
            'path' => 'images/wars/screen1.png',
        ]);

        $this->assertStringContainsString('images/wars/screen1.png', $html);
    }
}
