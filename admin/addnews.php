<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Add News
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

if (($pcadmin['news_add'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $addnews = $_GET['addnews'] ?? '';

    if ($addnews === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');

        if (empty($title) || empty($text)) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Bitte fülle alle Felder aus! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
        } else {
            // BUG-025: strip_tags entfernt, Ausgabe wird stattdessen via e()/htmlspecialchars escaped
            $now = time();
            $userId = (int) ($pcadmin['id'] ?? 0);
            $nick = $pcadmin['nick'] ?? '';
            $email = $pcadmin['email'] ?? '';

            // Use prepared statement to prevent SQL injection
            $sql = 'INSERT INTO pc_news (userid, time, nick, email, title, text) '
                . 'VALUES (?, ?, ?, ?, ?, ?)';
            $stmt = db_prepare($conn, $sql);
            $stmt->bind_param('iissss', $userId, $now, $nick, $email, $title, $text);
            $stmt->execute();
            $stmt->close();

            echo '<div class="alert alert-success" role="alert">'
                . 'Deine News wurden erfolgreich gepostet! '
                . '<a class="alert-link" href="choosenews.php">Zur News-Übersicht</a></div>';
        }
    } else {
        $phpSelf = e($_SERVER['PHP_SELF']);
        $nickDisplay = e($pcadmin['nick'] ?? '');
        $emailDisplay = e($pcadmin['email'] ?? '');
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
                <h1 class="h4 mb-0">News hinzufügen</h1>
            </div>
            <div class="card-body">
                <form action="<?php echo $phpSelf; ?>?addnews=YES" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Nickname</label>
                        <div class="col-sm-9">
                            <p class="form-control-plaintext fw-semibold mb-1"><?php echo $nickDisplay; ?></p>
                            <div class="form-text">
                                Wenn Du Deinen Nickname ändern möchtest, editiere
                                <a class="link-primary" href="profile.php">Dein Profil</a>.
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">E-Mail-Adresse</label>
                        <div class="col-sm-9">
                            <p class="form-control-plaintext mb-1"><?php echo $emailDisplay; ?></p>
                            <div class="form-text">
                                Wenn Du Deine E-Mail-Adresse ändern möchtest, editiere
                                <a class="link-primary" href="profile.php">Dein Profil</a>.
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="title" class="col-sm-3 col-form-label">Titel <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="title" name="title" type="text" class="form-control" maxlength="150" required aria-describedby="titleHelp">
                            <div id="titleHelp" class="form-text">Der Titel für Deinen Newseintrag.</div>
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
                            <textarea id="text" name="text" class="form-control" rows="12" required aria-describedby="textHelp"></textarea>
                            <div id="textHelp" class="form-text">
                                Folgende BBCode-Befehle stehen zur Verfügung:
                                <code>[b]fett[/b]</code>,
                                <code>[u]unterstrichen[/u]</code>,
                                <code>[i]kursiv[/i]</code>,
                                <code>[url]https://...[/url]</code>,
                                <code>[url=https://...]Linktext[/url]</code>,
                                <code>[email]a@b.de[/email]</code>,
                                <code>[email=a@b.de]Mail[/email]</code>.
                                Enter erzeugt einen Zeilenumbruch.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">News hinzufügen</button>
                        <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
