<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit News
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

$newsid = $_GET['newsid'] ?? '';
$row = null;

// Get news data if newsid provided
if (!empty($newsid)) {
    $stmt = $conn->prepare('SELECT * FROM pc_news WHERE id = ?');
    $newsidInt = (int) $newsid;
    $stmt->bind_param('i', $newsidInt);
    $stmt->execute();
    $result = $stmt->get_result();
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Check permissions
$hasAccess = ($pcadmin['news_edit'] ?? '') === 'YES'
    || ($row !== null && (int) ($row['userid'] ?? 0) === (int) ($pcadmin['id'] ?? 0))
    || ($pcadmin['superadmin'] ?? '') === 'YES';

if ($hasAccess) {
    if ($row !== null) {
        $editnews = $_GET['editnews'] ?? '';

        if ($editnews === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $text = trim($_POST['text'] ?? '');

            if (empty($title) || empty($text)) {
                echo '<center><a href="javascript:history.back()">Bitte f&uuml;lle alle Felder aus!</a></center>';
            } else {
                $title = strip_tags($title);
                $newsIdForUpdate = (int) $row['id'];

                // Use prepared statement
                $updateStmt = $conn->prepare('UPDATE pc_news SET title = ?, text = ? WHERE id = ?');
                $updateStmt->bind_param('ssi', $title, $text, $newsIdForUpdate);
                $updateStmt->execute();
                $updateStmt->close();

                echo '<center><a href="choosenews.php">Die News wurden erfolgreich editiert!</a></center>';
            }
        } else {
            $phpSelf = e($_SERVER['PHP_SELF']);
            $newsIdEsc = (int) $row['id'];
            $nickEsc = e($row['nick'] ?? '');
            $emailEsc = e($row['email'] ?? '');
            $titleEsc = e($row['title'] ?? '');
            $textEsc = e($row['text'] ?? '');

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
<form action=\"{$phpSelf}?editnews=YES&newsid={$newsIdEsc}\" method=\"post\">
" . csrf_field() . "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td colspan=\"2\" align=\"center\">
<b>News editieren</b>
</td></tr>
<tr><td bgcolor=\"{$admin_tbl1}\" valign=\"top\">
<b>Nickname</b><br>
<small>Der Nickname des Autors</small>
</td><td bgcolor=\"{$admin_tbl1}\" valign=\"top\" width=\"400\">
{$nickEsc}
</td></tr>
<tr><td valign=\"top\">
<b>E-Mail Adresse</b><br>
<small>Die E-Mail Adresse des Autors</small>
</td><td valign=\"top\">
{$emailEsc}
</td></tr>
<tr><td bgcolor=\"{$admin_tbl1}\" valign=\"top\">
<b>Titel</b><br>
<small>Der Titel des Newseintrags</small>
</td><td bgcolor=\"{$admin_tbl1}\" valign=\"top\">
<input name=\"title\" size=\"50\" maxlength=\"150\" value=\"{$titleEsc}\" required>
</td></tr>
<tr><td valign=\"top\">
<b>Text</b><br>
<small>Der Text des Newseintrags</small><br>
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
</td><td valign=\"top\">
<button type=\"button\" onclick=\"insertBBCode('b')\">Fett</button>
<button type=\"button\" onclick=\"insertBBCode('u')\">Unterstrichen</button>
<button type=\"button\" onclick=\"insertBBCode('i')\">Kursiv</button>
<button type=\"button\" onclick=\"insertBBCode('url')\">URL</button>
<button type=\"button\" onclick=\"insertBBCode('email')\">E-Mail</button>
<textarea id=\"text\" name=\"text\" cols=\"45\" rows=\"15\" style=\"margin-top: 5px;\" required>{$textEsc}</textarea>
</td></tr>
<tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$admin_tbl1}\">
<input type=\"submit\" value=\"News editieren\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzen\">
</td></tr>
</table>
</form>
</center>";
        }
    } elseif (!empty($newsid)) {
        echo '<center><a href="choosenews.php">Der gew&auml;hlte Newseintrag existiert nicht!</a></center>';
    } else {
        echo '<center><a href="choosenews.php">Bitte w&auml;hle einen Newseintrag aus!</a></center>';
    }
} else {
    echo '<center>Du hast keine Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
