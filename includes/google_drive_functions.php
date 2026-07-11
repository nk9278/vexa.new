<?php
// File: /includes/google_drive_functions.php

/**
 * Helper file for handling Google Drive interactions.
 * Note: These functions contain mock implementations of the Google Drive REST API calls
 * since we don't have an active Google Cloud Console integration available.
 */

function getGoogleDriveAccessToken($agency_id) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $stmt = $pdo->prepare("SELECT * FROM google_drive_accounts WHERE agency_id = :agency_id");
    $stmt->execute(['agency_id' => $agency_id]);
    $account = $stmt->fetch();

    if (!$account) return null;

    $encryption_key = 'super_secret_key_from_env_vars'; // Use real environment variable in production

    // Check if token expired
    if ($account['token_expires_at'] < time()) {
        // Mock Refresh Process
        list($encrypted_data, $iv) = explode('::', base64_decode($account['refresh_token']), 2);
        $refresh_token = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);

        /* Real Implementation Example
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => 'YOUR_CLIENT_ID',
            'client_secret' => 'YOUR_CLIENT_SECRET',
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        $new_access_token = $response['access_token'];
        */

        $new_access_token = 'refreshed_mock_token_' . bin2hex(random_bytes(8));
        $new_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted_access = base64_encode(openssl_encrypt($new_access_token, 'aes-256-cbc', $encryption_key, 0, $new_iv) . '::' . $new_iv);

        $stmt = $pdo->prepare("UPDATE google_drive_accounts SET access_token = :token, token_expires_at = :expires WHERE agency_id = :id");
        $stmt->execute(['token' => $encrypted_access, 'expires' => time() + 3599, 'id' => $agency_id]);

        return $new_access_token;
    }

    // Decrypt current token
    list($encrypted_data, $iv) = explode('::', base64_decode($account['access_token']), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
}

/**
 * Creates or gets a Google Drive folder and records it in `google_drive_folders`
 */
function getOrCreateDriveFolder($agency_id, $folder_name, $entity_type, $entity_id, $parent_folder_id = null) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    // Check if already mapped
    $query = "SELECT drive_folder_id, id FROM google_drive_folders WHERE agency_id = :agency_id AND entity_type = :type AND folder_name = :name";
    $params = ['agency_id' => $agency_id, 'type' => $entity_type, 'name' => $folder_name];
    if ($entity_id) {
        $query .= " AND entity_id = :entity_id";
        $params['entity_id'] = $entity_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $existing = $stmt->fetch();

    if ($existing) {
        return ['drive_id' => $existing['drive_folder_id'], 'local_id' => $existing['id']];
    }

    $token = getGoogleDriveAccessToken($agency_id);
    if (!$token) return null; // Not connected

    // Mock API Call to create folder in Google Drive
    $mock_drive_folder_id = 'drive_folder_' . bin2hex(random_bytes(10));

    // Save to Database
    $stmt = $pdo->prepare("
        INSERT INTO google_drive_folders (agency_id, entity_type, entity_id, folder_name, drive_folder_id, parent_folder_id)
        VALUES (:agency_id, :type, :entity_id, :name, :drive_id, :parent_id)
    ");
    $stmt->execute([
        'agency_id' => $agency_id,
        'type' => $entity_type,
        'entity_id' => $entity_id ?: null,
        'name' => $folder_name,
        'drive_id' => $mock_drive_folder_id,
        'parent_id' => $parent_folder_id
    ]);

    return ['drive_id' => $mock_drive_folder_id, 'local_id' => $pdo->lastInsertId()];
}

/**
 * Automatically resolves the nested folder structure.
 * Agency > Clients > Client Name > Project Name > Role
 */
function resolveDriveUploadPath($agency_id, $project_id, $role_name) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    // Fetch info
    $stmt = $pdo->prepare("SELECT p.project_name, c.id as client_id, c.client_name, a.name as agency_name FROM projects p JOIN clients c ON p.client_id = c.id JOIN agencies a ON p.agency_id = a.id WHERE p.id = :id AND p.agency_id = :agency_id");
    $stmt->execute(['id' => $project_id, 'agency_id' => $agency_id]);
    $info = $stmt->fetch();

    if (!$info) return null;

    // 1. Root Agency Folder
    $agency_dir = getOrCreateDriveFolder($agency_id, $info['agency_name'], 'Agency', $agency_id);
    if (!$agency_dir) return null;

    // 2. Clients Root Folder
    $clients_root = getOrCreateDriveFolder($agency_id, 'Clients', 'Category', null, $agency_dir['drive_id']);

    // 3. Specific Client Folder
    $client_dir = getOrCreateDriveFolder($agency_id, $info['client_name'], 'Client', $info['client_id'], $clients_root['drive_id']);

    // 4. Project Folder
    $project_dir = getOrCreateDriveFolder($agency_id, $info['project_name'], 'Project', $project_id, $client_dir['drive_id']);

    // 5. Category/Role Folder (e.g. Graphic Design, Photography, Documents)
    $category_name = match($role_name) {
        'Sales' => 'Documents',
        'Photographer' => 'Photography',
        'Graphic Designer' => 'Graphics',
        'Video Editor' => 'Videos',
        'Content Writer', 'Content Approval' => 'Content',
        default => 'Other'
    };

    $final_dir = getOrCreateDriveFolder($agency_id, $category_name, 'Category', null, $project_dir['drive_id']);

    return $final_dir;
}

/**
 * Mocks uploading a file to Google Drive and saving local DB record.
 */
function uploadToGoogleDrive($agency_id, $project_id, $uploader_id, $role_name, $local_file_path, $original_filename, $file_size) {
    global $pdo;

    // Determine target folder
    $target_folder = resolveDriveUploadPath($agency_id, $project_id, $role_name);

    if (!$target_folder) {
        return false; // Not connected or failed
    }

    $token = getGoogleDriveAccessToken($agency_id);

    // Mock Drive Upload
    $mock_drive_file_id = 'drive_file_' . bin2hex(random_bytes(10));
    $mock_drive_link = 'https://drive.google.com/file/d/' . $mock_drive_file_id . '/view';
    $file_ext = pathinfo($original_filename, PATHINFO_EXTENSION);

    $stmt = $pdo->prepare("
        INSERT INTO google_drive_files (agency_id, project_id, uploader_id, folder_id, file_name, drive_file_id, drive_link, file_size, file_type)
        VALUES (:agency_id, :project_id, :uploader_id, :folder_id, :file_name, :drive_file_id, :drive_link, :size, :type)
    ");

    $stmt->execute([
        'agency_id' => $agency_id,
        'project_id' => $project_id,
        'uploader_id' => $uploader_id,
        'folder_id' => $target_folder['local_id'],
        'file_name' => basename($original_filename),
        'drive_file_id' => $mock_drive_file_id,
        'drive_link' => $mock_drive_link,
        'size' => $file_size,
        'type' => $file_ext
    ]);

    // In a real application, you would unlink($local_file_path) here to save hosting space.
    // For demonstration, we keep it so the old system doesn't break if someone clicks the local link.

    return [
        'local_file_id' => $pdo->lastInsertId(),
        'drive_link' => $mock_drive_link
    ];
}
