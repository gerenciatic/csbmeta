<?php
session_start();
date_default_timezone_set('America/Caracas');

// Configuración de bases de datos por empresa
$empresas = [
    'A' => 'REPORT',      // CSB
    'B' => 'MX_REPORT',   // MAXI  
    'C' => 'MD_REPORT'    // MERIDA
];

// Obtener empresa seleccionada
$empresaSeleccionada = $_POST['empresa'] ?? $_SESSION['empresa_seleccionada'] ?? 'A';
$_SESSION['empresa_seleccionada'] = $empresaSeleccionada;
$basededatos = $empresas[$empresaSeleccionada] ?? 'REPORT';

// CONEXIÓN CORREGIDA
$serverName = "SRV-PROFIT\CATA";
$connectionOptions = [
    "Database" => $basededatos,
    "Uid" => "admin",
    "PWD" => "admin", 
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true
];

// Intentar conexión
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Error de conexión BD: " . print_r(sqlsrv_errors(), true));
}
?>