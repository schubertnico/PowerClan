<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Delete War
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
                echo '<div class="alert alert-success" role="alert">'
                    . 'Der War wurde erfolgreich gelöscht. '
                    . '<a class="alert-link" href="choosewar.php">Zur War-Übersicht</a></div>';
            } else {
                $date = date('d.m.Y', (int) $row['time']);
                $time = date('H:i', (int) $row['time']);
                $enemy = e($row['enemy'] ?? '');
                $warId = (int) $row['id'];
                ?>
                <div class="card border-danger shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h1 class="h5 mb-0">War löschen?</h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <strong>Achtung:</strong> Dieser Vorgang kann nicht rückgängig gemacht werden.
                        </div>
                        <p>
                            Soll der War gegen <strong><?php echo $enemy; ?></strong> am
                            <strong><?php echo $date; ?></strong> um <strong><?php echo $time; ?></strong>
                            wirklich gelöscht werden?
                        </p>
                        <form action="delwar.php" method="post" class="d-flex flex-wrap gap-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="warid" value="<?php echo $warId; ?>">
                            <input type="hidden" name="delwar" value="YES">
                            <button type="submit" class="btn btn-danger">Ja, War endgültig löschen</button>
                            <a class="btn btn-outline-secondary" href="choosewar.php">Nein, abbrechen</a>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            $stmt->close();
            echo '<div class="alert alert-warning" role="alert">'
                . 'Der gewählte War existiert nicht. '
                . '<a class="alert-link" href="choosewar.php">Zurück zur Übersicht</a></div>';
        }
    } else {
        echo '<div class="alert alert-warning" role="alert">'
            . 'Bitte wähle einen War aus. '
            . '<a class="alert-link" href="choosewar.php">Zur Übersicht</a></div>';
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
