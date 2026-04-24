<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$whatsapp = $_POST['whatsapp'] ?? '';
if (empty($whatsapp) || empty($_FILES['payment_proof'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing whatsapp or file']);
    exit;
}

$file = $_FILES['payment_proof'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $err_msg = 'Upload error code: ' . $file['error'];
    if ($file['error'] === UPLOAD_ERR_INI_SIZE) {
        $err_msg = 'Ukuran file terlalu besar (Maksimal 2MB)';
    }
    echo json_encode(['success' => false, 'message' => $err_msg]);
    exit;
}


$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'proof_' . preg_replace('/[^0-9]/', '', $whatsapp) . '_' . time() . '.' . $ext;
$target_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // Save to DB
    $db_host = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? 'localhost');
    $db_name = getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? 'alilogis_syirkahgenz_v2');
    $db_user = getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? 'alilogis_syirkahgenz_v2');
    $db_pass = getenv('DB_PASS') ?: ($_SERVER['DB_PASS'] ?? 'MBYVUYRynj3uVXcZG8DU');

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $stmt = $conn->prepare('UPDATE registrations SET payment_proof = ? WHERE whatsapp_number = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('ss', $filename, $whatsapp);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Upload successful', 'filename' => $filename]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
}
?>
