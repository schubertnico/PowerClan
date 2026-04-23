<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose War to Edit/Delete
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

<?php
$query = 'SELECT * FROM pc_wars ORDER BY time DESC';
$result = db_query($conn, $query);
$num = mysqli_num_rows($result);

if ($num === 0) {
    echo '<center>Keine Wars vorhanden!</center>';
} else {
    echo "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td width=\"80\" bgcolor=\"{$admin_tbl1}\" align=\"center\">
<b>Termin</b>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"100\">
<b>Gegner</b>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"*\">
<b>Maps</b>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"150\" align=\"center\">
<b>Adminfunktionen</b>
</td></tr>";

    $i = 0;
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $bgcolor = ($i === 0) ? '' : $admin_tbl1;
        $i = ($i === 0) ? 1 : 0;

        $date = date('d.m.Y', (int) $row['time']);
        $time = date('H:i', (int) $row['time']);
        $enemy = e($row['enemy'] ?? '');
        $map1 = e($row['map1'] ?? '');
        $map2 = e($row['map2'] ?? '');
        $map3 = e($row['map3'] ?? '');
        $warId = (int) $row['id'];

        echo "
<tr><td align=\"center\" bgcolor=\"{$bgcolor}\">
<small>{$date}<br>{$time}</small>
</td><td bgcolor=\"{$bgcolor}\">
{$enemy}
</td><td bgcolor=\"{$bgcolor}\">";

        if (!empty($row['res1'])) {
            echo "<b class=\"green\">{$map1}</b>";
        } else {
            echo $map1;
        }

        if (!empty($row['res2'])) {
            echo " | <b class=\"green\">{$map2}</b>";
        } else {
            echo " | {$map2}";
        }

        if (!empty($row['res3'])) {
            echo " | <b class=\"green\">{$map3}</b>";
        } elseif (!empty($map3)) {
            echo " | {$map3}";
        }

        // BUG-021: Aktionslinks nur bei Berechtigung
        $actions = [];
        if (pc_can('wars_edit')) {
            $actions[] = "<a href=\"editwar.php?warid={$warId}\">editieren</a>";
        }
        if (pc_can('wars_del')) {
            $actions[] = "<a href=\"delwar.php?warid={$warId}\">l&ouml;schen</a>";
        }
        $actionHtml = $actions === [] ? '&mdash;' : '[ ' . implode(' | ', $actions) . ' ]';

        echo "
</td><td align=\"center\" bgcolor=\"{$bgcolor}\">
<small>{$actionHtml}</small>
</td></tr>";
    }
    echo '</table>';
}
?>
<br>
<center><small>Bei <b class="green">gr&uuml;n</b> markierten Maps wurde bereits ein Ergebnis eingetragen</small></center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
