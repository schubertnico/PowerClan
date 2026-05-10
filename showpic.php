<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Picture Display (with Path Traversal Protection)
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

?>
<!--HEADER FILE-->
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAIN PAGE-->

<div class="card shadow-sm mb-4">
    <div class="card-body text-center">
<?php
$path = $_GET['path'] ?? '';

if (empty($path)) {
    default_error('index.php', 'Es wurde kein Pfad angegeben!');
} else {
    // SECURITY: Path Traversal Protection
    // Only allow specific directories and file extensions
    $allowedDirs = ['images', 'images/wars'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Normalize the path - remove any directory traversal attempts
    $path = str_replace(['../', '..\\', '..'], '', $path);

    // Get the real path components
    $pathInfo = pathinfo($path);
    $extension = strtolower($pathInfo['extension'] ?? '');
    $directory = $pathInfo['dirname'] ?? '';

    // Validate file extension
    $validExtension = in_array($extension, $allowedExtensions, true);

    // Check if the file is in an allowed directory or is a direct URL
    $isAllowedPath = false;

    // Allow external URLs (http/https)
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        $isAllowedPath = true;
    } else {
        // For local files, check if in allowed directory
        foreach ($allowedDirs as $allowedDir) {
            if ($directory === $allowedDir || $directory === './' . $allowedDir) {
                $isAllowedPath = true;
                break;
            }
        }
        // Also allow files directly in images directory without subdirectory
        if (str_starts_with($path, 'images/') && !str_contains($path, '..')) {
            $isAllowedPath = true;
        }
    }

    // Final validation
    if ($validExtension && $isAllowedPath) {
        $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        echo '<a class="d-inline-block" href="javascript:history.back()" title="Zur vorherigen Seite">'
            . '<img src="' . $safePath . '" class="img-fluid rounded shadow-sm" alt="Zur vorherigen Seite">'
            . '</a>'
            . '<div class="mt-3"><a class="btn btn-outline-secondary btn-sm" href="javascript:history.back()">&laquo; Zurück</a></div>';
    } else {
        default_error('index.php', 'Ungültiger Bildpfad!');
    }
}
?>
    </div>
</div>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
