<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Delete War
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

<center>
<?php
// CSRF protection
csrf_check();

if (($pcadmin['wars_del'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $warid = $_GET['warid'] ?? $_POST['warid'] ?? '';

    if (!empty($warid)) {
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

            $delwar = $_POST['delwar'] ?? '';

            if ($delwar === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $delStmt = db_prepare($conn, 'DELETE FROM pc_wars WHERE id = ?');
                $delStmt->bind_param('i', $waridInt);
                $delStmt->execute();
                $delStmt->close();
                echo '<center><a href="choosewar.php">Der War wurde erfolgreich gel&ouml;scht!</a></center>';
            } else {
                $date = date('d.m.Y', (int) $row['time']);
                $time = date('H:i', (int) $row['time']);
                $enemy = e($row['enemy'] ?? '');
                $warId = (int) $row['id'];

                echo "
<center>
Soll der War gegen <b>{$enemy}</b> am <b>{$date}</b> um <b>{$time}</b> wirklich gel&ouml;scht werden?<br>
<br>
<form action=\"delwar.php\" method=\"post\" style=\"display:inline;\">
" . csrf_field() . "
<input type=\"hidden\" name=\"warid\" value=\"{$warId}\">
<input type=\"hidden\" name=\"delwar\" value=\"YES\">
<button type=\"submit\">Ja, War l&ouml;schen!</button>
</form>
 | <a href=\"choosewar.php\">Nein, War nicht l&ouml;schen!</a>
</center>";
            }
        } else {
            $stmt->close();
            echo '<center><a href="choosewar.php">Der gew&auml;hlte War existiert nicht!</a></center>';
        }
    } else {
        echo '<center><a href="choosewar.php">Bitte w&auml;hle einen War aus!</a></center>';
    }
} else {
    echo '<center>Du hast keinen Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
