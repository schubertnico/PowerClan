<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit Member (Admin)
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<center>
<?php
// Initialize form variables
$memberid = (int) ($_GET['memberid'] ?? $_POST['memberid'] ?? 0);
$editmember = $_GET['editmember'] ?? '';

// CSRF protection for POST requests
csrf_check();

if (($pcadmin['member_edit'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    if ($memberid > 0) {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare('SELECT * FROM pc_members WHERE id = ?');
        $stmt->bind_param('i', $memberid);
        $stmt->execute();
        $result = $stmt->get_result();
        $num = mysqli_num_rows($result);

        if ($num === 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $stmt->close();
            $rowId = (int) $row['id'];

            if ($editmember === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get and sanitize form data
                $nick = trim($_POST['nick'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password1 = $_POST['password1'] ?? '';
                $password2 = $_POST['password2'] ?? '';
                $work = trim($_POST['work'] ?? '');
                $icq = (int) ($_POST['icq'] ?? 0);
                $homepage = trim($_POST['homepage'] ?? '');
                $realname = trim($_POST['realname'] ?? '');
                $age = (int) ($_POST['age'] ?? 0);
                $hardware = trim(strip_tags($_POST['hardware'] ?? ''));
                $info = trim(strip_tags($_POST['info'] ?? ''));
                $pic = trim($_POST['pic'] ?? '');

                // Permission checkboxes (default to NO if not checked)
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
                if ($nick === '' || $email === '') {
                    echo '<center><a href="javascript:history.back()">Bitte gib Nickname und E-Mail an!</a></center>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Check for duplicate email/nick using prepared statement
                $checkStmt = $conn->prepare('SELECT id FROM pc_members WHERE (email = ? OR nick = ?) AND id != ?');
                $checkStmt->bind_param('ssi', $email, $nick, $rowId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if (mysqli_num_rows($checkResult) !== 0) {
                    $checkStmt->close();
                    echo '<center><a href="javascript:history.back()">'
                        . 'Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname!'
                        . '</a></center>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }
                $checkStmt->close();

                // Validate email
                if (!validate_email($email)) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Die angegebene E-Mail Adresse ist ung&uuml;ltig!</a></center>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Password validation
                if (
                    ($password1 !== '' && $password2 === '')
                    || ($password1 === '' && $password2 !== '')
                ) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Du musst das neue Passwort f&uuml;r ' . e($row['nick'])
                        . ' best&auml;tigen</a></center>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                if ($password1 !== $password2) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Das neue Passwort wurde falsch best&auml;tigt!</a></center>';
                    include __DIR__ . '/footer.inc.php';
                    exit;
                }

                // Update member using prepared statement
                $updateStmt = $conn->prepare('UPDATE pc_members SET
                    nick = ?, email = ?, work = ?, icq = ?, homepage = ?,
                    realname = ?, age = ?, hardware = ?, info = ?, pic = ?,
                    member_add = ?, member_edit = ?, member_del = ?,
                    news_add = ?, news_edit = ?, news_del = ?,
                    wars_add = ?, wars_edit = ?, wars_del = ?
                    WHERE id = ?');
                $updateStmt->bind_param(
                    'sssississssssssssssi',
                    $nick,
                    $email,
                    $work,
                    $icq,
                    $homepage,
                    $realname,
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

                echo '<center><a href="choosemember.php">'
                    . 'Der Member <b>' . e($row['nick']) . '</b> wurde erfolgreich editiert!'
                    . '</a></center>';

                // Password change
                if ($password1 !== '' && $password2 !== '' && $password1 === $password2) {
                    // Use secure password hashing (NOT base64!)
                    $newPasswordHash = password_hash($password1, PASSWORD_DEFAULT);

                    $pwStmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
                    $pwStmt->bind_param('si', $newPasswordHash, $rowId);
                    $pwStmt->execute();
                    $pwStmt->close();

                    // Send email notification
                    $mailSubject = 'PowerClan - Passwort geaendert';
                    $mailBody = "Hallo {$nick},\n\n" .
                        "{$pcadmin['nick']} hat Dein Passwort geaendert.\n\n" .
                        "Hier sind Deine neuen Logindaten fuer den {$settings['clanname']} Clan:\n\n" .
                        "Nickname: {$nick}\n" .
                        "E-Mail: {$email}\n" .
                        "Passwort: {$password1}\n\n" .
                        "Du kannst Deine Daten jederzeit aendern!\n\n" .
                        '-BITTE NICHT AUF DIESE AUTOMATISCH GENERIERTE EMAIL ANTWORTEN-';

                    $mailHeaders = "From: PowerClan Automailer <noreply@{$_SERVER['HTTP_HOST']}>";
                    @mail($email, $mailSubject, $mailBody, $mailHeaders);

                    echo '<center><br><br>Au&szlig;erdem wurde ' . e($row['nick'])
                        . ' eine E-Mail mit seinem neuen Passwort zugeschickt!</center>';
                }
            } else {
                // Display edit form
                $phpSelf = e($_SERVER['PHP_SELF']);
                ?>
                <center>
                <form action="<?= $phpSelf ?>?memberid=<?= $rowId ?>&editmember=YES" method="post">
                <?= csrf_field() ?>
                <table border="0" cellpadding="3" cellspacing="2" width="100%">
                <tr><td colspan="2" align="center">
                <b>Member editieren</b>
                </td></tr>
                <tr><td valign="top" width="*" bgcolor="<?= e($admin_tbl1) ?>">
                <b>Nickname</b><br>
                <small>Der Nickname unter dem der Member bekannt ist</small>
                </td><td valign="top" width="250" bgcolor="<?= e($admin_tbl1) ?>">
                <input name="nick" size="25" maxlength="100" value="<?= e($row['nick']) ?>" required>
                </td></tr>
                <tr><td valign="top">
                <b>E-Mail Adresse</b><br>
                <small>Die korrekte E-Mail Adresse des Members</small>
                </td><td valign="top">
                <input name="email" type="email" size="25" maxlength="200" value="<?= e($row['email']) ?>" required>
                </td></tr>
                <tr><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <b>Passwort</b><br>
                <small>Das neue Passwort f&uuml;r den Member (mit Best&auml;tigung)</small>
                </td><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <input name="password1" size="25" maxlength="100" type="password" autocomplete="new-password"><br>
                <input name="password2" size="25" maxlength="100" type="password" autocomplete="new-password">
                </td></tr>
                <tr><td valign="top">
                <b>Aufgabe</b><br>
                <small>Die Aufgabe die der Member im Clan &uuml;bernimmt</small>
                </td><td valign="top">
                <input name="work" size="25" maxlength="200" value="<?= e($row['work']) ?>">
                </td></tr>
                <tr><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <b>ICQ Nummer</b><br>
                <small>Die ICQ Nummer des Members, falls dieser ICQ n&uuml;tzt (0 = Keine Angabe)</small>
                </td><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <input name="icq" type="number" size="10" maxlength="10" value="<?= (int) $row['icq'] ?>" min="0">
                </td></tr>
                <tr><td valign="top">
                <b>Homepage</b><br>
                <small>Die URL zur Homepage des Members wenn dieser eine besitzt (mit http://)</small>
                </td><td valign="top">
                <input name="homepage" type="url" size="25" maxlength="250" value="<?= e($row['homepage']) ?>">
                </td></tr>
                <tr><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <b>Realname</b><br>
                <small>Der Realname des Members</small>
                </td><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <input name="realname" size="25" maxlength="200" value="<?= e($row['realname']) ?>">
                </td></tr>
                <tr><td valign="top">
                <b>Alter</b><br>
                <small>Das Alter des Members (0 = Keine Angabe)</small>
                </td><td valign="top">
                <input name="age" type="number" size="2" maxlength="3"
                    value="<?= (int) $row['age'] ?>" min="0" max="150">
                </td></tr>
                <tr><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <b>Hardware Informationen</b><br>
                <small>Informationen &uuml;ber die Hardware des Members (CPU, RAM, Grafikkarte, ...)</small>
                </td><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <textarea name="hardware" cols="35" rows="5"><?= e($row['hardware']) ?></textarea>
                </td></tr>
                <tr><td valign="top">
                <b>Pers&ouml;nliche Informationen</b><br>
                <small>Pers&ouml;nliche Informationen &uuml;ber den Member (Hobbies, Job, ...)</small>
                </td><td valign="top">
                <textarea name="info" cols="35" rows="5"><?= e($row['info']) ?></textarea>
                </td></tr>
                <tr><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <b>Bild</b><br>
                <small>URL zu einem Bild des Members (mit http://)</small>
                </td><td valign="top" bgcolor="<?= e($admin_tbl1) ?>">
                <input name="pic" type="url" size="25" maxlength="250" value="<?= e($row['pic']) ?>">
                </td></tr>
                <tr><td valign="top">
                <b>Adminrechte</b><br>
                <small>Die Adminrechte die der Member besitzt</small>
                </td><td valign="top">
                <?php
                $permissions = [
                    'member_add' => 'Member hinzuf&uuml;gen',
                    'member_edit' => 'Member editieren',
                    'member_del' => 'Member l&ouml;schen',
                    'news_add' => 'News hinzuf&uuml;gen',
                    'news_edit' => 'News editieren',
                    'news_del' => 'News l&ouml;schen',
                    'wars_add' => 'Wars hinzuf&uuml;gen',
                    'wars_edit' => 'Wars editieren',
                    'wars_del' => 'Wars l&ouml;schen',
                ];
                foreach ($permissions as $field => $label) {
                    $checked = ($row[$field] === 'YES') ? 'checked' : '';
                    echo "<input type=\"checkbox\" name=\"{$field}\" value=\"YES\" {$checked}> {$label}<br>\n";
                }
                ?>
                </td></tr>
                <tr><td colspan="2" align="center" bgcolor="<?= e($admin_tbl1) ?>">
                <input type="submit" value="Member editieren"> <input type="reset" value="Daten zur&uuml;cksetzen">
                </td></tr>
                </table>
                </form>
                </center>
                <?php
            }
        } else {
            $stmt->close();
            echo '<center><a href="choosemember.php">Der gew&auml;hlte Member existiert nicht!</a></center>';
        }
    } else {
        echo '<center><a href="choosemember.php">Bitte w&auml;hle einen Member aus!</a></center>';
    }
} else {
    echo '<center>Du hast keine Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
