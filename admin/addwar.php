<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Add War
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
/** @var array<int, string> $leagues */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<center>
<?php
// CSRF protection
csrf_check();

if (($pcadmin['wars_add'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $addwar = $_GET['addwar'] ?? '';

    if ($addwar === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

        if (
            empty($enemy) || empty($enemy_tag) || empty($homepage)
            || empty($league) || empty($map1) || empty($map2) || $time_day < 1
        ) {
            echo '<center><a href="javascript:history.back()">'
                . 'Bitte f&uuml;lle alle nicht optionalen Felder aus!</a></center>';
        } else {
            $playtime = mktime($time_hour, $time_minute, 0, $time_month, $time_day, $time_year);

            // Use prepared statement to prevent SQL injection
            $sql = 'INSERT INTO pc_wars (enemy, enemy_tag, homepage, league, map1, map2, map3, '
                . 'time, report, res1, res2, res3, screen1, screen2, screen3) '
                . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '', '', '', '')";
            $stmt = db_prepare($conn,$sql);
            $stmt->bind_param(
                'sssssssi',
                $enemy,
                $enemy_tag,
                $homepage,
                $league,
                $map1,
                $map2,
                $map3,
                $playtime
            );
            $stmt->execute();
            $stmt->close();

            echo '<center><a href="choosewar.php">Der War wurde erfolgreich hinzugef&uuml;gt</a></center>';
        }
    } else {
        $phpSelf = e($_SERVER['PHP_SELF']);

        echo "
<center>
<form action=\"{$phpSelf}?addwar=YES\" method=\"post\">
" . csrf_field() . "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td colspan=\"2\" align=\"center\">
<b>War hinzuf&uuml;gen</b>
</td></tr>
<tr><td bgcolor=\"{$admin_tbl1}\" width=\"*\" align=\"top\">
<b>Gegner</b><br>
<small>Der Name des Clans gegen den gespielt wird</small>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"400\" align=\"top\">
<input name=\"enemy\" size=\"25\" maxlength=\"150\" required>
</td></tr>
<tr><td align=\"top\">
<b>Gegner (Clantag)</b><br>
<small>Das Clank&uuml;rzel des Clans gegen den gespielt wird</small>
</td><td align=\"top\">
<input name=\"enemy_tag\" size=\"10\" maxlength=\"10\" required>
</td></tr>
<tr><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Gegner (Homepage)</b><br>
<small>Die Homepage des Clans gegen den gespielt wird</small>
</td><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"homepage\" size=\"25\" maxlength=\"250\" type=\"url\" required>
</td></tr>
<tr><td align=\"top\">
<b>Liga</b><br>
<small>Die Liga in der das Spiel gespielt wird</small>
</td><td align=\"top\">
<select name=\"league\" size=\"1\" required>";

        foreach ($leagues as $leagueOption) {
            $leagueEscaped = e($leagueOption);
            echo "<option value=\"{$leagueEscaped}\">{$leagueEscaped}</option>";
        }

        echo "
</select>
</td></tr>
<tr><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Map (1)</b><br>
<small>Die erste Map die gespielt wird</small>
</td><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"map1\" size=\"25\" maxlength=\"100\" required>
</td></tr>
<tr><td align=\"top\">
<b>Map (2)</b><br>
<small>Die zweite Map die gespielt wird</small>
</td><td align=\"top\">
<input name=\"map2\" size=\"25\" maxlength=\"100\" required>
</td></tr>
<tr><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Map (3)</b><br>
<small>Die dritte Map die gespielt wird (optional)</small>
</td><td align=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"map3\" size=\"25\" maxlength=\"100\">
</td></tr>
<tr><td align=\"top\">
<b>Termin</b><br>
<small>Der Termin an dem gespielt wird (Monat/Tag/Jahr/Stunde/Minute)</small>
</td><td align=\"top\">
<select name=\"time_month\" size=\"1\">
<option value=\"1\">Januar</option>
<option value=\"2\">Februar</option>
<option value=\"3\">M&auml;rz</option>
<option value=\"4\">April</option>
<option value=\"5\">Mai</option>
<option value=\"6\">Juni</option>
<option value=\"7\">Juli</option>
<option value=\"8\">August</option>
<option value=\"9\">September</option>
<option value=\"10\">Oktober</option>
<option value=\"11\">November</option>
<option value=\"12\">Dezember</option>
</select>
<input name=\"time_day\" size=\"2\" maxlength=\"2\" type=\"number\" min=\"1\" max=\"31\" required>
<select name=\"time_year\" size=\"1\">";

        $curyear = (int) date('Y');
        for ($i = 0; $i <= 4; $i++) {
            $year = $curyear + $i;
            echo "<option value=\"{$year}\">{$year}</option>";
        }

        echo '
</select>
<select name="time_hour" size="1">';

        for ($i = 0; $i <= 23; $i++) {
            $selected = ($i === 20) ? ' selected' : '';
            $hour = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            echo "<option value=\"{$i}\"{$selected}>{$hour}</option>";
        }

        echo "
</select>
<select name=\"time_minute\" size=\"1\">
<option value=\"0\">00</option>
<option value=\"15\">15</option>
<option value=\"30\">30</option>
<option value=\"45\">45</option>
</select>
</td></tr>
<tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$admin_tbl1}\">
<input type=\"submit\" value=\"War hinzuf&uuml;gen\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzten\">
</td></tr>
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
