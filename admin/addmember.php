<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Add Member
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
// CSRF protection
csrf_check();

if (($pcadmin['member_add'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $addmember = $_GET['addmember'] ?? '';

    if ($addmember === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');

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

        if (empty($nickname) || empty($email)) {
            echo '<center><a href="javascript:history.back()">Bitte gib Nickname und E-Mail an!</a></center>';
            exit;
        }

        // Check for existing member using prepared statement
        $checkStmt = $conn->prepare("SELECT id FROM pc_members WHERE email = ? OR nick = ?");
        $checkStmt->bind_param('ss', $email, $nickname);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if (mysqli_num_rows($checkResult) !== 0) {
            echo '<center><a href="javascript:history.back()">Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname!</a></center>';
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();

        // Validate email
        if (!validate_email($email)) {
            echo '<center><a href="javascript:history.back()">Die angegebene E-Mail-Adresse ist ung&uuml;ltig!</a></center>';
            exit;
        }

        // Generate secure random password
        $generatedPassword = bin2hex(random_bytes(8)); // 16 character password
        $passwordHash = password_hash($generatedPassword, PASSWORD_DEFAULT);

        // Insert member using prepared statement
        $insertStmt = $conn->prepare("INSERT INTO pc_members (nick, email, password, work, member_add, member_edit, member_del, news_add, news_edit, news_del, wars_add, wars_edit, wars_del, superadmin, realname, homepage, hardware, info, pic) VALUES (?, ?, ?, 'Fighter', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'NO', '', '', '', '', '')");
        $insertStmt->bind_param('ssssssssssss', $nickname, $email, $passwordHash, $member_add, $member_edit, $member_del, $news_add, $news_edit, $news_del, $wars_add, $wars_edit, $wars_del);
        $insertStmt->execute();
        $insertStmt->close();

        // Send email notification
        $adminNick = $pcadmin['nick'] ?? 'Admin';
        $clanname = $settings['clanname'] ?? 'PowerClan';
        $siteUrl = $settings['url'] ?? '';

        $subject = 'PowerClan Autoemail';
        $message = "Hallo {$nickname},

Du wurdest gerade von {$adminNick} als Member in das PowerClan System des {$clanname} Clans aufgenommen.
Du kannst Dich mit den folgenden Daten unter {$siteUrl} einloggen:

Nickname: {$nickname}
E-Mail: {$email}
Passwort: {$generatedPassword}

Das Passwort und Deine anderen Daten kannst Du jederzeit aendern.

-BITTE NICHT AUF DIESE AUTOMATISCH GENERIERTE EMAIL ANTWORTEN-";

        $headers = 'From: PowerClan Automailer <powerclan@powerscripts.org>';

        // Suppress mail errors if mail server not configured
        @mail($email, $subject, $message, $headers);

        echo '<center><a href="index.php">Der Member wurde erfolgreich hinzugef&uuml;gt und per E-Mail benachrichtigt!</a></center>';
    } else {
        $phpSelf = e($_SERVER['PHP_SELF']);

        echo "
<form action=\"{$phpSelf}?addmember=YES\" method=\"post\">
" . csrf_field() . "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td colspan=\"2\" align=\"center\">
<b>Member hinzuf&uuml;gen</b>
</td></tr>
<tr><td width=\"*\" bgcolor=\"{$admin_tbl1}\" valign=\"top\">
<b>Nickname</b><br>
<small>Der Nickname unter dem der Member bekannt ist</small>
</td><td width=\"400\" bgcolor=\"{$admin_tbl1}\" valign=\"top\">
<input name=\"nickname\" size=\"25\" maxlength=\"100\" required>
</td></tr>
<tr><td valign=\"top\">
<b>E-Mail</b><br>
<small>Die korrekte E-Mail Adresse des Members</small>
</td><td valign=\"top\">
<input name=\"email\" size=\"25\" maxlength=\"250\" type=\"email\" required>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Adminrechte</b><br>
<small>Die Adminrechte die der Member besitzt</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input type=\"checkbox\" name=\"member_add\" value=\"YES\"> Member hinzuf&uuml;gen<br>
<input type=\"checkbox\" name=\"member_edit\" value=\"YES\"> Member editieren<br>
<input type=\"checkbox\" name=\"member_del\" value=\"YES\"> Member l&ouml;schen<br>
<input type=\"checkbox\" name=\"news_add\" value=\"YES\"> News hinzuf&uuml;gen<br>
<input type=\"checkbox\" name=\"news_edit\" value=\"YES\"> News editieren<br>
<input type=\"checkbox\" name=\"news_del\" value=\"YES\"> News l&ouml;schen<br>
<input type=\"checkbox\" name=\"wars_add\" value=\"YES\"> Wars hinzuf&uuml;gen<br>
<input type=\"checkbox\" name=\"wars_edit\" value=\"YES\"> Wars editieren<br>
<input type=\"checkbox\" name=\"wars_del\" value=\"YES\"> Wars l&ouml;schen<br>
</td></tr>
<tr><td colspan=\"2\" align=\"center\">
<input type=\"submit\" value=\"Member hinzuf&uuml;gen\">
</td></tr>
</table>
</form>";
    }
} else {
    echo '<center>Du hast keine Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
