<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Member Page
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
$memberid = $_GET['memberid'] ?? '';

switch ($pcpage) {
    default:
        $result = db_query($conn, 'SELECT * FROM pc_members ORDER BY nick');
        $num = mysqli_num_rows($result);
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary fw-semibold">
                Memberübersicht
            </div>
            <?php if ($num === 0): ?>
                <div class="card-body">
                    <div class="alert alert-info mb-0" role="alert">
                        Es sind keine Member vorhanden.
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="w-50">Nick</th>
                                <th scope="col">Aufgabe</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)): ?>
                            <?php
                            $memberId = (int) $row['id'];
                            $nick = e($row['nick'] ?? '');
                            $work = e($row['work'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <a class="fw-semibold" href="member.php?pcpage=showmember&amp;memberid=<?php echo $memberId; ?>">
                                        <?php echo $nick; ?>
                                    </a>
                                </td>
                                <td><?php echo $work !== '' ? $work : '<span class="text-body-secondary">—</span>'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'showmember':
        if (empty($memberid)) {
            default_error('member.php', 'Bitte wähle einen Member aus!');
        } else {
            $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE id = ?');
            $memberIdInt = (int) $memberid;
            $stmt->bind_param('i', $memberIdInt);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result === false) {
                throw new RuntimeException('Failed to get result');
            }
            $num = mysqli_num_rows($result);

            if ($num === 1) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                // Safe values
                $nick = e($row['nick'] ?? '');
                $email = e($row['email'] ?? '');
                $work = e($row['work'] ?? '');
                $icqRaw = $row['icq'] ?? '';
                $homepageRaw = $row['homepage'] ?? '';
                $realnameRaw = $row['realname'] ?? '';
                $ageRaw = $row['age'] ?? '';
                $infoRaw = $row['info'] ?? '';
                $hardwareRaw = $row['hardware'] ?? '';
                $picRaw = $row['pic'] ?? '';

                $icqHtml = empty($icqRaw)
                    ? '<span class="text-body-secondary">N/A</span>'
                    : '<a class="link-secondary" href="https://web.icq.com/people/' . e($icqRaw) . '">' . e($icqRaw) . '</a>';

                $homepageHtml = empty($homepageRaw)
                    ? '<span class="text-body-secondary">Keine Homepage</span>'
                    : '<a class="link-secondary" href="' . e($homepageRaw) . '" target="_blank" rel="noopener noreferrer">' . e($homepageRaw) . '</a>';

                $realnameHtml = empty($realnameRaw) ? '<span class="text-body-secondary">N/A</span>' : e($realnameRaw);
                $ageHtml = empty($ageRaw) ? '<span class="text-body-secondary">N/A</span>' : e($ageRaw) . ' Jahre';
                $infosHtml = empty($infoRaw) ? '<span class="text-body-secondary">Keine persönlichen Informationen</span>' : nl2br(e($infoRaw));
                $hardwareHtml = empty($hardwareRaw) ? '<span class="text-body-secondary">Keine Hardwareinformationen</span>' : nl2br(e($hardwareRaw));

                if (!empty($picRaw)) {
                    $safePic = e($picRaw);
                    $picHtml = '<a href="showpic.php?path=' . $safePic . '">'
                        . '<img src="' . $safePic . '" class="pc-member-pic" alt="' . $nick . '">'
                        . '</a>';
                } else {
                    $picHtml = '<span class="text-body-secondary">Kein Bild vorhanden</span>';
                }
                ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-body-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h1 class="h4 mb-0"><?php echo $nick; ?>s Details</h1>
                        <a class="btn btn-sm btn-outline-secondary" href="member.php">&laquo; Zurück zur Memberübersicht</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-md-3 text-center">
                                <?php echo $picHtml; ?>
                            </div>
                            <div class="col-12 col-md-9">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">E-Mail</dt>
                                    <dd class="col-sm-8"><a class="link-secondary" href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></dd>

                                    <dt class="col-sm-4">Aufgabe</dt>
                                    <dd class="col-sm-8"><?php echo $work !== '' ? $work : '<span class="text-body-secondary">—</span>'; ?></dd>

                                    <dt class="col-sm-4">ICQ</dt>
                                    <dd class="col-sm-8"><?php echo $icqHtml; ?></dd>

                                    <dt class="col-sm-4">Homepage</dt>
                                    <dd class="col-sm-8"><?php echo $homepageHtml; ?></dd>

                                    <dt class="col-sm-4">Realname</dt>
                                    <dd class="col-sm-8"><?php echo $realnameHtml; ?></dd>

                                    <dt class="col-sm-4">Alter</dt>
                                    <dd class="col-sm-8"><?php echo $ageHtml; ?></dd>

                                    <dt class="col-sm-4">Persönliche Infos</dt>
                                    <dd class="col-sm-8"><?php echo $infosHtml; ?></dd>

                                    <dt class="col-sm-4">Hardware</dt>
                                    <dd class="col-sm-8"><?php echo $hardwareHtml; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                default_error('member.php', 'Bitte wähle einen existierenden Member aus!');
            }
            $stmt->close();
        }
        break;
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
