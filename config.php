<?php
declare(strict_types=1);
$dbHost = getenv('DB_HOST') ?: 'mysql-danigt.alwaysdata.net';
$dbName = getenv('DB_NAME') ?: 'danigt_gestionar';
$dbUser = getenv('DB_USER') ?: 'danigt';
// Por seguridad, sustituye este marcador por tu contraseña o define DB_PASS en el servidor.
$dbPass = getenv('DB_PASS') ?: 'clase1234';
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS articulos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(180) NOT NULL,
        marca VARCHAR(120) NOT NULL DEFAULT '',
        unidades INT UNSIGNED NOT NULL DEFAULT 0,
        ubicacion VARCHAR(180) NOT NULL DEFAULT '',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_nombre (nombre), INDEX idx_marca (marca), INDEX idx_ubicacion (ubicacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    http_response_code(500);
    exit('No fue posible conectar con MySQL. Revisa config.php y los permisos para crear tablas.');
}
