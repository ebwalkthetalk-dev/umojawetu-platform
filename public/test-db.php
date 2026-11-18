<?php
echo "Current directory: " . __DIR__ . "<br>";
echo "Looking for config folder: " . realpath(__DIR__ . "/../config") . "<br>";
echo "Checking if database.php exists: " . (file_exists(__DIR__ . "/../config/database.php") ? "YES" : "NO") . "<br>";

if (file_exists(__DIR__ . "/../config/database.php")) {
    require_once __DIR__ . "/../config/database.php";
    echo "✅ Database connection successful!";
} else {
    echo "❌ database.php not found in config folder";
}
?>