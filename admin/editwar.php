<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit War
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
/** @var array<int, string> $leagues */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<center>
<?php
// CSRF protection
csrf_check();

if (($pcadmin['wars_edit'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $warid = $_GET['warid'] ?? '';
    $uploadscreen = $_GET['uploadscreen'] ?? '';

    if (!empty($warid)) {
        // Get war data using prepared statement
        $stmt = db_prepare($conn, 'SELECT * FROM pc_wars WHERE id = ?');
        $waridInt = (int) $warid;
        $stmt->bind_param('i', $waridInt);
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
                throw new RuntimeException('Failed to fetch war data');
            }
            $rowId = (int) $row['id'];

            $editwar = $_GET['editwar'] ?? '';

            if ($editwar === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get POST data
                $enemy = trim($_POST['enemy'] ?? '');
                $enemy_tag = trim($_POST['enemy_tag'] ?? '');
                $homepage = trim($_POST['homepage'] ?? '');
                $league = trim($_POST['league'] ?? '');
                $map1 = trim($_POST['map1'] ?? '');
                $map2 = trim($_POST['map2'] ?? '');
                $map3 = trim($_POST['map3'] ?? '');
                $time_day = (int) ($_POST['time_day'] ?? 1);
                $time_hour = (int) ($_POST['time_hour'] ?? 20);
                $time_minute = (int) ($_POST['time_minute'] ?? 0);
                $time_month = (int) ($_POST['time_month'] ?? 1);
                $time_year = (int) ($_POST['time_year'] ?? date('Y'));
                $report = trim($_POST['report'] ?? '');
                $res1 = trim($_POST['res1'] ?? '');
                $res2 = trim($_POST['res2'] ?? '');
                $res3 = trim($_POST['res3'] ?? '');

                if (
                    empty($enemy) || empty($enemy_tag) || empty($homepage)
                    || empty($league) || empty($map1) || empty($map2) || $time_day < 1
                ) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Bitte f&uuml;lle alle nicht optionalen Felder aus!</a></center>';
                } elseif (
                    !checkdate($time_month, $time_day, $time_year)
                    || $time_hour < 0 || $time_hour > 23
                    || $time_minute < 0 || $time_minute > 59
                ) {
                    echo '<center><a href="javascript:history.back()">'
                        . 'Ung&uuml;ltiges Datum oder Uhrzeit!</a></center>';
                } else {
                    $playtime = mktime($time_hour, $time_minute, 0, $time_month, $time_day, $time_year);

                    $sql = 'UPDATE pc_wars SET enemy = ?, enemy_tag = ?, homepage = ?, '
                        . 'league = ?, map1 = ?, map2 = ?, map3 = ?, time = ?, report = ?, '
                        . 'res1 = ?, res2 = ?, res3 = ? WHERE id = ?';
                    $updateStmt = db_prepare($conn, $sql);
                    $updateStmt->bind_param(
                        'ssssssssssssi',
                        $enemy,
                        $enemy_tag,
                        $homepage,
                        $league,
                        $map1,
                        $map2,
                        $map3,
                        $playtime,
                        $report,
                        $res1,
                        $res2,
                        $res3,
                        $rowId
                    );
                    $updateStmt->execute();
                    $updateStmt->close();

                    echo '<center><a href="choosewar.php">Der War wurde erfolgreich editiert</a></center>';
                }
            } elseif ($uploadscreen === 'YES' && isset($_GET['map'])) {
                $map = (int) $_GET['map'];

                if ($map >= 1 && $map <= 3) {
                    $targetDirectory = __DIR__ . '/../images/wars/';
                    $targetFileName = $rowId . '_map' . $map . '.jpg';

                    if (isset($_FILES['screen' . $map]['tmp_name'])) {
                        $screen = $_FILES['screen' . $map]['tmp_name'];

                        if (is_uploaded_file($screen)) {
                            if (is_writable($targetDirectory)) {
                                if (move_uploaded_file($screen, $targetDirectory . $targetFileName)) {
                                    $screenColumn = 'screen' . $map;
                                    $updateStmt = db_prepare($conn, "UPDATE pc_wars SET {$screenColumn} = ? WHERE id = ?");
                                    $updateStmt->bind_param('si', $targetFileName, $rowId);
                                    $updateStmt->execute();
                                    $updateStmt->close();
                                    echo '<center><a href="choosewar.php">'
                                        . "Der Screenshot f&uuml;r Map {$map} wurde erfolgreich hochgeladen"
                                        . '</a></center>';
                                } else {
                                    echo '<center><a href="javascript:history.back()">'
                                        . "Fehler beim Verschieben des Screenshots f&uuml;r Map {$map}"
                                        . '</a></center>';
                                }
                            } else {
                                echo '<center><a href="choosewar.php">'
                                    . 'Fehler: Das Zielverzeichnis ist nicht beschreibbar.</a></center>';
                            }
                        } else {
                            echo '<center><a href="choosewar.php">'
                                . "Bitte einen Screenshot f&uuml;r Map {$map} ausw&auml;hlen."
                                . '</a></center>';
                        }
                    } else {
                        echo '<center><a href="choosewar.php">'
                            . "Fehler: Keine Screenshot-Datei f&uuml;r Map {$map} gefunden."
                            . '</a></center>';
                    }
                } else {
                    echo '<center><a href="javascript:history.back()">Ung&uuml;ltige Kartennummer.</a></center>';
                }
            } else {
                // Display edit form
                $phpSelf = e($_SERVER['PHP_SELF']);

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

                $enemyEsc = e($row['enemy'] ?? '');
                $enemyTagEsc = e($row['enemy_tag'] ?? '');
                $homepageEsc = e($row['homepage'] ?? '');
                $leagueEsc = e($row['league'] ?? '');
                $map1Esc = e($row['map1'] ?? '');
                $map2Esc = e($row['map2'] ?? '');
                $map3Esc = e($row['map3'] ?? '');
                $res1Esc = e($row['res1'] ?? '');
                $res2Esc = e($row['res2'] ?? '');
                $res3Esc = e($row['res3'] ?? '');
                $reportEsc = e($row['report'] ?? '');
                $warTime = (int) $row['time'];

                $month = (int) date('n', $warTime);
                $day = date('d', $warTime);
                $year = (int) date('Y', $warTime);
                $hour = (int) date('G', $warTime);
                $minute = date('i', $warTime);

                echo "<center>
<form action=\"{$phpSelf}?editwar=YES&warid={$rowId}\" method=\"post\">
" . csrf_field() . "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td colspan=\"2\" align=\"center\">
<b>War editieren</b>
</td></tr>
<tr><td bgcolor=\"{$admin_tbl1}\" width=\"*\" align=\"top\">
<b>Gegner</b><br>
<small>Der Name des Clans gegen den gespielt wird</small>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"400\" align=\"top\">
<input name=\"enemy\" size=\"25\" maxlength=\"150\" value=\"{$enemyEsc}\" required>
</td></tr>
<tr><td align=\"top\">
<b>Gegner (Clantag)</b><br>
<small>Das Clank&uuml;rzel des Clans gegen den gespielt wird</small>
</td><td align=\"top\">
<input name=\"enemy_tag\" size=\"10\" maxlength=\"10\" value=\"{$enemyTagEsc}\" required>
</td></tr>
<tr><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Gegner (Homepage)</b><br>
<small>Die Homepage des Clans gegen den gespielt wird</small>
</td><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"homepage\" size=\"25\" maxlength=\"250\" value=\"{$homepageEsc}\" type=\"url\" required>
</td></tr>
<tr><td align=\"top\">
<b>Liga</b><br>
<small>Die Liga in der das Spiel gespielt wird</small>
</td><td align=\"top\">
<select name=\"league\" size=\"1\" required>";

                foreach ($leagues as $leagueOption) {
                    $leagueOptEsc = e($leagueOption);
                    $selected = ($leagueOption === ($row['league'] ?? '')) ? ' selected' : '';
                    echo "<option value=\"{$leagueOptEsc}\"{$selected}>{$leagueOptEsc}</option>";
                }

                echo "
</select>
</td></tr>
<tr><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Map (1)</b><br>
<small>Die erste Map die gespielt wird</small>
</td><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"map1\" size=\"25\" maxlength=\"100\" value=\"{$map1Esc}\" required>
</td></tr>
<tr><td align=\"top\">
<b>Map (2)</b><br>
<small>Die zweite Map die gespielt wird</small>
</td><td align=\"top\">
<input name=\"map2\" size=\"25\" maxlength=\"100\" value=\"{$map2Esc}\" required>
</td></tr>
<tr><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Map (3)</b><br>
<small>Die dritte Map die gespielt wird (optional)</small>
</td><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"map3\" size=\"25\" maxlength=\"100\" value=\"{$map3Esc}\">
</td></tr>
<tr><td align=\"top\">
<b>Termin</b><br>
<small>Der Termin an dem gespielt wird</small>
</td><td align=\"top\">
<select name=\"time_month\" size=\"1\">";

                $months = [
                    'Januar', 'Februar', 'M&auml;rz', 'April', 'Mai', 'Juni',
                    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
                ];
                for ($i = 1; $i <= 12; $i++) {
                    $selected = ($i === $month) ? ' selected' : '';
                    echo "<option value=\"{$i}\"{$selected}>{$months[$i - 1]}</option>";
                }

                echo "
</select>
<input name=\"time_day\" size=\"2\" maxlength=\"2\" value=\"{$day}\" type=\"number\" min=\"1\" max=\"31\" required>
<select name=\"time_year\" size=\"1\">";

                $curyear = (int) date('Y');
                for ($i = $curyear - 5; $i <= $curyear + 5; $i++) {
                    $selected = ($i === $year) ? ' selected' : '';
                    echo "<option value=\"{$i}\"{$selected}>{$i}</option>";
                }

                echo '
</select>
<select name="time_hour" size="1">';

                for ($i = 0; $i <= 23; $i++) {
                    $selected = ($i === $hour) ? ' selected' : '';
                    $hourStr = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    echo "<option value=\"{$i}\"{$selected}>{$hourStr}</option>";
                }

                echo '
</select>
<select name="time_minute" size="1">
<option value="0"' . ((int) $minute === 0 ? ' selected' : '') . '>00</option>
<option value="15"' . ((int) $minute === 15 ? ' selected' : '') . '>15</option>
<option value="30"' . ((int) $minute === 30 ? ' selected' : '') . '>30</option>
<option value="45"' . ((int) $minute === 45 ? ' selected' : '') . ">45</option>
</select>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Ergebnis (Map1)</b><br>
<small>Das Ergenis von Map1 (EIGENES:GEGNER)</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"res1\" size=\"10\" maxlength=\"10\" value=\"{$res1Esc}\">
</td></tr>
<tr><td valign=\"top\">
<b>Ergebnis (Map2)</b><br>
<small>Das Ergenis von Map2 (EIGENES:GEGNER)</small>
</td><td valign=\"top\">
<input name=\"res2\" size=\"10\" maxlength=\"10\" value=\"{$res2Esc}\">
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Ergebnis (Map3)</b><br>
<small>Das Ergenis von Map3 (EIGENES:GEGNER)</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"res3\" size=\"10\" maxlength=\"10\" value=\"{$res3Esc}\">
</td></tr>
<tr><td valign=\"top\">
<b>Warbericht</b><br>
<small>Ein Bericht &uuml;ber den Clanwar (optional)</small><br>
<br>
<small>
Folgende Befehle k&ouml;nnen verwendet werden:<br>
<b class=\"red\">[b]</b><b>fett</b><b class=\"red\">[/b]</b><br>
<b class=\"red\">[u]</b><u>unterstrichen</u><b class=\"red\">[/u]</b><br>
<b class=\"red\">[i]</b><i>kursiv</i><b class=\"red\">[/i]</b><br>
<b class=\"red\">[url]</b>Link<b class=\"red\">[/url]</b><br>
<b class=\"red\">[email]</b>E-Mail<b class=\"red\">[/email]</b>
</small>
</td><td valign=\"top\">
<button type=\"button\" onclick=\"insertBBCode('b')\">Fett</button>
<button type=\"button\" onclick=\"insertBBCode('u')\">Unterstrichen</button>
<button type=\"button\" onclick=\"insertBBCode('i')\">Kursiv</button>
<button type=\"button\" onclick=\"insertBBCode('url')\">URL</button>
<button type=\"button\" onclick=\"insertBBCode('email')\">E-Mail</button>
<textarea id=\"text\" name=\"report\" cols=\"45\" rows=\"15\" style=\"margin-top: 5px;\">{$reportEsc}</textarea>
</td></tr>
<tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$admin_tbl1}\">
<input type=\"submit\" value=\"War editieren\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzen\">
</td></tr>
</table>
</form>
<br><br>";

                // Screenshot upload forms
                for ($mapNum = 1; $mapNum <= 3; $mapNum++) {
                    $screenField = 'screen' . $mapNum;
                    $screenValue = $row[$screenField] ?? '';
                    $bgColor = ($mapNum === 2) ? $admin_tbl1 : '';

                    echo "
<form action=\"{$phpSelf}?uploadscreen=YES&warid={$rowId}&map={$mapNum}\" "
    . 'method="post" enctype="multipart/form-data">
' . csrf_field() . "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td valign=\"top\" width=\"*\" bgcolor=\"{$bgColor}\">
<b>Screenshot (Map{$mapNum})</b><br>
<small>Screenshot vom Ergebnis (optional/nur JPG)</small>
</td><td valign=\"top\" width=\"400\" bgcolor=\"{$bgColor}\">
<input name=\"screen{$mapNum}\" size=\"25\" type=\"file\" accept=\"image/jpeg\">";

                    if (!empty($screenValue)) {
                        $screenEsc = e($screenValue);
                        echo " <small><a href=\"../showpic.php?path=images/wars/{$screenEsc}\""
                            . ' target="_screen">Aktuell</a></small>';
                    }

                    echo "
</td></tr>
<tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$bgColor}\">
<input type=\"submit\" value=\"Upload Screenshot\">
</td></tr>
</table>
</form>";
                }

                echo '</center>';
            }
        } else {
            $stmt->close();
            echo '<center><a href="choosewar.php">Der gew&auml;hlte Wareintrag existiert nicht!</a></center>';
        }
    } else {
        echo '<center><a href="choosewar.php">Bitte w&auml;hle einen Wareintrag aus!</a></center>';
    }
} else {
    echo '<center>Du hast keinen Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
