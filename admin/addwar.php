<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Add War
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
/** @var array<int, string> $leagues */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<?php
// CSRF protection
csrf_check();

if (($pcadmin['wars_add'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $addwar = $_GET['addwar'] ?? '';

    if ($addwar === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $enemy = trim($_POST['enemy'] ?? '');
        $enemy_tag = trim($_POST['enemy_tag'] ?? '');
        $homepage = trim($_POST['homepage'] ?? '');
        $league = trim($_POST['league'] ?? '');
        $map1 = trim($_POST['map1'] ?? '');
        $map2 = trim($_POST['map2'] ?? '');
        $map3 = trim($_POST['map3'] ?? '');
        $time_day = (int) ($_POST['time_day'] ?? 1);
        $time_hour = (int) ($_POST['time_hour'] ?? 20);
        $time_minute = (int) ($_POST['time_minute'] ?? 0);
        $time_month = (int) ($_POST['time_month'] ?? 1);
        $time_year = (int) ($_POST['time_year'] ?? date('Y'));

        if (
            empty($enemy) || empty($enemy_tag) || empty($homepage)
            || empty($league) || empty($map1) || empty($map2) || $time_day < 1
        ) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Bitte fülle alle nicht optionalen Felder aus! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
        } elseif (
            !checkdate($time_month, $time_day, $time_year)
            || $time_hour < 0 || $time_hour > 23
            || $time_minute < 0 || $time_minute > 59
        ) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Ungültiges Datum oder Uhrzeit! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
        } else {
            $playtime = mktime($time_hour, $time_minute, 0, $time_month, $time_day, $time_year);

            // Use prepared statement to prevent SQL injection
            $sql = 'INSERT INTO pc_wars (enemy, enemy_tag, homepage, league, map1, map2, map3, '
                . 'time, report, res1, res2, res3, screen1, screen2, screen3) '
                . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '', '', '', '')";
            $stmt = db_prepare($conn, $sql);
            $stmt->bind_param(
                'sssssssi',
                $enemy,
                $enemy_tag,
                $homepage,
                $league,
                $map1,
                $map2,
                $map3,
                $playtime
            );
            $stmt->execute();
            $stmt->close();

            echo '<div class="alert alert-success" role="alert">'
                . 'Der War wurde erfolgreich hinzugefügt. '
                . '<a class="alert-link" href="choosewar.php">Zur War-Übersicht</a></div>';
        }
    } else {
        $phpSelf = e($_SERVER['PHP_SELF']);
        $months = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
        $curyear = (int) date('Y');
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                <h1 class="h4 mb-0">War hinzufügen</h1>
            </div>
            <div class="card-body">
                <form action="<?php echo $phpSelf; ?>?addwar=YES" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="row mb-3">
                        <label for="enemy" class="col-sm-3 col-form-label">Gegner <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="enemy" name="enemy" type="text" class="form-control" maxlength="150" required aria-describedby="enemyHelp">
                            <div id="enemyHelp" class="form-text">Der Name des Clans, gegen den gespielt wird.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="enemy_tag" class="col-sm-3 col-form-label">Clantag <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="enemy_tag" name="enemy_tag" type="text" class="form-control" maxlength="10" required aria-describedby="enemyTagHelp">
                            <div id="enemyTagHelp" class="form-text">Das Clankürzel des Clans, gegen den gespielt wird.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="homepage" class="col-sm-3 col-form-label">Homepage <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="homepage" name="homepage" type="url" class="form-control" maxlength="250" required aria-describedby="homepageHelp">
                            <div id="homepageHelp" class="form-text">Die Homepage des Clans, gegen den gespielt wird.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="league" class="col-sm-3 col-form-label">Liga <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <select id="league" name="league" class="form-select" required aria-describedby="leagueHelp">
                                <?php foreach ($leagues as $leagueOption): ?>
                                    <option value="<?php echo e($leagueOption); ?>"><?php echo e($leagueOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="leagueHelp" class="form-text">Die Liga, in der das Spiel gespielt wird.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="map1" class="col-sm-3 col-form-label">Map 1 <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="map1" name="map1" type="text" class="form-control" maxlength="100" required aria-describedby="map1Help">
                            <div id="map1Help" class="form-text">Die erste Map, die gespielt wird.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="map2" class="col-sm-3 col-form-label">Map 2 <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="map2" name="map2" type="text" class="form-control" maxlength="100" required aria-describedby="map2Help">
                            <div id="map2Help" class="form-text">Die zweite Map, die gespielt wird.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="map3" class="col-sm-3 col-form-label">Map 3</label>
                        <div class="col-sm-9">
                            <input id="map3" name="map3" type="text" class="form-control" maxlength="100" aria-describedby="map3Help">
                            <div id="map3Help" class="form-text">Optional &mdash; die dritte Map, die gespielt wird.</div>
                        </div>
                    </div>
                    <fieldset class="row mb-4">
                        <legend class="col-sm-3 col-form-label pt-0">Termin <span class="text-danger" aria-hidden="true">*</span></legend>
                        <div class="col-sm-9">
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label for="time_day" class="form-label small">Tag</label>
                                    <input id="time_day" name="time_day" type="number" min="1" max="31" class="form-control" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="time_month" class="form-label small">Monat</label>
                                    <select id="time_month" name="time_month" class="form-select">
                                        <?php foreach ($months as $mNum => $mLabel): ?>
                                            <option value="<?php echo $mNum; ?>"><?php echo e($mLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label for="time_year" class="form-label small">Jahr</label>
                                    <select id="time_year" name="time_year" class="form-select">
                                        <?php for ($i = 0; $i <= 4; $i++): $year = $curyear + $i; ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label for="time_hour" class="form-label small">Stunde</label>
                                    <select id="time_hour" name="time_hour" class="form-select">
                                        <?php for ($i = 0; $i <= 23; $i++): ?>
                                            <option value="<?php echo $i; ?>"<?php echo $i === 20 ? ' selected' : ''; ?>><?php echo str_pad((string) $i, 2, '0', STR_PAD_LEFT); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label for="time_minute" class="form-label small">Minute</label>
                                    <select id="time_minute" name="time_minute" class="form-select">
                                        <option value="0">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-text mt-1">Der Termin, an dem gespielt wird.</div>
                        </div>
                    </fieldset>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">War hinzufügen</button>
                        <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
