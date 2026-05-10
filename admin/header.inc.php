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

// Hinweis: Die ehemaligen $admin_tbl1/$admin_tbl2/$admin_tbl3 Variablen werden
// im Bootstrap-Layout nicht mehr fuer Hintergrundfarben verwendet. Sie werden
// hier weiterhin als leere Strings bereitgestellt, um Abwaertskompatibilitaet
// fuer Admin-Skripte zu gewaehrleisten, die sie ggf. noch referenzieren.
$admin_tbl1 = '';
$admin_tbl2 = '';
$admin_tbl3 = '';

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

$pcAdminCurrentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerClan Adminbereich</title>
    <meta name="author" content="PowerScripts">
    <link rel="stylesheet" href="../assets/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="../powerclan.css">
</head>
<body class="bg-body-tertiary">
<a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-primary" href="#admin-main">Zum Inhalt springen</a>
<nav class="navbar navbar-dark bg-dark mb-4" aria-label="Adminnavigation">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="index.php">
            PowerClan
            <span class="badge text-bg-secondary ms-1">Adminbereich</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-light btn-sm" href="../">&laquo; Öffentliche Seite</a>
<?php if ($loggedin === 'YES'): ?>
            <span class="navbar-text pc-on-dark small d-none d-md-inline">
                <?php
                /** @var array<string, mixed>|array{} $pcadmin */
                $nickValue = $pcadmin['nick'] ?? 'Unknown'; // @phpstan-ignore-line
                echo 'Eingeloggt als <strong>' . e($nickValue) . '</strong>';
                ?>
            </span>
            <a class="btn btn-outline-warning btn-sm fw-semibold" href="<?php echo e($_SERVER['PHP_SELF']); ?>?logout=YES">Logout</a>
<?php endif; ?>
        </div>
    </div>
</nav>
<?php if ($loggedin === 'NO'): ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-body-secondary">
                    <h1 class="h5 mb-0">Login</h1>
                </div>
                <div class="card-body">
                    <p class="text-body-secondary small mb-3">
                        Bitte melde Dich mit Deiner E-Mail-Adresse und Deinem Passwort an, um den
                        Adminbereich zu nutzen.
                    </p>
<?php
$loginError = (string) ($_SESSION['login_error'] ?? '');
unset($_SESSION['login_error']);
if ($loginError !== ''):
?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo e($loginError); ?>
                    </div>
<?php endif; ?>
                    <form action="<?php echo e($_SERVER['PHP_SELF']) . '?login=YES'; ?>" method="post" novalidate>
                        <input type="hidden" name="login_csrf" value="<?php echo e(login_csrf_token()); ?>">
                        <div class="mb-3">
                            <label for="loginemail" class="form-label">Deine E-Mail</label>
                            <input id="loginemail" name="loginemail" type="email" class="form-control" maxlength="200" autocomplete="email" required aria-describedby="loginemailHelp">
                            <div id="loginemailHelp" class="form-text">Die im Profil hinterlegte E-Mail-Adresse.</div>
                        </div>
                        <div class="mb-3">
                            <label for="loginpassword" class="form-label">Dein Passwort</label>
                            <input id="loginpassword" name="loginpassword" type="password" class="form-control" maxlength="100" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
                <div class="card-footer text-body-secondary small text-center">
                    <a class="link-secondary" href="../">Zurück zur öffentlichen Seite</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php
exit;
endif;
?>
<div class="container-fluid">
    <div class="row g-4">
        <aside class="col-12 col-lg-3 col-xl-2 pc-admin-sidebar">
            <div class="card shadow-sm sticky-lg-top" style="top: 1rem;">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <a class="fw-semibold" href="profile.php">
                            <?php
                            /** @var array<string, mixed>|array{} $pcadmin */
                            echo e($pcadmin['nick'] ?? 'Unknown'); // @phpstan-ignore-line
                            ?>
                        </a>
                        <div class="text-body-secondary small">Mein Profil</div>
                    </div>
                    <hr>
                    <h2 class="h6 text-uppercase text-body-secondary small mb-2">News</h2>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['addnews.php']); ?>" href="addnews.php">News hinzufügen</a></li>
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['choosenews.php', 'editnews.php', 'delnews.php']); ?>" href="choosenews.php">News editieren</a></li>
                    </ul>
                    <h2 class="h6 text-uppercase text-body-secondary small mb-2">Clanwars</h2>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['addwar.php']); ?>" href="addwar.php">War hinzufügen</a></li>
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['choosewar.php', 'editwar.php', 'delwar.php']); ?>" href="choosewar.php">War editieren</a></li>
                    </ul>
                    <h2 class="h6 text-uppercase text-body-secondary small mb-2">Member</h2>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['addmember.php']); ?>" href="addmember.php">Member hinzufügen</a></li>
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['choosemember.php', 'editmember.php', 'delmember.php']); ?>" href="choosemember.php">Member editieren</a></li>
                    </ul>
                    <h2 class="h6 text-uppercase text-body-secondary small mb-2">Konfiguration</h2>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item"><a class="nav-link p-1<?php echo pc_admin_nav_active($pcAdminCurrentScript, ['editconfig.php']); ?>" href="editconfig.php">Konfiguration editieren</a></li>
                    </ul>
                    <hr>
                    <div class="text-center small">
                        <a class="link-secondary d-block mb-1" href="../">Öffentliche Seite</a>
                        <a class="link-secondary d-block mb-1" href="<?php echo e($_SERVER['PHP_SELF']); ?>?logout=YES">Logout</a>
                        <a class="link-secondary d-block" href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts.org</a>
                    </div>
                </div>
            </div>
        </aside>
        <section id="admin-main" class="col-12 col-lg-9 col-xl-10">
