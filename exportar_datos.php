<?php
// Incluir archivo de conexión
include_once 'includes/conexsql.php';

// Parámetros de entrada
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

try {
    // Consulta para obtener los datos
    $sql = "
    SELECT
        Fecha,
        YEAR(Fecha) AS ANNO,
        MONTH(Fecha) AS MES,
        DAY(Fecha) AS DIA,
        SUM(Total_Facturas) AS Total_Facturas,
        SUM(Total_Notas_Credito) AS Total_Notas_Credito,
        SUM(Total_Documentos) AS Total_Documentos,
        SUM(Monto_Total) AS Monto_Total
    FROM (
        -- Facturas
        SELECT
            B002200FEC AS Fecha,
            COUNT(*) AS Total_Facturas,
            0 AS Total_Notas_Credito,
            COUNT(*) AS Total_Documentos,
            SUM(B002200BIV) AS Monto_Total
        FROM dbo.FAC_CAB
        WHERE B002200FEC IS NOT NULL
        AND B002200FEC BETWEEN ? AND ?
        GROUP BY B002200FEC
        
        UNION ALL
        
        -- Notas de Crédito
        SELECT
            C002110FEF AS Fecha,
            0 AS Total_Facturas,
            COUNT(*) AS Total_Notas_Credito,
            COUNT(*) AS Total_Documentos,
            SUM(C002111PPR) AS Monto_Total
        FROM dbo.NC_DET
        WHERE C002110FEF IS NOT NULL
        AND C002110FEF BETWEEN ? AND ?
        GROUP BY C002110FEF
    ) AS Combined
    GROUP BY Fecha
    ORDER BY Fecha DESC
    ";

    // Ejecutar consulta con parámetros
    $params = array($fechaInicio, $fechaFin, $fechaInicio, $fechaFin);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        throw new Exception('Error en la consulta: ' . print_r(sqlsrv_errors(), true));
    }
    
    $datos = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $datos[] = $row;
    }
    
    // Liberar recursos
    sqlsrv_free_stmt($stmt);
    
    // Configurar headers para descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_facturas_notas.csv');
    
    // Crear output CSV
    $output = fopen('php://output', 'w');
    
    // Escribir headers
    fputcsv($output, [
        'Fecha', 'Año', 'Mes', 'Día', 
        'Total Facturas', 'Total Notas', 'Total Documentos', 'Monto Total'
    ]);
    
    // Escribir datos
    foreach ($datos as $fila) {
        fputcsv($output, $fila);
    }
    
    fclose($output);
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>