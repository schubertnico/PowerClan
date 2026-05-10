<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Profile Editor
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

// Get current member data using prepared statement
$stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE id = ?');
$memberId = (int) ($pcadmin['id'] ?? 0);
$stmt->bind_param('i', $memberId);
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    throw new RuntimeException('Failed to get result');
}
$num = mysqli_num_rows($result);

// CSRF protection
csrf_check();

if ($num === 1) {
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stmt->close();

    $editprofile = $_GET['editprofile'] ?? '';

    if ($editprofile === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get POST values with null coalescing
        $nick = $_POST['nick'] ?? '';
        $email = $_POST['email'] ?? '';
        $password1 = $_POST['password1'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $icq = $_POST['icq'] ?? '';
        $homepage = $_POST['homepage'] ?? '';
        $realname = $_POST['realname'] ?? '';
        $age = $_POST['age'] ?? '';
        $hardware = $_POST['hardware'] ?? '';
        $info = $_POST['info'] ?? '';
        $pic = $_POST['pic'] ?? '';

        // Validation
        if (empty($nick) || empty($email)) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Bitte gib Nickname und E-Mail an! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            include __DIR__ . '/footer.inc.php';
            exit;
        }

        // Check for duplicate email/nick using prepared statement
        $checkStmt = db_prepare($conn, 'SELECT id FROM pc_members WHERE (email = ? OR nick = ?) AND id != ?');
        $checkStmt->bind_param('ssi', $email, $nick, $memberId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult === false) {
            throw new RuntimeException('Failed to get result');
        }
        if (mysqli_num_rows($checkResult) !== 0) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Es gibt schon einen Member mit dieser E-Mail-Adresse oder diesem Nickname! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            $checkStmt->close();
            include __DIR__ . '/footer.inc.php';
            exit;
        }
        $checkStmt->close();

        // Validate email using filter_var (replaces deprecated regex)
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
            echo '<div class="alert alert-danger" role="alert">'
                . 'Du musst Dein neues Passwort bestätigen. '
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

        // Sanitize inputs
        $nick = trim($nick);
        $email = trim($email);
        $icq = trim($icq);
        $homepage = trim($homepage);
        $realname = trim($realname);
        $age = trim($age);
        $hardware = strip_tags(trim($hardware));
        $info = strip_tags(trim($info));
        $pic = trim($pic);

        // Update profile using prepared statement
        $sql = 'UPDATE pc_members SET nick = ?, email = ?, realname = ?, icq = ?, '
            . 'homepage = ?, age = ?, hardware = ?, info = ?, pic = ? WHERE id = ?';
        $updateStmt = db_prepare($conn, $sql);
        $updateStmt->bind_param(
            'sssssssssi',
            $nick,
            $email,
            $realname,
            $icq,
            $homepage,
            $age,
            $hardware,
            $info,
            $pic,
            $memberId
        );
        $updateStmt->execute();
        $updateStmt->close();

        echo '<div class="alert alert-success" role="alert">'
            . 'Dein Profil wurde erfolgreich editiert. '
            . '<a class="alert-link" href="profile.php">Profil erneut öffnen</a></div>';

        // Update password if changed
        if ($password1 !== '' && $password2 !== '' && $password1 === $password2) {
            if (strlen($password1) < 8) {
                echo '<div class="alert alert-warning" role="alert">'
                    . 'Das Passwort muss mindestens 8 Zeichen haben. Andere Profildaten wurden gespeichert.</div>';
            } else {
                $newPassword = password_hash(trim($password1), PASSWORD_DEFAULT);
                $pwStmt = db_prepare($conn, 'UPDATE pc_members SET password = ? WHERE id = ?');
                $pwStmt->bind_param('si', $newPassword, $memberId);
                $pwStmt->execute();
                $pwStmt->close();

                pc_session_logout();
                echo '<div class="alert alert-info" role="alert">'
                    . 'Da Du Dein Passwort geändert hast, wurdest Du abgemeldet. '
                    . '<a class="alert-link" href="index.php">Bitte neu einloggen</a>.</div>';
            }
        }
    } else {
        // Display edit form with escaped values
        $nickValue = e($row['nick'] ?? '');
        $emailValue = e($row['email'] ?? '');
        $icqValue = e($row['icq'] ?? '');
        $homepageValue = e($row['homepage'] ?? '');
        $realnameValue = e($row['realname'] ?? '');
        $ageValue = e($row['age'] ?? '');
        $hardwareValue = e($row['hardware'] ?? '');
        $infoValue = e($row['info'] ?? '');
        $picValue = e($row['pic'] ?? '');
        $phpSelf = e($_SERVER['PHP_SELF']);
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                <h1 class="h4 mb-0">Profil editieren</h1>
            </div>
            <div class="card-body">
                <form action="<?php echo $phpSelf; ?>?editprofile=YES" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="row mb-3">
                        <label for="nick" class="col-sm-3 col-form-label">Nickname <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="nick" name="nick" type="text" class="form-control" maxlength="100" value="<?php echo $nickValue; ?>" required>
                            <div class="form-text">Der Nickname, unter dem Du bekannt bist.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label">E-Mail-Adresse <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="email" name="email" type="email" class="form-control" maxlength="400" value="<?php echo $emailValue; ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="password1" class="col-sm-3 col-form-label">Neues Passwort</label>
                        <div class="col-sm-9">
                            <input id="password1" name="password1" type="password" class="form-control mb-2" maxlength="72" autocomplete="new-password" aria-describedby="pwHelp">
                            <input id="password2" name="password2" type="password" class="form-control" maxlength="72" autocomplete="new-password" placeholder="Passwort bestätigen">
                            <div id="pwHelp" class="form-text">Mindestens 8 Zeichen. Leer lassen, wenn nichts geändert werden soll.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="icq" class="col-sm-3 col-form-label">ICQ-Nummer</label>
                        <div class="col-sm-9">
                            <input id="icq" name="icq" type="text" class="form-control" maxlength="10" value="<?php echo $icqValue; ?>">
                            <div class="form-text">0 = keine Angabe.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="homepage" class="col-sm-3 col-form-label">Homepage</label>
                        <div class="col-sm-9">
                            <input id="homepage" name="homepage" type="url" class="form-control" maxlength="250" value="<?php echo $homepageValue; ?>" placeholder="https://...">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="realname" class="col-sm-3 col-form-label">Realname</label>
                        <div class="col-sm-9">
                            <input id="realname" name="realname" type="text" class="form-control" maxlength="200" value="<?php echo $realnameValue; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="age" class="col-sm-3 col-form-label">Alter</label>
                        <div class="col-sm-9">
                            <input id="age" name="age" type="number" min="0" max="99" class="form-control" value="<?php echo $ageValue; ?>">
                            <div class="form-text">0 = keine Angabe.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="hardware" class="col-sm-3 col-form-label">Hardware</label>
                        <div class="col-sm-9">
                            <textarea id="hardware" name="hardware" class="form-control" rows="4"><?php echo $hardwareValue; ?></textarea>
                            <div class="form-text">CPU, RAM, Grafikkarte ...</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="info" class="col-sm-3 col-form-label">Persönliche Infos</label>
                        <div class="col-sm-9">
                            <textarea id="info" name="info" class="form-control" rows="4"><?php echo $infoValue; ?></textarea>
                            <div class="form-text">Hobbies, Job, ...</div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="pic" class="col-sm-3 col-form-label">Bild-URL</label>
                        <div class="col-sm-9">
                            <input id="pic" name="pic" type="url" class="form-control" maxlength="250" value="<?php echo $picValue; ?>" placeholder="https://...">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Profil editieren</button>
                        <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
} else {
    $stmt->close();
    echo '<div class="alert alert-warning" role="alert">'
        . 'Der Member existiert nicht oder Du bist nicht eingeloggt. '
        . '<a class="alert-link" href="index.php">Zum Dashboard</a></div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
