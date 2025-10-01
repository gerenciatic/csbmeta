<?php
// test_json.php - Probar respuestas JSON con versión
require_once __DIR__ . '/config.php';

// Simular datos de ventas
$datosVentas = [
    'total_ventas' => 15000,
    'ventas_hoy' => 25,
    'empresa_actual' => getEmpresaActual(),
    'config_empresa' => getConfigEmpresa()
];

// Usar la función sendJsonResponse
sendJsonResponse([
    'mensaje' => 'Prueba de respuesta JSON con versión',
    'datos_ventas' => $datosVentas,
    'empresa' => getEmpresaActual()
]);
?>