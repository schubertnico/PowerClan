<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit Member
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
// CSRF-Schutz (BUG-017)
csrf_check();

if (($pcadmin['member_edit'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $memberid = $_GET['memberid'] ?? '';

    if (!empty($memberid)) {
        $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE id = ?');
        $memberidInt = (int) $memberid;
        $stmt->bind_param('i', $memberidInt);
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
                throw new RuntimeException('Failed to fetch member data');
            }
            $rowId = (int) $row['id'];

            $editmember = $_GET['editmember'] ?? '';

            if ($editmember === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get POST data
                $nick = trim($_POST['nick'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password1 = $_POST['password1'] ?? '';
                $password2 = $_POST['password2'] ?? '';
                $work = trim($_POST['work'] ?? '');
                $icq = (int) ($_POST['icq'] ?? 0);              // BUG-018: int statt string
                $homepage = trim($_POST['homepage'] ?? '');
                $realname = trim($_POST['realname'] ?? '');
                $age = (int) ($_POST['age'] ?? 0);              // BUG-018: int statt string
                $hardware = strip_tags(trim($_POST['hardware'] ?? ''));
                $info = strip_tags(trim($_POST['info'] ?? ''));
                $pic = trim($_POST['pic'] ?? '');

                if ($age < 0 || $age > 99) {
                    echo '<div class="alert alert-danger" role="alert">'
                        . 'Alter muss zwischen 0 und 99 liegen. '
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Get permission checkboxes
                $member_add = ($_POST['member_add'] ?? '') === 'YES' ? 'YES' : 'NO';
                $member_edit = ($_POST['member_edit'] ?? '') === 'YES' ? 'YES' : 'NO';
                $member_del = ($_POST['member_del'] ?? '') === 'YES' ? 'YES' : 'NO';
                $news_add = ($_POST['news_add'] ?? '') === 'YES' ? 'YES' : 'NO';
                $news_edit = ($_POST['news_edit'] ?? '') === 'YES' ? 'YES' : 'NO';
                $news_del = ($_POST['news_del'] ?? '') === 'YES' ? 'YES' : 'NO';
                $wars_add = ($_POST['wars_add'] ?? '') === 'YES' ? 'YES' : 'NO';
                $wars_edit = ($_POST['wars_edit'] ?? '') === 'YES' ? 'YES' : 'NO';
                $wars_del = ($_POST['wars_del'] ?? '') === 'YES' ? 'YES' : 'NO';

                // Validation
                if (empty($nick) || empty($email)) {
                    echo '<div class="alert alert-danger" role="alert">'
                        . 'Bitte gib Nickname und E-Mail an! '
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Check for duplicate
                $checkStmt = db_prepare($conn, 'SELECT id FROM pc_members WHERE (email = ? OR nick = ?) AND id != ?');
                $checkStmt->bind_param('ssi', $email, $nick, $rowId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult === false) {
                    throw new RuntimeException('Failed to get result');
                }
                if (mysqli_num_rows($checkResult) !== 0) {
                    echo '<div class="alert alert-danger" role="alert">'
                        . 'Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname! '
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    $checkStmt->close();
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }
                $checkStmt->close();

                // Validate email
                if (!validate_email($email)) {
                    echo '<div class="alert alert-danger" role="alert">'
                        . 'Die angegebene E-Mail-Adresse ist ungültig! '
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Password validation
                if (
                    ($password1 !== '' && $password2 === '')
                    || ($password1 === '' && $password2 !== '')
                ) {
                    $nickEsc = e($row['nick'] ?? '');
                    echo '<div class="alert alert-danger" role="alert">'
                        . "Du musst das neue Passwort für {$nickEsc} bestätigen. "
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }
                if ($password1 !== $password2) {
                    echo '<div class="alert alert-danger" role="alert">'
                        . 'Das neue Passwort wurde falsch bestätigt! '
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Update member using prepared statement
                // Typen-Signatur: nick s, email s, work s, realname s, icq i, homepage s, age i,
                // hardware s, info s, pic s, 9x permission s, id i
                $sql = 'UPDATE pc_members SET nick = ?, email = ?, work = ?, realname = ?, '
                    . 'icq = ?, homepage = ?, age = ?, hardware = ?, info = ?, pic = ?, '
                    . 'member_add = ?, member_edit = ?, member_del = ?, news_add = ?, '
                    . 'news_edit = ?, news_del = ?, wars_add = ?, wars_edit = ?, wars_del = ? '
                    . 'WHERE id = ?';
                try {
                    $updateStmt = db_prepare($conn, $sql);
                    $updateStmt->bind_param(
                        'ssssisissssssssssssi',
                        $nick,
                        $email,
                        $work,
                        $realname,
                        $icq,
                        $homepage,
                        $age,
                        $hardware,
                        $info,
                        $pic,
                        $member_add,
                        $member_edit,
                        $member_del,
                        $news_add,
                        $news_edit,
                        $news_del,
                        $wars_add,
                        $wars_edit,
                        $wars_del,
                        $rowId
                    );
                    $updateStmt->execute();
                    $updateStmt->close();
                } catch (Throwable $ex) {
                    error_log('editmember.php update failed: ' . $ex->getMessage());
                    echo '<div class="alert alert-danger" role="alert"><strong>Fehler beim Speichern:</strong> '
                        . e($ex->getMessage()) . '</div>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                $nickEsc = e($row['nick'] ?? '');
                echo '<div class="alert alert-success" role="alert">'
                    . "Der Member <strong>{$nickEsc}</strong> wurde erfolgreich editiert. "
                    . '<a class="alert-link" href="choosemember.php">Zur Übersicht</a></div>';

                // Update password if changed
                if ($password1 !== '' && $password2 !== '' && $password1 === $password2) {
                    $newPassword = password_hash(trim($password1), PASSWORD_DEFAULT);
                    $pwStmt = db_prepare($conn, 'UPDATE pc_members SET password = ? WHERE id = ?');
                    $pwStmt->bind_param('si', $newPassword, $rowId);
                    $pwStmt->execute();
                    $pwStmt->close();

                    // Send email notification
                    $adminNick = $pcadmin['nick'] ?? 'Admin';
                    $clanname = $settings['clanname'] ?? 'PowerClan';
                    $memberEmail = $row['email'] ?? '';

                    $subject = 'PowerClan Autoemail';
                    $message = "Hallo {$nick},

{$adminNick} hat Dein Passwort geaendert. Hier sind Deine neuen Logindaten fuer den {$clanname} Clan:

Nickname: {$nick}
E-Mail: {$email}
Passwort: {$password1}

Du kannst Deine Daten jederzeit aendern!

-BITTE NICHT AUF DIESE AUTOMATISCH GENERIERTE EMAIL ANTWORTEN-";

                    $headers = 'From: PowerClan Automailer <powerclan@powerscripts.org>';
                    $ok = @mail($memberEmail, $subject, $message, $headers);

                    if ($ok) {
                        echo '<div class="alert alert-info" role="alert">'
                            . "Außerdem wurde {$nickEsc} eine E-Mail mit dem neuen Passwort zugeschickt.</div>";
                    } else {
                        echo '<div class="alert alert-warning" role="alert">'
                            . "<strong>Achtung:</strong> Die Passwort-Mail an {$nickEsc} konnte nicht versendet werden &mdash; "
                            . 'bitte das neue Passwort manuell übermitteln.</div>';
                    }
                }
            } else {
                // Display edit form
                $phpSelf = e($_SERVER['PHP_SELF']);
                $nickEsc = e($row['nick'] ?? '');
                $emailEsc = e($row['email'] ?? '');
                $workEsc = e($row['work'] ?? '');
                $icqEsc = e($row['icq'] ?? '');
                $homepageEsc = e($row['homepage'] ?? '');
                $realnameEsc = e($row['realname'] ?? '');
                $ageEsc = e($row['age'] ?? '');
                $hardwareEsc = e($row['hardware'] ?? '');
                $infoEsc = e($row['info'] ?? '');
                $picEsc = e($row['pic'] ?? '');

                // Permission checkboxes
                $permissions = [
                    'member_add' => 'Member hinzufügen',
                    'member_edit' => 'Member editieren',
                    'member_del' => 'Member löschen',
                    'news_add' => 'News hinzufügen',
                    'news_edit' => 'News editieren',
                    'news_del' => 'News löschen',
                    'wars_add' => 'Wars hinzufügen',
                    'wars_edit' => 'Wars editieren',
                    'wars_del' => 'Wars löschen',
                ];
                ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-body-secondary">
                        <h1 class="h4 mb-0">Member editieren</h1>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo $phpSelf; ?>?memberid=<?php echo $rowId; ?>&editmember=YES" method="post" novalidate>
                            <?php echo csrf_field(); ?>
                            <div class="row mb-3">
                                <label for="nick" class="col-sm-3 col-form-label">Nickname <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="nick" name="nick" type="text" class="form-control" maxlength="100" value="<?php echo $nickEsc; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="email" class="col-sm-3 col-form-label">E-Mail <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="email" name="email" type="email" class="form-control" maxlength="400" value="<?php echo $emailEsc; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="password1" class="col-sm-3 col-form-label">Neues Passwort</label>
                                <div class="col-sm-9">
                                    <input id="password1" name="password1" type="password" class="form-control mb-2" maxlength="72" autocomplete="new-password" aria-describedby="pwHelp">
                                    <input id="password2" name="password2" type="password" class="form-control" maxlength="72" autocomplete="new-password" placeholder="Passwort bestätigen">
                                    <div id="pwHelp" class="form-text">Leer lassen, wenn das Passwort nicht geändert werden soll.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="work" class="col-sm-3 col-form-label">Aufgabe</label>
                                <div class="col-sm-9">
                                    <input id="work" name="work" type="text" class="form-control" maxlength="200" value="<?php echo $workEsc; ?>">
                                    <div class="form-text">Die Aufgabe, die der Member im Clan übernimmt.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="icq" class="col-sm-3 col-form-label">ICQ-Nummer</label>
                                <div class="col-sm-9">
                                    <input id="icq" name="icq" type="number" class="form-control" min="0" maxlength="10" value="<?php echo $icqEsc; ?>">
                                    <div class="form-text">0 = keine Angabe.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="homepage" class="col-sm-3 col-form-label">Homepage</label>
                                <div class="col-sm-9">
                                    <input id="homepage" name="homepage" type="url" class="form-control" maxlength="250" value="<?php echo $homepageEsc; ?>" placeholder="https://...">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="realname" class="col-sm-3 col-form-label">Realname</label>
                                <div class="col-sm-9">
                                    <input id="realname" name="realname" type="text" class="form-control" maxlength="200" value="<?php echo $realnameEsc; ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="age" class="col-sm-3 col-form-label">Alter</label>
                                <div class="col-sm-9">
                                    <input id="age" name="age" type="number" min="0" max="99" class="form-control" value="<?php echo $ageEsc; ?>">
                                    <div class="form-text">0 = keine Angabe.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="hardware" class="col-sm-3 col-form-label">Hardware</label>
                                <div class="col-sm-9">
                                    <textarea id="hardware" name="hardware" class="form-control" rows="4"><?php echo $hardwareEsc; ?></textarea>
                                    <div class="form-text">Informationen über die Hardware des Members.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="info" class="col-sm-3 col-form-label">Persönliche Infos</label>
                                <div class="col-sm-9">
                                    <textarea id="info" name="info" class="form-control" rows="4"><?php echo $infoEsc; ?></textarea>
                                    <div class="form-text">Persönliche Informationen über den Member (Hobbies, Job, ...).</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="pic" class="col-sm-3 col-form-label">Bild-URL</label>
                                <div class="col-sm-9">
                                    <input id="pic" name="pic" type="url" class="form-control" maxlength="250" value="<?php echo $picEsc; ?>" placeholder="https://...">
                                </div>
                            </div>
                            <fieldset class="row mb-4">
                                <legend class="col-sm-3 col-form-label pt-0">Adminrechte</legend>
                                <div class="col-sm-9">
                                    <?php foreach ($permissions as $key => $label):
                                        $checked = (($row[$key] ?? '') === 'YES') ? ' checked' : '';
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="perm_<?php echo $key; ?>" name="<?php echo $key; ?>" value="YES"<?php echo $checked; ?>>
                                            <label class="form-check-label" for="perm_<?php echo $key; ?>"><?php echo e($label); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="form-text mt-1">Superadmin-Rechte können hier nicht gesetzt werden.</div>
                                </div>
                            </fieldset>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Member editieren</button>
                                <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                                <a class="btn btn-link ms-auto" href="choosemember.php">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            $stmt->close();
            echo '<div class="alert alert-warning" role="alert">'
                . 'Der gewählte Member existiert nicht. '
                . '<a class="alert-link" href="choosemember.php">Zur Übersicht</a></div>';
        }
    } else {
        echo '<div class="alert alert-warning" role="alert">'
            . 'Bitte wähle einen Member aus. '
            . '<a class="alert-link" href="choosemember.php">Zur Übersicht</a></div>';
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
