<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
require_once __DIR__ . '/../../config/database.php';




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


function validateRegistrationData(array $data): array {
    $errors = [];

    $required = ['name', 'email', 'username', 'password'];
    foreach ($required as $feild) {
        if (empty($data[$feild])) {
            $errors[$feild] = 'This field is required';
        }
    }

    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (preg_match('/\s/', $data['username'] ?? '')) {
        $errors['username'] = 'Username cannot contain spaces';
    }

    if (strlen($data['password'] ?? '') < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if (!preg_match('/[A-Z]/', $data['password'] ?? '') || 
        !preg_match('/[0-9]/', $data['password'] ?? '')) {
        $errors['password'] = 'Must contain 1 uppercase letter and 1 number';
    }

    return $errors;
}


// $ip = $_SERVER['REMOTE_ADDR'];
// if (isRateLimited($ip)) {
//     http_response_code(429);
//     die(json_encode(['error' => 'Too many requests']));
// }

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $validationErros = validateRegistrationData($input);
    if (!empty($validationErros)) {
        http_response_code(422);
        echo json_encode(['errors' => $validationErros]);
        exit;
    }

    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
    $stmt->execute([$input['email']]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("
        INSERT INTO User (
            name,
            email,
            username,
            password,
            created_at
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $input['name'],
        $input['email'],
        $input['username'],
        $hashedPassword
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'user_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}

?>