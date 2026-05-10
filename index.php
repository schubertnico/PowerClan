<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Main Index Page
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/** @var mysqli $conn */
/** @var array<string, mixed> $settings */

?>
<!--HEADER FILE-->
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAIN PAGE-->

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-body-secondary fw-semibold">
                Neueste News
            </div>
            <ul class="list-group list-group-flush">
<?php
$newsLimit = (int) ($settings['newslimit'] ?? 5);
$stmt = db_prepare($conn, 'SELECT * FROM pc_news ORDER BY id DESC LIMIT ?');
$stmt->bind_param('i', $newsLimit);
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    throw new RuntimeException('Failed to get result');
}
$num = mysqli_num_rows($result);

if ($num !== 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $date = date('d.m.Y', (int) $row['time']);
        $newsId = (int) $row['id'];
        $title = e($row['title'] ?? '');
        echo '<li class="list-group-item d-flex justify-content-between align-items-start gap-3">'
            . '<a class="fw-semibold" href="#news' . $newsId . '">' . $title . '</a>'
            . '<span class="text-body-secondary small text-nowrap">' . $date . '</span>'
            . "</li>\n";
    }
} else {
    echo '<li class="list-group-item text-body-secondary">Keine News vorhanden</li>';
}
$stmt->close();
?>
            </ul>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-body-secondary fw-semibold">
                Neueste Wars
            </div>
            <ul class="list-group list-group-flush">
<?php
$warLimit = (int) ($settings['warlimit'] ?? 5);
$stmt = db_prepare($conn, "SELECT * FROM pc_wars WHERE res1 != '' AND res2 != '' ORDER BY time DESC LIMIT ?");
$stmt->bind_param('i', $warLimit);
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    throw new RuntimeException('Failed to get result');
}
$num = mysqli_num_rows($result);

if ($num !== 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $date = date('d.m.Y', (int) $row['time']);
        $allres = ['left' => 0, 'right' => 0];

        if (!empty($row['map1']) && !empty($row['res1'])) {
            $res = explode(':', (string) $row['res1']);
            $allres['left'] += (int) ($res[0] ?? 0);
            $allres['right'] += (int) ($res[1] ?? 0);
        }
        if (!empty($row['map2']) && !empty($row['res2'])) {
            $res = explode(':', (string) $row['res2']);
            $allres['left'] += (int) ($res[0] ?? 0);
            $allres['right'] += (int) ($res[1] ?? 0);
        }
        if (!empty($row['map3']) && !empty($row['res3'])) {
            $res = explode(':', (string) $row['res3']);
            $allres['left'] += (int) ($res[0] ?? 0);
            $allres['right'] += (int) ($res[1] ?? 0);
        }

        if ($allres['left'] > $allres['right']) {
            $badgeClass = 'text-bg-success';
            $badgeLabel = 'Gewonnen';
        } elseif ($allres['left'] === $allres['right']) {
            $badgeClass = 'text-bg-warning';
            $badgeLabel = 'Unentschieden';
        } else {
            $badgeClass = 'text-bg-danger';
            $badgeLabel = 'Verloren';
        }

        $warId = (int) $row['id'];
        $clanTag = e($settings['clantag'] ?? '');
        $enemyTag = e($row['enemy_tag'] ?? '');
        echo '<li class="list-group-item d-flex justify-content-between align-items-center gap-2 flex-wrap">'
            . '<a class="fw-semibold" href="wars.php#war' . $warId . '">'
            . $clanTag . ' vs. ' . $enemyTag
            . '</a>'
            . '<span class="d-inline-flex align-items-center gap-2">'
            . '<span class="badge ' . $badgeClass . '">' . $badgeLabel . '</span>'
            . '<span class="text-body-secondary small text-nowrap">' . $date . '</span>'
            . '</span>'
            . "</li>\n";
    }
} else {
    echo '<li class="list-group-item text-body-secondary">Keine Wars vorhanden</li>';
}
$stmt->close();
?>
            </ul>
        </div>
    </div>
</div>

<section aria-label="News">
<?php
$stmt = db_prepare($conn, 'SELECT * FROM pc_news ORDER BY id DESC LIMIT ?');
$stmt->bind_param('i', $newsLimit);
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    throw new RuntimeException('Failed to get result');
}
$num = mysqli_num_rows($result);

if ($num !== 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $date = date('d.m.Y', (int) $row['time']);
        $text = news_replace($row['text'] ?? '');
        $newsId = (int) $row['id'];
        $title = e($row['title'] ?? '');
        $nick = e($row['nick'] ?? '');
        $email = e($row['email'] ?? '');

        echo '<article class="card shadow-sm mb-4" id="news' . $newsId . '">'
            . '<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">'
            . '<h2 class="h5 mb-0">' . $title . '</h2>'
            . '<span class="text-body-secondary small">' . $date . '</span>'
            . '</div>'
            . '<div class="card-body pc-news-body">' . $text . '</div>'
            . '<div class="card-footer text-body-secondary small">'
            . 'von <a class="link-secondary" href="mailto:' . $email . '">' . $nick . '</a>'
            . '</div>'
            . '</article>';
    }
} else {
    echo '<div class="alert alert-info" role="alert">Aktuell sind keine News vorhanden.</div>';
}
$stmt->close();
?>
</section>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
