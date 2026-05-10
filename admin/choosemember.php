<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Choose Member to Edit/Delete
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
$query = 'SELECT * FROM pc_members ORDER BY nick';
$result = db_query($conn, $query);
$num = mysqli_num_rows($result);
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="h4 mb-0">Member verwalten</h1>
        <?php if (pc_can('member_add')): ?>
            <a class="btn btn-sm btn-primary" href="addmember.php">Member hinzufügen</a>
        <?php endif; ?>
    </div>
    <?php if ($num === 0): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0" role="alert">Keine Member vorhanden.</div>
        </div>
    <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)):
                $nick = e($row['nick'] ?? '');
                $memberId = (int) $row['id'];
                $isSuperadmin = ($row['superadmin'] ?? '') === 'YES';
                $isSelf = $memberId === (int) ($pcadmin['id'] ?? 0);

                // BUG-021: Aktionslinks nur bei Berechtigung
                $canEdit = pc_can('member_edit');
                $canDel = pc_can('member_del') && !$isSelf && !$isSuperadmin;
            ?>
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="fw-semibold"><?php echo $nick; ?></span>
                        <?php if ($isSuperadmin): ?>
                            <span class="badge text-bg-warning ms-1">Superadmin</span>
                        <?php endif; ?>
                        <?php if ($isSelf): ?>
                            <span class="badge text-bg-info ms-1">Du selbst</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$canEdit && !$canDel): ?>
                        <span class="text-body-secondary small">&mdash;</span>
                    <?php else: ?>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Member-Aktionen für <?php echo $nick; ?>">
                            <?php if ($canEdit): ?>
                                <a class="btn btn-outline-primary" href="editmember.php?memberid=<?php echo $memberId; ?>">Editieren</a>
                            <?php endif; ?>
                            <?php if ($canDel): ?>
                                <a class="btn btn-outline-danger" href="delmember.php?memberid=<?php echo $memberId; ?>">Löschen</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
</div>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
