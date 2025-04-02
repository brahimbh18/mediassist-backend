<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load environment variables
$rootPath = dirname(__DIR__, 3);
$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->load();

function authorizeRequest() {
    $headers = getallheaders();
    error_log("All headers received: " . print_r($headers, true));
    
    // Check if Authorization header exists
    if (!isset($headers['authorization'])) {
        error_log("Authorization header is missing");
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header missing']);
        exit;
    }
    
    $authHeader = $headers['authorization'];
    error_log("Authorization header value: " . $authHeader);

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        error_log("Invalid Authorization header format");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Authorization header format']);
        exit;
    }

    $token = $matches[1];
    error_log("Extracted token: " . substr($token, 0, 20) . "...");

    try {
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        http_response_code(401);
        echo json_encode([
            'error' => 'Invalid token', 
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

?>