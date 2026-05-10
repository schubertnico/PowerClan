<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose War to Edit/Delete
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
$query = 'SELECT * FROM pc_wars ORDER BY time DESC';
$result = db_query($conn, $query);
$num = mysqli_num_rows($result);
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="h4 mb-0">Wars verwalten</h1>
        <?php if (pc_can('wars_add')): ?>
            <a class="btn btn-sm btn-primary" href="addwar.php">War hinzufügen</a>
        <?php endif; ?>
    </div>
    <?php if ($num === 0): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0" role="alert">Keine Wars vorhanden.</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" style="width: 110px">Termin</th>
                        <th scope="col" style="width: 160px">Gegner</th>
                        <th scope="col">Maps</th>
                        <th scope="col" class="text-end" style="width: 220px">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)):
                        $date = date('d.m.Y', (int) $row['time']);
                        $time = date('H:i', (int) $row['time']);
                        $enemy = e($row['enemy'] ?? '');
                        $map1 = e($row['map1'] ?? '');
                        $map2 = e($row['map2'] ?? '');
                        $map3 = e($row['map3'] ?? '');
                        $warId = (int) $row['id'];

                        // BUG-021: Aktionslinks nur bei Berechtigung
                        $canEdit = pc_can('wars_edit');
                        $canDel = pc_can('wars_del');
                    ?>
                        <tr>
                            <td>
                                <span class="d-block"><?php echo $date; ?></span>
                                <span class="text-body-secondary small"><?php echo $time; ?></span>
                            </td>
                            <td><?php echo $enemy; ?></td>
                            <td>
                                <span class="d-inline-flex flex-wrap gap-1">
                                    <span class="badge <?php echo !empty($row['res1']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                        <?php echo $map1; ?><?php echo !empty($row['res1']) ? ' &check;' : ''; ?>
                                    </span>
                                    <span class="badge <?php echo !empty($row['res2']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                        <?php echo $map2; ?><?php echo !empty($row['res2']) ? ' &check;' : ''; ?>
                                    </span>
                                    <?php if (!empty($map3)): ?>
                                        <span class="badge <?php echo !empty($row['res3']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                            <?php echo $map3; ?><?php echo !empty($row['res3']) ? ' &check;' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if (!$canEdit && !$canDel): ?>
                                    <span class="text-body-secondary small">&mdash;</span>
                                <?php else: ?>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="War-Aktionen">
                                        <?php if ($canEdit): ?>
                                            <a class="btn btn-outline-primary" href="editwar.php?warid=<?php echo $warId; ?>">Editieren</a>
                                        <?php endif; ?>
                                        <?php if ($canDel): ?>
                                            <a class="btn btn-outline-danger" href="delwar.php?warid=<?php echo $warId; ?>">Löschen</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-body-secondary small">
            Maps mit grünem Hintergrund haben bereits ein Ergebnis.
        </div>
    <?php endif; ?>
</div>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
