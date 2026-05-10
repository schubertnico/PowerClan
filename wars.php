<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Wars Page
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/** @var mysqli $conn */
/** @var array<string, mixed> $settings */

?>
<!--HEADER FILE-->
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAIN PAGE-->

<?php
$pcpage = $_GET['pcpage'] ?? '';

switch ($pcpage) {
    default:
        getwarstats();

        $result = db_query($conn, 'SELECT * FROM pc_wars ORDER BY time DESC');
        $num = mysqli_num_rows($result);
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary fw-semibold">
                Warübersicht
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
                                <th scope="col" class="text-end">#</th>
                                <th scope="col">Gegner</th>
                                <th scope="col">Termin</th>
                                <th scope="col">Liga</th>
                                <th scope="col">Map 1</th>
                                <th scope="col">Map 2</th>
                                <th scope="col">Map 3</th>
                                <th scope="col">Ergebnis</th>
                                <th scope="col">Bericht</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $warnumber = (int) $num;
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)):
                            $endres = [0, 0];
                            $warId = (int) $row['id'];
                            $date = date('d.m.Y', (int) $row['time']);
                            $time = date('H:i', (int) $row['time']);
                            $enemyTag = e($row['enemy_tag'] ?? '');
                            $homepage = $row['homepage'] ?? '';
                            $league = e($row['league'] ?? '');

                            $map1Cell = pc_render_war_map_cell($row['map1'] ?? '', $row['screen1'] ?? '', $row['res1'] ?? '', $endres);
                            $map2Cell = pc_render_war_map_cell($row['map2'] ?? '', $row['screen2'] ?? '', $row['res2'] ?? '', $endres);
                            $map3Cell = pc_render_war_map_cell($row['map3'] ?? '', $row['screen3'] ?? '', $row['res3'] ?? '', $endres);

                            // Total result
                            if ($endres[0] > $endres[1]) {
                                $totalCls = 'pc-result pc-result-won';
                                $totalLabel = 'Gewonnen';
                            } elseif ($endres[0] === $endres[1]) {
                                $totalCls = 'pc-result pc-result-draw';
                                $totalLabel = 'Unentschieden';
                            } else {
                                $totalCls = 'pc-result pc-result-lost';
                                $totalLabel = 'Verloren';
                            }
                            ?>
                            <tr id="war<?php echo $warId; ?>">
                                <th scope="row" class="text-end"><?php echo $warnumber; ?></th>
                                <td>
                                    <?php if (!empty($homepage)): ?>
                                        <a class="link-secondary" href="<?php echo e($homepage); ?>" target="_blank" rel="noopener noreferrer"><?php echo $enemyTag; ?></a>
                                    <?php else: ?>
                                        <?php echo $enemyTag; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="d-block"><?php echo $date; ?></span>
                                    <span class="text-body-secondary small"><?php echo $time; ?></span>
                                </td>
                                <td><span class="badge text-bg-secondary"><?php echo $league; ?></span></td>
                                <td><?php echo $map1Cell; ?></td>
                                <td><?php echo $map2Cell; ?></td>
                                <td><?php echo $map3Cell; ?></td>
                                <td>
                                    <?php if (!empty($row['res1']) || !empty($row['res2']) || !empty($row['res3'])): ?>
                                        <span class="<?php echo $totalCls; ?>" aria-label="<?php echo $totalLabel; ?>" title="<?php echo $totalLabel; ?>">
                                            <?php echo $endres[0] . ':' . $endres[1]; ?>
                                        </span>
                                        <span class="visually-hidden"><?php echo $totalLabel; ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">offen</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['report'])): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="wars.php?pcpage=showreport&amp;warid=<?php echo $warId; ?>">
                                            Bericht lesen
                                        </a>
                                    <?php else: ?>
                                        <span class="text-body-secondary small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $warnumber--;
                        endwhile;
                        ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'showreport':
        $warid = $_GET['warid'] ?? '';
        if (empty($warid)) {
            default_error('wars.php', 'Du musst einen War auswählen!');
        } else {
            $stmt = db_prepare($conn, 'SELECT * FROM pc_wars WHERE id = ?');
            $warIdInt = (int) $warid;
            $stmt->bind_param('i', $warIdInt);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result === false) {
                throw new RuntimeException('Failed to get result');
            }
            $num = mysqli_num_rows($result);

            if ($num === 1) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                if (!empty($row['report'])) {
                    $report = news_replace($row['report']);
                    $clanname = e($settings['clanname'] ?? '');
                    $enemy = e($row['enemy'] ?? '');
                    ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-body-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h1 class="h4 mb-0"><?php echo $clanname; ?> vs. <?php echo $enemy; ?></h1>
                            <a class="btn btn-sm btn-outline-secondary" href="wars.php">&laquo; Zurück zur Warübersicht</a>
                        </div>
                        <div class="card-body pc-news-body">
                            <?php echo $report; ?>
                        </div>
                    </div>
                    <?php
                } else {
                    default_error('wars.php', 'Zum gewählten War wurde noch kein Bericht geschrieben!');
                }
            } else {
                default_error('wars.php', 'Der gewählte War existiert nicht!');
            }
            $stmt->close();
        }
        break;
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
