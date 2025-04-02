<?php
header('Content-Type: application/json');
require __DIR__.'/../vendor/autoload.php';

// Load environment variables
$rootPath = dirname(__DIR__, 2);
$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->load();

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!isset($_ENV['JWT_SECRET'])) {
    http_response_code(500);
    echo json_encode(['error' => 'JWT secret not configured']);
    exit;
}

try {
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
    
    echo json_encode([
        'user_id' => $decoded->sub,
        'valid' => true,
        'decoded' => $decoded
    ]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'valid' => false,
        'error' => $e->getMessage()
    ]);
}