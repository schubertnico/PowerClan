<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose News to Edit/Delete
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
$query = 'SELECT * FROM pc_news ORDER BY id DESC';
$result = db_query($conn, $query);
$num = mysqli_num_rows($result);
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="h4 mb-0">News verwalten</h1>
        <?php if (pc_can('news_add')): ?>
            <a class="btn btn-sm btn-primary" href="addnews.php">News hinzufügen</a>
        <?php endif; ?>
    </div>
    <?php if ($num === 0): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0" role="alert">Keine News vorhanden.</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" style="width: 110px">Datum</th>
                        <th scope="col">Titel</th>
                        <th scope="col" style="width: 200px">Autor</th>
                        <th scope="col" class="text-end" style="width: 220px">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)):
                        $date = date('d.m.Y', (int) $row['time']);
                        $title = e($row['title'] ?? '');
                        $nick = e($row['nick'] ?? '');
                        $email = e($row['email'] ?? '');
                        $newsId = (int) $row['id'];
                        $authorId = (int) ($row['userid'] ?? 0);

                        // BUG-021: Aktionslinks nur bei tatsächlicher Berechtigung
                        $canEdit = pc_can('news_edit') || $authorId === (int) ($pcadmin['id'] ?? 0);
                        $canDel = pc_can('news_del');
                        ?>
                        <tr>
                            <td><span class="text-body-secondary small"><?php echo $date; ?></span></td>
                            <td><?php echo $title; ?></td>
                            <td><a class="link-secondary" href="mailto:<?php echo $email; ?>"><?php echo $nick; ?></a></td>
                            <td class="text-end">
                                <?php if (!$canEdit && !$canDel): ?>
                                    <span class="text-body-secondary small">&mdash;</span>
                                <?php else: ?>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="News-Aktionen">
                                        <?php if ($canEdit): ?>
                                            <a class="btn btn-outline-primary" href="editnews.php?newsid=<?php echo $newsId; ?>">Editieren</a>
                                        <?php endif; ?>
                                        <?php if ($canDel): ?>
                                            <a class="btn btn-outline-danger" href="delnews.php?newsid=<?php echo $newsId; ?>">Löschen</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
