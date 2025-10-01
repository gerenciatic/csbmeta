<?php
session_start();
date_default_timezone_set('America/Caracas');
// Obtener el mes actual
$mes = date('n');
// Configuración de tiempo
set_time_limit(300);
ini_set('max_execution_time', 300);


// Configuración de bases de datos por empresa
$empresas = [
    'A' => 'REPORT',      // CSB
    'B' => 'MX_REPORT',   // MAXI
    'C' => 'MD_REPORT'    // MERIDA
];

// Obtener empresa seleccionada (de POST o SESSION)
$empresaSeleccionada = $_POST['empresa'] ?? $_SESSION['empresa_seleccionada'] ?? 'A';

// Guardar en sesión para persistencia
$_SESSION['empresa_seleccionada'] = $empresaSeleccionada;
$basededatos = $empresas[$empresaSeleccionada] ?? 'REPORT';

// Conexión a la base de datos
$serverName = "SRV-PROFIT\CATA";
$connectionOptions = [
    "Database" => $basededatos,
    "Uid" => "admin",
    "PWD" => "admin",
    "TrustServerCertificate"=>"true",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}
?>