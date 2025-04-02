<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // For JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$rootPath = dirname(__DIR__, 3);
$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->load();
if (!isset($_ENV['JWT_SECRET']) || empty($_ENV['JWT_SECRET'])) {
    http_response_code(500);
    echo json_encode(['error' => 'JWT secret not configured']);
    exit;
}

$jwtSecret = $_ENV['JWT_SECRET'];
$jwtAlgorithm = 'HS256';
$tokenExpiration = 3600;


function isRateLimited(string $ip): bool {
    $limit = 5; // per minute
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5($ip) . '.ratelimit';

    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (time() - $data['time'] < 60 && $data['count'] >= $limit) {
            return true;
        }
    }

    file_put_contents($cacheFile, json_encode([
        'time' => time(),
        'count' => ($data['count'] ?? 0) + 1
    ]));

    return false;
}

function logLoginActivity(PDO $pdo, ?int $userId, bool $isSuccess): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Login_activity (
                user_id,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log login activity: " . $e->getMessage());
    }
}

function validateLoginData(array $data): array {
    $errors = [];
    if (empty($data['username'])) $errors['username'] = 'Username required';
    if (empty($data['password'])) $errors['password'] = 'Password required';

    return $errors; 
}


$ip = $_SERVER['REMOTE_ADDR'];
if (isRateLimited($ip)) {
    http_response_code(429);
    die(json_encode(['error' => 'Too many requests']));
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $validationErrors = validateLoginData($input);

    if (!empty($validationErrors)) {
        http_response_code(422);
        exit(json_encode(['errors' => $validationErrors]));
    }

    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare("SELECT id, password FROM User WHERE username = ?");
    $stmt->execute([$input['username']]);
    $user = $stmt->fetch();

    
    if (!$user || !password_verify($input['password'], $user['password'])) {
        http_response_code(401);
        exit(json_encode(['error' => 'Invalid credentials']));
    }
  
    logLoginActivity($pdo, $user['id'], true);

    $payload = [
        'iss' => 'mediassist_backend',
        'aud' => 'mediassist_frontend',
        'iat' => time(),
        'exp' => time() + $tokenExpiration,
        'sub' => $user['id'],
        'username' => $input['username']
    ];

    $jwt = JWT::encode($payload, $jwtSecret, $jwtAlgorithm);

    echo json_encode([
        'success' => true,
        'token' => $jwt,
        'expires_in' => $tokenExpiration
    ]);

    

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'details' => $e->getMessage()
    ]);
}
?>