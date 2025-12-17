<?php
header('Content-Type: application/json');

$configFile = 'config.json';

// GET: Read all settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($configFile)) {
        echo file_get_contents($configFile);
    } else {
        // Defaults if file is missing
        echo json_encode([
            "email" => "",
            "refresh_rate" => 2000,
            "sound_enabled" => true
        ]);
    }
}

// POST: Save settings (Merge with existing)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 1. Read existing config
    $currentConfig = [];
    if (file_exists($configFile)) {
        $currentConfig = json_decode(file_get_contents($configFile), true);
    }

    // 2. Update only the fields sent by the user
    if (isset($input['email'])) {
        $currentConfig['email'] = $input['email'];
    }
    if (isset($input['refresh_rate'])) {
        $currentConfig['refresh_rate'] = intval($input['refresh_rate']);
    }
    if (isset($input['sound_enabled'])) {
        // Ensure it's a boolean (true/false)
        $currentConfig['sound_enabled'] = filter_var($input['sound_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    // 3. Save back to file
    if (file_put_contents($configFile, json_encode($currentConfig, JSON_PRETTY_PRINT))) {
        echo json_encode(["status" => "success", "message" => "Settings Saved!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save file"]);
    }
}
?>