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

<center>
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
                echo '<center><a href="javascript:history.back()">Bitte f&uuml;lle alle Felder aus!</a></center>';
            } else {
                // Use prepared statement to prevent SQL injection
                $updateStmt = db_prepare($conn,'UPDATE pc_config SET
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
                echo "<center><a href=\"{$phpSelf}\">Die Konfiguration wurde erfolgreich ge&auml;ndert!</a></center>";
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

            echo "
<center>
<form action=\"{$phpSelf}?editconfig=YES\" method=\"post\">
" . csrf_field() . "
<table border=\"0\" cellpadding=\"3\" cellspacing=\"2\" width=\"100%\">
<tr><td colspan=\"2\" align=\"center\">
<b>Konfiguration editieren</b>
</td></tr>
<tr><td valign=\"top\" width=\"*\" bgcolor=\"{$admin_tbl1}\">
<b>Clanname</b><br>
<small>Der Name Deines Clans</small>
</td><td valign=\"top\" width=\"400\" bgcolor=\"{$admin_tbl1}\">
<input name=\"clanname\" size=\"25\" maxlength=\"150\" value=\"{$clanname}\" required>
</td></tr>
<tr><td valign=\"top\">
<b>Clantag</b><br>
<small>Das K&uuml;rzel Deines Clans</small>
</td><td valign=\"top\">
<input name=\"clantag\" size=\"10\" maxlength=\"10\" value=\"{$clantag}\" required>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Clanpage-URL</b><br>
<small>Die vollst&auml;ndige URL zum PowerClan Verzeichnis(*)</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"url\" size=\"25\" maxlength=\"250\" value=\"{$urlVal}\" type=\"url\" required>
</td></tr>
<tr><td valign=\"top\">
<b>Serverpfad</b><br>
<small>Der absolute Pfad zum PowerClan Verzeichnis (*)</small>
</td><td valign=\"top\">
<small>{$serverpath}</small> <input name=\"serverpath\" size=\"25\" maxlength=\"250\" value=\"{$serverpath}\" required>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Header-Datei</b><br>
<small>Die Datei mit dem Quellcode f&uuml;r den externen Header</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"header\" size=\"25\" maxlength=\"250\" value=\"{$headerVal}\" required>
</td></tr>
<tr><td valign=\"top\">
<b>Footer-Datei</b><br>
<small>Die Datei mit dem Quellcode f&uuml;r den externen Footer</small>
</td><td valign=\"top\">
<input name=\"footer\" size=\"25\" maxlength=\"250\" value=\"{$footerVal}\" required>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Tabellenhintergrund</b><br>
<small>Die drei Farben f&uuml;r den externen Tabellenhintergrund</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
  <table border=\"0\" cellpadding=\"0\" cellspacing=\"3\" width=\"100%\">
  <tr><td>
  <input name=\"tablebg1\" size=\"7\" maxlength=\"7\" value=\"{$tablebg1}\" required>
  </td><td bgcolor=\"{$tablebg1}\" width=\"25\">
  &nbsp;
  </td></tr>
  <tr><td>
  <input name=\"tablebg2\" size=\"7\" maxlength=\"7\" value=\"{$tablebg2}\" required>
  </td><td bgcolor=\"{$tablebg2}\" width=\"25\">
  &nbsp;
  </td></tr>
  <tr><td>
  <input name=\"tablebg3\" size=\"7\" maxlength=\"7\" value=\"{$tablebg3}\" required>
  </td><td bgcolor=\"{$tablebg3}\" width=\"25\">
  &nbsp;
  </td></tr>
  </table>
</td></tr>
<tr><td valign=\"top\">
<b>Warstatusfarben</b><br>
<small>Die drei Farben f&uuml;r den Warstatus</small>
</td><td valign=\"top\">
  <table border=\"0\" cellpadding=\"0\" cellspacing=\"3\" width=\"100%\">
  <tr><td>
  <input name=\"clrwon\" size=\"7\" maxlength=\"7\" value=\"{$clrwon}\" required> (gewonnen)
  </td><td bgcolor=\"{$clrwon}\" width=\"25\">
  &nbsp;
  </td></tr>
  <tr><td>
  <input name=\"clrdraw\" size=\"7\" maxlength=\"7\" value=\"{$clrdraw}\" required> (unentschieden)
  </td><td bgcolor=\"{$clrdraw}\" width=\"25\">
  &nbsp;
  </td></tr>
  <tr><td>
  <input name=\"clrlost\" size=\"7\" maxlength=\"7\" value=\"{$clrlost}\" required> (verloren)
  </td><td bgcolor=\"{$clrlost}\" width=\"25\">
  &nbsp;
  </td></tr>
  </table>
</td></tr>
<tr><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<b>Newslimit</b><br>
<small>Das Anzeigelimit f&uuml;r News auf der externen Seite</small>
</td><td valign=\"top\" bgcolor=\"{$admin_tbl1}\">
<input name=\"newslimit\" size=\"2\" maxlength=\"2\" value=\"{$newslimit}\" type=\"number\" min=\"1\" max=\"99\" required>
</td></tr>
<tr><td valign=\"top\">
<b>Warlimit</b><br>
<small>Das Anzeigelimit f&uuml;r Wars auf der externen Seite</small>
</td><td valign=\"top\">
<input name=\"warlimit\" size=\"2\" maxlength=\"2\" value=\"{$warlimit}\" type=\"number\" min=\"1\" max=\"99\" required>
</td></tr>
<tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$admin_tbl1}\">
<input type=\"submit\" value=\"Konfiguration editieren\"> <input type=\"reset\" value=\"Daten zur&uuml;cksetzen\">
</td></tr>
</table>
</form>
<br>
<small>*: Das PowerClan Verzeichnis ist das Verzeichnis, in dem die externe index.php liegt!</small>
</center>";
        }
    } else {
        $stmt->close();
        echo '<center>Fehler beim Laden der Konfiguration!</center>';
    }
} else {
    echo '<center>Du hast keinen Zugang zu dieser Funktion!</center>';
}
?>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
