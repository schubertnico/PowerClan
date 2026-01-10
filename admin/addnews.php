<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Add News
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

if (($pcadmin['news_add'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $addnews = $_GET['addnews'] ?? '';

    if ($addnews === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');

        if (empty($title) || empty($text)) {
            echo '<center><a href="javascript:history.back()">Bitte f&uuml;lle alle Felder aus!</a></center>';
        } else {
            $title = strip_tags($title);
            $now = time();
            $userId = (int)($pcadmin['id'] ?? 0);
            $nick = $pcadmin['nick'] ?? '';
            $email = $pcadmin['email'] ?? '';

            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO pc_news (userid, time, nick, email, title, text) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iissss', $userId, $now, $nick, $email, $title, $text);
            $stmt->execute();
            $stmt->close();

            echo '<center><a href="choosenews.php">Deine News wurden erfolgreich gepostet!</a></center>';
        }
    } else {
        $phpSelf = e($_SERVER['PHP_SELF']);
        $nickDisplay = e($pcadmin['nick'] ?? '');
        $emailDisplay = e($pcadmin['email'] ?? '');

        echo '<script>
function insertBBCode(tag) {
    var textarea = document.getElementById("text");
    var startPos = textarea.selectionStart;
    var endPos = textarea.selectionEnd;
    var selectedText = textarea.value.substring(startPos, endPos);

    var newText = "[" + tag + "]" + selectedText + "[/" + tag + "]";
    textarea.value = textarea.value.substring(0, startPos) + newText + textarea.value.substring(endPos);
}
</script>';

        echo "<center>
<form action=\"{$phpSelf}?addnews=YES\" method=\"post\">
    " . csrf_field() . "
    <table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
        <tr>
            <td colspan=\"2\" align=\"center\">
                <b>News hinzuf&uuml;gen</b>
            </td>
        </tr>
        <tr>
            <td bgcolor=\"{$admin_tbl1}\" valign=\"top\">
                <b>Nickname</b><br>
                <small>Wenn Du Deinen Nickname &auml;ndern m&ouml;chtest, editiere <a href=\"profile.php\">Dein Profil</a></small>
            </td>
            <td bgcolor=\"{$admin_tbl1}\" valign=\"top\" width=\"400\">
                {$nickDisplay}
            </td>
        </tr>
        <tr>
            <td valign=\"top\">
                <b>E-Mail Adresse</b><br>
                <small>Wenn Du Deine E-Mail Adresse &auml;ndern m&ouml;chtest, editiere <a href=\"profile.php\">Dein Profil</a></small>
            </td>
            <td valign=\"top\">
                {$emailDisplay}
            </td>
        </tr>
        <tr>
            <td bgcolor=\"{$admin_tbl1}\" valign=\"top\">
                <b>Titel</b><br>
                <small>Der Titel f&uuml;r Deinen Newseintrag</small>
            </td>
            <td bgcolor=\"{$admin_tbl1}\" valign=\"top\">
                <input name=\"title\" size=\"50\" maxlength=\"150\" required>
            </td>
        </tr>
        <tr>
            <td valign=\"top\">
                <b>Text</b><br>
                <small>Der Text f&uuml;r Deinen Newseintrag</small><br>
                <br>
                <small>
                    Folgende, rotgekennzeichneten, Befehle k&ouml;nnen verwendet werden:<br>
                    <b class=\"red\">[b]</b><b>fett</b><b class=\"red\">[/b]</b><br>
                    <b class=\"red\">[u]</b><u>unterstrichen</u><b class=\"red\">[/u]</b><br>
                    <b class=\"red\">[i]</b><i>kursiv</i><b class=\"red\">[/i]</b><br>
                    <b class=\"red\">[url]</b><a href=\"https://www.powerscripts.org/\" target=\"_new\">www.powerscripts.org</a><b class=\"red\">[/url]</b><br>
                    <b class=\"red\">[url=https://www.powerscripts.org/]</b><a href=\"https://www.powerscripts.org/\">PowerScripts</a><b class=\"red\">[/url]</b><br>
                    <b class=\"red\">[email]</b><a href=\"mailto:support@powerscripts.org\">support@powerscripts.org</a><b class=\"red\">[/email]</b><br>
                    <b class=\"red\">[email=support@powerscripts.org]</b><a href=\"mailto:support@powerscripts.org\">Support</a><b class=\"red\">[/email]</b><br>
                    <br>
                    Enter f&uuml;r Zeilenumbruch
                </small>
            </td>
            <td valign=\"top\">
                <button type=\"button\" onclick=\"insertBBCode('b')\">Fett</button>
                <button type=\"button\" onclick=\"insertBBCode('u')\">Unterstrichen</button>
                <button type=\"button\" onclick=\"insertBBCode('i')\">Kursiv</button>
                <button type=\"button\" onclick=\"insertBBCode('url')\">URL</button>
                <button type=\"button\" onclick=\"insertBBCode('email')\">E-Mail</button>
                <textarea id=\"text\" name=\"text\" cols=\"45\" rows=\"15\" style=\"margin-top: 5px;\" required></textarea>
            </td>
        </tr>
        <tr>
            <td colspan=\"2\" align=\"center\" bgcolor=\"{$admin_tbl1}\">
                <input type=\"submit\" value=\"News hinzuf&uuml;gen\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzen\">
            </td>
        </tr>
    </table>
</form>
</center>";
    }
} else {
    echo '<center>Du hast keine Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
