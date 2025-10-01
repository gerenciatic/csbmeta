<?php
// Configuración global de la aplicación
define('APP_NAME', 'Dashboard de Ventas - CSB Meta');
define('APP_VERSION', '2.0');
define('DEFAULT_TIMEZONE', 'America/Caracas');
define('MAX_EXECUTION_TIME', 300);
define('DEFAULT_EMPRESA', 'A');

// Configuración de empresas
$GLOBALS['empresas_config'] = [
    'A' => [
        'nombre' => 'CSB',
        'basededatos' => 'REPORT'
    ],
    'B' => [
        'nombre' => 'MAXI', 
        'basededatos' => 'MX_REPORT'
    ],
    'C' => [
        'nombre' => 'MERIDA',
        'basededatos' => 'MD_REPORT'
    ]
];

// Headers para CORS y JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de tiempo
set_time_limit(MAX_EXECUTION_TIME);
ini_set('max_execution_time', MAX_EXECUTION_TIME);
date_default_timezone_set(DEFAULT_TIMEZONE);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para obtener empresa actual
function getEmpresaActual() {
    return $_SESSION['empresa_seleccionada'] ?? DEFAULT_EMPRESA;
}

// Función para obtener configuración de empresa
function getConfigEmpresa($empresa = null) {
    $empresa = $empresa ?? getEmpresaActual();
    return $GLOBALS['empresas_config'][$empresa] ?? $GLOBALS['empresas_config'][DEFAULT_EMPRESA];
}
?>