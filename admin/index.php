<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Dashboard
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

?>
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAINPAGE-->

<div class="card shadow-sm mb-4">
    <div class="card-header bg-body-secondary">
        <h1 class="h4 mb-0">Willkommen im Adminbereich von PowerClan</h1>
    </div>
    <div class="card-body">
        <p class="mb-3">
            Hier kannst Du als Member <a class="link-primary" href="profile.php">dein Profil</a> editieren oder als
            Admin die Member, News und Wars verwalten.
        </p>
        <h2 class="h6 text-uppercase text-body-secondary small mb-2">Du hast im Adminbereich folgende Rechte</h2>
<?php
$rights = [];
if (($pcadmin['member_add'] ?? '') === 'YES') {
    $rights[] = 'Member hinzufügen';
}
if (($pcadmin['member_edit'] ?? '') === 'YES') {
    $rights[] = 'Member editieren';
}
if (($pcadmin['member_del'] ?? '') === 'YES') {
    $rights[] = 'Member löschen';
}
if (($pcadmin['news_add'] ?? '') === 'YES') {
    $rights[] = 'News hinzufügen';
}
if (($pcadmin['news_edit'] ?? '') === 'YES') {
    $rights[] = 'News editieren';
}
if (($pcadmin['news_del'] ?? '') === 'YES') {
    $rights[] = 'News löschen';
}
if (($pcadmin['wars_add'] ?? '') === 'YES') {
    $rights[] = 'Wars hinzufügen';
}
if (($pcadmin['wars_edit'] ?? '') === 'YES') {
    $rights[] = 'Wars editieren';
}
if (($pcadmin['wars_del'] ?? '') === 'YES') {
    $rights[] = 'Wars löschen';
}
if (($pcadmin['superadmin'] ?? '') === 'YES') {
    $rights[] = 'Alle Rechte + Konfiguration editieren';
}

if ($rights === []) {
    echo '<div class="alert alert-warning mb-0" role="alert">Du hast <strong>keine</strong> Adminrechte.</div>';
} else {
    echo '<div class="d-flex flex-wrap gap-2">';
    foreach ($rights as $right) {
        echo '<span class="badge text-bg-primary fs-6 fw-normal">' . e($right) . '</span>';
    }
    echo '</div>';
}
?>
    </div>
</div>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
