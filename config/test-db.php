<?php
// First, just check if the file exists
echo "Step 1: test-db.php is running<br>";

// Try to include database.php
$db_file = '../config/database.php';
echo "Step 2: Looking for database.php at: " . $db_file . "<br>";

if (file_exists($db_file)) {
    echo "Step 3: ✅ database.php found!<br>";
    require_once $db_file;
    echo "Step 4: ✅ Database connection loaded!";
} else {
    echo "Step 3: ❌ database.php NOT found at: " . realpath('../config/') . "<br>";
}
?>