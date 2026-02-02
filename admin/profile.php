<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Profile Editor
 *
 * @copyright 2001-2025 PowerScripts
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

<center>
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
            echo '<center><a href="javascript:history.back()">Bitte gib Nickname und E-Mail an!</a></center>';
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
            echo '<center><a href="javascript:history.back()">'
                . 'Es gibt schon einen Member mit dieser E-Mail Adresse oder diesem Nickname!'
                . '</a></center>';
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();

        // Validate email using filter_var (replaces deprecated regex)
        if (!validate_email($email)) {
            echo '<center><a href="javascript:history.back()">'
                . 'Die angegebene E-Mail Adresse ist ung&uuml;ltig!</a></center>';
            exit;
        }

        // Password validation
        if (
            ($password1 !== '' && $password2 === '')
            || ($password1 === '' && $password2 !== '')
        ) {
            echo '<center><a href="javascript:history.back()">'
                . 'Du musst Dein neues Passwort best&auml;tigen</a></center>';
            exit;
        }
        if ($password1 !== $password2) {
            echo '<center><a href="javascript:history.back()">'
                . 'Das neue Passwort wurde falsch best&auml;tigt!</a></center>';
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
        $updateStmt = db_prepare($conn,$sql);
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

        echo '<center><a href="profile.php">Dein Profil wurde erfolgreich editiert</a></center>';

        // Update password if changed
        if ($password1 !== '' && $password2 !== '' && $password1 === $password2) {
            $newPassword = password_hash(trim($password1), PASSWORD_DEFAULT);
            $pwStmt = db_prepare($conn,'UPDATE pc_members SET password = ? WHERE id = ?');
            $pwStmt->bind_param('si', $newPassword, $memberId);
            $pwStmt->execute();
            $pwStmt->close();
            echo '<br><br><center>Da Du Dein Passwort ge&auml;ndert hast musst Du Dich nun neu einloggen!</center>';
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

        echo "
        <center>
        <form action=\"{$phpSelf}?editprofile=YES\" method=\"post\">
        " . csrf_field() . "
        <table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
        <tr><td colspan=\"2\" align=\"center\">
        <b>Profil editieren</b>
        </td></tr>
        <tr><td valign=\"top\" width=\"*\" bgcolor=\"{$admin_tbl1}\">
        <b>Nickname</b><br>
        <small>Der Nickname unter dem Du bekannt ist</small>
        </td><td valign=\"top\" width=\"250\" bgcolor=\"{$admin_tbl1}\">
        <input name=\"nick\" size=\"25\" maxlength=\"100\" value=\"{$nickValue}\">
        </td></tr>
        <tr><td valign=\"top\">
        <b>E-Mail Adresse</b><br>
        <small>Deine korrekte E-Mail Adresse</small>
        </td><td valign=\"top\">
        <input name=\"email\" size=\"25\" maxlength=\"400\" value=\"{$emailValue}\" type=\"email\">
        </td></tr>
        <tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <b>Passwort</b><br>
        <small>Dein neues Passwort(mit Best&auml;tigung)</small>
        </td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <input name=\"password1\" size=\"25\" maxlength=\"25\" type=\"password\"><br>
        <input name=\"password2\" size=\"25\" maxlength=\"25\" type=\"password\">
        </td></tr>
        <tr><td valign=\"top\">
        <b>ICQ Nummer</b><br>
        <small>Deine ICQ Nummer (0 = Keine Angabe)</small>
        </td><td valign=\"top\">
        <input name=\"icq\" size=\"10\" maxlength=\"10\" value=\"{$icqValue}\">
        </td></tr>
        <tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <b>Homepage</b><br>
        <small>Die URL zu Deiner Homepage (mit http://)</small>
        </td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <input name=\"homepage\" size=\"25\" maxlength=\"250\" value=\"{$homepageValue}\" type=\"url\">
        </td></tr>
        <tr><td valign=\"top\">
        <b>Realname</b><br>
        <small>Dein realer Name</small>
        </td><td valign=\"top\">
        <input name=\"realname\" size=\"25\" maxlength=\"200\" value=\"{$realnameValue}\">
        </td></tr>
        <tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <b>Alter</b><br>
        <small>Dein Alter (0 = Keine Angabe)</small>
        </td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <input name=\"age\" size=\"2\" maxlength=\"2\" value=\"{$ageValue}\" type=\"number\" min=\"0\" max=\"99\">
        </td></tr>
        <tr><td valign=\"top\">
        <b>Hardware Informationen</b><br>
        <small>Informationen &uuml;ber Deine Hardware (CPU, RAM, Grafikkarte, ...)</small>
        </td><td valign=\"top\">
        <textarea name=\"hardware\" cols=\"35\" rows=\"5\">{$hardwareValue}</textarea>
        </td></tr>
        <tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <b>Pers&ouml;nliche Informationen</b><br>
        <small>Pers&ouml;nliche Informationen &uuml;ber Dich (Hobbies, Job, ...)</small>
        </td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
        <textarea name=\"info\" cols=\"35\" rows=\"5\">{$infoValue}</textarea>
        </td></tr>
        <tr><td valign=\"top\">
        <b>Bild</b><br>
        <small>URL zu einem Bild von Dir (mit http://)</small>
        </td><td valign=\"top\">
        <input name=\"pic\" size=\"25\" maxlength=\"250\" value=\"{$picValue}\" type=\"url\">
        </td></tr>
        <tr><td colspan=\"2\" align=\"center\">
        <input type=\"submit\" value=\"Profil editieren\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzten\">
        </td></tr>
        </table>
        </form>
        </center>
      ";
    }
} else {
    $stmt->close();
    echo '<center><a href="index.php">Der Member existiert nicht, oder Du bist nicht eingeloggt!</a></center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
