<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for war management
 */
class WarTest extends IntegrationTestCase
{
    // =========================================================================
    // War Creation Tests
    // =========================================================================

    #[Test]
    public function canCreateWar(): void
    {
        global $conn;

        $warId = $this->createWar([
            'enemy' => 'Enemy Clan',
            'enemy_tag' => 'EC',
            'homepage' => 'https://enemy.com',
            'league' => 'ESPL',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
        ]);

        $stmt = $conn->prepare('SELECT * FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($war);
        $this->assertSame('Enemy Clan', $war['enemy']);
        $this->assertSame('EC', $war['enemy_tag']);
        $this->assertSame('ESPL', $war['league']);
    }

    #[Test]
    public function warHasCorrectMaps(): void
    {
        global $conn;

        $warId = $this->createWar([
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
            'map3' => 'de_nuke',
        ]);

        $stmt = $conn->prepare('SELECT map1, map2, map3 FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame('de_dust2', $war['map1']);
        $this->assertSame('de_inferno', $war['map2']);
        $this->assertSame('de_nuke', $war['map3']);
    }

    #[Test]
    public function warHasScheduledTime(): void
    {
        global $conn;

        $scheduledTime = time() + 86400; // Tomorrow

        $warId = $this->createWar([
            'time' => $scheduledTime,
        ]);

        $stmt = $conn->prepare('SELECT time FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame($scheduledTime, (int) $war['time']);
    }

    // =========================================================================
    // War Results Tests
    // =========================================================================

    #[Test]
    public function canSetWarResults(): void
    {
        global $conn;

        $warId = $this->createWar();

        // Set results
        $res1 = '16:10';
        $res2 = '13:16';
        $stmt = $conn->prepare('UPDATE pc_wars SET res1 = ?, res2 = ? WHERE id = ?');
        $stmt->bind_param('ssi', $res1, $res2, $warId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('SELECT res1, res2 FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame('16:10', $war['res1']);
        $this->assertSame('13:16', $war['res2']);
    }

    #[Test]
    public function canCalculateWarOutcome(): void
    {
        // Test result parsing
        $res1 = '16:10';
        $res2 = '13:16';

        $parts1 = explode(':', $res1);
        $parts2 = explode(':', $res2);

        $ownScore = (int) $parts1[0] + (int) $parts2[0]; // 16 + 13 = 29
        $enemyScore = (int) $parts1[1] + (int) $parts2[1]; // 10 + 16 = 26

        $this->assertSame(29, $ownScore);
        $this->assertSame(26, $enemyScore);
        $this->assertGreaterThan($enemyScore, $ownScore); // Won
    }

    #[Test]
    public function warWithoutResultsIsOpen(): void
    {
        global $conn;

        $warId = $this->createWar([
            'res1' => '',
            'res2' => '',
        ]);

        $stmt = $conn->prepare('SELECT res1, res2 FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertEmpty($war['res1']);
        $this->assertEmpty($war['res2']);
    }

    // =========================================================================
    // War Report Tests
    // =========================================================================

    #[Test]
    public function canSetWarReport(): void
    {
        global $conn;

        $warId = $this->createWar();

        $report = 'Great match! We won 2-0 with excellent teamplay.';
        $stmt = $conn->prepare('UPDATE pc_wars SET report = ? WHERE id = ?');
        $stmt->bind_param('si', $report, $warId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('SELECT report FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame($report, $war['report']);
    }

    // =========================================================================
    // Screenshot Tests
    // =========================================================================

    #[Test]
    public function canSetScreenshotFilenames(): void
    {
        global $conn;

        $warId = $this->createWar();

        $screen1 = 'war_123_map1.jpg';
        $screen2 = 'war_123_map2.jpg';
        $stmt = $conn->prepare('UPDATE pc_wars SET screen1 = ?, screen2 = ? WHERE id = ?');
        $stmt->bind_param('ssi', $screen1, $screen2, $warId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('SELECT screen1, screen2 FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $war = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame('war_123_map1.jpg', $war['screen1']);
        $this->assertSame('war_123_map2.jpg', $war['screen2']);
    }

    // =========================================================================
    // War Deletion Tests
    // =========================================================================

    #[Test]
    public function canDeleteWar(): void
    {
        global $conn;

        $warId = $this->createWar(['enemy' => 'ToDelete']);

        $stmt = $conn->prepare('DELETE FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('SELECT id FROM pc_wars WHERE id = ?');
        $stmt->bind_param('i', $warId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $this->assertSame(0, $result->num_rows);
    }

    // =========================================================================
    // War Listing Tests
    // =========================================================================

    #[Test]
    public function canListAllWars(): void
    {
        global $conn;

        $this->createWar(['enemy' => 'Team A']);
        $this->createWar(['enemy' => 'Team B']);
        $this->createWar(['enemy' => 'Team C']);

        $result = $conn->query('SELECT COUNT(*) as count FROM pc_wars');
        $row = $result->fetch_assoc();

        $this->assertSame(3, (int) $row['count']);
    }

    #[Test]
    public function warsCanBeOrderedByTime(): void
    {
        global $conn;

        $this->createWar(['enemy' => 'Past', 'time' => time() - 86400]);
        $this->createWar(['enemy' => 'Future', 'time' => time() + 86400]);

        $result = $conn->query('SELECT enemy FROM pc_wars ORDER BY time DESC');
        $first = $result->fetch_assoc();

        $this->assertSame('Future', $first['enemy']);
    }

    #[Test]
    public function canFilterWarsByLeague(): void
    {
        global $conn;

        $this->createWar(['enemy' => 'ESPL Team', 'league' => 'ESPL']);
        $this->createWar(['enemy' => 'Friendly Team', 'league' => 'Friendly']);
        $this->createWar(['enemy' => 'ESPL Team 2', 'league' => 'ESPL']);

        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM pc_wars WHERE league = ?');
        $league = 'ESPL';
        $stmt->bind_param('s', $league);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame(2, (int) $row['count']);
    }
}
