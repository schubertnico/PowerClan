<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit Member
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
if (($pcadmin['member_edit'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $memberid = $_GET['memberid'] ?? '';

    if (!empty($memberid)) {
        $stmt = $conn->prepare('SELECT * FROM pc_members WHERE id = ?');
        $memberidInt = (int) $memberid;
        $stmt->bind_param('i', $memberidInt);
        $stmt->execute();
        $result = $stmt->get_result();
        $num = mysqli_num_rows($result);

        if ($num === 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $stmt->close();
            $rowId = (int) $row['id'];

            $editmember = $_GET['editmember'] ?? '';

            if ($editmember === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get POST data
                $nick = trim($_POST['nick'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password1 = $_POST['password1'] ?? '';
                $password2 = $_POST['password2'] ?? '';
                $work = trim($_POST['work'] ?? '');
                $icq = trim($_POST['icq'] ?? '');
                $homepage = trim($_POST['homepage'] ?? '');
                $realname = trim($_POST['realname'] ?? '');
                $age = trim($_POST['age'] ?? '');
                $hardware = strip_tags(trim($_POST['hardware'] ?? ''));
                $info = strip_tags(trim($_POST['info'] ?? ''));
                $pic = trim($_POST['pic'] ?? '');

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
                    echo '<center><a href="javascript:history.back()">Bitte gib Nickname und E-Mail an!</a></center>';
                    exit;
                }

                // Check for duplicate
                $checkStmt = $conn->prepare('SELECT id FROM pc_members WHERE (email = ? OR nick = ?) AND id != ?');
                $checkStmt->bind_param('ssi', $email, $nick, $rowId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if (mysqli_num_rows($checkResult) !== 0) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname!'
                        . '</a></center>';
                    $checkStmt->close();
                    exit;
                }
                $checkStmt->close();

                // Validate email
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
                    $nickEsc = e($row['nick'] ?? '');
                    echo '<center><a href="javascript:history.back()">'
                        . "Du musst das neue Passwort f&uuml;r {$nickEsc} best&auml;tigen"
                        . '</a></center>';
                    exit;
                }
                if ($password1 !== $password2) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Das neue Passwort wurde falsch best&auml;tigt!</a></center>';
                    exit;
                }

                // Update member using prepared statement
                $sql = 'UPDATE pc_members SET nick = ?, email = ?, work = ?, realname = ?, '
                    . 'icq = ?, homepage = ?, age = ?, hardware = ?, info = ?, pic = ?, '
                    . 'member_add = ?, member_edit = ?, member_del = ?, news_add = ?, '
                    . 'news_edit = ?, news_del = ?, wars_add = ?, wars_edit = ?, wars_del = ? '
                    . 'WHERE id = ?';
                $updateStmt = $conn->prepare($sql);
                $updateStmt->bind_param(
                    'sssssssssssssssssssi',
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

                $nickEsc = e($row['nick'] ?? '');
                echo '<center><a href="choosemember.php">'
                    . "Der Member <b>{$nickEsc}</b> wurde erfolgreich editiert!</a></center>";

                // Update password if changed
                if ($password1 !== '' && $password2 !== '' && $password1 === $password2) {
                    $newPassword = password_hash(trim($password1), PASSWORD_DEFAULT);
                    $pwStmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
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
                    @mail($memberEmail, $subject, $message, $headers);

                    echo "<center><br><br>Au&szlig;erdem wurde {$nickEsc} "
                        . 'eine E-Mail mit seinem neuen Passwort zugeschickt!</center>';
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

                echo "<center>
<form action=\"{$phpSelf}?memberid={$rowId}&editmember=YES\" method=\"post\">
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td colspan=\"2\" align=\"center\">
<b>Member editieren</b>
</td></tr>
<tr><td valign=\"top\" width=\"*\" bgcolor=\"{$admin_tbl1}\">
<b>Nickname</b><br>
<small>Der Nickname unter dem der Member bekannt ist</small>
</td><td valign=\"top\" width=\"250\" bgcolor=\"{$admin_tbl1}\">
<input name=\"nick\" size=\"25\" maxlength=\"100\" value=\"{$nickEsc}\" required>
</td></tr>
<tr><td valign=\"top\">
<b>E-Mail Adresse</b><br>
<small>Die korrekte E-Mail Adresse des Members</small>
</td><td valign=\"top\">
<input name=\"email\" size=\"25\" maxlength=\"400\" value=\"{$emailEsc}\" type=\"email\" required>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Passwort</b><br>
<small>Das neue Passwort f&uuml;r den Member (mit Best&auml;tigung)</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"password1\" size=\"25\" maxlength=\"25\" type=\"password\"><br>
<input name=\"password2\" size=\"25\" maxlength=\"25\" type=\"password\">
</td></tr>
<tr><td valign=\"top\">
<b>Aufgabe</b><br>
<small>Die Aufgabe die der Member im Clan &uuml;bernimmt</small>
</td><td valign=\"top\">
<input name=\"work\" size=\"25\" maxlength=\"200\" value=\"{$workEsc}\">
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>ICQ Nummer</b><br>
<small>Die ICQ Nummer des Members (0 = Keine Angabe)</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"icq\" size=\"10\" maxlength=\"10\" value=\"{$icqEsc}\">
</td></tr>
<tr><td valign=\"top\">
<b>Homepage</b><br>
<small>Die URL zur Homepage des Members (mit http://)</small>
</td><td valign=\"top\">
<input name=\"homepage\" size=\"25\" maxlength=\"250\" value=\"{$homepageEsc}\" type=\"url\">
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Realname</b><br>
<small>Der Realname des Members</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"realname\" size=\"25\" maxlength=\"200\" value=\"{$realnameEsc}\">
</td></tr>
<tr><td valign=\"top\">
<b>Alter</b><br>
<small>Das Alter des Members (0 = Keine Angabe)</small>
</td><td valign=\"top\">
<input name=\"age\" size=\"2\" maxlength=\"2\" value=\"{$ageEsc}\" type=\"number\" min=\"0\" max=\"99\">
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Hardware Informationen</b><br>
<small>Informationen &uuml;ber die Hardware des Members</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<textarea name=\"hardware\" cols=\"35\" rows=\"5\">{$hardwareEsc}</textarea>
</td></tr>
<tr><td valign=\"top\">
<b>Pers&ouml;nliche Informationen</b><br>
<small>Pers&ouml;nliche Informationen &uuml;ber den Member</small>
</td><td valign=\"top\">
<textarea name=\"info\" cols=\"35\" rows=\"5\">{$infoEsc}</textarea>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Bild</b><br>
<small>URL zu einem Bild des Members (mit http://)</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"pic\" size=\"25\" maxlength=\"250\" value=\"{$picEsc}\" type=\"url\">
</td></tr>
<tr><td valign=\"top\">
<b>Adminrechte</b><br>
<small>Die Adminrechte die der Member besitzt</small>
</td><td valign=\"top\">";

                // Permission checkboxes
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

                foreach ($permissions as $key => $label) {
                    $checked = (($row[$key] ?? '') === 'YES') ? ' checked' : '';
                    echo "<input type=\"checkbox\" name=\"{$key}\" value=\"YES\"{$checked}> {$label}<br>\n";
                }

                echo "
</td></tr>
<tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$admin_tbl1}\">
<input type=\"submit\" value=\"Member editieren\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzten\">
</td></tr>
</table>
</form>
</center>";
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
