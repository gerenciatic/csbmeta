<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simplemente devolver datos de prueba
echo json_encode([
    "success" => true,
    "message" => "Conexión de prueba exitosa",
    "sales" => [
        {
            "vendor": "VEND01",
            "product": "PROD001",
            "kg": "10.5",
            "quantity": "5",
            "total": "52.50",
            "date": "2024-01-15"
        }
    ],
    "quotas" => [],
    "class_quotas" => [],
    "last_billing_date" => "2024-01-15",
    "debug" => [
        "server_time" => date('Y-m-d H:i:s'),
        "php_version" => phpversion()
    ]
]);
?>