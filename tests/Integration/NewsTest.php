<?php

declare(strict_types=1);

namespace PowerClan\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for news management
 */
class NewsTest extends IntegrationTestCase
{
    // =========================================================================
    // News Creation Tests
    // =========================================================================

    #[Test]
    public function canCreateNewsEntry(): void
    {
        global $conn;

        $newsId = $this->createNews([
            'title' => 'Test News Title',
            'text' => 'Test news content here.',
            'nick' => 'Author',
            'email' => 'author@example.com',
        ]);

        $stmt = $conn->prepare("SELECT * FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($news);
        $this->assertSame('Test News Title', $news['title']);
        $this->assertSame('Test news content here.', $news['text']);
    }

    #[Test]
    public function newsHasTimestamp(): void
    {
        global $conn;

        $beforeTime = time();
        $newsId = $this->createNews(['title' => 'Timestamp Test']);
        $afterTime = time();

        $stmt = $conn->prepare("SELECT time FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();

        $this->assertGreaterThanOrEqual($beforeTime, (int)$news['time']);
        $this->assertLessThanOrEqual($afterTime, (int)$news['time']);
    }

    #[Test]
    public function newsIsAssociatedWithAuthor(): void
    {
        global $conn;

        $adminId = $this->createAdmin(['nick' => 'NewsAuthor', 'email' => 'newsauthor@example.com']);

        $newsId = $this->createNews([
            'userid' => $adminId,
            'nick' => 'NewsAuthor',
            'email' => 'newsauthor@example.com',
            'title' => 'Author Test',
        ]);

        $stmt = $conn->prepare("SELECT userid, nick, email FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame($adminId, (int)$news['userid']);
        $this->assertSame('NewsAuthor', $news['nick']);
    }

    // =========================================================================
    // News Update Tests
    // =========================================================================

    #[Test]
    public function canUpdateNewsTitle(): void
    {
        global $conn;

        $newsId = $this->createNews(['title' => 'Original Title']);

        $newTitle = 'Updated Title';
        $stmt = $conn->prepare("UPDATE pc_news SET title = ? WHERE id = ?");
        $stmt->bind_param('si', $newTitle, $newsId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT title FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame('Updated Title', $news['title']);
    }

    #[Test]
    public function canUpdateNewsText(): void
    {
        global $conn;

        $newsId = $this->createNews(['text' => 'Original text']);

        $newText = 'Updated text with [b]BBCode[/b]';
        $stmt = $conn->prepare("UPDATE pc_news SET text = ? WHERE id = ?");
        $stmt->bind_param('si', $newText, $newsId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT text FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();

        $this->assertSame($newText, $news['text']);
    }

    // =========================================================================
    // News Deletion Tests
    // =========================================================================

    #[Test]
    public function canDeleteNews(): void
    {
        global $conn;

        $newsId = $this->createNews(['title' => 'To Be Deleted']);

        $stmt = $conn->prepare("DELETE FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM pc_news WHERE id = ?");
        $stmt->bind_param('i', $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $this->assertSame(0, $result->num_rows);
    }

    // =========================================================================
    // BBCode Processing Tests
    // =========================================================================

    #[Test]
    public function newsTextSupportsBbcode(): void
    {
        require_once __DIR__ . '/../../functions.inc.php';

        $text = '[b]Bold[/b] and [i]italic[/i] and [u]underline[/u]';
        $processed = news_replace($text);

        $this->assertStringContainsString('<b>Bold</b>', $processed);
        $this->assertStringContainsString('<i>italic</i>', $processed);
        $this->assertStringContainsString('<u>underline</u>', $processed);
    }

    #[Test]
    public function newsTextEscapesHtml(): void
    {
        require_once __DIR__ . '/../../functions.inc.php';

        $text = '<script>alert("xss")</script>';
        $processed = news_replace($text);

        $this->assertStringNotContainsString('<script>', $processed);
        $this->assertStringContainsString('&lt;script&gt;', $processed);
    }

    // =========================================================================
    // News Listing Tests
    // =========================================================================

    #[Test]
    public function canListMultipleNews(): void
    {
        global $conn;

        $this->createNews(['title' => 'News 1']);
        $this->createNews(['title' => 'News 2']);
        $this->createNews(['title' => 'News 3']);

        $result = $conn->query("SELECT COUNT(*) as count FROM pc_news");
        $row = $result->fetch_assoc();

        $this->assertSame(3, (int)$row['count']);
    }

    #[Test]
    public function newsCanBeOrderedByTime(): void
    {
        global $conn;

        $this->createNews(['title' => 'Old News', 'time' => time() - 3600]);
        $this->createNews(['title' => 'New News', 'time' => time()]);

        $result = $conn->query("SELECT title FROM pc_news ORDER BY time DESC");
        $first = $result->fetch_assoc();

        $this->assertSame('New News', $first['title']);
    }
}
