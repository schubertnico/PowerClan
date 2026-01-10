<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Delete Member
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
if (($pcadmin['member_del'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $memberid = $_GET['memberid'] ?? '';

    if (!empty($memberid)) {
        $stmt = $conn->prepare("SELECT * FROM pc_members WHERE id = ?");
        $memberidInt = (int)$memberid;
        $stmt->bind_param('i', $memberidInt);
        $stmt->execute();
        $result = $stmt->get_result();
        $num = mysqli_num_rows($result);

        if ($num === 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $stmt->close();

            // Prevent self-deletion
            if ((int)$row['id'] === (int)($pcadmin['id'] ?? 0)) {
                echo '<center><a href="choosemember.php">Du kannst Dich nicht selbst l&ouml;schen!</a></center>';
                exit;
            }

            // Prevent superadmin deletion
            if (($row['superadmin'] ?? '') === 'YES') {
                echo '<center><a href="choosemember.php">Du kannst einen Superadmin nicht l&ouml;schen!</a></center>';
                exit;
            }

            $delmember = $_GET['delmember'] ?? '';

            if ($delmember === 'YES') {
                $delStmt = $conn->prepare("DELETE FROM pc_members WHERE id = ?");
                $delStmt->bind_param('i', $memberidInt);
                $delStmt->execute();
                $delStmt->close();
                echo '<center><a href="choosemember.php">Der Member wurde erfolgreich gel&ouml;scht!</a></center>';
            } else {
                $nick = e($row['nick'] ?? '');
                $work = e($row['work'] ?? '');
                $memberId = (int)$row['id'];

                echo "
<center>
Soll der Member <b>{$nick}</b> ({$work}) wirklich gel&ouml;scht werden?<br>
<br>
[ <a href=\"delmember.php?memberid={$memberId}&delmember=YES\">Ja, Member l&ouml;schen!</a> | <a href=\"choosemember.php\">Nein, Member nicht l&ouml;schen!</a> ]
</center>";
            }
        } else {
            $stmt->close();
            echo '<center><a href="choosemember.php">Der gew&auml;hlte Member existiert nicht!</a></center>';
        }
    } else {
        echo '<center><a href="choosemember.php">Bitte w&auml;hle einen Member aus!</a></center>';
    }
} else {
    echo '<center>Du hast keine Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
