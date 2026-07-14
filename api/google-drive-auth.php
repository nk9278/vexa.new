<?php
// File: /api/google-drive-auth.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

// These should ideally be in constants.php, hardcoded for demonstration based on the prompt scope.
// The real implementation requires setting these up in the Google Cloud Console.
$google_client_id = 'YOUR_GOOGLE_CLIENT_ID';
$redirect_uri = BASE_URL . '/api/google-drive-callback.php';

$scope = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email';

// Generate a random state string to protect against CSRF during OAuth
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $google_client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => $scope,
    'access_type' => 'offline',
    'prompt' => 'consent', // Force consent to ensure we get a refresh token
    'state' => $state
]);

header("Location: $auth_url");
exit;
