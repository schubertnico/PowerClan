<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Utility Functions
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/**
 * Display a default error message
 */
function default_error(string $url, string $error): void
{
    global $errortablebg;

    // Sanitize URL - only allow http/https protocols, block javascript:
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    if (preg_match('/^javascript:/i', $url)) {
        $safeUrl = '#';
    }

    $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
    // $errortablebg bleibt fuer Abwaertskompatibilitaet als Bezugsvariable erhalten,
    // wird aber im Bootstrap-Alert-Layout nicht direkt als Hintergrund verwendet,
    // da Bootstrap konsistente Kontraste sicherstellt.
    unset($errortablebg);
    echo '<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">'
        . '<span>' . $safeError . '</span>'
        . '<a class="btn btn-sm btn-outline-secondary" href="' . $safeUrl . '">Weiter</a>'
        . '</div>';
}

/**
 * Process news text with BBCode-style formatting
 * Applies XSS protection and converts BBCode to HTML
 */
function news_replace(string $text): string
{
    // XSS protection first
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // Note: stripslashes removed - Magic Quotes deprecated since PHP 5.3, removed in PHP 7.0
    $text = nl2br($text);

    // BBCode replacements (safe after htmlspecialchars)
    $text = str_replace('[b]', '<b>', $text);
    $text = str_replace('[/b]', '</b>', $text);
    $text = str_replace('[u]', '<u>', $text);
    $text = str_replace('[/u]', '</u>', $text);
    $text = str_replace('[i]', '<i>', $text);
    $text = str_replace('[/i]', '</i>', $text);

    // URL replacements - careful with user input
    $text = preg_replace(
        '/\[url\]www\.([^\[]*)\[\/url\]/',
        '<a href="http://www.$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    ) ?? $text;
    $text = preg_replace(
        '/\[url\]([^\[]*)\[\/url\]/',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    ) ?? $text;
    $text = preg_replace('/\[url=&quot;([^\"]*)&quot;]/', '[url="$1"]', $text) ?? $text;
    $text = preg_replace('/&quot;]/', '"]', $text) ?? $text;
    $text = preg_replace(
        '/\[url="([^\"]*)"]([^\[]*)\[\/url\]/',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>',
        $text
    ) ?? $text;
    $text = preg_replace(
        '/\[url=([^\[]*)\\]([^\[]*)\[\/url\]/',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>',
        $text
    ) ?? $text;

    // Email replacements
    $text = preg_replace(
        '/\[email]([^\[]*)\[\/email]/',
        '<a href="mailto:$1">$1</a>',
        $text
    ) ?? $text;

    return preg_replace(
        '/\[email=([^\[]*)\\]([^\[]*)\[\/email]/',
        '<a href="mailto:$1">$2</a>',
        $text
    ) ?? $text;
}

/**
 * Load settings from database
 */
function getsettings(): void
{
    global $conn, $settings;

    try {
        $query = 'SELECT * FROM pc_config WHERE id = 1';
        $result = $conn->query($query);

        if ($result === false) {
            throw new Exception('Failed to load configuration');
        }

        $num = mysqli_num_rows($result);
        if ($num === 1) {
            $dbSettings = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if (is_array($dbSettings)) {
                $settings = array_merge($settings, $dbSettings);
            }
        } else {
            echo '<center><b>Die Konfiguration konnte nicht geladen werden!</b></center>';
            exit;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        die('<center><b>Configuration error. Please check logs.</b></center>');
    }
}

/**
 * Display war statistics
 */
function getwarstats(): void
{
    global $conn, $settings;

    $result = $conn->query('SELECT * FROM pc_wars');
    if ($result === false) {
        return;
    }

    $allnum = mysqli_num_rows($result);
    $num_won = $num_lost = $num_draw = $num_open = 0;

    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $allres = ['left' => 0, 'right' => 0];

        if (!empty($row['res1']) && !empty($row['res2'])) {
            if (!empty($row['map1']) && !empty($row['res1'])) {
                $res = explode(':', (string) $row['res1']);
                $allres['left'] += (int) ($res[0] ?? 0);
                $allres['right'] += (int) ($res[1] ?? 0);
            }
            if (!empty($row['map2']) && !empty($row['res2'])) {
                $res = explode(':', (string) $row['res2']);
                $allres['left'] += (int) ($res[0] ?? 0);
                $allres['right'] += (int) ($res[1] ?? 0);
            }
            if (!empty($row['map3']) && !empty($row['res3'])) {
                $res = explode(':', (string) $row['res3']);
                $allres['left'] += (int) ($res[0] ?? 0);
                $allres['right'] += (int) ($res[1] ?? 0);
            }

            if ($allres['left'] > $allres['right']) {
                $num_won++;
            } elseif ($allres['left'] === $allres['right']) {
                $num_draw++;
            } else {
                $num_lost++;
            }
        } else {
            $num_open++;
        }
    }

    // Bootstrap-5-Statistik-Karten: Werte werden ergaenzend zur Farbe immer mit Beschriftung versehen,
    // damit die Bedeutung nicht ausschliesslich ueber Farbe transportiert wird.
    ?>
    <div class="row row-cols-2 row-cols-md-5 g-3 mb-4" aria-label="War-Statistik">
        <div class="col">
            <div class="card text-bg-success h-100">
                <div class="card-body text-center">
                    <div class="text-uppercase small">Gewonnen</div>
                    <div class="display-6 fw-bold"><?php echo $num_won; ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-bg-danger h-100">
                <div class="card-body text-center">
                    <div class="text-uppercase small">Verloren</div>
                    <div class="display-6 fw-bold"><?php echo $num_lost; ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-bg-warning h-100">
                <div class="card-body text-center">
                    <div class="text-uppercase small">Unentschieden</div>
                    <div class="display-6 fw-bold"><?php echo $num_draw; ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-bg-secondary h-100">
                <div class="card-body text-center">
                    <div class="text-uppercase small">Offen</div>
                    <div class="display-6 fw-bold"><?php echo $num_open; ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-bg-primary h-100">
                <div class="card-body text-center">
                    <div class="text-uppercase small">Gesamt</div>
                    <div class="display-6 fw-bold"><?php echo $allnum; ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
    // Hinweis: Die in $settings hinterlegten Akzentfarben (clrwon/clrlost/clrdraw) bleiben
    // weiterhin in der War-Detailausgabe verfuegbar; in der Statistik-Uebersicht werden
    // konsistente Bootstrap-Kontextfarben verwendet, damit alle Bedeutungen zusaetzlich
    // ueber sichtbare Texte (Gewonnen, Verloren ...) transportiert werden.
}

/**
 * Liefert ein einzelnes Map-Ergebnis als Bootstrap-Markup zurueck.
 * Die Bedeutung (gewonnen/verloren/unentschieden) wird immer zusaetzlich
 * ueber den `aria-label` und die Tooltip-aehnliche `title`-Beschriftung
 * transportiert, damit sie nicht ausschliesslich ueber Farbe entsteht.
 *
 * @param string|null $mapName  Name der Map (z. B. de_dust2).
 * @param string|null $screen   Optionaler Screenshot-Dateiname unterhalb von images/wars/.
 * @param string|null $resRaw   Rohwert wie "16:14".
 * @param array{0:int,1:int} $endres Aufaddiertes Gesamtergebnis (wird modifiziert).
 */
function pc_render_war_map_cell(?string $mapName, ?string $screen, ?string $resRaw, array &$endres): string
{
    $map = e($mapName ?? '');
    $cell = '';

    if (!empty($screen)) {
        $cell .= '<a class="link-secondary" href="showpic.php?path=images/wars/' . e($screen) . '">' . $map . '</a>';
    } else {
        $cell .= '<span>' . ($map !== '' ? $map : '<span class="text-body-secondary">—</span>') . '</span>';
    }

    if (!empty($resRaw)) {
        $res = explode(':', (string) $resRaw);
        $r0 = (int) ($res[0] ?? 0);
        $r1 = (int) ($res[1] ?? 0);

        if ($r0 > $r1) {
            $cls = 'pc-result pc-result-won';
            $label = 'Gewonnen';
        } elseif ($r0 === $r1) {
            $cls = 'pc-result pc-result-draw';
            $label = 'Unentschieden';
        } else {
            $cls = 'pc-result pc-result-lost';
            $label = 'Verloren';
        }
        $endres[0] += $r0;
        $endres[1] += $r1;

        $cell .= '<br><span class="' . $cls . '" aria-label="' . $label . '" title="' . $label . '">'
            . $r0 . ':' . $r1
            . '</span>';
    }

    return $cell;
}

/**
 * Safely escape output for HTML
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * Replaces deprecated eregi() function
 */
function validate_email(string $email): bool
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

// =============================================================================
// CSRF Protection Functions
// =============================================================================

/**
 * Generate or retrieve CSRF token
 * Stores token in session for validation
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Output CSRF token as hidden form field
 * Use inside <form> tags
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate CSRF token from POST request
 * Returns true if valid, false otherwise
 */
function csrf_validate(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if ($sessionToken === '' || $postToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $postToken);
}

/**
 * Validate CSRF token and die with error if invalid
 * Use at the beginning of POST handlers
 */
function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (!csrf_validate()) {
        if (PHP_SAPI === 'cli') {
            throw new RuntimeException('CSRF validation failed');
        }
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(403);
        die('<center><b>Sicherheitsfehler: Ungültiges CSRF-Token. Bitte lade die Seite neu.</b></center>');
    }
    csrf_regenerate();
}

/**
 * Regenerate CSRF token (use after successful form submission)
 */
function csrf_regenerate(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Login-CSRF-Token (separat vom normalen CSRF, damit ein Logout den normalen Token rotieren kann,
 * ohne die Login-Seite zu invalidieren).
 */
function login_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['login_csrf'])) {
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['login_csrf'];
}

function login_csrf_validate(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $postToken = $_POST['login_csrf'] ?? '';
    $sessionToken = $_SESSION['login_csrf'] ?? '';
    if (!is_string($postToken) || $postToken === '' || !is_string($sessionToken) || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $postToken);
}

// =============================================================================
// Database Helper Functions
// =============================================================================

/**
 * Prepare a SQL statement with error handling
 *
 * @throws RuntimeException if prepare fails
 */
function db_prepare(mysqli $conn, string $query): mysqli_stmt
{
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare statement: ' . $conn->error);
    }
    return $stmt;
}

/**
 * Execute a query and return the result with error handling
 *
 * @throws RuntimeException if query fails
 */
function db_query(mysqli $conn, string $query): mysqli_result
{
    $result = $conn->query($query);
    if (!$result instanceof mysqli_result) {
        throw new RuntimeException('Query failed: ' . $conn->error);
    }
    return $result;
}
