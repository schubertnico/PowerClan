<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Picture Display (with Path Traversal Protection)
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

?>
<!--HEADER FILE-->
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAIN PAGE-->

<center>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr><td align="center" width="100%">
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
        echo '<a href="javascript:history.back()"><img src="' . $safePath . '" border="0" alt="Zur vorherigen Seite"></a>';
    } else {
        default_error('index.php', 'Ungültiger Bildpfad!');
    }
}
?>
</td></tr>
</table>
</center>
<br>
<center>
<small><a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerClan</a> &copy; Copyright 2001-2025 by <a href="mailto:info@powerscripts.org?subject=PowerClan Copyright">PowerScripts</a></small>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
