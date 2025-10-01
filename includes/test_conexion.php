<?php
header('Content-Type: text/plain; charset=utf-8'); // Cambiado a text/plain
session_start();
date_default_timezone_set('America/Caracas');

echo "=== INICIANDO PRUEBA DE CONEXIÓN ===\n\n";

$serverName = "SRV-PROFIT\CATA";
$basededatos = "REPORT";

// OPCIONES CORREGIDAS
$connectionOptions = [
    "Database" => $basededatos,
    "Uid" => "admin",
    "PWD" => "admin",
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true
];

echo "Configuración de conexión:\n";
echo "- Servidor: $serverName\n";
echo "- Base de datos: $basededatos\n"; 
echo "- Usuario: admin\n";
echo "- Opciones: " . print_r($connectionOptions, true) . "\n";

echo "\nIntentando conectar...\n";

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    $errors = sqlsrv_errors();
    foreach ($errors as $error) {
        echo "SQLSTATE: " . $error['SQLSTATE'] . "\n";
        echo "Código: " . $error['code'] . "\n"; 
        echo "Mensaje: " . $error['message'] . "\n\n";
    }
} else {
    echo "✅ CONEXIÓN EXITOSA!\n\n";
    
    // Probar consulta simple
    echo "Probando consulta...\n";
    $sql = "SELECT TOP 5 B002200CVE as vendor FROM VENTA_ACUMULADA";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        echo "❌ Error en consulta:\n";
        $errors = sqlsrv_errors();
        foreach ($errors as $error) {
            echo "SQLSTATE: " . $error['SQLSTATE'] . "\n";
            echo "Mensaje: " . $error['message'] . "\n";
        }
    } else {
        echo "✅ Consulta ejecutada correctamente\n\n";
        echo "Resultados:\n";
        
        $count = 0;
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $count++;
            echo "Vendedor $count: " . ($row['vendor'] ?? 'N/A') . "\n";
        }
        echo "\nTotal registros encontrados: $count\n";
        
        sqlsrv_free_stmt($stmt);
    }
    
    // Probar otra consulta para ver estructura
    echo "\n--- Probando estructura de VENTA_ACUMULADA ---\n";
    $sql2 = "SELECT TOP 1 * FROM VENTA_ACUMULADA";
    $stmt2 = sqlsrv_query($conn, $sql2);
    
    if ($stmt2 !== false) {
        $row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
        if ($row) {
            echo "Columnas disponibles:\n";
            foreach(array_keys($row) as $key) {
                echo "- $key\n";
            }
        }
        sqlsrv_free_stmt($stmt2);
    }
    
    sqlsrv_close($conn);
    echo "\n✅ Conexión cerrada correctamente\n";
}

echo "\n=== PRUEBA FINALIZADA ===\n";
?>