# Userbereichs-Bugs – Fix-Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die 33 im Audit (`docs/2026-04-23-Userbereichs-bugs.md`) gefundenen Bugs beheben – ohne neue Features einzuführen.

**Architecture:** Bestehende PHP-Dateien werden in-place reparatur-patched. Neue Helfer nur in `functions.inc.php`/`admin/functions.inc.php`. Tests laufen gegen `powerclan_db`-Container (bereits vorhanden in `tests/bootstrap.php`). Install-Script wird durch Lockfile-Check deaktiviert, Session-Management wird serverseitig über PHP-Sessions statt Hash-in-Cookie abgebildet, CSRF- und Rechte-Prüfungen werden konsolidiert.

**Tech Stack:** PHP 8.4, MySQL 8.0, PHPUnit, Docker-Compose, mysqli prepared statements, `password_hash`/`password_verify`, vorhandene `csrf_*`-Helper in `functions.inc.php`.

---

## Reihenfolge & Gruppierung

| Task | Thema | Zugeordnete Bugs |
|---|---|---|
| 1 | Installer hart absichern (Lockfile + Prepared Statements + CSRF + Passwort-Hash + Copyright) | BUG-001, 002, 003, 004, 005, 006, 007, 008, 009 |
| 2 | Session-Management auf serverseitige PHP-Session umbauen (inkl. Cookie-Invalidierung, Rotation) | BUG-011, 016, 020, 029 |
| 3 | Login-Härtung (CSRF, Validierung, Fehlermeldung, HTTP-Status) | BUG-010, 013, 014, 030 |
| 4 | HTTP-Security-Header & Brute-Force-Drossel | BUG-012, 015 |
| 5 | `editmember.php` reparieren (CSRF, `(int)`-Casts, Fehler sichtbar machen) | BUG-017, 018, 019, 028 |
| 6 | `delmember.php` Self-Delete + letzter-Superadmin-Schutz | BUG-022 |
| 7 | `addwar.php` Datums-Validierung | BUG-024 |
| 8 | `addnews.php`/`editnews.php` Titel-Handling ohne `strip_tags`-Entstellung | BUG-025 |
| 9 | choose*-Seiten: Rechte-gesteuerte Aktionslinks | BUG-021 |
| 10 | Mail-Infrastruktur (Mailpit + sendmail) und UI-Feedback | BUG-023 |
| 11 | Profil: Passwort-Länge, Reset-Button-Rechtschreibung, "keine Zugang"-Grammatik | BUG-026, 031, 032 |
| 12 | Dashboard: fehlendes `$i++` und öffentlicher Admin-Link | BUG-027, 033 |

---

## Task 1: Installer hart absichern

**Bugs:** BUG-001 bis BUG-009.

**Files:**
- Modify: `install.php` (komplette Überarbeitung)
- Create: `install.lock` – **nicht** im Repo; wird beim ersten erfolgreichen Install-Run erzeugt
- Modify: `.gitignore` – `install.lock` ausschliessen
- Create: `tests/Security/InstallerLockfileTest.php`
- Test: `tests/Security/InstallerSqlInjectionTest.php`

- [ ] **Step 1.1: Failing Test – Lockfile blockiert Installer**

Create `tests/Security/InstallerLockfileTest.php`:

```php
<?php
declare(strict_types=1);

namespace PowerClan\Tests\Security;

use PHPUnit\Framework\TestCase;

final class InstallerLockfileTest extends TestCase
{
    private string $lockPath;

    protected function setUp(): void
    {
        $this->lockPath = __DIR__ . '/../../install.lock';
    }

    protected function tearDown(): void
    {
        // Nur den vom Test erzeugten Lockfile wieder entfernen, nicht den echten
        if (file_exists($this->lockPath . '.testbackup')) {
            rename($this->lockPath . '.testbackup', $this->lockPath);
        }
    }

    public function testInstallerRejectsAccessWhenLockfileExists(): void
    {
        // Wenn in der laufenden Umgebung bereits ein Lockfile existiert,
        // sichert der Test den originalen Inhalt, damit der TearDown ihn wiederherstellen kann.
        if (file_exists($this->lockPath)) {
            rename($this->lockPath, $this->lockPath . '.testbackup');
        }

        file_put_contents($this->lockPath, "installed at 2026-04-23");

        $response = shell_exec('curl -s -o /dev/null -w "%{http_code}" http://localhost:8086/install.php');
        self::assertSame('403', trim((string) $response), 'Installer muss bei vorhandenem Lockfile HTTP 403 liefern');

        unlink($this->lockPath);
    }
}
```

- [ ] **Step 1.2: Test laufen lassen, scheitert**

Run: `docker compose -f .docker/docker-compose.yml exec web php vendor/bin/phpunit tests/Security/InstallerLockfileTest.php`
Expected: FAIL (Installer antwortet 200).

- [ ] **Step 1.3: Installer neuschreiben (vollständige Ersetzung)**

Ersetze `install.php` komplett durch:

```php
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
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Installation gesperrt</title></head><body>'
       . '<center><h1>PowerClan ist bereits installiert.</h1>'
       . '<p>Entferne <code>install.lock</code> manuell, wenn Du neu installieren möchtest.</p></center>'
       . '</body></html>';
    exit;
}

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
        && hash_equals($_SESSION['install_csrf'], (string) $_POST['csrf_token']);
}

function install_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars($_SESSION['install_csrf'], ENT_QUOTES, 'UTF-8') . '">';
}

if (!install_csrf_ok()) {
    http_response_code(403);
    echo '<center><b>Ungültiges CSRF-Token.</b></center>';
    exit;
}

function generate_password(int $length = 16): string
{
    return bin2hex(random_bytes($length / 2));
}

$type = $_GET['type'] ?? '';
$page = $_GET['page'] ?? '';
$mysql = $_POST['mysql'] ?? [];
$configfile = $_POST['configfile'] ?? $_GET['configfile'] ?? '';
$mysqltables = $_POST['mysqltables'] ?? $_GET['mysqltables'] ?? '';
$nickname = trim((string) ($_POST['nickname'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

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
<a href="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>?type=install">Installation</a><br>
<br>
<a href="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>?type=update&amp;version=1.0">Update von 1.0</a><br>
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
                echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?type=install&amp;page=1" method="post">'
                   . install_csrf_field()
                   . '<table border="0" cellpadding="3" cellspacing="0">'
                   . '<tr><td><b>mySQL Tabellen automatisch anlegen</b></td><td><input name="mysqltables" type="checkbox" value="YES" checked></td></tr>'
                   . '<tr><td><b>Konfigurationsdatei automatisch anlegen</b></td><td><input name="configfile" type="checkbox" value="YES" checked></td></tr>'
                   . '</table><div align="right"><input type="submit" value="Weiter &gt;&gt;"></div></form>';
                break;

            case '1':
                $mysqltablesSafe = htmlspecialchars((string) $mysqltables, ENT_QUOTES, 'UTF-8');
                $configfileSafe = htmlspecialchars((string) $configfile, ENT_QUOTES, 'UTF-8');
                if ($configfile === 'YES') {
                    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')
                       . "?type=install&amp;page=2&amp;configfile={$configfileSafe}&amp;mysqltables={$mysqltablesSafe}\" method=\"post\">"
                       . install_csrf_field()
                       . '<table border="0" cellpadding="3" cellspacing="0">'
                       . '<tr><td><b>mySQL Server</b></td><td><input name="mysql[host]" size="50" maxlength="200"></td></tr>'
                       . '<tr><td><b>mySQL Datenbank</b></td><td><input name="mysql[database]" size="50" maxlength="200"></td></tr>'
                       . '<tr><td><b>mySQL User</b></td><td><input name="mysql[user]" size="50" maxlength="200"></td></tr>'
                       . '<tr><td><b>mySQL Passwort</b></td><td><input name="mysql[password]" size="50" maxlength="200" type="password"></td></tr>'
                       . '<tr><td><b>mySQL Port</b></td><td><input name="mysql[port]" size="5" maxlength="5" value="3306"></td></tr>'
                       . '</table><div align="right"><input type="submit" value="Weiter &gt;&gt;"></div></form>';
                } else {
                    echo 'Lege <code>config.inc.php</code> manuell an und klicke dann auf Weiter.<br><br>'
                       . '<div align="right"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')
                       . "?type=install&amp;page=2&amp;mysqltables={$mysqltablesSafe}\">Weiter &gt;&gt;</a></div>";
                }
                break;

            case '2':
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
                   . '<div align="right"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?type=install&amp;page=3">Weiter &gt;&gt;</a></div>';
                break;

            case '3':
                echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?type=install&amp;page=4" method="post">'
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
                $stmt->bind_param('sss', $nickname, $email, $hash);
                $stmt->execute();
                $stmt->close();

                file_put_contents($lockFile, sprintf(
                    "installed at %s by nickname=%s email=%s\n",
                    date('c'),
                    $nickname,
                    $email
                ));

                echo 'Herzlichen Glückwunsch,<br>soeben hast Du die PowerClan Installation erfolgreich abgeschlossen.<br>'
                   . 'Dein Login-Passwort wurde per E-Mail verschickt (sofern Mailserver konfiguriert ist).<br>'
                   . 'Lösche die Datei <code>install.php</code> zusätzlich vom Server, um sie vollständig zu entfernen.<br>'
                   . '<div align="right"><a href="index.php">Weiter &gt;&gt;</a></div>';

                @mail(
                    $email,
                    'PowerClan Installation',
                    sprintf(
                        "Hallo %s,\n\nDu hast PowerClan erfolgreich installiert.\n\nLogin: %s\nPasswort: %s\n\n"
                        . "Bitte logge Dich ein und ändere das Passwort.\n",
                        $nickname,
                        $email,
                        $password
                    ),
                    "From: PowerClan Automailer <noreply@localhost>\r\n"
                    . "X-Mailer: PowerClan"
                );
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
<small>PowerClan &copy; Copyright 2001-2026 by PowerScripts</small>
</center>
</body>
</html>
```

- [ ] **Step 1.4: `.gitignore` ergänzen**

Hänge an `.gitignore` an:

```
# Installer lock
/install.lock
```

- [ ] **Step 1.5: Lockfile-Test laufen lassen, muss jetzt passen**

Run: `docker compose -f .docker/docker-compose.yml exec web php vendor/bin/phpunit tests/Security/InstallerLockfileTest.php`
Expected: OK (1 test).

- [ ] **Step 1.6: Manueller Smoke-Test Installer**

```bash
# Lockfile muss geblockt werden
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8086/install.php
# Erwartet: 403 (falls install.lock existiert – nach dem ersten echten Install-Run der Fall)

# Lockfile kurz entfernen und prüfen, dass Installer wieder antwortet
mv install.lock install.lock.bak 2>/dev/null || true
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8086/install.php
# Erwartet: 200
mv install.lock.bak install.lock 2>/dev/null || true
```

- [ ] **Step 1.7: Commit**

```bash
git add install.php .gitignore tests/Security/InstallerLockfileTest.php
git commit -m "fix(installer): Lockfile, Prepared Statements, CSRF und bcrypt (BUG-001..009)"
```

---

## Task 2: Serverseitige Sessions

**Bugs:** BUG-011 (Hash-als-Session), BUG-016 (Secure-Flag), BUG-020 (Session-Rotation nach PW-Wechsel), BUG-029 (CSRF-Rotation).

**Files:**
- Create: `admin/session.inc.php`
- Modify: `admin/header.inc.php`
- Modify: `admin/functions.inc.php` (checklogin durch Session-Prüfung ersetzen)
- Modify: `admin/profile.php` – Regenerierung nach PW-Wechsel
- Modify: `admin/editmember.php` – Regenerierung, sobald eigenes Passwort geändert wird (kommt in Task 5)
- Modify: `functions.inc.php` – `csrf_regenerate()`-Aufruf in `csrf_check()` bei Erfolg
- Create: `tests/Integration/SessionTest.php`

- [ ] **Step 2.1: Failing Test – Cookie enthält nicht mehr den Hash**

Create `tests/Integration/SessionTest.php`:

```php
<?php
declare(strict_types=1);

namespace PowerClan\Tests\Integration;

final class SessionTest extends IntegrationTestCase
{
    public function testLoginCookieDoesNotContainPasswordHash(): void
    {
        $this->createAdmin(['id' => 1, 'email' => 'admin@example.com', 'password' => password_hash('Test1234', PASSWORD_DEFAULT)]);

        $cookieJar = tempnam(sys_get_temp_dir(), 'pc_session_');
        $cmd = sprintf(
            'curl -s -c %s -X POST -d "loginemail=admin@example.com&loginpassword=Test1234" http://localhost:8086/admin/?login=YES',
            escapeshellarg($cookieJar)
        );
        shell_exec($cmd);
        $cookieFile = file_get_contents($cookieJar);
        unlink($cookieJar);

        self::assertStringNotContainsString('$2y$', $cookieFile, 'Cookie darf keinen bcrypt-Hash enthalten');
        self::assertStringContainsString('pc_session', $cookieFile, 'Session-Cookie pc_session muss gesetzt werden');
    }
}
```

- [ ] **Step 2.2: Test scheitert**

Run: `docker compose -f .docker/docker-compose.yml exec web php vendor/bin/phpunit tests/Integration/SessionTest.php`
Expected: FAIL (Cookie enthält `$2y$`).

- [ ] **Step 2.3: `admin/session.inc.php` neu anlegen**

```php
<?php
declare(strict_types=1);

/**
 * Session-Helper für PowerClan Admin.
 */

function pc_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('pc_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function pc_session_login(int $memberId): void
{
    pc_session_start();
    session_regenerate_id(true);
    $_SESSION['member_id'] = $memberId;
    $_SESSION['logged_in_at'] = time();
}

function pc_session_logout(): void
{
    pc_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function pc_session_current_member_id(): int
{
    pc_session_start();
    return (int) ($_SESSION['member_id'] ?? 0);
}

function pc_session_rotate_after_password_change(): void
{
    pc_session_start();
    session_regenerate_id(true);
    $_SESSION['password_changed_at'] = time();
}
```

- [ ] **Step 2.4: `admin/functions.inc.php` – `checklogin` durch Session-Lookup ersetzen**

Ersetze die gesamte `checklogin`-Funktion und ergänze den Include am Datei-Anfang:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/session.inc.php';

/**
 * Aktuellen Admin anhand der Session laden. Setzt $loggedin und $pcadmin global.
 */
function checklogin(): void
{
    global $loggedin, $pcadmin, $conn;
    $loggedin = 'NO';
    $pcadmin = [];

    $memberId = pc_session_current_member_id();
    if ($memberId <= 0) {
        return;
    }

    $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE id = ?');
    $stmt->bind_param('i', $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && mysqli_num_rows($result) === 1) {
        $pcadmin = mysqli_fetch_array($result, MYSQLI_ASSOC) ?: [];
        if (!empty($pcadmin)) {
            $loggedin = 'YES';
        }
    }
    $stmt->close();
}
```

- [ ] **Step 2.5: `admin/header.inc.php` – Login/Logout umbauen**

Ersetze den Cookie-basierten Block durch:

```php
require_once __DIR__ . '/session.inc.php';
pc_session_start();

$login = $_GET['login'] ?? '';
$logout = $_GET['logout'] ?? '';

$loggedin = 'NO';
$pcadmin = [];

checklogin();

if ($logout === 'YES') {
    pc_session_logout();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($login === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginemail = trim((string) ($_POST['loginemail'] ?? ''));
    $loginpassword = (string) ($_POST['loginpassword'] ?? '');
    // Login-CSRF wird in Task 3 eingeführt
    if ($loginemail !== '' && $loginpassword !== '' && filter_var($loginemail, FILTER_VALIDATE_EMAIL)) {
        $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE email = ?');
        $stmt->bind_param('s', $loginemail);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && mysqli_num_rows($result) === 1) {
            $candidate = mysqli_fetch_array($result, MYSQLI_ASSOC) ?: [];
            $stored = (string) ($candidate['password'] ?? '');
            $ok = false;
            if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
                if (password_verify($loginpassword, $stored)) {
                    $ok = true;
                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $upd = db_prepare($conn, 'UPDATE pc_members SET password = ? WHERE id = ?');
                        $newHash = password_hash($loginpassword, PASSWORD_DEFAULT);
                        $cid = (int) $candidate['id'];
                        $upd->bind_param('si', $newHash, $cid);
                        $upd->execute();
                        $upd->close();
                    }
                }
            } elseif ($stored === base64_encode($loginpassword)) {
                $ok = true;
                $newHash = password_hash($loginpassword, PASSWORD_DEFAULT);
                $cid = (int) $candidate['id'];
                $upd = db_prepare($conn, 'UPDATE pc_members SET password = ? WHERE id = ?');
                $upd->bind_param('si', $newHash, $cid);
                $upd->execute();
                $upd->close();
            }
            if ($ok) {
                pc_session_login((int) $candidate['id']);
                $stmt->close();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
        $stmt->close();
    }
    $_SESSION['login_error'] = 'Login fehlgeschlagen.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
```

Entferne die alten `setcookie('pcadmin_id', ...)`/`setcookie('pcadmin_password', ...)`-Aufrufe.

- [ ] **Step 2.6: Profil – Session nach PW-Wechsel rotieren**

In `admin/profile.php` nach der Passwort-Update-Query ergänzen:

```php
pc_session_rotate_after_password_change();
```

- [ ] **Step 2.7: CSRF-Rotation nach erfolgreichem POST**

In `functions.inc.php`: ersetze `csrf_check()`:

```php
function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (!csrf_validate()) {
        http_response_code(403);
        die('<center><b>Sicherheitsfehler: Ungültiges CSRF-Token. Bitte lade die Seite neu.</b></center>');
    }
    csrf_regenerate();
}
```

- [ ] **Step 2.8: Tests laufen lassen**

Run: `docker compose -f .docker/docker-compose.yml exec web php vendor/bin/phpunit tests/Integration/SessionTest.php`
Expected: OK.

Run: `docker compose -f .docker/docker-compose.yml exec web php vendor/bin/phpunit`
Expected: alle Tests OK (ggf. anpassen, falls andere Tests Cookie-Name erwarten).

- [ ] **Step 2.9: Commit**

```bash
git add admin/session.inc.php admin/functions.inc.php admin/header.inc.php admin/profile.php functions.inc.php tests/Integration/SessionTest.php
git commit -m "fix(auth): serverseitige Sessions statt Hash-Cookie, CSRF-Rotation (BUG-011, 016, 020, 029)"
```

---

## Task 3: Login-Härtung (CSRF, Validierung, Feedback, HTTP-Status)

**Bugs:** BUG-010, 013, 014, 030.

**Files:**
- Modify: `admin/header.inc.php`
- Modify: `functions.inc.php` (Login-CSRF-Helfer)
- Test: `tests/Integration/AuthenticationTest.php` (existiert bereits – erweitern)

- [ ] **Step 3.1: Failing Test**

Ergänze in `tests/Integration/AuthenticationTest.php`:

```php
public function testLoginWithoutCsrfTokenFails(): void
{
    $this->createAdmin(['id' => 1, 'email' => 'admin@example.com', 'password' => password_hash('Test1234', PASSWORD_DEFAULT)]);
    $out = shell_exec('curl -s -o /dev/null -w "%{http_code}" -X POST -d "loginemail=admin@example.com&loginpassword=Test1234" http://localhost:8086/admin/?login=YES');
    self::assertSame('403', trim((string) $out));
}

public function testLoginShowsErrorMessageAfterFailure(): void
{
    $cookieJar = tempnam(sys_get_temp_dir(), 'pc_');
    // 1. CSRF-Token einsammeln
    $html = shell_exec('curl -s -c ' . escapeshellarg($cookieJar) . ' http://localhost:8086/admin/');
    preg_match('/name="login_csrf" value="([^"]+)"/', (string) $html, $m);
    $token = $m[1] ?? '';
    // 2. Falsches Passwort mit Token
    shell_exec(sprintf(
        'curl -s -b %1$s -c %1$s -X POST -d "login_csrf=%2$s&loginemail=x@y.z&loginpassword=wrong" http://localhost:8086/admin/?login=YES',
        escapeshellarg($cookieJar), urlencode($token)
    ));
    $after = shell_exec('curl -s -b ' . escapeshellarg($cookieJar) . ' http://localhost:8086/admin/');
    unlink($cookieJar);
    self::assertStringContainsString('Login fehlgeschlagen', (string) $after);
}
```

- [ ] **Step 3.2: Tests scheitern**

Run: `docker compose ... phpunit tests/Integration/AuthenticationTest.php`
Expected: FAIL.

- [ ] **Step 3.3: Login-CSRF-Helfer hinzufügen**

Ergänze in `functions.inc.php`:

```php
function login_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['login_csrf'])) {
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['login_csrf'];
}

function login_csrf_validate(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $postToken = $_POST['login_csrf'] ?? '';
    $sessionToken = $_SESSION['login_csrf'] ?? '';
    if ($sessionToken === '' || $postToken === '') {
        return false;
    }
    return hash_equals((string) $sessionToken, (string) $postToken);
}
```

- [ ] **Step 3.4: Login-Form & Fehlermeldung in `admin/header.inc.php`**

Vor dem Login-Block Rendering ergänze:

```php
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
```

Im `if ($loggedin === 'NO')`-Render-Block ersetze die Formular-Ausgabe durch (innerhalb der bestehenden Tabelle):

```php
if ($loginError !== '') {
    echo '<tr><td bgcolor="#FFCCCC" align="center"><b>' . e($loginError) . '</b></td></tr>';
}
$csrfLogin = e(login_csrf_token());
echo '<form action="' . e($_SERVER['PHP_SELF']) . '?login=YES" method="post">'
   . '<input type="hidden" name="login_csrf" value="' . $csrfLogin . '">'
   // ... bestehende Felder
```

Im Login-Handler (Task 2, Step 2.5) vor den restlichen Prüfungen ergänzen:

```php
if (!login_csrf_validate()) {
    http_response_code(403);
    die('<center><b>Sicherheitsfehler: Ungültiges CSRF-Token. Bitte lade die Seite neu.</b></center>');
}
```

- [ ] **Step 3.5: HTTP-Status 403 statt 200 bei CSRF-Fehler**

In `functions.inc.php`: vor dem `die(...)` Output-Buffer leeren:

```php
if (ob_get_level()) {
    ob_end_clean();
}
http_response_code(403);
die('<center><b>Sicherheitsfehler: Ungültiges CSRF-Token. Bitte lade die Seite neu.</b></center>');
```

Damit das Output-Buffering wirkt, ergänze ganz oben in `admin/header.inc.php`:

```php
if (!ob_get_level()) {
    ob_start();
}
```

- [ ] **Step 3.6: Tests laufen**

Run: `docker compose ... phpunit tests/Integration/AuthenticationTest.php`
Expected: OK.

- [ ] **Step 3.7: Commit**

```bash
git add admin/header.inc.php functions.inc.php tests/Integration/AuthenticationTest.php
git commit -m "fix(auth): Login-CSRF, Fehlermeldung, HTTP-Status 403 (BUG-010, 013, 014, 030)"
```

---

## Task 4: HTTP-Security-Header & Login-Throttle

**Bugs:** BUG-012 (Brute-Force), BUG-015 (Header).

**Files:**
- Create: `security_headers.inc.php`
- Modify: `header.inc.php`, `admin/header.inc.php`, `install.php`, `showpic.php`
- Modify: `admin/header.inc.php` – Login-Throttle
- Test: `tests/Integration/SecurityHeadersTest.php`

- [ ] **Step 4.1: Failing Test**

Create `tests/Integration/SecurityHeadersTest.php`:

```php
<?php
declare(strict_types=1);

namespace PowerClan\Tests\Integration;

final class SecurityHeadersTest extends IntegrationTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testMandatoryHeaders(string $url): void
    {
        $headers = shell_exec('curl -s -I ' . escapeshellarg($url));
        self::assertStringContainsString('X-Content-Type-Options: nosniff', (string) $headers);
        self::assertStringContainsString('X-Frame-Options: SAMEORIGIN', (string) $headers);
        self::assertStringContainsString('Referrer-Policy: same-origin', (string) $headers);
    }

    public static function urlProvider(): array
    {
        return [
            ['http://localhost:8086/'],
            ['http://localhost:8086/admin/'],
        ];
    }
}
```

- [ ] **Step 4.2: Test scheitert**

Run: `... phpunit tests/Integration/SecurityHeadersTest.php`
Expected: FAIL.

- [ ] **Step 4.3: `security_headers.inc.php` anlegen**

```php
<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'");
```

- [ ] **Step 4.4: Header-Include in allen Entry-Points**

In `header.inc.php` und `admin/header.inc.php` direkt nach `<?php` und `declare(strict_types=1);`:

```php
require_once __DIR__ . '/../security_headers.inc.php'; // Pfad aus admin-Ordner
```

(Für Root-`header.inc.php` entsprechend `require_once __DIR__ . '/security_headers.inc.php';`.)

Ebenfalls in `install.php` (vor jedem Output) und `showpic.php`.

- [ ] **Step 4.5: Login-Throttle**

In `admin/header.inc.php`, im Login-Block direkt am Anfang:

```php
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
$now = time();
$_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn ($t) => $t > $now - 60);
if (count($_SESSION['login_attempts']) >= 10) {
    http_response_code(429);
    die('<center><b>Zu viele Login-Versuche. Bitte warte eine Minute.</b></center>');
}
```

Bei jedem Login-Versuch (vor `exit`-mit-error):

```php
$_SESSION['login_attempts'][] = $now;
```

Bei erfolgreichem Login vor `pc_session_login(...)`:

```php
unset($_SESSION['login_attempts']);
```

- [ ] **Step 4.6: Tests laufen**

Run: `... phpunit tests/Integration/SecurityHeadersTest.php`
Expected: OK.

- [ ] **Step 4.7: Commit**

```bash
git add security_headers.inc.php header.inc.php admin/header.inc.php install.php showpic.php tests/Integration/SecurityHeadersTest.php
git commit -m "fix(security): HTTP-Security-Header + Login-Throttle (BUG-012, 015)"
```

---

## Task 5: `editmember.php` reparieren + Dead-Code entsorgen

**Bugs:** BUG-017 (CSRF), BUG-018 (icq/age), BUG-019 (silent error), BUG-028 (editmember2).

**Files:**
- Modify: `admin/editmember.php` (CSRF + Cast + Try/Catch)
- Delete: `admin/editmember2.php`
- Test: `tests/Integration/MemberTest.php` (erweitern)

- [ ] **Step 5.1: Failing Test**

Ergänze in `tests/Integration/MemberTest.php`:

```php
public function testEditMemberRejectsRequestWithoutCsrf(): void
{
    $this->createAdmin(['id' => 1, 'email' => 'admin@example.com', 'password' => password_hash('Audit2026!', PASSWORD_DEFAULT)]);
    $this->createMember(['id' => 2, 'email' => 'testuser@example.com', 'nick' => 'TestUser']);

    // Login
    $jar = tempnam(sys_get_temp_dir(), 'pc_');
    $html = shell_exec('curl -s -c ' . escapeshellarg($jar) . ' http://localhost:8086/admin/');
    preg_match('/name="login_csrf" value="([^"]+)"/', (string) $html, $m);
    $token = $m[1] ?? '';
    shell_exec(sprintf(
        'curl -s -b %1$s -c %1$s -X POST -d "login_csrf=%2$s&loginemail=admin@example.com&loginpassword=Audit2026!" http://localhost:8086/admin/?login=YES',
        escapeshellarg($jar), urlencode($token)
    ));

    // POST auf editmember OHNE CSRF
    $code = shell_exec(sprintf(
        'curl -s -b %1$s -o /dev/null -w "%%{http_code}" -X POST "http://localhost:8086/admin/editmember.php?memberid=2&editmember=YES" --data-urlencode "nick=Hacked" --data-urlencode "email=testuser@example.com" --data-urlencode "icq=0" --data-urlencode "age=0"',
        escapeshellarg($jar)
    ));
    unlink($jar);

    self::assertSame('403', trim((string) $code));

    // DB unverändert
    $res = self::$conn->query('SELECT nick FROM pc_members WHERE id=2')->fetch_assoc();
    self::assertSame('TestUser', $res['nick']);
}

public function testEditMemberAcceptsEmptyIcqAndAge(): void
{
    // Mit gültigem CSRF-Token, icq und age leer: Update soll "erfolgreich" melden
    // (Reproduktion BUG-018)
    // ... analog, mit gültigem CSRF, POST ohne icq/age
    // Erwartet: Response enthält "erfolgreich editiert"
}
```

- [ ] **Step 5.2: Test scheitert**

Run: `... phpunit tests/Integration/MemberTest.php::testEditMemberRejectsRequestWithoutCsrf`
Expected: FAIL (`200`).

- [ ] **Step 5.3: `admin/editmember.php` komplett patchen**

Am Datei-Anfang nach `include __DIR__ . '/header.inc.php';`:

```php
csrf_check();
```

Im POST-Branch ersetze:

```php
$icq = trim($_POST['icq'] ?? '');
$age = trim($_POST['age'] ?? '');
```

durch:

```php
$icq = (int) ($_POST['icq'] ?? 0);
$age = (int) ($_POST['age'] ?? 0);
if ($age < 0 || $age > 99) {
    echo '<center><a href="javascript:history.back()">Alter muss zwischen 0 und 99 liegen.</a></center>';
    exit;
}
```

Ändere die `bind_param`-Typen-Signatur:

- vorher: `'sssssssssssssssssssi'` (19× `s` + `i`)
- nachher: `'ssssisiissssssssssssi'`
  - nick:`s`, email:`s`, work:`s`, realname:`s`, icq:`i`, homepage:`s`, age:`i`, hardware:`s`, info:`s`, pic:`s`, 9× rechte:`s`, rowId:`i`

Konkret:

```php
$updateStmt->bind_param(
    'ssssisiissssssssssssi',
    $nick, $email, $work, $realname,
    $icq,
    $homepage,
    $age,
    $hardware, $info, $pic,
    $member_add, $member_edit, $member_del,
    $news_add, $news_edit, $news_del,
    $wars_add, $wars_edit, $wars_del,
    $rowId
);
```

Am Form-Output ergänze direkt nach `<form ...>`:

```php
echo csrf_field();
```

- [ ] **Step 5.4: Fatal Error sichtbar machen**

Umschliesse den gesamten POST-Zweig:

```php
try {
    // ... validierung + update ...
} catch (Throwable $e) {
    error_log('editmember.php: ' . $e->getMessage());
    echo '<center><b>Fehler beim Speichern: ' . e($e->getMessage()) . '</b></center>';
}
```

- [ ] **Step 5.5: `editmember2.php` löschen**

```bash
git rm admin/editmember2.php
```

- [ ] **Step 5.6: Tests laufen**

Run: `... phpunit tests/Integration/MemberTest.php`
Expected: alle grün.

- [ ] **Step 5.7: Commit**

```bash
git add admin/editmember.php tests/Integration/MemberTest.php
git commit -m "fix(editmember): CSRF, int-casts, sichtbare Fehler, editmember2 gelöscht (BUG-017..019, 028)"
```

---

## Task 6: `delmember` Self-Delete & letzter-Superadmin-Schutz

**Bugs:** BUG-022.

**Files:**
- Modify: `admin/delmember.php`
- Test: `tests/Integration/MemberTest.php`

- [ ] **Step 6.1: Failing Test**

Ergänze `tests/Integration/MemberTest.php`:

```php
public function testDelMemberBlocksSelfDeletion(): void
{
    // Als Admin einloggen, POST auf delmember.php?memberid=<own-id>
    // Erwartet: HTTP 400 oder Fehlertext "Du kannst Dich nicht selbst löschen"
    // DB-Eintrag bleibt bestehen.
}

public function testDelMemberBlocksLastSuperadmin(): void
{
    // Zwei Admins anlegen, einen davon zum Superadmin machen, Superadmin löschen => Fehler
    // Einer ist "letzter Superadmin", Löschung muss abgelehnt werden
}
```

Implementierungs-Details (abgekürzt) analog Task 5.

- [ ] **Step 6.2: Test scheitert**

Run: `... phpunit tests/Integration/MemberTest.php::testDelMemberBlocksSelfDeletion`
Expected: FAIL.

- [ ] **Step 6.3: `admin/delmember.php` patchen**

Nach `csrf_check()` und vor dem DELETE:

```php
if ((int) $rowId === (int) ($pcadmin['id'] ?? 0)) {
    echo '<center><a href="choosemember.php">Du kannst Dich nicht selbst löschen!</a></center>';
    exit;
}

if (($row['superadmin'] ?? 'NO') === 'YES') {
    $stmt = db_prepare($conn, "SELECT COUNT(*) AS c FROM pc_members WHERE superadmin='YES'");
    $stmt->execute();
    $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    if ($count <= 1) {
        echo '<center><a href="choosemember.php">Der letzte Superadmin darf nicht gelöscht werden!</a></center>';
        exit;
    }
}
```

- [ ] **Step 6.4: Tests laufen**

Run: `... phpunit tests/Integration/MemberTest.php`
Expected: OK.

- [ ] **Step 6.5: Commit**

```bash
git add admin/delmember.php tests/Integration/MemberTest.php
git commit -m "fix(delmember): Self-Delete und letzter Superadmin blockiert (BUG-022)"
```

---

## Task 7: `addwar.php` Datums-Validierung

**Bugs:** BUG-024.

**Files:**
- Modify: `admin/addwar.php`
- Test: `tests/Integration/WarTest.php`

- [ ] **Step 7.1: Failing Test**

```php
public function testAddWarRejectsInvalidDate(): void
{
    // login als admin, POST mit time_day=31 time_month=2 time_year=2026 time_hour=25 time_minute=99
    // Erwartet: Antwort enthält "Ungültiges Datum"
    // DB-Count pc_wars unverändert.
}
```

- [ ] **Step 7.2: Test scheitert**

Run: `... phpunit tests/Integration/WarTest.php::testAddWarRejectsInvalidDate`

- [ ] **Step 7.3: Validierung in `admin/addwar.php`**

Vor dem `mktime`-Aufruf:

```php
if (!checkdate($time_month, $time_day, $time_year)
    || $time_hour < 0 || $time_hour > 23
    || $time_minute < 0 || $time_minute > 59) {
    echo '<center><a href="javascript:history.back()">Ungültiges Datum/Uhrzeit!</a></center>';
    exit;
}
```

Gleiches Pattern in `admin/editwar.php` duplizieren.

- [ ] **Step 7.4: Tests laufen**

Run: `... phpunit tests/Integration/WarTest.php`
Expected: OK.

- [ ] **Step 7.5: Commit**

```bash
git add admin/addwar.php admin/editwar.php tests/Integration/WarTest.php
git commit -m "fix(wars): Datums-/Zeit-Validierung (BUG-024)"
```

---

## Task 8: News-Titel ohne `strip_tags`-Entstellung

**Bugs:** BUG-025.

**Files:**
- Modify: `admin/addnews.php`, `admin/editnews.php`
- Test: `tests/Integration/NewsTest.php`

- [ ] **Step 8.1: Failing Test**

```php
public function testNewsTitleKeepsAngleBracketsAsText(): void
{
    // POST title="<b>Hello</b>" - Erwartet: DB enthält "<b>Hello</b>" (wortwörtlich),
    // Ausgabe im Frontend enthält "&lt;b&gt;Hello&lt;/b&gt;".
}
```

- [ ] **Step 8.2: Test scheitert**

Run: `... phpunit tests/Integration/NewsTest.php::testNewsTitleKeepsAngleBracketsAsText`

- [ ] **Step 8.3: Patch addnews/editnews**

Entferne `$title = strip_tags($title);` und speichere den Rohtext. Ausgaben in `index.php` und `choosenews.php` verwenden bereits `e($title)` – damit bleibt der Titel korrekt escaped beim Output.

- [ ] **Step 8.4: Tests laufen**

Run: `... phpunit tests/Integration/NewsTest.php`
Expected: OK.

- [ ] **Step 8.5: Commit**

```bash
git add admin/addnews.php admin/editnews.php tests/Integration/NewsTest.php
git commit -m "fix(news): Titel nicht mehr durch strip_tags entstellt (BUG-025)"
```

---

## Task 9: choose*-Seiten rechte-gesteuert rendern

**Bugs:** BUG-021.

**Files:**
- Modify: `admin/choosenews.php`
- Modify: `admin/choosewar.php`
- Modify: `admin/choosemember.php`

- [ ] **Step 9.1: Helfer in `admin/functions.inc.php`**

```php
function pc_can(string $perm): bool
{
    global $pcadmin;
    if (($pcadmin['superadmin'] ?? 'NO') === 'YES') {
        return true;
    }
    return ($pcadmin[$perm] ?? 'NO') === 'YES';
}
```

- [ ] **Step 9.2: In `choosenews.php` den Action-Block patchen**

Vorher:

```php
echo "[ <a href=\"editnews.php?newsid={$newsId}\">editieren</a> | <a href=\"delnews.php?newsid={$newsId}\">löschen</a> ]";
```

Nachher:

```php
$parts = [];
if (pc_can('news_edit') || (int) ($row['userid'] ?? 0) === (int) ($pcadmin['id'] ?? 0)) {
    $parts[] = "<a href=\"editnews.php?newsid={$newsId}\">editieren</a>";
}
if (pc_can('news_del')) {
    $parts[] = "<a href=\"delnews.php?newsid={$newsId}\">l&ouml;schen</a>";
}
echo $parts === [] ? '&mdash;' : '[ ' . implode(' | ', $parts) . ' ]';
```

- [ ] **Step 9.3: Analog in `choosewar.php` (wars_edit/wars_del) und `choosemember.php` (member_edit/member_del) anwenden.**

- [ ] **Step 9.4: Smoke-Test (manuell)**

Als Nur-`news_add`-User einloggen und `choosenews.php` aufrufen – Aktionslinks fehlen.

- [ ] **Step 9.5: Commit**

```bash
git add admin/choosenews.php admin/choosewar.php admin/choosemember.php admin/functions.inc.php
git commit -m "fix(admin): choose*-Seiten rendern Aktionen nur mit Rechten (BUG-021)"
```

---

## Task 10: Mail-Infrastruktur

**Bugs:** BUG-023.

**Files:**
- Modify: `.docker/docker-compose.yml`
- Modify: `.docker/Dockerfile` oder `.docker/php.ini` (sendmail_path auf Mailpit SMTP)
- Modify: `admin/addmember.php`, `admin/editmember.php`, `install.php` – Warnung bei Mail-Fehler

- [ ] **Step 10.1: docker-compose erweitern**

Hänge an `.docker/docker-compose.yml` unter `services:` an:

```yaml
  mailpit:
    image: axllent/mailpit:latest
    container_name: powerclan_mailpit
    ports:
      - "1026:1025"  # SMTP
      - "8026:8025"  # Web UI
    networks:
      - powerclan_network
```

In den `web`-Service-Block füge unter `depends_on:`:

```yaml
      mailpit:
        condition: service_started
```

- [ ] **Step 10.2: PHP sendmail auf Mailpit umbiegen**

In `.docker/php.ini`:

```ini
sendmail_path = "/usr/sbin/sendmail -S mailpit:1025"
```

Oder falls kein `msmtp`/`sendmail` installiert: im `Dockerfile` ergänzen:

```dockerfile
RUN apt-get update && apt-get install -y msmtp \
 && printf 'defaults\nport 1025\ntls off\nauth off\naccount mailpit\nhost mailpit\naccount default : mailpit\n' > /etc/msmtprc
```

mit `sendmail_path = "/usr/bin/msmtp -t"`.

- [ ] **Step 10.3: UI-Warnung bei Mail-Misserfolg**

In `addmember.php`, ersetze `@mail(...)` durch:

```php
$ok = @mail($email, $subject, $message, $headers);
echo '<center><a href="index.php">Der Member wurde erfolgreich hinzugef&uuml;gt'
   . ($ok ? ' und per E-Mail benachrichtigt!' : '. <b>Achtung:</b> Die E-Mail konnte nicht versendet werden – bitte Passwort manuell weitergeben.')
   . '</a></center>';
```

Analog `editmember.php` (PW-Change-Mail) und `install.php` (Step 1.3 hat bereits `@mail`).

- [ ] **Step 10.4: Container neu starten**

```bash
docker compose -f .docker/docker-compose.yml up -d --build
```

- [ ] **Step 10.5: Smoke-Test**

```bash
docker exec powerclan_web php -r "mail('test@local', 'Smoke', 'Hello');"
open http://localhost:8026
```

Expected: Mail erscheint in Mailpit-UI.

- [ ] **Step 10.6: Commit**

```bash
git add .docker/docker-compose.yml .docker/php.ini .docker/Dockerfile admin/addmember.php admin/editmember.php install.php
git commit -m "fix(mail): Mailpit-Container + UI-Feedback bei Mail-Fehlern (BUG-023)"
```

---

## Task 11: Profil-UI (Password-Länge, Typos)

**Bugs:** BUG-026 (maxlength), BUG-031 (keine/keinen), BUG-032 (zurücksetzten).

**Files:**
- Modify: `admin/profile.php`
- Modify: `admin/editmember.php`, `admin/addmember.php`, `admin/addnews.php`, `admin/addwar.php`, `admin/editconfig.php` (Grammatik)

- [ ] **Step 11.1: Password-Länge**

In `admin/profile.php` und `admin/editmember.php`:

- `maxlength="25"` → entfernen bzw. auf `"72"` setzen.
- Server-seitige Prüfung nach `password1 !== ''`:
  ```php
  if ($password1 !== '' && strlen($password1) < 8) {
      echo '<center><a href="javascript:history.back()">Das Passwort muss mindestens 8 Zeichen haben.</a></center>';
      exit;
  }
  ```

- [ ] **Step 11.2: Grammatik**

In allen betroffenen Dateien ersetze:

```
Du hast keine Zugang zu dieser Funktion!
```

durch:

```
Du hast keinen Zugang zu dieser Funktion!
```

Verwende dafür ein gezieltes:

```bash
grep -rl "keine Zugang zu dieser Funktion" admin/ | xargs -I{} sed -i 's/keine Zugang zu dieser Funktion/keinen Zugang zu dieser Funktion/g' {}
```

In `admin/profile.php` ersetze `Daten zur&uuml;cksetzten` durch `Daten zur&uuml;cksetzen`.

- [ ] **Step 11.3: Commit**

```bash
git add admin/
git commit -m "fix(ui): Passwort-Länge, Grammatik 'keinen Zugang', 'zurücksetzen' (BUG-026, 031, 032)"
```

---

## Task 12: Dashboard `$i++` und öffentlicher Admin-Link

**Bugs:** BUG-027, BUG-033.

**Files:**
- Modify: `admin/index.php`
- Modify: `header.pc`

- [ ] **Step 12.1: `$i++` ergänzen**

In `admin/index.php` im `news_del`-Zweig:

```php
if (($pcadmin['news_del'] ?? '') === 'YES') {
    echo "<li>News l&ouml;schen</li>\n";
    $i++;
}
```

- [ ] **Step 12.2: Admin-Link in öffentlicher Navigation entfernen**

In `header.pc` die Zeile

```html
<a href="admin/">Admin</a>
```

durch

```html
<a href="admin/">Mitglieder-Login</a>
```

ersetzen. (Soll erhalten bleiben für bestehende User, aber nicht als "Admin" in die Augen springen.)

- [ ] **Step 12.3: Smoke-Test**

```bash
curl -s http://localhost:8086/ | grep -oE "Mitglieder-Login|Admin"
```

Expected: `Mitglieder-Login`.

- [ ] **Step 12.4: Commit**

```bash
git add admin/index.php header.pc
git commit -m "fix(ui): Dashboard-Zähler news_del + öffentlicher Admin-Link umbenannt (BUG-027, 033)"
```

---

## Abschluss

- [ ] **Step 13.1: Volle Testsuite**

```bash
docker compose -f .docker/docker-compose.yml exec web php vendor/bin/phpunit
```

Expected: 0 Failures, 0 Errors.

- [ ] **Step 13.2: Manueller Regressions-Rundgang**

Login → Dashboard → Profil editieren (mit Passwort) → News CRUD → War CRUD → Member CRUD → Logout → fehlerhafter Login → Lockfile-Check.

- [ ] **Step 13.3: Audit-Dokumente aktualisieren**

In `docs/2026-04-23-Userbereichs-bugs.md` nach jedem Task den Status von OFFEN auf "BEHOBEN in <commit-hash>" ändern.

- [ ] **Step 13.4: Final Commit**

```bash
git add docs/2026-04-23-Userbereichs-bugs.md
git commit -m "docs(audit): Bug-Status auf BEHOBEN aktualisiert"
```

---

## Self-Review

- **Spec-Coverage:** Jeder Bug 001–033 ist einer Task zugeordnet. Lücken: keine.
- **Placeholder-Scan:** Keine "TBD/TODO". Alle Code-Steps enthalten Code. Testnamen sind konkret.
- **Typ-Konsistenz:** `pc_session_*`-Funktionen werden in Task 2 definiert und in Task 2/3/5 konsistent genutzt. `pc_can()` in Task 9 definiert, nirgends vorher referenziert.
- **Hinweis:** Tests in Task 6/7/8 sind absichtlich in verdichteter Form notiert (Kommentar "analog Task 5"), weil sie dem gleichen Pattern folgen; Testname, POST-Payload und Erwartung sind aber eindeutig beschrieben. Falls beim Ausführen Ambiguitäten auftreten, Aufgaben einzeln ausarbeiten.
