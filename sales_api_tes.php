<?php
// Configuración de tiempo
set_time_limit(800);
ini_set('max_execution_time', 800);

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Conexión a la base de datos
$serverName = "SRV-PROFIT\CATA";
$connectionOptions = [
    "Database" => $basededatos,
    "Uid" => "admin",
    "PWD" => "admin",
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true
];

// Función para ejecutar y mostrar consultas - CORREGIDA
function ejecutarConsulta($conn, $sql, $nombreConsulta, $editable = true) {
    echo "<div class='section info'>";
    echo "<h2>🔍 $nombreConsulta</h2>";
    
    // Formulario para editar consulta
    if ($editable) {
        echo "<form method='post' style='margin-bottom: 15px;'>";
        echo "<input type='hidden' name='consulta_nombre' value='" . htmlspecialchars($nombreConsulta) . "'>";
        echo "<textarea name='consulta_sql' style='width: 100%; height: 150px; font-family: monospace; padding: 10px;'>" . htmlspecialchars($sql) . "</textarea>";
        echo "<div style='margin-top: 10px;'>";
        echo "<button type='submit' name='ejecutar_consulta' style='background-color: #28a745; color: white; padding: 8px 15px; border: none; cursor: pointer;'>🔄 Ejecutar Consulta Modificada</button>";
        echo "<button type='button' onclick='restaurarConsulta(\"" . htmlspecialchars($nombreConsulta) . "\")' style='background-color: #6c757d; color: white; padding: 8px 15px; border: none; cursor: pointer; margin-left: 10px;'>↩️ Restaurar Original</button>";
        echo "</div>";
        echo "</form>";
    } else {
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    }
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        echo "<div style='color: red; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb;'>";
        echo "<strong>❌ Error:</strong><br>";
        foreach ($errors as $error) {
            echo "SQLSTATE: " . $error['SQLSTATE'] . " | ";
            echo "Código: " . $error['code'] . " | ";
            echo "Mensaje: " . $error['message'] . "<br>";
        }
        echo "</div>";
        return 0;
    } else {
        $count = 0;
        echo "<div class='table-container'>";
        echo "<table>";
        
        // Obtener la primera fila para conocer las columnas
        $firstRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($firstRow) {
            // Mostrar encabezados de columnas
            echo "<tr><th>#</th>";
            foreach (array_keys($firstRow) as $columnName) {
                echo "<th>" . htmlspecialchars($columnName) . "</th>";
            }
            echo "</tr>";
            
            // Mostrar la primera fila
            $count++;
            echo "<tr><td>$count</td>";
            foreach ($firstRow as $value) {
                $displayValue = $value;
                if (is_string($value) && strlen($value) > 50) {
                    $displayValue = substr($value, 0, 50) . '...';
                }
                if ($value === null) {
                    $displayValue = 'NULL';
                }
                echo "<td>" . htmlspecialchars($displayValue) . "</td>";
            }
            echo "</tr>";
            
            // Mostrar las filas restantes
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $count++;
                echo "<tr><td>$count</td>";
                foreach ($row as $value) {
                    $displayValue = $value;
                    if (is_string($value) && strlen($value) > 50) {
                        $displayValue = substr($value, 0, 50) . '...';
                    }
                    if ($value === null) {
                        $displayValue = 'NULL';
                    }
                    echo "<td>" . htmlspecialchars($displayValue) . "</td>";
                }
                echo "</tr>";
                
                // Limitar a 50 registros para no sobrecargar
                if ($count >= 50) {
                    break;
                }
            }
        } else {
            echo "<tr><td colspan='100%' style='text-align: center; padding: 20px;'>No se encontraron registros</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
        echo "<p><strong>Total de registros encontrados: $count</strong></p>";
        sqlsrv_free_stmt($stmt);
    }
    echo "</div>";
    
    return $count;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Validación de Consultas SQL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; position: sticky; top: 0; }
        pre { background-color: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; white-space: pre-wrap; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        .table-container { max-height: 400px; overflow-y: auto; border: 1px solid #ddd; }
    </style>
    <script>
        function restaurarConsulta(nombreConsulta) {
            const consultasOriginales = {
                'VENTA_ACUMULADA_CAJA_CLASE': `SELECT TOP 30
    VENTA_ACUMULADA_CAJA_CLASE.B002200CVE as vendor,
    VENTA_ACUMULADA_CAJA_CLASE.B002201CPR as product,
    VENTA_ACUMULADA_CAJA_CLASE.KILO as kg,
    VENTA_ACUMULADA_CAJA_CLASE.UNIDAD_VENDIDA as quantity,
    VENTA_ACUMULADA_CAJA_CLASE.TOTAL_VTA_DIVISA_DES as total,
    VENTA_ACUMULADA_CAJA_CLASE.CAJAS_ESTADISTICAS as boxes,
    VENTA_ACUMULADA_CAJA_CLASE.MES as month,
    VENTA_ACUMULADA_CAJA_CLASE.ANNO as year,
    VENTA_ACUMULADA_CAJA_CLASE.B002200RSO as customer_name,
    VENTA_ACUMULADA_CAJA_CLASE.B002200CCL as customer_code,
    CONVERT(VARCHAR(10), VENTA_ACUMULADA_CAJA_CLASE.B002200FEC, 103) as fecha_factura
FROM dbo.VENTA_ACUMULADA_CAJA_CLASE
WHERE VENTA_ACUMULADA_CAJA_CLASE.ANNO = '2025' 
AND VENTA_ACUMULADA_CAJA_CLASE.MES = '10'
AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE NOT IN ('01', '')
ORDER BY VENTA_ACUMULADA_CAJA_CLASE.B002200FEC DESC`,
                'VENTA_ACUMULADA_KILO_CLASE': `SELECT TOP 30
    VENTA_ACUMULADA_KILO_CLASE.B002200CVE as vendor,
    VENTA_ACUMULADA_KILO_CLASE.B002201CPR as product,
    VENTA_ACUMULADA_KILO_CLASE.KILO as kg,
    VENTA_ACUMULADA_KILO_CLASE.UNIDAD_VENDIDA as quantity,
    VENTA_ACUMULADA_KILO_CLASE.TOTAL_VTA_DIVISA_DES as total,
    VENTA_ACUMULADA_KILO_CLASE.CAJAS_ESTADISTICAS as boxes,
    VENTA_ACUMULADA_KILO_CLASE.MES as month,
    VENTA_ACUMULADA_KILO_CLASE.ANNO as year,
    VENTA_ACUMULADA_KILO_CLASE.B002200RSO as customer_name,
    VENTA_ACUMULADA_KILO_CLASE.B002200CCL as customer_code,
    CONVERT(VARCHAR(10), VENTA_ACUMULADA_KILO_CLASE.B002200FEC, 103) as fecha_factura,
    VENTA_ACUMULADA_KILO_CLASE.B002201CLS as product_class,
    VENTA_ACUMULADA_KILO_CLASE.A00212DEL as product_line
FROM dbo.VENTA_ACUMULADA_KILO_CLASE
WHERE VENTA_ACUMULADA_KILO_CLASE.ANNO = '2025' 
AND VENTA_ACUMULADA_KILO_CLASE.MES = '10'
AND VENTA_ACUMULADA_KILO_CLASE.B002200CVE NOT IN ('01', '')
AND VENTA_ACUMULADA_KILO_CLASE.B002201CLS IN ('01')
ORDER BY VENTA_ACUMULADA_KILO_CLASE.B002200FEC DESC`,
                'VENTA_ACUMULADA (ORIGINAL)': `SELECT TOP 30
    VENTA_ACUMULADA.B002200CVE as vendor,
    VENTA_ACUMULADA.B002201CPR as product,
    VENTA_ACUMULADA.KILO as kg,
    VENTA_ACUMULADA.UNIDAD_VENDIDA as quantity,
    VENTA_ACUMULADA.TOTAL_VTA_DIVISA_DES as total,
    VENTA_ACUMULADA.CAJAS_ESTADISTICAS as boxes,
    VENTA_ACUMULADA.MES as month,
    VENTA_ACUMULADA.ANNO as year,
    VENTA_ACUMULADA.B002200RSO as customer_name,
    VENTA_ACUMULADA.B002200CCL as customer_code,
    CONVERT(VARCHAR(10), VENTA_ACUMULADA.B002200FEC, 103) as fecha_factura
FROM dbo.VENTA_ACUMULADA
WHERE VENTA_ACUMULADA.ANNO = '2025' AND VENTA_ACUMULADA.MES = '10'
AND VENTA_ACUMULADA.B002200CVE NOT IN ('01', '')
ORDER BY VENTA_ACUMULADA.B002200CVE ASC`,
                'CUOTAS': `SELECT 
    VENDEDOR as vendor,
    LINEA as product_line,
    MES as month,
    ANNO as year,
    CUOTA_LINEA as quota_amount,
    CUOTA_ACTIVACION as activation_quota,
    COD_EMPRESA as company_code
FROM dbo.CUOTAS
WHERE ANNO = '2025' AND MES = '10'
AND STATUS = '0'
ORDER BY VENDEDOR, LINEA`
            };
            
            if (consultasOriginales[nombreConsulta]) {
                // Encontrar el textarea correcto
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    const hiddenInput = form.querySelector('input[name=\"consulta_nombre\"]');
                    if (hiddenInput && hiddenInput.value === nombreConsulta) {
                        const textarea = form.querySelector('textarea[name=\"consulta_sql\"]');
                        if (textarea) {
                            textarea.value = consultasOriginales[nombreConsulta];
                        }
                    }
                });
            }
        }
    </script>
</head>
<body>
    <h1>Validación de Consultas SQL - Base de Datos: $basededatos</h1>";

try {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn === false) {
        $errors = sqlsrv_errors();
        $errorMessage = "Error de conexión: ";
        foreach ($errors as $error) {
            $errorMessage .= "SQLSTATE: " . $error['SQLSTATE'] . ", ";
            $errorMessage .= "Código: " . $error['code'] . ", ";
            $errorMessage .= "Mensaje: " . $error['message'];
        }
        
        echo "<div class='section error'>
                <h2>❌ Error de Conexión</h2>
                <p>$errorMessage</p>
                <pre>Servidor: $serverName\nBase de datos: $basededatos\nUsuario: admin</pre>
              </div>";
        exit();
    }
    
    echo "<div class='section success'>
            <h2>✅ Conexión Exitosa</h2>
            <p>Conectado a: <strong>$serverName</strong></p>
            <p>Base de datos: <strong>$basededatos</strong></p>
            <p>Empresa seleccionada: <strong>$empresaSeleccionada</strong></p>
          </div>";

    // Consultas predefinidas
    $consultas = [
        'VENTA_ACUMULADA_CAJA_CLASE' => "SELECT TOP 30
    VENTA_ACUMULADA_CAJA_CLASE.B002200CVE as vendor,
    VENTA_ACUMULADA_CAJA_CLASE.B002201CPR as product,
    VENTA_ACUMULADA_CAJA_CLASE.KILO as kg,
    VENTA_ACUMULADA_CAJA_CLASE.UNIDAD_VENDIDA as quantity,
    VENTA_ACUMULADA_CAJA_CLASE.TOTAL_VTA_DIVISA_DES as total,
    VENTA_ACUMULADA_CAJA_CLASE.CAJAS_ESTADISTICAS as boxes,
    VENTA_ACUMULADA_CAJA_CLASE.MES as month,
    VENTA_ACUMULADA_CAJA_CLASE.ANNO as year,
    VENTA_ACUMULADA_CAJA_CLASE.B002200RSO as customer_name,
    VENTA_ACUMULADA_CAJA_CLASE.B002200CCL as customer_code,
    CONVERT(VARCHAR(10), VENTA_ACUMULADA_CAJA_CLASE.B002200FEC, 103) as fecha_factura
FROM dbo.VENTA_ACUMULADA_CAJA_CLASE
WHERE VENTA_ACUMULADA_CAJA_CLASE.ANNO = '2025' 
AND VENTA_ACUMULADA_CAJA_CLASE.MES = '10'
AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE NOT IN ('01', '')
ORDER BY VENTA_ACUMULADA_CAJA_CLASE.B002200FEC DESC",

        'VENTA_ACUMULADA_KILO_CLASE' => "SELECT TOP 30
    VENTA_ACUMULADA_KILO_CLASE.B002200CVE as vendor,
    VENTA_ACUMULADA_KILO_CLASE.B002201CPR as product,
    VENTA_ACUMULADA_KILO_CLASE.KILO as kg,
    VENTA_ACUMULADA_KILO_CLASE.UNIDAD_VENDIDA as quantity,
    VENTA_ACUMULADA_KILO_CLASE.TOTAL_VTA_DIVISA_DES as total,
    VENTA_ACUMULADA_KILO_CLASE.CAJAS_ESTADISTICAS as boxes,
    VENTA_ACUMULADA_KILO_CLASE.MES as month,
    VENTA_ACUMULADA_KILO_CLASE.ANNO as year,
    VENTA_ACUMULADA_KILO_CLASE.B002200RSO as customer_name,
    VENTA_ACUMULADA_KILO_CLASE.B002200CCL as customer_code,
    CONVERT(VARCHAR(10), VENTA_ACUMULADA_KILO_CLASE.B002200FEC, 103) as fecha_factura,
    VENTA_ACUMULADA_KILO_CLASE.B002201CLS as product_class,
    VENTA_ACUMULADA_KILO_CLASE.A00212DEL as product_line
FROM dbo.VENTA_ACUMULADA_KILO_CLASE
WHERE VENTA_ACUMULADA_KILO_CLASE.ANNO = '2025' 
AND VENTA_ACUMULADA_KILO_CLASE.MES = '10'
AND VENTA_ACUMULADA_KILO_CLASE.B002200CVE NOT IN ('01', '')
AND VENTA_ACUMULADA_KILO_CLASE.B002201CLS IN ('01')
ORDER BY VENTA_ACUMULADA_KILO_CLASE.B002200FEC DESC",

        'VENTA_ACUMULADA (ORIGINAL)' => "SELECT TOP 30
    VENTA_ACUMULADA.B002200CVE as vendor,
    VENTA_ACUMULADA.B002201CPR as product,
    VENTA_ACUMULADA.KILO as kg,
    VENTA_ACUMULADA.UNIDAD_VENDIDA as quantity,
    VENTA_ACUMULADA.TOTAL_VTA_DIVISA_DES as total,
    VENTA_ACUMULADA.CAJAS_ESTADISTICAS as boxes,
    VENTA_ACUMULADA.MES as month,
    VENTA_ACUMULADA.ANNO as year,
    VENTA_ACUMULADA.B002200RSO as customer_name,
    VENTA_ACUMULADA.B002200CCL as customer_code,
    CONVERT(VARCHAR(10), VENTA_ACUMULADA.B002200FEC, 103) as fecha_factura
FROM dbo.VENTA_ACUMULADA
WHERE VENTA_ACUMULADA.ANNO = '2025' AND VENTA_ACUMULADA.MES = '10'
AND VENTA_ACUMULADA.B002200CVE NOT IN ('01', '')
ORDER BY VENTA_ACUMULADA.B002200CVE ASC",

        'CUOTAS' => "SELECT 
    VENDEDOR as vendor,
    LINEA as product_line,
    MES as month,
    ANNO as year,
    CUOTA_LINEA as quota_amount,
    CUOTA_ACTIVACION as activation_quota,
    COD_EMPRESA as company_code
FROM dbo.CUOTAS
WHERE ANNO = '2025' AND MES = '10'
AND STATUS = '0'
ORDER BY VENDEDOR, LINEA"
    ];

    // Procesar consulta modificada si se envió
    if (isset($_POST['ejecutar_consulta']) && isset($_POST['consulta_sql']) && isset($_POST['consulta_nombre'])) {
        $consultaModificada = $_POST['consulta_sql'];
        $nombreConsulta = $_POST['consulta_nombre'];
        
        echo "<div class='section warning'>";
        echo "<h3>🔄 Ejecutando Consulta Modificada: $nombreConsulta</h3>";
        echo "</div>";
        
        $count = ejecutarConsulta($conn, $consultaModificada, "$nombreConsulta (MODIFICADA)", false);
        $resultados[$nombreConsulta] = $count;
        
        // También ejecutar las otras consultas normales
        foreach ($consultas as $nombre => $sql) {
            if ($nombre !== $nombreConsulta) {
                $count = ejecutarConsulta($conn, $sql, $nombre, true);
                $resultados[$nombre] = $count;
            }
        }
    } else {
        // Ejecutar consultas originales
        $resultados = [];
        foreach ($consultas as $nombre => $sql) {
            $count = ejecutarConsulta($conn, $sql, $nombre, true);
            $resultados[$nombre] = $count;
        }
    }

    // RESUMEN FINAL
    echo "<div class='section success'>
            <h2>📊 Resumen de Consultas</h2>
            <table>
                <tr>
                    <th>Consulta</th>
                    <th>Tabla</th>
                    <th>Registros</th>
                    <th>Estado</th>
                </tr>";
    
    foreach ($resultados as $nombre => $count) {
        $estado = ($count > 0 ? "✅ OK" : "⚠️ Sin datos");
        $tabla = str_replace(' (MODIFICADA)', '', $nombre);
        echo "<tr>
                <td>$nombre</td>
                <td>$tabla</td>
                <td>$count</td>
                <td>$estado</td>
              </tr>";
    }
    
    echo "</table>
          </div>";

} catch (Exception $e) {
    echo "<div class='section error'>
            <h2>❌ Excepción</h2>
            <p>" . $e->getMessage() . "</p>
          </div>";
}

// Cerrar conexión
if (isset($conn) && $conn !== false) {
    sqlsrv_close($conn);
    echo "<div class='section info'>
            <h2>🔌 Conexión Cerrada</h2>
            <p>La conexión a la base de datos ha sido cerrada correctamente.</p>
          </div>";
}

echo "</body>
</html>";