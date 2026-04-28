<?php
session_start();
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if (!$logged_in) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Unauthorized");
}

// Database Configuration
$db_host = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? '127.0.0.1');
$db_name = getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? 'alilogis_syirkahgenz_v2');
$db_user = getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? 'alilogis_syirkahgenz_v2');
$db_pass = getenv('DB_PASS') ?: ($_SERVER['DB_PASS'] ?? 'MBYVUYRynj3uVXcZG8DU');

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    exit('Database connection failed');
}

$conn->set_charset('utf8mb4');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Data_Peserta_' . date('Y-m-d_H-i') . '.csv');

$output = fopen('php://output', 'w');
// Add BOM to fix UTF-8 in Excel
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Write Headers
fputcsv($output, ['No', 'Waktu Daftar', 'Nama Lengkap', 'Gender', 'No WhatsApp', 'Alamat/Domisili', 'Bisnis', 'Status', 'Waktu Check-in']);

$result = $conn->query('SELECT * FROM registrations ORDER BY created_at DESC');
if ($result) {
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $no++,
            $row['created_at'],
            $row['full_name'],
            $row['gender'],
            $row['whatsapp_number'],
            $row['corporate_address'],
            $row['business_activity'],
            $row['status'],
            $row['checked_in_at'] ?? 'Belum Hadir'
        ]);
    }
}

fclose($output);
$conn->close();
