<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Dashboard
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

?>
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAINPAGE-->

Willkommen im Adminbereich von <b>PowerClan</b>.<br>
<br>
Hier kannst Du als Member <a href="profile.php">dein Profil</a> editieren oder als Admin die Member, News und
Wars verwalten.<br>
<br>
Du hast im Adminbereich folgende Rechte:<br>
<br>
<ul>
<?php
$i = 0;
if (($pcadmin['member_add'] ?? '') === 'YES') {
    echo "<li>Member hinzuf&uuml;gen</li>\n";
    $i++;
}
if (($pcadmin['member_edit'] ?? '') === 'YES') {
    echo "<li>Member editieren</li>\n";
    $i++;
}
if (($pcadmin['member_del'] ?? '') === 'YES') {
    echo "<li>Member l&ouml;schen</li>\n";
    $i++;
}
if (($pcadmin['news_add'] ?? '') === 'YES') {
    echo "<li>News hinzuf&uuml;gen</li>\n";
    $i++;
}
if (($pcadmin['news_edit'] ?? '') === 'YES') {
    echo "<li>News editieren</li>\n";
    $i++;
}
if (($pcadmin['news_del'] ?? '') === 'YES') {
    echo "<li>News l&ouml;schen</li>\n";
}
if (($pcadmin['wars_add'] ?? '') === 'YES') {
    echo "<li>Wars hinzuf&uuml;gen</li>\n";
    $i++;
}
if (($pcadmin['wars_edit'] ?? '') === 'YES') {
    echo "<li>Wars editieren</li>\n";
    $i++;
}
if (($pcadmin['wars_del'] ?? '') === 'YES') {
    echo "<li>Wars l&ouml;schen</li>\n";
    $i++;
}
if (($pcadmin['superadmin'] ?? '') === 'YES') {
    echo "<li>Alle Rechte + Konfiguration editieren</li>\n";
    $i++;
}
if ($i === 0) {
    echo "<li>Du hast <b>keine</b> Adminrechte</li>\n";
}
?>
</ul>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
