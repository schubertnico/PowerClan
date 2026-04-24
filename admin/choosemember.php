<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose Member to Edit/Delete
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
$query = 'SELECT * FROM pc_members ORDER BY nick';
$result = db_query($conn, $query);
$num = mysqli_num_rows($result);

if ($num === 0) {
    echo '<center>Keine Member vorhanden!</center>';
} else {
    echo "<ul type=\"square\">Memberliste\n";
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $nick = e($row['nick'] ?? '');
        $memberId = (int) $row['id'];

        // BUG-021: Aktionslinks nur bei Berechtigung
        $actions = [];
        if (pc_can('member_edit')) {
            $actions[] = "<a href=\"editmember.php?memberid={$memberId}\">editieren</a>";
        }
        if (pc_can('member_del')) {
            $actions[] = "<a href=\"delmember.php?memberid={$memberId}\">l&ouml;schen</a>";
        }
        $actionHtml = $actions === [] ? '&mdash;' : '[ ' . implode(' | ', $actions) . ' ]';

        echo "<li><b>{$nick}</b> <small>{$actionHtml}</small></li>\n";
    }
    echo '</ul>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
