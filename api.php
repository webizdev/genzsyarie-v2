<?php
/**
 * API Endpoint for Syirkah Bisnis Mastery Registration
 * Compatible with cPanel / Shared Hosting (PHP + MySQL)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database Configuration
$db_host = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? '127.0.0.1');
$db_name = getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? 'alilogis_syirkahgenz_v2');
$db_user = getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? 'alilogis_syirkahgenz_v2');
$db_pass = getenv('DB_PASS') ?: ($_SERVER['DB_PASS'] ?? 'MBYVUYRynj3uVXcZG8DU');

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8mb4');

// Auto-init site_stats table
$conn->query("CREATE TABLE IF NOT EXISTS site_stats (
    stat_key VARCHAR(50) PRIMARY KEY,
    stat_value INT DEFAULT 0
)");
// Ensure initial keys exist
$conn->query("INSERT IGNORE INTO site_stats (stat_key, stat_value) VALUES ('page_view', 0), ('click_register', 0), ('click_wa', 0)");

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
        $stmt->close();
        break;

    case 'POST':
        // Handle Tracking
        if (isset($_GET['action']) && $_GET['action'] === 'track') {
            $key = trim($input['key'] ?? '');
            if (!empty($key)) {
                $stmt = $conn->prepare("UPDATE site_stats SET stat_value = stat_value + 1 WHERE stat_key = ?");
                $stmt->bind_param('s', $key);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Key is required']);
            }
            break;
        }

        // Create new registration (Original POST logic)
        $full_name = trim($input['full_name'] ?? '');
        $whatsapp = trim($input['whatsapp_number'] ?? '');
        $address = trim($input['corporate_address'] ?? '');
        $business_activity = trim($input['business_activity'] ?? '');

        if (empty($full_name) || empty($whatsapp) || empty($address) || empty($business_activity)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
            exit;
        }

        $stmt = $conn->prepare('INSERT INTO registrations (full_name, whatsapp_number, corporate_address, business_activity, status) VALUES (?, ?, ?, ?, ?)');
        $status = 'pending';
        $stmt->bind_param('sssss', $full_name, $whatsapp, $address, $business_activity, $status);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Registrasi berhasil disimpan',
                'data' => [
                    'id' => $stmt->insert_id,
                    'full_name' => $full_name,
                    'whatsapp_number' => $whatsapp,
                    'corporate_address' => $address,
                    'business_activity' => $business_activity,
                    'status' => 'pending'
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'GET':
        // Handle Stats retrieval
        if (isset($_GET['action']) && $_GET['action'] === 'stats') {
            $result = $conn->query('SELECT * FROM site_stats');
            $stats = [];
            while ($row = $result->fetch_assoc()) {
                $stats[$row['stat_key']] = intval($row['stat_value']);
            }
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
        }

        // Get all registrations (for admin)
        $result = $conn->query('SELECT * FROM registrations ORDER BY created_at DESC');
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'PUT':
        $id = intval($input['id'] ?? 0);
        $status = trim($input['status'] ?? '');
        $valid_statuses = ['pending', 'confirmed', 'tolak'];

        if ($id === 0 || !in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tidak valid atau status tidak dikenali']);
            exit;
        }

        $stmt = $conn->prepare('UPDATE registrations SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = intval($input['id'] ?? 0);

        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit;
        }

        $stmt = $conn->prepare('DELETE FROM registrations WHERE id = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Data peserta berhasil dihapus']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();

