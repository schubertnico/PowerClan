<?php

declare(strict_types=1);

/**
 * PowerClan - Installer
 *
 * Wird nach dem ersten erfolgreichen Lauf per `install.lock` deaktiviert.
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 */

$lockFile = __DIR__ . '/install.lock';
if (is_file($lockFile)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Installation gesperrt</title>'
       . '<link rel="stylesheet" href="assets/bootstrap-5.3.3/css/bootstrap.min.css">'
       . '</head><body class="bg-body-tertiary">'
       . '<main class="container py-5">'
       . '<div class="card shadow-sm mx-auto" style="max-width: 640px">'
       . '<div class="card-body">'
       . '<h1 class="h3 mb-3">PowerClan ist bereits installiert.</h1>'
       . '<p class="mb-0">Entferne <code>install.lock</code> manuell, wenn Du neu installieren möchtest.</p>'
       . '</div></div></main></body></html>';
    exit;
}

// Security-Header
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

function install_csrf_ok(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    return isset($_POST['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals((string) $_SESSION['install_csrf'], (string) $_POST['csrf_token']);
}

function install_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars((string) $_SESSION['install_csrf'], ENT_QUOTES, 'UTF-8') . '">';
}

if (!install_csrf_ok()) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
       . '<link rel="stylesheet" href="assets/bootstrap-5.3.3/css/bootstrap.min.css">'
       . '</head><body class="bg-body-tertiary"><main class="container py-5">'
       . '<div class="alert alert-danger" role="alert"><strong>Ungültiges CSRF-Token.</strong></div>'
       . '</main></body></html>';
    exit;
}

function generate_password(int $length = 16): string
{
    $bytes = max(1, intdiv($length, 2));
    /** @var int<1, max> $bytes */
    return bin2hex(random_bytes($bytes));
}

function install_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$type = (string) ($_GET['type'] ?? '');
$page = (string) ($_GET['page'] ?? '');
/** @var array<string, mixed> $mysql */
$mysql = is_array($_POST['mysql'] ?? null) ? $_POST['mysql'] : [];
$configfile = (string) ($_POST['configfile'] ?? $_GET['configfile'] ?? '');
$mysqltables = (string) ($_POST['mysqltables'] ?? $_GET['mysqltables'] ?? '');
$nickname = trim((string) ($_POST['nickname'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

$self = install_e((string) $_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerClan Installation</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="powerclan.css">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand fw-semibold">PowerClan Installer</span>
        <span class="badge text-bg-secondary">Setup</span>
    </div>
</nav>
<main class="container pb-5">
    <div class="row g-4">
        <aside class="col-12 col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-body-secondary small mb-2">Aktionen</h2>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link p-1<?= $type === 'install' ? ' active' : ''; ?>" href="<?= $self; ?>?type=install">Installation</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link p-1<?= $type === 'update' ? ' active' : ''; ?>" href="<?= $self; ?>?type=update&amp;version=1.0">Update von 1.0</a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
        <section class="col-12 col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
<?php
switch ($type) {
    default:
        ?>
        <h1 class="h3 mb-3">Willkommen beim PowerClan-Installer</h1>
        <p class="mb-3">
            Wähle links die gewünschte Aktion. <strong>Achtung:</strong> Bei einer Installation werden
            bestehende Datenbank-Tabellen mit <code>pc_*</code>-Prefix überschrieben.
        </p>
        <a class="btn btn-primary" href="<?= $self; ?>?type=install">Installation starten</a>
        <?php
        break;

    case 'install':
        switch ($page) {
            default:
                ?>
                <h1 class="h3 mb-4">Installation &mdash; Schritt 1 von 4: Optionen</h1>
                <form action="<?= $self; ?>?type=install&amp;page=1" method="post">
                    <?= install_csrf_field(); ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="optTables" name="mysqltables" value="YES" checked>
                        <label class="form-check-label" for="optTables">mySQL-Tabellen automatisch anlegen</label>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="optConfig" name="configfile" value="YES" checked>
                        <label class="form-check-label" for="optConfig">Konfigurationsdatei automatisch anlegen</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Weiter &raquo;</button>
                </form>
                <?php
                break;

            case '1':
                $mysqltablesSafe = install_e($mysqltables);
                $configfileSafe = install_e($configfile);
                ?>
                <h1 class="h3 mb-4">Installation &mdash; Schritt 2 von 4: Datenbank</h1>
                <?php if ($configfile === 'YES'): ?>
                    <form action="<?= $self; ?>?type=install&amp;page=2&amp;configfile=<?= $configfileSafe; ?>&amp;mysqltables=<?= $mysqltablesSafe; ?>" method="post" novalidate>
                        <?= install_csrf_field(); ?>
                        <div class="row mb-3">
                            <label for="mysql_host" class="col-sm-4 col-form-label">mySQL-Server</label>
                            <div class="col-sm-8">
                                <input id="mysql_host" name="mysql[host]" type="text" class="form-control" maxlength="200" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="mysql_database" class="col-sm-4 col-form-label">Datenbank</label>
                            <div class="col-sm-8">
                                <input id="mysql_database" name="mysql[database]" type="text" class="form-control" maxlength="200" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="mysql_user" class="col-sm-4 col-form-label">User</label>
                            <div class="col-sm-8">
                                <input id="mysql_user" name="mysql[user]" type="text" class="form-control" maxlength="200" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="mysql_password" class="col-sm-4 col-form-label">Passwort</label>
                            <div class="col-sm-8">
                                <input id="mysql_password" name="mysql[password]" type="password" class="form-control" maxlength="200">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="mysql_port" class="col-sm-4 col-form-label">Port</label>
                            <div class="col-sm-8">
                                <input id="mysql_port" name="mysql[port]" type="number" class="form-control" maxlength="5" value="3306" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Weiter &raquo;</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Lege <code>config.inc.php</code> manuell an und klicke dann auf Weiter.
                    </div>
                    <a class="btn btn-primary" href="<?= $self; ?>?type=install&amp;page=2&amp;mysqltables=<?= $mysqltablesSafe; ?>">Weiter &raquo;</a>
                <?php endif; ?>
                <?php
                break;

            case '2':
                $conn = null;
                if ($configfile === 'YES') {
                    if (empty($mysql['host']) || empty($mysql['user']) || empty($mysql['database']) || empty($mysql['port'])) {
                        echo '<div class="alert alert-danger" role="alert">Bitte gib mySQL-Server, User und Datenbank an. <a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                        break;
                    }
                    try {
                        $conn = new mysqli(
                            (string) $mysql['host'],
                            (string) $mysql['user'],
                            (string) ($mysql['password'] ?? ''),
                            (string) $mysql['database'],
                            (int) $mysql['port']
                        );
                    } catch (mysqli_sql_exception) {
                        echo '<div class="alert alert-danger" role="alert">Es konnte keine mySQL-Verbindung hergestellt werden. <a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                        break;
                    }
                    $filecontent = "<?php\ndeclare(strict_types=1);\n\$mysql = "
                        . var_export([
                            'host' => (string) $mysql['host'],
                            'user' => (string) $mysql['user'],
                            'password' => (string) ($mysql['password'] ?? ''),
                            'database' => (string) $mysql['database'],
                            'port' => (int) $mysql['port'],
                        ], true)
                        . ";\n\$settings = ['tablebg1' => '#000000', 'footer' => ''];\n\$version = 2.30;\n";
                    if (file_put_contents(__DIR__ . '/config.inc.php', $filecontent) === false) {
                        echo '<div class="alert alert-danger" role="alert">Die Konfigurationsdatei konnte nicht geschrieben werden! <a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                        break;
                    }
                    if (!copy(__DIR__ . '/config.inc.php', __DIR__ . '/admin/config.inc.php')) {
                        echo '<div class="alert alert-danger" role="alert">Die Konfigurationsdatei konnte nicht in den Adminbereich kopiert werden! <a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                        break;
                    }
                } else {
                    if (!file_exists(__DIR__ . '/config.inc.php')) {
                        echo '<div class="alert alert-warning" role="alert">Es existiert keine Konfigurationsdatei.</div>';
                        break;
                    }
                    require __DIR__ . '/config.inc.php';
                    try {
                        $conn = new mysqli(
                            (string) $mysql['host'],
                            (string) $mysql['user'],
                            (string) ($mysql['password'] ?? ''),
                            (string) $mysql['database'],
                            (int) $mysql['port']
                        );
                    } catch (mysqli_sql_exception) {
                        echo '<div class="alert alert-danger" role="alert">Es konnte keine mySQL-Verbindung hergestellt werden.</div>';
                        break;
                    }
                }

                if ($mysqltables === 'YES') {
                    $conn->query('DROP TABLE IF EXISTS pc_config');
                    $conn->query('DROP TABLE IF EXISTS pc_members');
                    $conn->query('DROP TABLE IF EXISTS pc_news');
                    $conn->query('DROP TABLE IF EXISTS pc_wars');
                    $conn->query('CREATE TABLE pc_config (id int(11) NOT NULL auto_increment, clanname varchar(150) NOT NULL, clantag varchar(10) NOT NULL, url varchar(250) NOT NULL, serverpath varchar(250) NOT NULL, header varchar(200) NOT NULL, footer varchar(200) NOT NULL, tablebg1 varchar(7) NOT NULL, tablebg2 varchar(7) NOT NULL, tablebg3 varchar(7) NOT NULL, clrwon varchar(7) NOT NULL, clrdraw varchar(7) NOT NULL, clrlost varchar(7) NOT NULL, newslimit int(2) NOT NULL, warlimit int(2) NOT NULL, PRIMARY KEY (id))');
                    $conn->query("CREATE TABLE pc_members (id int(11) NOT NULL auto_increment, nick varchar(100) NOT NULL, email varchar(200) NOT NULL, password varchar(255) NOT NULL, work varchar(200) NOT NULL, realname varchar(250) NOT NULL, icq int(10) DEFAULT 0 NOT NULL, homepage varchar(250) NOT NULL, age int(3) DEFAULT 0 NOT NULL, hardware text NOT NULL, info text NOT NULL, pic varchar(250) NOT NULL, member_add enum('YES','NO') DEFAULT 'NO' NOT NULL, member_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, member_del enum('YES','NO') DEFAULT 'NO' NOT NULL, news_add enum('YES','NO') DEFAULT 'NO' NOT NULL, news_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, news_del enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_add enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_del enum('YES','NO') DEFAULT 'NO' NOT NULL, superadmin enum('YES','NO') DEFAULT 'NO' NOT NULL, PRIMARY KEY (id))");
                    $conn->query('CREATE TABLE pc_news (id int(11) NOT NULL auto_increment, time int(14) DEFAULT 0 NOT NULL, userid int(11) NOT NULL, nick varchar(100) NOT NULL, email varchar(250) NOT NULL, title varchar(150) NOT NULL, text text NOT NULL, PRIMARY KEY (id))');
                    $conn->query('CREATE TABLE pc_wars (id int(11) NOT NULL auto_increment, enemy varchar(150) NOT NULL, enemy_tag varchar(10) NOT NULL, homepage varchar(250) NOT NULL, league varchar(150) NOT NULL, map1 varchar(100) NOT NULL, map2 varchar(100) NOT NULL, map3 varchar(100) NOT NULL, time int(14) DEFAULT 0 NOT NULL, report text NOT NULL, res1 varchar(50) NOT NULL, res2 varchar(50) NOT NULL, res3 varchar(50) NOT NULL, screen1 varchar(200) NOT NULL, screen2 varchar(200) NOT NULL, screen3 varchar(200) NOT NULL, PRIMARY KEY (id))');
                    $conn->query("INSERT INTO pc_config (clanname, clantag, url, serverpath, header, footer, tablebg1, tablebg2, tablebg3, clrwon, clrdraw, clrlost, newslimit, warlimit) VALUES('PowerClan', 'PC', 'https://www.powerscripts.org/', '', 'header.pc', 'footer.pc', '#A0A0A0', '#F0F0F0', '#E0E0E0', '#008000', '#808080', '#800000', '10', '10')");
                }
                ?>
                <div class="alert alert-success" role="alert">
                    Konfigurationsdateien und/oder Tabellen wurden erfolgreich erstellt.
                </div>
                <a class="btn btn-primary" href="<?= $self; ?>?type=install&amp;page=3">Weiter &raquo;</a>
                <?php
                break;

            case '3':
                ?>
                <h1 class="h3 mb-4">Installation &mdash; Schritt 3 von 4: Superadmin</h1>
                <form action="<?= $self; ?>?type=install&amp;page=4" method="post" novalidate>
                    <?= install_csrf_field(); ?>
                    <div class="row mb-3">
                        <label for="nickname" class="col-sm-4 col-form-label">Dein Nickname</label>
                        <div class="col-sm-8">
                            <input id="nickname" name="nickname" type="text" class="form-control" maxlength="100" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="email" class="col-sm-4 col-form-label">Deine E-Mail-Adresse</label>
                        <div class="col-sm-8">
                            <input id="email" name="email" type="email" class="form-control" maxlength="250" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Weiter &raquo;</button>
                </form>
                <?php
                break;

            case '4':
                if ($nickname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo '<div class="alert alert-danger" role="alert">Bitte gib einen Nickname und eine gültige E-Mail-Adresse an. <a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
                    break;
                }
                if (!file_exists(__DIR__ . '/config.inc.php')) {
                    echo '<div class="alert alert-warning" role="alert">Es existiert keine Konfigurationsdatei.</div>';
                    break;
                }
                require __DIR__ . '/config.inc.php';
                try {
                    $conn = new mysqli(
                        (string) $mysql['host'],
                        (string) $mysql['user'],
                        (string) ($mysql['password'] ?? ''),
                        (string) $mysql['database'],
                        (int) $mysql['port']
                    );
                } catch (mysqli_sql_exception) {
                    echo '<div class="alert alert-danger" role="alert">Es konnte keine mySQL-Verbindung hergestellt werden.</div>';
                    break;
                }

                $password = generate_password(16);
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    'INSERT INTO pc_members (nick, email, password, work, superadmin, realname, homepage, hardware, info, pic)'
                    . " VALUES (?, ?, ?, 'Webmaster', 'YES', '', '', '', '', '')"
                );
                if ($stmt === false) {
                    echo '<div class="alert alert-danger" role="alert">Der Superadmin konnte nicht angelegt werden.</div>';
                    break;
                }
                $stmt->bind_param('sss', $nickname, $email, $hash);
                $stmt->execute();
                $stmt->close();

                file_put_contents($lockFile, sprintf(
                    "installed at %s by nickname=%s email=%s\n",
                    date('c'),
                    $nickname,
                    $email
                ));

                $safeHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $safeHost = preg_replace('/[^A-Za-z0-9\.\-:]/', '', $safeHost) ?? 'localhost';

                $ok = @mail(
                    $email,
                    'PowerClan Installation',
                    sprintf(
                        "Hallo %s,\n\nDu hast PowerClan erfolgreich installiert (%s).\n\nLogin-E-Mail: %s\nPasswort: %s\n\nBitte logge Dich ein und ändere das Passwort.\n",
                        $nickname,
                        $safeHost,
                        $email,
                        $password
                    ),
                    "From: PowerClan Automailer <noreply@localhost>\r\nX-Mailer: PowerClan"
                );
                ?>
                <h1 class="h3 mb-3">Installation abgeschlossen</h1>
                <div class="alert alert-success" role="alert">
                    Du kannst Dich jetzt unter <code>admin/</code> einloggen.
                </div>
                <?php if ($ok): ?>
                    <p>Dein Passwort wurde Dir per E-Mail zugesandt.</p>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        <strong>Hinweis:</strong> Die E-Mail konnte nicht verschickt werden.
                        Bitte notiere Dir jetzt Dein Passwort:
                        <code><?= install_e($password); ?></code>
                    </div>
                <?php endif; ?>
                <p class="text-body-secondary small">Bitte lösche <code>install.php</code> zusätzlich vom Server.</p>
                <a class="btn btn-primary" href="admin/">Zum Admin-Login &raquo;</a>
                <?php
                break;
        }
        break;

    case 'update':
        ?>
        <h1 class="h3 mb-3">Update von 1.0</h1>
        <div class="alert alert-info" role="alert">
            Update-Funktion derzeit nicht verfügbar. Bitte manuell durchführen.
        </div>
        <?php
        break;
}
?>
                </div>
            </div>
        </section>
    </div>
</main>
<footer class="border-top bg-body py-3">
    <div class="container text-center text-body-secondary small">
        PowerClan &copy; Copyright 2001-2026 by
        <a class="link-secondary" href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts</a>
    </div>
</footer>
<script src="assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
