<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Edit Configuration (Superadmin Only)
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

if (($pcadmin['superadmin'] ?? '') === 'YES') {
    $stmt = db_prepare($conn, 'SELECT * FROM pc_config WHERE id = 1');
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        throw new RuntimeException('Failed to get result');
    }
    $num = mysqli_num_rows($result);

    if ($num === 1) {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $stmt->close();

        $editconfig = $_GET['editconfig'] ?? '';

        if ($editconfig === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get and validate all fields
            $clanname = trim($_POST['clanname'] ?? '');
            $clantag = trim($_POST['clantag'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $serverpath = trim($_POST['serverpath'] ?? '');
            $header = trim($_POST['header'] ?? '');
            $footer = trim($_POST['footer'] ?? '');
            $tablebg1 = trim($_POST['tablebg1'] ?? '');
            $tablebg2 = trim($_POST['tablebg2'] ?? '');
            $tablebg3 = trim($_POST['tablebg3'] ?? '');
            $clrwon = trim($_POST['clrwon'] ?? '');
            $clrdraw = trim($_POST['clrdraw'] ?? '');
            $clrlost = trim($_POST['clrlost'] ?? '');
            $newslimit = trim($_POST['newslimit'] ?? '');
            $warlimit = trim($_POST['warlimit'] ?? '');

            // Validate required fields
            if (
                empty($clanname) || empty($clantag) || empty($url) || empty($serverpath) ||
                empty($header) || empty($footer) || empty($tablebg1) || empty($tablebg2) ||
                empty($tablebg3) || empty($clrwon) || empty($clrdraw) || empty($clrlost) ||
                empty($newslimit) || empty($warlimit)
            ) {
                echo '<div class="alert alert-danger" role="alert">'
                    . 'Bitte fülle alle Felder aus! '
                    . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            } else {
                // Use prepared statement to prevent SQL injection
                $updateStmt = db_prepare($conn, 'UPDATE pc_config SET
                    clanname = ?, clantag = ?, url = ?, serverpath = ?,
                    header = ?, footer = ?, tablebg1 = ?, tablebg2 = ?,
                    tablebg3 = ?, clrwon = ?, clrdraw = ?, clrlost = ?,
                    newslimit = ?, warlimit = ?
                    WHERE id = 1');

                $updateStmt->bind_param(
                    'ssssssssssssss',
                    $clanname,
                    $clantag,
                    $url,
                    $serverpath,
                    $header,
                    $footer,
                    $tablebg1,
                    $tablebg2,
                    $tablebg3,
                    $clrwon,
                    $clrdraw,
                    $clrlost,
                    $newslimit,
                    $warlimit
                );
                $updateStmt->execute();
                $updateStmt->close();

                $phpSelf = e($_SERVER['PHP_SELF']);
                echo '<div class="alert alert-success" role="alert">'
                    . 'Die Konfiguration wurde erfolgreich geändert. '
                    . '<a class="alert-link" href="' . $phpSelf . '">Erneut öffnen</a></div>';
            }
        } else {
            // Display form
            $phpSelf = e($_SERVER['PHP_SELF']);
            $clanname = e($row['clanname'] ?? '');
            $clantag = e($row['clantag'] ?? '');
            $urlVal = e($row['url'] ?? '');
            $serverpath = e($row['serverpath'] ?? '');
            $headerVal = e($row['header'] ?? '');
            $footerVal = e($row['footer'] ?? '');
            $tablebg1 = e($row['tablebg1'] ?? '');
            $tablebg2 = e($row['tablebg2'] ?? '');
            $tablebg3 = e($row['tablebg3'] ?? '');
            $clrwon = e($row['clrwon'] ?? '');
            $clrdraw = e($row['clrdraw'] ?? '');
            $clrlost = e($row['clrlost'] ?? '');
            $newslimit = e($row['newslimit'] ?? '');
            $warlimit = e($row['warlimit'] ?? '');
            ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body-secondary">
                    <h1 class="h4 mb-0">Konfiguration editieren</h1>
                </div>
                <div class="card-body">
                    <form action="<?php echo $phpSelf; ?>?editconfig=YES" method="post" novalidate>
                        <?php echo csrf_field(); ?>
                        <div class="row mb-3">
                            <label for="clanname" class="col-sm-3 col-form-label">Clanname <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="clanname" name="clanname" type="text" class="form-control" maxlength="150" value="<?php echo $clanname; ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="clantag" class="col-sm-3 col-form-label">Clantag <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="clantag" name="clantag" type="text" class="form-control" maxlength="10" value="<?php echo $clantag; ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="url" class="col-sm-3 col-form-label">Clanpage-URL <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="url" name="url" type="url" class="form-control" maxlength="250" value="<?php echo $urlVal; ?>" required aria-describedby="urlHelp">
                                <div id="urlHelp" class="form-text">Vollständige URL zum PowerClan-Verzeichnis.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="serverpath" class="col-sm-3 col-form-label">Serverpfad <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="serverpath" name="serverpath" type="text" class="form-control" maxlength="250" value="<?php echo $serverpath; ?>" required aria-describedby="serverpathHelp">
                                <div id="serverpathHelp" class="form-text">Aktuell: <code><?php echo $serverpath; ?></code></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="header" class="col-sm-3 col-form-label">Header-Datei <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="header" name="header" type="text" class="form-control" maxlength="250" value="<?php echo $headerVal; ?>" required>
                                <div class="form-text">Datei mit dem Quellcode für den externen Header (z. B. <code>header.pc</code>).</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="footer" class="col-sm-3 col-form-label">Footer-Datei <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="footer" name="footer" type="text" class="form-control" maxlength="250" value="<?php echo $footerVal; ?>" required>
                            </div>
                        </div>
                        <fieldset class="row mb-3">
                            <legend class="col-sm-3 col-form-label pt-0">Tabellenhintergrund</legend>
                            <div class="col-sm-9">
                                <?php foreach ([
                                    ['tablebg1', $tablebg1, 'Hauptfarbe'],
                                    ['tablebg2', $tablebg2, 'Sekundärfarbe 1'],
                                    ['tablebg3', $tablebg3, 'Sekundärfarbe 2'],
                                ] as [$fieldName, $fieldValue, $fieldLabel]): ?>
                                    <div class="row g-2 align-items-center mb-2">
                                        <label class="col-form-label col-4 col-md-3" for="cfg_<?php echo $fieldName; ?>"><?php echo e($fieldLabel); ?></label>
                                        <div class="col-5 col-md-4">
                                            <input id="cfg_<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>" type="text" class="form-control" maxlength="7" value="<?php echo $fieldValue; ?>" required>
                                        </div>
                                        <div class="col-3 col-md-3">
                                            <span class="d-inline-block w-100 rounded border" style="height: 1.8rem; background-color: <?php echo $fieldValue; ?>;" aria-label="Vorschau <?php echo e($fieldLabel); ?>" title="Vorschau"></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="form-text">Drei Hex-Farben für den klassischen Tabellenhintergrund (z. B. <code>#A0A0A0</code>).</div>
                            </div>
                        </fieldset>
                        <fieldset class="row mb-3">
                            <legend class="col-sm-3 col-form-label pt-0">Warstatusfarben</legend>
                            <div class="col-sm-9">
                                <?php foreach ([
                                    ['clrwon', $clrwon, 'Gewonnen'],
                                    ['clrdraw', $clrdraw, 'Unentschieden'],
                                    ['clrlost', $clrlost, 'Verloren'],
                                ] as [$fieldName, $fieldValue, $fieldLabel]): ?>
                                    <div class="row g-2 align-items-center mb-2">
                                        <label class="col-form-label col-4 col-md-3" for="cfg_<?php echo $fieldName; ?>"><?php echo e($fieldLabel); ?></label>
                                        <div class="col-5 col-md-4">
                                            <input id="cfg_<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>" type="text" class="form-control" maxlength="7" value="<?php echo $fieldValue; ?>" required>
                                        </div>
                                        <div class="col-3 col-md-3">
                                            <span class="d-inline-block w-100 rounded border" style="height: 1.8rem; background-color: <?php echo $fieldValue; ?>;" aria-label="Vorschau <?php echo e($fieldLabel); ?>" title="Vorschau"></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <div class="row mb-3">
                            <label for="newslimit" class="col-sm-3 col-form-label">Newslimit <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="newslimit" name="newslimit" type="number" min="1" max="99" class="form-control" value="<?php echo $newslimit; ?>" required>
                                <div class="form-text">Anzeigelimit für News auf der externen Seite.</div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="warlimit" class="col-sm-3 col-form-label">Warlimit <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="col-sm-9">
                                <input id="warlimit" name="warlimit" type="number" min="1" max="99" class="form-control" value="<?php echo $warlimit; ?>" required>
                                <div class="form-text">Anzeigelimit für Wars auf der externen Seite.</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Konfiguration editieren</button>
                            <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                        </div>
                        <p class="form-text mt-3 mb-0">
                            Hinweis: Das PowerClan-Verzeichnis ist das Verzeichnis, in dem die externe <code>index.php</code> liegt.
                        </p>
                    </form>
                </div>
            </div>
            <?php
        }
    } else {
        $stmt->close();
        echo '<div class="alert alert-danger" role="alert">Fehler beim Laden der Konfiguration.</div>';
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
