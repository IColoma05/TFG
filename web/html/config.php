<?php
// web/html/config.php

// Parámetros de conexión
$host = 'postgres';
$port = 5432;
$db   = 'postgres';
$user = 'admin';
$pass = '1234';

// DSN de PDO
$dsn = "pgsql:host={$host};port={$port};dbname={$db};";

try {
    // Crear conexión PDO
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Si falla la conexión, muestra error y para la ejecución
    die('Error de Conexión: ' . $e->getMessage());
}
