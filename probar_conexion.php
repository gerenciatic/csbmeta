<?php
header('Content-Type: application/json');

// Incluir archivo de conexión
include_once 'includes/conexsql.php';

try {
    // Verificar si la conexión es válida
    if ($conn === false) {
        throw new Exception('Conexión no establecida');
    }
    
    // Ejecutar una consulta simple para probar la conexión
    $sql = "SELECT 1 as test";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        throw new Exception('Error en consulta: ' . print_r(sqlsrv_errors(), true));
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if ($row && $row['test'] == 1) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Conexión exitosa a la base de datos (' . $basededatos . ')'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo verificar la conexión'
        ]);
    }
    
    // Liberar recursos
    sqlsrv_free_stmt($stmt);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>