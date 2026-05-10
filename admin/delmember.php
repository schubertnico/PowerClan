<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Delete Member
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
// CSRF protection
csrf_check();

if (($pcadmin['member_del'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $memberid = $_GET['memberid'] ?? $_POST['memberid'] ?? '';

    if (!empty($memberid)) {
        $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE id = ?');
        $memberidInt = (int) $memberid;
        $stmt->bind_param('i', $memberidInt);
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
                throw new RuntimeException('Failed to fetch member data');
            }

            // Prevent self-deletion
            if ((int) $row['id'] === (int) ($pcadmin['id'] ?? 0)) {
                echo '<div class="alert alert-danger" role="alert">'
                    . 'Du kannst Dich nicht selbst löschen. '
                    . '<a class="alert-link" href="choosemember.php">Zurück zur Übersicht</a></div>';
                include __DIR__ . '/footer.inc.php';
                exit;
            }

            // Prevent superadmin deletion
            if (($row['superadmin'] ?? '') === 'YES') {
                echo '<div class="alert alert-danger" role="alert">'
                    . 'Du kannst einen Superadmin nicht löschen. '
                    . '<a class="alert-link" href="choosemember.php">Zurück zur Übersicht</a></div>';
                include __DIR__ . '/footer.inc.php';
                exit;
            }

            $delmember = $_POST['delmember'] ?? '';

            if ($delmember === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $delStmt = db_prepare($conn, 'DELETE FROM pc_members WHERE id = ?');
                $delStmt->bind_param('i', $memberidInt);
                $delStmt->execute();
                $delStmt->close();
                echo '<div class="alert alert-success" role="alert">'
                    . 'Der Member wurde erfolgreich gelöscht. '
                    . '<a class="alert-link" href="choosemember.php">Zur Member-Übersicht</a></div>';
            } else {
                $nick = e($row['nick'] ?? '');
                $work = e($row['work'] ?? '');
                $memberId = (int) $row['id'];
                ?>
                <div class="card border-danger shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h1 class="h5 mb-0">Member löschen?</h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <strong>Achtung:</strong> Dieser Vorgang kann nicht rückgängig gemacht werden.
                            Alle Member-Daten gehen verloren.
                        </div>
                        <p>
                            Soll der Member <strong><?php echo $nick; ?></strong>
                            <?php if ($work !== ''): ?>(<?php echo $work; ?>)<?php endif; ?>
                            wirklich gelöscht werden?
                        </p>
                        <form action="delmember.php" method="post" class="d-flex flex-wrap gap-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="memberid" value="<?php echo $memberId; ?>">
                            <input type="hidden" name="delmember" value="YES">
                            <button type="submit" class="btn btn-danger">Ja, Member endgültig löschen</button>
                            <a class="btn btn-outline-secondary" href="choosemember.php">Nein, abbrechen</a>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            $stmt->close();
            echo '<div class="alert alert-warning" role="alert">'
                . 'Der gewählte Member existiert nicht. '
                . '<a class="alert-link" href="choosemember.php">Zurück zur Übersicht</a></div>';
        }
    } else {
        echo '<div class="alert alert-warning" role="alert">'
            . 'Bitte wähle einen Member aus. '
            . '<a class="alert-link" href="choosemember.php">Zur Übersicht</a></div>';
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
