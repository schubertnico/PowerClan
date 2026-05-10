<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit War
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

if (($pcadmin['wars_edit'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $warid = $_GET['warid'] ?? '';
    $uploadscreen = $_GET['uploadscreen'] ?? '';

    if (!empty($warid)) {
        // Get war data using prepared statement
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
            $rowId = (int) $row['id'];

            $editwar = $_GET['editwar'] ?? '';

            if ($editwar === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get POST data
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
                $report = trim($_POST['report'] ?? '');
                $res1 = trim($_POST['res1'] ?? '');
                $res2 = trim($_POST['res2'] ?? '');
                $res3 = trim($_POST['res3'] ?? '');

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

                    $sql = 'UPDATE pc_wars SET enemy = ?, enemy_tag = ?, homepage = ?, '
                        . 'league = ?, map1 = ?, map2 = ?, map3 = ?, time = ?, report = ?, '
                        . 'res1 = ?, res2 = ?, res3 = ? WHERE id = ?';
                    $updateStmt = db_prepare($conn, $sql);
                    $updateStmt->bind_param(
                        'ssssssssssssi',
                        $enemy,
                        $enemy_tag,
                        $homepage,
                        $league,
                        $map1,
                        $map2,
                        $map3,
                        $playtime,
                        $report,
                        $res1,
                        $res2,
                        $res3,
                        $rowId
                    );
                    $updateStmt->execute();
                    $updateStmt->close();

                    echo '<div class="alert alert-success" role="alert">'
                        . 'Der War wurde erfolgreich editiert. '
                        . '<a class="alert-link" href="choosewar.php">Zur War-Übersicht</a></div>';
                }
            } elseif ($uploadscreen === 'YES' && isset($_GET['map'])) {
                $map = (int) $_GET['map'];

                if ($map >= 1 && $map <= 3) {
                    $targetDirectory = __DIR__ . '/../images/wars/';
                    $targetFileName = $rowId . '_map' . $map . '.jpg';

                    if (isset($_FILES['screen' . $map]['tmp_name'])) {
                        $screen = $_FILES['screen' . $map]['tmp_name'];

                        if (is_uploaded_file($screen)) {
                            if (is_writable($targetDirectory)) {
                                if (move_uploaded_file($screen, $targetDirectory . $targetFileName)) {
                                    $screenColumn = 'screen' . $map;
                                    $updateStmt = db_prepare($conn, "UPDATE pc_wars SET {$screenColumn} = ? WHERE id = ?");
                                    $updateStmt->bind_param('si', $targetFileName, $rowId);
                                    $updateStmt->execute();
                                    $updateStmt->close();
                                    echo '<div class="alert alert-success" role="alert">'
                                        . "Der Screenshot für Map {$map} wurde erfolgreich hochgeladen. "
                                        . '<a class="alert-link" href="choosewar.php">Zur Übersicht</a></div>';
                                } else {
                                    echo '<div class="alert alert-danger" role="alert">'
                                        . "Fehler beim Verschieben des Screenshots für Map {$map}. "
                                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger" role="alert">'
                                    . 'Das Zielverzeichnis ist nicht beschreibbar.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning" role="alert">'
                                . "Bitte einen Screenshot für Map {$map} auswählen.</div>";
                        }
                    } else {
                        echo '<div class="alert alert-danger" role="alert">'
                            . "Keine Screenshot-Datei für Map {$map} gefunden.</div>";
                    }
                } else {
                    echo '<div class="alert alert-danger" role="alert">'
                        . 'Ungültige Kartennummer. '
                        . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                }
            } else {
                // Display edit form
                $phpSelf = e($_SERVER['PHP_SELF']);
                $enemyEsc = e($row['enemy'] ?? '');
                $enemyTagEsc = e($row['enemy_tag'] ?? '');
                $homepageEsc = e($row['homepage'] ?? '');
                $map1Esc = e($row['map1'] ?? '');
                $map2Esc = e($row['map2'] ?? '');
                $map3Esc = e($row['map3'] ?? '');
                $res1Esc = e($row['res1'] ?? '');
                $res2Esc = e($row['res2'] ?? '');
                $res3Esc = e($row['res3'] ?? '');
                $reportEsc = e($row['report'] ?? '');
                $warTime = (int) $row['time'];

                $month = (int) date('n', $warTime);
                $day = date('d', $warTime);
                $year = (int) date('Y', $warTime);
                $hour = (int) date('G', $warTime);
                $minute = (int) date('i', $warTime);

                $months = [
                    1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                    5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
                ];
                $curyear = (int) date('Y');
                ?>
                <script>
                function insertBBCode(tag) {
                    var textarea = document.getElementById("report");
                    var startPos = textarea.selectionStart;
                    var endPos = textarea.selectionEnd;
                    var selectedText = textarea.value.substring(startPos, endPos);

                    var newText = "[" + tag + "]" + selectedText + "[/" + tag + "]";
                    textarea.value = textarea.value.substring(0, startPos) + newText + textarea.value.substring(endPos);
                }
                </script>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-body-secondary">
                        <h1 class="h4 mb-0">War editieren</h1>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo $phpSelf; ?>?editwar=YES&warid=<?php echo $rowId; ?>" method="post" novalidate>
                            <?php echo csrf_field(); ?>
                            <div class="row mb-3">
                                <label for="enemy" class="col-sm-3 col-form-label">Gegner <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="enemy" name="enemy" type="text" class="form-control" maxlength="150" value="<?php echo $enemyEsc; ?>" required>
                                    <div class="form-text">Der Name des Clans, gegen den gespielt wird.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="enemy_tag" class="col-sm-3 col-form-label">Clantag <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="enemy_tag" name="enemy_tag" type="text" class="form-control" maxlength="10" value="<?php echo $enemyTagEsc; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="homepage" class="col-sm-3 col-form-label">Homepage <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="homepage" name="homepage" type="url" class="form-control" maxlength="250" value="<?php echo $homepageEsc; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="league" class="col-sm-3 col-form-label">Liga <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <select id="league" name="league" class="form-select" required>
                                        <?php foreach ($leagues as $leagueOption):
                                            $selected = ($leagueOption === ($row['league'] ?? '')) ? ' selected' : '';
                                            ?>
                                            <option value="<?php echo e($leagueOption); ?>"<?php echo $selected; ?>><?php echo e($leagueOption); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="map1" class="col-sm-3 col-form-label">Map 1 <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="map1" name="map1" type="text" class="form-control" maxlength="100" value="<?php echo $map1Esc; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="map2" class="col-sm-3 col-form-label">Map 2 <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="col-sm-9">
                                    <input id="map2" name="map2" type="text" class="form-control" maxlength="100" value="<?php echo $map2Esc; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="map3" class="col-sm-3 col-form-label">Map 3</label>
                                <div class="col-sm-9">
                                    <input id="map3" name="map3" type="text" class="form-control" maxlength="100" value="<?php echo $map3Esc; ?>">
                                    <div class="form-text">Optional &mdash; die dritte Map.</div>
                                </div>
                            </div>
                            <fieldset class="row mb-3">
                                <legend class="col-sm-3 col-form-label pt-0">Termin <span class="text-danger" aria-hidden="true">*</span></legend>
                                <div class="col-sm-9">
                                    <div class="row g-2">
                                        <div class="col-6 col-md-3">
                                            <label for="time_day" class="form-label small">Tag</label>
                                            <input id="time_day" name="time_day" type="number" min="1" max="31" class="form-control" value="<?php echo (int) $day; ?>" required>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label for="time_month" class="form-label small">Monat</label>
                                            <select id="time_month" name="time_month" class="form-select">
                                                <?php foreach ($months as $mNum => $mLabel): ?>
                                                    <option value="<?php echo $mNum; ?>"<?php echo $mNum === $month ? ' selected' : ''; ?>><?php echo e($mLabel); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label for="time_year" class="form-label small">Jahr</label>
                                            <select id="time_year" name="time_year" class="form-select">
                                                <?php for ($i = $curyear - 5; $i <= $curyear + 5; $i++): ?>
                                                    <option value="<?php echo $i; ?>"<?php echo $i === $year ? ' selected' : ''; ?>><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label for="time_hour" class="form-label small">Stunde</label>
                                            <select id="time_hour" name="time_hour" class="form-select">
                                                <?php for ($i = 0; $i <= 23; $i++): ?>
                                                    <option value="<?php echo $i; ?>"<?php echo $i === $hour ? ' selected' : ''; ?>><?php echo str_pad((string) $i, 2, '0', STR_PAD_LEFT); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label for="time_minute" class="form-label small">Minute</label>
                                            <select id="time_minute" name="time_minute" class="form-select">
                                                <?php foreach ([0, 15, 30, 45] as $mm): ?>
                                                    <option value="<?php echo $mm; ?>"<?php echo $mm === $minute ? ' selected' : ''; ?>><?php echo str_pad((string) $mm, 2, '0', STR_PAD_LEFT); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="row mb-3">
                                <label for="res1" class="col-sm-3 col-form-label">Ergebnis Map 1</label>
                                <div class="col-sm-9">
                                    <input id="res1" name="res1" type="text" class="form-control" maxlength="10" value="<?php echo $res1Esc; ?>" placeholder="z. B. 16:14">
                                    <div class="form-text">Format: <code>EIGENES:GEGNER</code>. Leer lassen wenn noch offen.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="res2" class="col-sm-3 col-form-label">Ergebnis Map 2</label>
                                <div class="col-sm-9">
                                    <input id="res2" name="res2" type="text" class="form-control" maxlength="10" value="<?php echo $res2Esc; ?>" placeholder="z. B. 16:14">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="res3" class="col-sm-3 col-form-label">Ergebnis Map 3</label>
                                <div class="col-sm-9">
                                    <input id="res3" name="res3" type="text" class="form-control" maxlength="10" value="<?php echo $res3Esc; ?>" placeholder="z. B. 16:14">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label for="report" class="col-sm-3 col-form-label">Warbericht</label>
                                <div class="col-sm-9">
                                    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="BBCode-Schnellbefehle">
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('b')"><strong>Fett</strong></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('u')"><u>Unterstrichen</u></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('i')"><em>Kursiv</em></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('url')">URL</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('email')">E-Mail</button>
                                    </div>
                                    <textarea id="report" name="report" class="form-control" rows="10"><?php echo $reportEsc; ?></textarea>
                                    <div class="form-text">Optionaler Bericht über den Clanwar. Unterstützt BBCode wie <code>[b]...[/b]</code>.</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">War editieren</button>
                                <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                                <a class="btn btn-link ms-auto" href="choosewar.php">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-body-secondary">
                        <h2 class="h5 mb-0">Screenshots hochladen</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-body-secondary small mb-3">Optional &mdash; nur JPG. Pro Map ein Screenshot.</p>
                        <?php for ($mapNum = 1; $mapNum <= 3; $mapNum++):
                            $screenField = 'screen' . $mapNum;
                            $screenValue = $row[$screenField] ?? '';
                            ?>
                            <form action="<?php echo $phpSelf; ?>?uploadscreen=YES&warid=<?php echo $rowId; ?>&map=<?php echo $mapNum; ?>" method="post" enctype="multipart/form-data" class="mb-3 pb-3 border-bottom">
                                <?php echo csrf_field(); ?>
                                <div class="row align-items-center g-2">
                                    <label for="screen<?php echo $mapNum; ?>" class="col-sm-3 col-form-label">Screenshot Map <?php echo $mapNum; ?></label>
                                    <div class="col-sm-6">
                                        <input id="screen<?php echo $mapNum; ?>" name="screen<?php echo $mapNum; ?>" type="file" class="form-control" accept="image/jpeg">
                                        <?php if (!empty($screenValue)): ?>
                                            <div class="form-text">
                                                Aktuell: <a class="link-secondary" href="../showpic.php?path=images/wars/<?php echo e($screenValue); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($screenValue); ?></a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-sm-3">
                                        <button type="submit" class="btn btn-outline-primary w-100">Hochladen</button>
                                    </div>
                                </div>
                            </form>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php
            }
        } else {
            $stmt->close();
            echo '<div class="alert alert-warning" role="alert">'
                . 'Der gewählte Wareintrag existiert nicht. '
                . '<a class="alert-link" href="choosewar.php">Zur Übersicht</a></div>';
        }
    } else {
        echo '<div class="alert alert-warning" role="alert">'
            . 'Bitte wähle einen Wareintrag aus. '
            . '<a class="alert-link" href="choosewar.php">Zur Übersicht</a></div>';
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
