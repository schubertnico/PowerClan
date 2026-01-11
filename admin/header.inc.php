<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Admin Header
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

// Start session for CSRF protection (must be before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// League array
$leagues = ['Friendly', 'Training', 'ESPL', 'Clanbase'];

if (file_exists(__DIR__ . '/../config.inc.php') && file_exists(__DIR__ . '/../mysql.inc.php') && file_exists(__DIR__ . '/functions.inc.php')) {
    include __DIR__ . '/../config.inc.php';
    include __DIR__ . '/../mysql.inc.php';
    include __DIR__ . '/../functions.inc.php'; // Main functions (CSRF, etc.)
    include __DIR__ . '/functions.inc.php';    // Admin functions
} else {
    echo '<center><b>Es fehlen wichtige Dateien!</b></center>';
    exit;
}

$admin_tbl1 = '#B0B0B0';
$admin_tbl2 = '#E0E0E0';
$admin_tbl3 = '#F0F0F0';

// Get cookie values safely
$pcadmin_id = $_COOKIE['pcadmin_id'] ?? '';
$pcadmin_password = $_COOKIE['pcadmin_password'] ?? '';

$login = $_GET['login'] ?? '';
$logout = $_GET['logout'] ?? '';

// Initialize variables
$loggedin = 'NO';
$pcadmin = [];

// Check existing session
checklogin($pcadmin_id, $pcadmin_password);

// Handle login
if ($login === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginemail = $_POST['loginemail'] ?? '';
    $loginpassword = $_POST['loginpassword'] ?? '';

    if (!empty($loginpassword) && !empty($loginemail)) {
        // Use prepared statement for login query
        $stmt = $conn->prepare('SELECT * FROM pc_members WHERE email = ?');
        $stmt->bind_param('s', $loginemail);
        $stmt->execute();
        $result = $stmt->get_result();
        $num = mysqli_num_rows($result);

        if ($num === 1) {
            $pcadmin = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $storedPassword = $pcadmin['password'] ?? '';
            $authenticated = false;

            // Try new password_hash format first
            if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$argon2')) {
                if (password_verify((string) $loginpassword, $storedPassword)) {
                    $authenticated = true;

                    // Rehash if needed
                    if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                        $newHash = password_hash((string) $loginpassword, PASSWORD_DEFAULT);
                        $updateStmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
                        $updateStmt->bind_param('si', $newHash, $pcadmin['id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                        $storedPassword = $newHash;
                    }
                }
            }
            // Fallback: Legacy base64 format with migration
            elseif ($storedPassword === base64_encode((string) $loginpassword)) {
                $authenticated = true;

                // Migrate to secure hash
                $newHash = password_hash((string) $loginpassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare('UPDATE pc_members SET password = ? WHERE id = ?');
                $updateStmt->bind_param('si', $newHash, $pcadmin['id']);
                $updateStmt->execute();
                $updateStmt->close();
                $storedPassword = $newHash;
            }

            if ($authenticated) {
                // Set secure cookies (HttpOnly for security)
                $cookieOptions = [
                    'expires' => time() + 3600 * 24 * 30, // 30 days
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ];
                setcookie('pcadmin_id', (string) $pcadmin['id'], $cookieOptions);
                setcookie('pcadmin_password', (string) $storedPassword, $cookieOptions);
                $loggedin = 'YES';

                // Redirect to make cookies available immediately
                $stmt->close();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
        $stmt->close();
    }
}

// Handle logout
if ($logout === 'YES') {
    $cookieOptions = [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    setcookie('pcadmin_id', '', $cookieOptions);
    setcookie('pcadmin_password', '', $cookieOptions);
    $loggedin = 'NO';
    $pcadmin = [];
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
    // Use htmlspecialchars for PHP_SELF to prevent XSS
    $formAction = e($_SERVER['PHP_SELF']) . '?login=YES';
    echo "
      <tr><td bgcolor=\"{$admin_tbl1}\">
      <b>Login</b>
      </td></tr>
      <tr><td bgcolor=\"{$admin_tbl2}\" align=\"center\">
        <form action=\"{$formAction}\" method=\"post\">
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

$nickDisplay = e($pcadmin['nick'] ?? 'Unknown');
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
