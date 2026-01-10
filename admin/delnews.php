<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Delete News
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
if (($pcadmin['news_del'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $newsid = $_GET['newsid'] ?? '';

    if (!empty($newsid)) {
        $stmt = $conn->prepare("SELECT * FROM pc_news WHERE id = ?");
        $newsidInt = (int)$newsid;
        $stmt->bind_param('i', $newsidInt);
        $stmt->execute();
        $result = $stmt->get_result();
        $num = mysqli_num_rows($result);

        if ($num === 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $stmt->close();

            $delnews = $_GET['delnews'] ?? '';

            if ($delnews === 'YES') {
                $delStmt = $conn->prepare("DELETE FROM pc_news WHERE id = ?");
                $delStmt->bind_param('i', $newsidInt);
                $delStmt->execute();
                $delStmt->close();
                echo '<center><a href="choosenews.php">Der Newseintrag wurde erfolgreich gel&ouml;scht!</a></center>';
            } else {
                $date = date('d.m.Y', (int)$row['time']);
                $title = e($row['title'] ?? '');
                $newsId = (int)$row['id'];

                echo "
<center>
Sollen die News <b>{$title}</b> vom {$date} wirklich gel&ouml;scht werden?<br>
<br>
[ <a href=\"delnews.php?newsid={$newsId}&delnews=YES\">Ja, Newseintrag l&ouml;schen!</a> | <a href=\"choosenews.php\">Nein, Newseintrag nicht l&ouml;schen!</a> ]
</center>";
            }
        } else {
            $stmt->close();
            echo '<center><a href="choosenews.php">Der gew&auml;hlte Newseintrag existiert nicht!</a></center>';
        }
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
