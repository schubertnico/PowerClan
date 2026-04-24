<?php

declare(strict_types=1);

/**
 * PowerClan - Installer
 *
 * Wird nach dem ersten erfolgreichen Lauf per `install.lock` deaktiviert.
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 */

$lockFile = __DIR__ . '/install.lock';
if (is_file($lockFile)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Installation gesperrt</title></head><body>'
       . '<center><h1>PowerClan ist bereits installiert.</h1>'
       . '<p>Entferne <code>install.lock</code> manuell, wenn Du neu installieren möchtest.</p></center>'
       . '</body></html>';
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
    echo '<center><b>Ungültiges CSRF-Token.</b></center>';
    exit;
}

function generate_password(int $length = 16): string
{
    $bytes = max(1, intdiv($length, 2));
    /** @var int<1, max> $bytes */
    return bin2hex(random_bytes($bytes));
}

$type = (string) ($_GET['type'] ?? '');
$page = (string) ($_GET['page'] ?? '');
/** @var array<string, mixed> $mysql */
$mysql = is_array($_POST['mysql'] ?? null) ? $_POST['mysql'] : [];
$configfile = (string) ($_POST['configfile'] ?? $_GET['configfile'] ?? '');
$mysqltables = (string) ($_POST['mysqltables'] ?? $_GET['mysqltables'] ?? '');
$nickname = trim((string) ($_POST['nickname'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

$self = htmlspecialchars((string) $_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>PowerClan Installation</title>
<link rel="stylesheet" href="powerclan.css">
</head>
<body>
<center>
<table border="0" cellpadding="0" cellspacing="0" width="95%">
<tr><td bgcolor="#000080" width="100%">
<table border="0" width="100%" cellpadding="2" cellspacing="1">
<tr><td bgcolor="#E0E0E0" width="125" valign="top">
<br>
<a href="<?= $self ?>?type=install">Installation</a><br>
<br>
<a href="<?= $self ?>?type=update&amp;version=1.0">Update von 1.0</a><br>
<br>
</td><td bgcolor="#F0F0F0" valign="top">
<br>
<?php
switch ($type) {
    default:
        echo '<h2>PowerClan Installation</h2>'
           . 'Beachte, dass bestehende Tabellen überschrieben werden!';
        break;

    case 'install':
        switch ($page) {
            default:
                echo '<form action="' . $self . '?type=install&amp;page=1" method="post">'
                   . install_csrf_field()
                   . '<table border="0" cellpadding="3" cellspacing="0">'
                   . '<tr><td><b>mySQL Tabellen automatisch anlegen</b></td><td><input name="mysqltables" type="checkbox" value="YES" checked></td></tr>'
                   . '<tr><td><b>Konfigurationsdatei automatisch anlegen</b></td><td><input name="configfile" type="checkbox" value="YES" checked></td></tr>'
                   . '</table><div align="right"><input type="submit" value="Weiter &gt;&gt;"></div></form>';
                break;

            case '1':
                $mysqltablesSafe = htmlspecialchars($mysqltables, ENT_QUOTES, 'UTF-8');
                $configfileSafe = htmlspecialchars($configfile, ENT_QUOTES, 'UTF-8');
                if ($configfile === 'YES') {
                    echo '<form action="' . $self
                       . "?type=install&amp;page=2&amp;configfile={$configfileSafe}&amp;mysqltables={$mysqltablesSafe}\" method=\"post\">"
                       . install_csrf_field()
                       . '<table border="0" cellpadding="3" cellspacing="0">'
                       . '<tr><td><b>mySQL Server</b></td><td><input name="mysql[host]" size="50" maxlength="200" required></td></tr>'
                       . '<tr><td><b>mySQL Datenbank</b></td><td><input name="mysql[database]" size="50" maxlength="200" required></td></tr>'
                       . '<tr><td><b>mySQL User</b></td><td><input name="mysql[user]" size="50" maxlength="200" required></td></tr>'
                       . '<tr><td><b>mySQL Passwort</b></td><td><input name="mysql[password]" size="50" maxlength="200" type="password"></td></tr>'
                       . '<tr><td><b>mySQL Port</b></td><td><input name="mysql[port]" size="5" maxlength="5" value="3306" required></td></tr>'
                       . '</table><div align="right"><input type="submit" value="Weiter &gt;&gt;"></div></form>';
                } else {
                    echo 'Lege <code>config.inc.php</code> manuell an und klicke dann auf Weiter.<br><br>'
                       . '<div align="right"><a href="' . $self
                       . "?type=install&amp;page=2&amp;mysqltables={$mysqltablesSafe}\">Weiter &gt;&gt;</a></div>";
                }
                break;

            case '2':
                $conn = null;
                if ($configfile === 'YES') {
                    if (empty($mysql['host']) || empty($mysql['user']) || empty($mysql['database']) || empty($mysql['port'])) {
                        echo '<center><a href="javascript:history.back()">Bitte gib mySQL Server, User und Datenbank an!</a></center>';
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
                        echo '<center><a href="javascript:history.back()">Es konnte keine mySQL Verbindung hergestellt werden.</a></center>';
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
                        . ";\n\$settings = ['tablebg1' => '#000000', 'footer' => ''];\n\$version = 2.00;\n";
                    if (file_put_contents(__DIR__ . '/config.inc.php', $filecontent) === false) {
                        echo '<center><a href="javascript:history.back()">Die Konfigurationsdatei konnte nicht geschrieben werden!</a></center>';
                        break;
                    }
                    if (!copy(__DIR__ . '/config.inc.php', __DIR__ . '/admin/config.inc.php')) {
                        echo '<center><a href="javascript:history.back()">Die Konfigurationsdatei konnte nicht kopiert werden!</a></center>';
                        break;
                    }
                } else {
                    if (!file_exists(__DIR__ . '/config.inc.php')) {
                        echo '<center>Es existiert keine Konfigurationsdatei!</center>';
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
                        echo '<center>Es konnte keine mySQL Verbindung hergestellt werden!</center>';
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
                    $conn->query("CREATE TABLE pc_news (id int(11) NOT NULL auto_increment, time int(14) DEFAULT 0 NOT NULL, userid int(11) NOT NULL, nick varchar(100) NOT NULL, email varchar(250) NOT NULL, title varchar(150) NOT NULL, text text NOT NULL, PRIMARY KEY (id))");
                    $conn->query("CREATE TABLE pc_wars (id int(11) NOT NULL auto_increment, enemy varchar(150) NOT NULL, enemy_tag varchar(10) NOT NULL, homepage varchar(250) NOT NULL, league varchar(150) NOT NULL, map1 varchar(100) NOT NULL, map2 varchar(100) NOT NULL, map3 varchar(100) NOT NULL, time int(14) DEFAULT 0 NOT NULL, report text NOT NULL, res1 varchar(50) NOT NULL, res2 varchar(50) NOT NULL, res3 varchar(50) NOT NULL, screen1 varchar(200) NOT NULL, screen2 varchar(200) NOT NULL, screen3 varchar(200) NOT NULL, PRIMARY KEY (id))");
                    $conn->query("INSERT INTO pc_config (clanname, clantag, url, serverpath, header, footer, tablebg1, tablebg2, tablebg3, clrwon, clrdraw, clrlost, newslimit, warlimit) VALUES('PowerClan', 'PC', 'https://www.powerscripts.org/', '', 'header.pc', 'footer.pc', '#A0A0A0', '#F0F0F0', '#E0E0E0', '#008000', '#808080', '#800000', '10', '10')");
                }
                echo 'Konfigurationsdateien und/oder Tabellen wurden erfolgreich erstellt!<br><br>'
                   . '<div align="right"><a href="' . $self . '?type=install&amp;page=3">Weiter &gt;&gt;</a></div>';
                break;

            case '3':
                echo '<form action="' . $self . '?type=install&amp;page=4" method="post">'
                   . install_csrf_field()
                   . '<table border="0" cellpadding="3" cellspacing="0">'
                   . '<tr><td><b>Dein Nickname</b></td><td><input name="nickname" size="25" maxlength="100" required></td></tr>'
                   . '<tr><td><b>Deine E-Mail Adresse</b></td><td><input name="email" size="25" maxlength="250" type="email" required></td></tr>'
                   . '<tr><td colspan="2" align="right"><input type="submit" value="Weiter &gt;&gt;"></td></tr>'
                   . '</table></form>';
                break;

            case '4':
                if ($nickname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo '<center><a href="javascript:history.back()">Bitte gib einen Nickname und eine gültige E-Mail-Adresse an!</a></center>';
                    break;
                }
                if (!file_exists(__DIR__ . '/config.inc.php')) {
                    echo '<center>Es existiert keine Konfigurationsdatei!</center>';
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
                    echo '<center>Es konnte keine mySQL Verbindung hergestellt werden!</center>';
                    break;
                }

                $password = generate_password(16);
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    'INSERT INTO pc_members (nick, email, password, work, superadmin, realname, homepage, hardware, info, pic)'
                    . " VALUES (?, ?, ?, 'Webmaster', 'YES', '', '', '', '', '')"
                );
                if ($stmt === false) {
                    echo '<center>Der Superadmin konnte nicht angelegt werden.</center>';
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

                echo '<h2>Installation abgeschlossen</h2>'
                   . 'Du kannst Dich jetzt unter <code>admin/</code> einloggen.<br>'
                   . ($ok
                        ? 'Dein Passwort wurde Dir per E-Mail zugesandt.'
                        : '<b>Hinweis:</b> E-Mail konnte nicht verschickt werden. '
                          . 'Bitte notiere Dir jetzt Dein Passwort: <code>' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '</code>')
                   . '<br><br>Bitte lösche <code>install.php</code> zusätzlich vom Server.<br>'
                   . '<div align="right"><a href="admin/">Weiter &gt;&gt;</a></div>';
                break;
        }
        break;

    case 'update':
        echo '<h2>Update von 1.0</h2><p>Update-Funktion derzeit nicht verfügbar. Bitte manuell durchführen.</p>';
        break;
}
?>
</td></tr></table></td></tr></table>
<br>
<small>PowerClan &copy; Copyright 2001-2025 by PowerScripts</small>
</center>
</body>
</html>
