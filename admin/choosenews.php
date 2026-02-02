<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose News to Edit/Delete
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
$query = 'SELECT * FROM pc_news ORDER BY id DESC';
$result = db_query($conn, $query);
$num = mysqli_num_rows($result);

if ($num === 0) {
    echo '<center>Keine News vorhanden!</center>';
} else {
    echo "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td width=\"80\" bgcolor=\"{$admin_tbl1}\" align=\"center\">
<b>Datum</b>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"*\">
<b>Titel</b>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"150\">
<b>Autor</b>
</td><td bgcolor=\"{$admin_tbl1}\" width=\"150\" align=\"center\">
<b>Adminfunktionen</b>
</td></tr>";

    $i = 0;
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $bgcolor = ($i === 0) ? '' : $admin_tbl1;
        $i = ($i === 0) ? 1 : 0;

        $date = date('d.m.Y', (int) $row['time']);
        $title = e($row['title'] ?? '');
        $nick = e($row['nick'] ?? '');
        $email = e($row['email'] ?? '');
        $newsId = (int) $row['id'];

        echo "
<tr><td align=\"center\" bgcolor=\"{$bgcolor}\">
<small>{$date}</small>
</td><td bgcolor=\"{$bgcolor}\">
{$title}
</td><td bgcolor=\"{$bgcolor}\">
<a href=\"mailto:{$email}\">{$nick}</a>
</td><td align=\"center\" bgcolor=\"{$bgcolor}\">
<small>[ <a href=\"editnews.php?newsid={$newsId}\">editieren</a> | <a href=\"delnews.php?newsid={$newsId}\">l&ouml;schen</a> ]</small>
</td></tr>";
    }
    echo '</table>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
