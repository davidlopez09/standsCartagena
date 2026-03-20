<?php
// ── Configuración de conexión ──────────────────────────────────────────────
$host    = 'localhost';
$dbname  = 'standscartagenas';
$db_user = 'root';
$db_pass = '';          // Cambia si tu MySQL tiene contraseña

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['ok' => false, 'msg' => 'Error de conexión: ' . $e->getMessage()]));
}
