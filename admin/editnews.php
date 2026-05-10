<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit News
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

$newsid = $_GET['newsid'] ?? '';
$row = null;

// Get news data if newsid provided
if (!empty($newsid)) {
    $stmt = db_prepare($conn, 'SELECT * FROM pc_news WHERE id = ?');
    $newsidInt = (int) $newsid;
    $stmt->bind_param('i', $newsidInt);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result !== false && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Check permissions
$hasAccess = ($pcadmin['news_edit'] ?? '') === 'YES'
    || ($row !== null && (int) ($row['userid'] ?? 0) === (int) ($pcadmin['id'] ?? 0))
    || ($pcadmin['superadmin'] ?? '') === 'YES';

if ($hasAccess) {
    if (is_array($row)) {
        $editnews = $_GET['editnews'] ?? '';

        if ($editnews === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $text = trim($_POST['text'] ?? '');

            if (empty($title) || empty($text)) {
                echo '<div class="alert alert-danger" role="alert">'
                    . 'Bitte fülle alle Felder aus! '
                    . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            } else {
                // BUG-025: kein strip_tags, Output-Escaping reicht
                $newsIdForUpdate = (int) $row['id'];

                // Use prepared statement
                $updateStmt = db_prepare($conn, 'UPDATE pc_news SET title = ?, text = ? WHERE id = ?');
                $updateStmt->bind_param('ssi', $title, $text, $newsIdForUpdate);
                $updateStmt->execute();
                $updateStmt->close();

                echo '<div class="alert alert-success" role="alert">'
                    . 'Die News wurden erfolgreich editiert. '
                    . '<a class="alert-link" href="choosenews.php">Zur News-Übersicht</a></div>';
            }
        } else {
            $phpSelf = e($_SERVER['PHP_SELF']);
            $newsIdEsc = (int) $row['id'];
            $nickEsc = e($row['nick'] ?? '');
            $emailEsc = e($row['email'] ?? '');
            $titleEsc = e($row['title'] ?? '');
            $textEsc = e($row['text'] ?? '');
            ?>
            <script>
            function insertBBCode(tag) {
                var textarea = document.getElementById("text");
                var startPos = textarea.selectionStart;
                var endPos = textarea.selectionEnd;
                var selectedText = textarea.value.substring(startPos, endPos);

                var newText = "[" + tag + "]" + selectedText + "[/" + tag + "]";
                textarea.value = textarea.value.substring(0, startPos) + newText + textarea.value.substring(endPos);
            }
            </script>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    <h1 class="h4 mb-0">News editieren</h1>
                </div>
                <div class="card-body">
                    <form action="<?php echo $phpSelf; ?>?editnews=YES&newsid=<?php echo $newsIdEsc; ?>" method="post" novalidate>
                        <?php echo csrf_field(); ?>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nickname</label>
                            <div class="col-sm-9">
                                <p class="form-control-plaintext fw-semibold mb-0"><?php echo $nickEsc; ?></p>
                                <div class="form-text">Der Nickname des Autors.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">E-Mail</label>
                            <div class="col-sm-9">
                                <p class="form-control-plaintext mb-0"><?php echo $emailEsc; ?></p>
                                <div class="form-text">Die E-Mail-Adresse des Autors.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="title" class="col-sm-3 col-form-label">Titel <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="title" name="title" type="text" class="form-control" maxlength="150" value="<?php echo $titleEsc; ?>" required aria-describedby="titleHelp">
                                <div id="titleHelp" class="form-text">Der Titel des Newseintrags.</div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="text" class="col-sm-3 col-form-label">Text <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <div class="btn-group btn-group-sm mb-2" role="group" aria-label="BBCode-Schnellbefehle">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('b')"><strong>Fett</strong></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('u')"><u>Unterstrichen</u></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('i')"><em>Kursiv</em></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('url')">URL</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('email')">E-Mail</button>
                                </div>
                                <textarea id="text" name="text" class="form-control" rows="12" required aria-describedby="textHelp"><?php echo $textEsc; ?></textarea>
                                <div id="textHelp" class="form-text">
                                    BBCode wie <code>[b]fett[/b]</code>, <code>[u]unterstrichen[/u]</code>,
                                    <code>[i]kursiv[/i]</code>, <code>[url]...[/url]</code>,
                                    <code>[url=...]...[/url]</code>, <code>[email]...[/email]</code>,
                                    <code>[email=...]...[/email]</code> wird unterstützt.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">News editieren</button>
                            <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                            <a class="btn btn-link ms-auto" href="choosenews.php">Abbrechen</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }
    } elseif (!empty($newsid)) {
        echo '<div class="alert alert-warning" role="alert">'
            . 'Der gewählte Newseintrag existiert nicht. '
            . '<a class="alert-link" href="choosenews.php">Zur Übersicht</a></div>';
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
