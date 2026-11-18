<?php
echo "Current directory: " . __DIR__ . "<br>";
echo "Config folder path: " . realpath(__DIR__ . "/../config") . "<br>";
echo "app.php exists: " . (file_exists(__DIR__ . "/../config/app.php") ? "YES" : "NO") . "<br>";
echo "database.php exists: " . (file_exists(__DIR__ . "/../config/database.php") ? "YES" : "NO") . "<br>";

if (file_exists(__DIR__ . "/../config/app.php")) {
    require_once __DIR__ . "/../config/app.php";
    echo "<br>✅ app.php loaded successfully!";
    echo "<br>Site name: " . $site_name;
} else {
    echo "<br>❌ app.php NOT found!";
}
?>