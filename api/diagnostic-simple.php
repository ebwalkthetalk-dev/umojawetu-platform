<?php

// --- AUTH ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
echo "<h1>Simple Diagnostic</h1>";
echo "<p>PHP is working</p>";

$basePath = 'C:/xampp/htdocs/umojawetu-platform/';

// Check if base path exists
if (file_exists($basePath)) {
    echo "<p style='color:green'>âœ… Base path exists: $basePath</p>";
} else {
    echo "<p style='color:red'>âŒ Base path NOT found: $basePath</p>";
}

// List public folder
$publicPath = $basePath . 'public';
if (is_dir($publicPath)) {
    echo "<h2>Files in /public/</h2><ul>";
    $files = scandir($publicPath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>âŒ public folder not found</p>";
}

// List admin folder
$adminPath = $basePath . 'admin';
if (is_dir($adminPath)) {
    echo "<h2>Files in /admin/</h2><ul>";
    $files = scandir($adminPath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>âŒ admin folder not found</p>";
}

// List api folder
$apiPath = $basePath . 'api';
if (is_dir($apiPath)) {
    echo "<h2>Folders in /api/</h2><ul>";
    $folders = scandir($apiPath);
    foreach ($folders as $folder) {
        if ($folder != '.' && $folder != '..' && is_dir($apiPath . '/' . $folder)) {
            echo "<li><strong>$folder/</strong></li>";
            // List files in each subfolder
            $subFiles = scandir($apiPath . '/' . $folder);
            foreach ($subFiles as $file) {
                if ($file != '.' && $file != '..') {
                    echo "<li style='margin-left:20px'>- $file</li>";
                }
            }
        }
    }
    echo "</ul>";
} else {
    echo "<p>âŒ api folder not found</p>";
}
?>