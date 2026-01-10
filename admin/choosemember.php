<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose Member to Edit/Delete
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<?php
$query = "SELECT * FROM pc_members ORDER BY nick";
$result = $conn->query($query);
$num = mysqli_num_rows($result);

if ($num === 0) {
    echo '<center>Keine Member vorhanden!</center>';
} else {
    echo "<ul type=\"square\">Memberliste\n";
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $nick = e($row['nick'] ?? '');
        $memberId = (int)$row['id'];
        echo "<li><b>{$nick}</b> <small>[ <a href=\"editmember.php?memberid={$memberId}\">editieren</a> | <a href=\"delmember.php?memberid={$memberId}\">l&ouml;schen</a> ]</small></li>\n";
    }
    echo '</ul>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
