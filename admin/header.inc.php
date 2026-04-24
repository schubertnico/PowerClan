<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Header
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/** @var mysqli $conn */
/** @var array{host: string, user: string, password: string, database: string, port: int} $mysql */
/** @var array<string, mixed> $settings */

// League array
$leagues = ['Friendly', 'Training', 'ESPL', 'Clanbase'];

if (file_exists(__DIR__ . '/../config.inc.php') && file_exists(__DIR__ . '/../mysql.inc.php') && file_exists(__DIR__ . '/functions.inc.php')) {
    require_once __DIR__ . '/../config.inc.php';
    require_once __DIR__ . '/../mysql.inc.php';
    require_once __DIR__ . '/../functions.inc.php'; // Main functions (CSRF, etc.)
    require_once __DIR__ . '/functions.inc.php';    // Admin functions + session helper
} else {
    echo '<center><b>Es fehlen wichtige Dateien!</b></center>';
    exit;
}

// HTTP-Security-Header
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

// Output-Buffering, damit csrf_check() im Fehlerfall HTTP 403 setzen kann
if (ob_get_level() === 0) {
    ob_start();
}

pc_session_start();

$admin_tbl1 = '#B0B0B0';
$admin_tbl2 = '#E0E0E0';
$admin_tbl3 = '#F0F0F0';

$login = $_GET['login'] ?? '';
$logout = $_GET['logout'] ?? '';

/** @var 'YES'|'NO' $loggedin */
$loggedin = 'NO';
$pcadmin = [];

// Log current user out before anything else if requested
if ($logout === 'YES') {
    pc_session_logout();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Populate $pcadmin / $loggedin from session
checklogin();

// Handle login POST
if ($login === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login-CSRF prüfen
    if (!login_csrf_validate()) {
        http_response_code(403);
        die('<center><b>Sicherheitsfehler: Ungültiges CSRF-Token. Bitte lade die Seite neu.</b></center>');
    }

    // Brute-Force-Drossel (max. 10 Versuche pro 60s pro Session)
    $now = time();
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'] ?? [],
        static fn ($t) => (int) $t > $now - 60
    );
    if (count($_SESSION['login_attempts']) >= 10) {
        http_response_code(429);
        die('<center><b>Zu viele Login-Versuche. Bitte warte eine Minute.</b></center>');
    }

    $loginemail = trim((string) ($_POST['loginemail'] ?? ''));
    $loginpassword = (string) ($_POST['loginpassword'] ?? '');

    $authenticated = false;

    if ($loginemail !== '' && $loginpassword !== '' && filter_var($loginemail, FILTER_VALIDATE_EMAIL)) {
        $stmt = db_prepare($conn, 'SELECT * FROM pc_members WHERE email = ?');
        $stmt->bind_param('s', $loginemail);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result && mysqli_num_rows($result) === 1) {
            $candidate = mysqli_fetch_array($result, MYSQLI_ASSOC) ?: [];
            $storedPassword = (string) ($candidate['password'] ?? '');

            if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$argon2')) {
                if (password_verify($loginpassword, $storedPassword)) {
                    $authenticated = true;
                    if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($loginpassword, PASSWORD_DEFAULT);
                        $updateStmt = db_prepare($conn, 'UPDATE pc_members SET password = ? WHERE id = ?');
                        $cid = (int) $candidate['id'];
                        $updateStmt->bind_param('si', $newHash, $cid);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
            } elseif ($storedPassword === base64_encode($loginpassword)) {
                $authenticated = true;
                $newHash = password_hash($loginpassword, PASSWORD_DEFAULT);
                $updateStmt = db_prepare($conn, 'UPDATE pc_members SET password = ? WHERE id = ?');
                $cid = (int) $candidate['id'];
                $updateStmt->bind_param('si', $newHash, $cid);
                $updateStmt->execute();
                $updateStmt->close();
            }

            if ($authenticated) {
                unset($_SESSION['login_attempts']);
                pc_session_login((int) $candidate['id']);
                $stmt->close();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
        $stmt->close();
    }

    $_SESSION['login_attempts'][] = $now;
    $_SESSION['login_error'] = 'Login fehlgeschlagen.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

getsettings();

?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>PowerClan Adminbereich</title>
<meta name="author" content="PowerScripts">
<link rel="stylesheet" href="powerclan.css" type="text/css">
</head>
<body text="#000000" bgcolor="#FFFFFF" link="#000080" alink="#000080" vlink="#000080">

<center>
<table border="0" cellpadding="0" cellspacing="0" width="95%">
<tr><td bgcolor="#000080" width="100%">
  <table border="0" width="100%" cellpadding="2" cellspacing="1">
<?php
if ($loggedin === 'NO') {
    $formAction = e($_SERVER['PHP_SELF']) . '?login=YES';
    $loginError = (string) ($_SESSION['login_error'] ?? '');
    unset($_SESSION['login_error']);
    $csrfLogin = e(login_csrf_token());
    echo "
      <tr><td bgcolor=\"{$admin_tbl1}\">
      <b>Login</b>
      </td></tr>";
    if ($loginError !== '') {
        echo "<tr><td bgcolor=\"#FFCCCC\" align=\"center\"><b>" . e($loginError) . "</b></td></tr>";
    }
    echo "
      <tr><td bgcolor=\"{$admin_tbl2}\" align=\"center\">
        <form action=\"{$formAction}\" method=\"post\">
        <input type=\"hidden\" name=\"login_csrf\" value=\"{$csrfLogin}\">
        <table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
        <tr><td>
        <b>Deine E-Mail</b>
        </td><td>
        <input name=\"loginemail\" size=\"25\" maxlength=\"200\" type=\"email\" required>
        </td></tr>
        <tr><td>
        <b>Dein Passwort</b>
        </td><td>
        <input name=\"loginpassword\" size=\"25\" maxlength=\"100\" type=\"password\" required>
        </td></tr>
        <tr><td colspan=\"2\" align=\"center\">
        <input type=\"submit\" value=\"Login\">
        </td></tr>
        </table>
        </form>
      </td></tr>
    ";
    exit;
}

/** @var array<string, mixed>|array{} $pcadmin */
$nickValue = $pcadmin['nick'] ?? 'Unknown'; // @phpstan-ignore-line
$nickDisplay = e($nickValue);
$phpSelf = e($_SERVER['PHP_SELF']);
?>
  <tr><td bgcolor="<?php echo $admin_tbl2; ?>" width="125" valign="top">
  <br>
  <center><b><a href="profile.php"><?php echo $nickDisplay; ?></a></b></center><br>
  <b>News</b><br>
  <a href="addnews.php">News hinzuf&uuml;gen</a><br>
  <a href="choosenews.php">News editieren</a><br>
  <br>
  <b>Clanwars</b><br>
  <a href="addwar.php">War hinzuf&uuml;gen</a><br>
  <a href="choosewar.php">War editieren</a><br>
  <br>
  <b>Member</b><br>
  <a href="addmember.php">Member hinzuf&uuml;gen</a><br>
  <a href="choosemember.php">Member editieren</a><br>
  <br>
  <b>Konfiguration</b><br>
  <a href="editconfig.php">Konf. editieren</a><br>
  <br>
  <center><small>
  <a href="../">&Ouml;ffentliche Seite</a><br>
  <br>
  <a href="<?php echo $phpSelf; ?>?logout=YES">Logout</a><br>
  <br>
  <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts.org</a><br>
  <br>
  </small></center>
  </td><td bgcolor="<?php echo $admin_tbl3; ?>" valign="top">
  <br>
