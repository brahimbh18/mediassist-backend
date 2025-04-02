<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth/middleware.php';

try {
    // 1. Authorization
    $authData = authorizeRequest();
    $userId = $authData->sub;
    
    // 2. Database connection
    $pdo = getDatabaseConnection();
    
    // 3. Handle Request
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all prescribed medicaments for the user
            $stmt = $pdo->prepare("
                SELECT pm.*, m.name as medicament_name, m.photo 
                FROM PrescribedMedicaments pm
                JOIN Medicament m ON pm.medicament_id = m.id
                WHERE pm.user_id = ?
                ORDER BY pm.created_at DESC
            ");
            $stmt->execute([$userId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'POST':
            // Log the raw input
            $rawInput = file_get_contents('php://input');
            error_log("Raw input received: " . $rawInput);
            
            $input = json_decode($rawInput, true);
            error_log("Decoded input: " . print_r($input, true));
            
            // Validate input
            if (empty($input['name'])) {
                error_log("Name is missing from input");
                http_response_code(422);
                echo json_encode(['error' => 'Medicament name is required']);
                exit;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // 1. Check if medicament exists
                $stmt = $pdo->prepare("SELECT id FROM Medicament WHERE name = ?");
                $stmt->execute([$input['name']]);
                $medicament = $stmt->fetch();
                
                if (!$medicament) {
                    // Create new medicament if it doesn't exist
                    $stmt = $pdo->prepare("INSERT INTO Medicament (name, photo) VALUES (?, ?)");
                    $stmt->execute([
                        $input['name'],
                        $input['photo'] ?? null
                    ]);
                    $medicamentId = $pdo->lastInsertId();
                } else {
                    $medicamentId = $medicament['id'];
                    // Update photo if provided
                    if (isset($input['photo'])) {
                        $stmt = $pdo->prepare("UPDATE Medicament SET photo = ? WHERE id = ?");
                        $stmt->execute([$input['photo'], $medicamentId]);
                    }
                }
                
                // 2. Create prescribed medicament entry
                $stmt = $pdo->prepare("
                    INSERT INTO PrescribedMedicaments (
                        user_id, 
                        medicament_id, 
                        type, 
                        frequency, 
                        dosage, 
                        time
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $userId,
                    $medicamentId,
                    $input['type'] ?? null,
                    $input['frequency'] ?? null,
                    $input['dosage'] ?? null,
                    $input['time'] ?? null
                ]);
                
                $prescribedId = $pdo->lastInsertId();
                
                // Commit transaction
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'prescribed_id' => $prescribedId,
                    'medicament_id' => $medicamentId
                ]);
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
    error_log("PDOException: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'details' => $e->getMessage()
    ]);
}
?> 