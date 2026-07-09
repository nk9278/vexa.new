<?php
// File: /api/google-drive-callback.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_functions.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Check state to prevent CSRF
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    $_SESSION['error_msg'] = "OAuth state mismatch. Please try connecting again.";
    redirect('/agency/google-drive.php');
}

if (isset($_GET['error'])) {
    $_SESSION['error_msg'] = "Google Drive connection failed: " . htmlspecialchars($_GET['error']);
    redirect('/agency/google-drive.php');
}

if (!isset($_GET['code'])) {
    $_SESSION['error_msg'] = "Authorization code missing.";
    redirect('/agency/google-drive.php');
}

// In a real application, you'd execute a POST request to Google's token endpoint here.
// Since we don't have real credentials, we will mock the token exchange process.
$code = $_GET['code'];

/* Mock Token Exchange
$google_client_id = 'YOUR_GOOGLE_CLIENT_ID';
$google_client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirect_uri = BASE_URL . '/api/google-drive-callback.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $code,
    'client_id' => $google_client_id,
    'client_secret' => $google_client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$token_data = json_decode($response, true);
*/

// DUMMY DATA FOR DEMONSTRATION
$token_data = [
    'access_token' => 'mock_access_token_' . bin2hex(random_bytes(8)),
    'refresh_token' => 'mock_refresh_token_' . bin2hex(random_bytes(8)),
    'expires_in' => 3599
];

if (isset($token_data['access_token'])) {
    // Basic encryption implementation as requested (mock encryption key)
    $encryption_key = 'super_secret_key_from_env_vars';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    $encrypted_access = openssl_encrypt($token_data['access_token'], 'aes-256-cbc', $encryption_key, 0, $iv);
    // Append IV so we can decrypt later
    $encrypted_access = base64_encode($encrypted_access . '::' . $iv);

    $refresh_token = $token_data['refresh_token'] ?? 'existing_refresh_token';
    $iv2 = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted_refresh = openssl_encrypt($refresh_token, 'aes-256-cbc', $encryption_key, 0, $iv2);
    $encrypted_refresh = base64_encode($encrypted_refresh . '::' . $iv2);

    $expires_at = time() + ($token_data['expires_in'] ?? 3600);
    $email = "connected_agency@example.com"; // In reality, fetch from userinfo endpoint

    // Save to Database
    $stmt = $pdo->prepare("
        INSERT INTO google_drive_accounts (agency_id, access_token, refresh_token, token_expires_at, email)
        VALUES (:agency_id, :access_token, :refresh_token, :token_expires_at, :email)
        ON DUPLICATE KEY UPDATE
            access_token = :access_token,
            refresh_token = :refresh_token,
            token_expires_at = :token_expires_at,
            email = :email
    ");

    $stmt->execute([
        'agency_id' => $agency_id,
        'access_token' => $encrypted_access,
        'refresh_token' => $encrypted_refresh,
        'token_expires_at' => $expires_at,
        'email' => $email
    ]);

    logActivity($agency_id, $_SESSION['user_id'], 'Google Drive Connected', "Agency connected to Google Drive successfully.");
    logAudit($agency_id, $_SESSION['user_id'], $_SESSION['role_name'], "Connected Google Drive OAuth");

    $_SESSION['success_msg'] = "Google Drive successfully connected!";
} else {
    $_SESSION['error_msg'] = "Failed to obtain access token.";
}

redirect('/agency/google-drive.php');
