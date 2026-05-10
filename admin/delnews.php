<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Delete News
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/** @var mysqli $conn */
/** @var string $admin_tbl1 */
/** @var string $admin_tbl2 */
/** @var string $admin_tbl3 */
/** @var array<string, mixed> $settings */
/** @var array<string, mixed> $pcadmin */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<?php
// CSRF protection
csrf_check();

if (($pcadmin['news_del'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $newsid = $_GET['newsid'] ?? $_POST['newsid'] ?? '';

    if (!empty($newsid)) {
        $stmt = db_prepare($conn, 'SELECT * FROM pc_news WHERE id = ?');
        $newsidInt = (int) $newsid;
        $stmt->bind_param('i', $newsidInt);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new RuntimeException('Failed to get result');
        }
        $num = mysqli_num_rows($result);

        if ($num === 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $stmt->close();
            if (!is_array($row)) {
                throw new RuntimeException('Failed to fetch news data');
            }

            $delnews = $_POST['delnews'] ?? '';

            if ($delnews === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $delStmt = db_prepare($conn, 'DELETE FROM pc_news WHERE id = ?');
                $delStmt->bind_param('i', $newsidInt);
                $delStmt->execute();
                $delStmt->close();
                echo '<div class="alert alert-success" role="alert">'
                    . 'Der Newseintrag wurde erfolgreich gelöscht. '
                    . '<a class="alert-link" href="choosenews.php">Zur News-Übersicht</a></div>';
            } else {
                $date = date('d.m.Y', (int) $row['time']);
                $title = e($row['title'] ?? '');
                $newsId = (int) $row['id'];
                ?>
                <div class="card border-danger shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h1 class="h5 mb-0">News löschen?</h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <strong>Achtung:</strong> Dieser Vorgang kann nicht rückgängig gemacht werden.
                        </div>
                        <p>
                            Sollen die News <strong><?php echo $title; ?></strong> vom <strong><?php echo $date; ?></strong>
                            wirklich gelöscht werden?
                        </p>
                        <form action="delnews.php" method="post" class="d-flex flex-wrap gap-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="newsid" value="<?php echo $newsId; ?>">
                            <input type="hidden" name="delnews" value="YES">
                            <button type="submit" class="btn btn-danger">Ja, Newseintrag endgültig löschen</button>
                            <a class="btn btn-outline-secondary" href="choosenews.php">Nein, abbrechen</a>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            $stmt->close();
            echo '<div class="alert alert-warning" role="alert">'
                . 'Der gewählte Newseintrag existiert nicht. '
                . '<a class="alert-link" href="choosenews.php">Zurück zur Übersicht</a></div>';
        }
    } else {
        echo '<div class="alert alert-warning" role="alert">'
            . 'Bitte wähle einen Newseintrag aus. '
            . '<a class="alert-link" href="choosenews.php">Zur Übersicht</a></div>';
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
