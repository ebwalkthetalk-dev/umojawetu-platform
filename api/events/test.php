<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
echo json_encode(['status' => 'API is working', 'time' => date('Y-m-d H:i:s')]);
?>