<?php

declare(strict_types=1);

namespace PowerClan\Tests\Fixtures;

use mysqli;

/**
 * Test Database Helper
 *
 * Provides database setup and teardown for integration tests.
 */
class TestDatabase
{
    private static ?mysqli $conn = null;

    /**
     * Get database connection
     */
    public static function getConnection(): mysqli
    {
        if (self::$conn === null) {
            $config = $GLOBALS['test_mysql'];
            self::$conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $config['port']
            );

            if (self::$conn->connect_error) {
                throw new \RuntimeException('Database connection failed: ' . self::$conn->connect_error);
            }

            self::$conn->set_charset('utf8mb4');
        }

        return self::$conn;
    }

    /**
     * Initialize test database with schema
     */
    public static function initialize(): void
    {
        $conn = self::getConnection();

        // Create tables if not exist
        $schema = file_get_contents(__DIR__ . '/../../powerclan.sql');
        if ($schema !== false) {
            $conn->multi_query($schema);
            while ($conn->next_result()) {
                // Flush results
            }
        }

        // Clear test data
        self::clearAll();

        // Insert default config
        $conn->query("INSERT INTO pc_config (id, clanname, clantag, url, serverpath, header, footer, tablebg1, tablebg2, tablebg3, clrwon, clrdraw, clrlost, newslimit, warlimit)
                      VALUES (1, 'TestClan', 'TC', 'http://localhost', '/var/www/html', '', '', '#000000', '#111111', '#222222', '#00FF00', '#FFFF00', '#FF0000', 10, 10)
                      ON DUPLICATE KEY UPDATE clanname = 'TestClan'");
    }

    /**
     * Clear all test data
     */
    public static function clearAll(): void
    {
        $conn = self::getConnection();
        $conn->query('DELETE FROM pc_news');
        $conn->query('DELETE FROM pc_wars');
        $conn->query('DELETE FROM pc_members WHERE id > 1');
    }

    /**
     * Create a test member
     */
    public static function createTestMember(array $data = []): int
    {
        $conn = self::getConnection();

        $defaults = [
            'nick' => 'TestUser' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => password_hash('testpassword', PASSWORD_DEFAULT),
            'work' => 'Tester',
            'realname' => 'Test User',
            'icq' => 0,
            'homepage' => '',
            'age' => 25,
            'hardware' => '',
            'info' => '',
            'pic' => '',
            'member_add' => 'NO',
            'member_edit' => 'NO',
            'member_del' => 'NO',
            'news_add' => 'NO',
            'news_edit' => 'NO',
            'news_del' => 'NO',
            'wars_add' => 'NO',
            'wars_edit' => 'NO',
            'wars_del' => 'NO',
            'superadmin' => 'NO',
        ];

        $data = array_merge($defaults, $data);

        $stmt = $conn->prepare('INSERT INTO pc_members (nick, email, password, work, realname, icq, homepage, age, hardware, info, pic, member_add, member_edit, member_del, news_add, news_edit, news_del, wars_add, wars_edit, wars_del, superadmin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'sssssisisssssssssssss',
            $data['nick'],
            $data['email'],
            $data['password'],
            $data['work'],
            $data['realname'],
            $data['icq'],
            $data['homepage'],
            $data['age'],
            $data['hardware'],
            $data['info'],
            $data['pic'],
            $data['member_add'],
            $data['member_edit'],
            $data['member_del'],
            $data['news_add'],
            $data['news_edit'],
            $data['news_del'],
            $data['wars_add'],
            $data['wars_edit'],
            $data['wars_del'],
            $data['superadmin']
        );
        $stmt->execute();
        $id = (int) $conn->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * Create a test admin (superadmin)
     */
    public static function createTestAdmin(array $data = []): int
    {
        $defaults = [
            'nick' => 'TestAdmin' . uniqid(),
            'email' => 'admin' . uniqid() . '@example.com',
            'superadmin' => 'YES',
            'member_add' => 'YES',
            'member_edit' => 'YES',
            'member_del' => 'YES',
            'news_add' => 'YES',
            'news_edit' => 'YES',
            'news_del' => 'YES',
            'wars_add' => 'YES',
            'wars_edit' => 'YES',
            'wars_del' => 'YES',
        ];

        return self::createTestMember(array_merge($defaults, $data));
    }

    /**
     * Create a test news entry
     */
    public static function createTestNews(array $data = []): int
    {
        $conn = self::getConnection();

        $defaults = [
            'userid' => 1,
            'time' => time(),
            'nick' => 'TestUser',
            'email' => 'test@example.com',
            'title' => 'Test News ' . uniqid(),
            'text' => 'Test news content',
        ];

        $data = array_merge($defaults, $data);

        $stmt = $conn->prepare('INSERT INTO pc_news (userid, time, nick, email, title, text) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iissss', $data['userid'], $data['time'], $data['nick'], $data['email'], $data['title'], $data['text']);
        $stmt->execute();
        $id = (int) $conn->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * Create a test war
     */
    public static function createTestWar(array $data = []): int
    {
        $conn = self::getConnection();

        $defaults = [
            'enemy' => 'Enemy Clan ' . uniqid(),
            'enemy_tag' => 'EC',
            'homepage' => 'https://example.com',
            'league' => 'Friendly',
            'map1' => 'de_dust2',
            'map2' => 'de_inferno',
            'map3' => '',
            'time' => time() + 86400,
            'report' => '',
            'res1' => '',
            'res2' => '',
            'res3' => '',
            'screen1' => '',
            'screen2' => '',
            'screen3' => '',
        ];

        $data = array_merge($defaults, $data);

        $stmt = $conn->prepare('INSERT INTO pc_wars (enemy, enemy_tag, homepage, league, map1, map2, map3, time, report, res1, res2, res3, screen1, screen2, screen3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'sssssssisssssss',
            $data['enemy'],
            $data['enemy_tag'],
            $data['homepage'],
            $data['league'],
            $data['map1'],
            $data['map2'],
            $data['map3'],
            $data['time'],
            $data['report'],
            $data['res1'],
            $data['res2'],
            $data['res3'],
            $data['screen1'],
            $data['screen2'],
            $data['screen3']
        );
        $stmt->execute();
        $id = (int) $conn->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * Get member by ID
     */
    public static function getMember(int $id): ?array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare('SELECT * FROM pc_members WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        if (self::$conn !== null) {
            self::$conn->close();
            self::$conn = null;
        }
    }
}
