<?php

// Configuración de tiempo
set_time_limit(800);
ini_set('max_execution_time', 800);

header('Content-Type: application/json');
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

// Conexión a la base de datos con manejo de errores mejorado
$serverName = "SRV-PROFIT\CATA";
$connectionOptions = [
    "Database" => $basededatos,
    "Uid" => "admin",
    "PWD" => "admin",
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true // IMPORTANTE: Devuelve fechas como strings
];

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
        
        echo json_encode([
            "success" => false, 
            "message" => $errorMessage,
            "debug" => [
                "server" => $serverName,
                "database" => $basededatos,
                "user" => "admin",
                "empresa" => $empresaSeleccionada
            ]
        ]);
        exit();
    }
    
    // Si la conexión es exitosa, continuar con el procesamiento
    $action = $_POST['action'] ?? '';

    if ($action === 'get_sales_data') {
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        $vendor = $_POST['vendor'] ?? 'all';
        
        // CONSULTA MEJORADA USANDO VENTA_ACUMULADA
        $sql = "SELECT
            VENTA_ACUMULADA.B002200CVE as vendor,
            VENTA_ACUMULADA.B002201CPR as product,
            VENTA_ACUMULADA.KILO as kg,
            VENTA_ACUMULADA.UNIDAD_VENDIDA as quantity,
            VENTA_ACUMULADA.TOTAL_VTA_DIVISA_DES as total,
            VENTA_ACUMULADA.CAJAS_ESTADISTICAS as boxes,
            VENTA_ACUMULADA.MES as month,
            VENTA_ACUMULADA.ANNO as year,
            -- VENTA_ACUMULADA.B002201CLS as product_class,
            VENTA_ACUMULADA.B002200RSO as customer_name,
            VENTA_ACUMULADA.B002200CCL as customer_code,
            CONVERT(VARCHAR(10), VENTA_ACUMULADA.B002200FEC, 103) as fecha_factura

            
        FROM dbo.VENTA_ACUMULADA
        WHERE VENTA_ACUMULADA.ANNO = '2025' AND VENTA_ACUMULADA.MES = '09'
        AND VENTA_ACUMULADA.B002200CVE NOT IN ('01', '')"; // FILTRO PARA EXCLUIR VENDEDORES 01 Y EN BLANCO
        
        $params = [$year, $month];
        
        if ($vendor !== 'all') {
            $sql .= " AND VENTA_ACUMULADA.B002200CVE = ?";
            $params[] = $vendor;
        }
        
        $sql .= " ORDER BY vendor, customer_code";
        
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt === false) {
            echo json_encode(["success" => false, "message" => "Error en consulta: " . print_r(sqlsrv_errors(), true)]);
            exit();
        }
        
        // Procesar resultados
        $salesData = [];
        $lastBillingDate = null;
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $salesData[] = $row;
            
            // Obtener la última fecha de facturación
            if (isset($row['fecha_factura']) && !empty($row['fecha_factura'])) {
                $currentDate = $row['fecha_factura'];
                
                // Si es la primera fecha o es más reciente que la almacenada
                if (!$lastBillingDate || $currentDate > $lastBillingDate) {
                    $lastBillingDate = $currentDate;
                }
            }
        }
        
// CONSULTA ESPECÍFICA PARA OBTENER LA ÚLTIMA FECHA DE FACTURACIÓN
// Si no encontramos fecha en los datos anteriores, hacemos una consulta específica
if (!$lastBillingDate) {
            // $lastDateSql = "SELECT MAX(B002200FEC) as ultima_fecha                            FROM dbo.VENTA_ACUMULADA                            WHERE ANNO = ? AND MES = ?                           AND B002200CVE NOT IN ('01', '')";

// ... dentro de la acción 'get_sales_data' ...

// CONSULTA ESPECÍFICA PARA OBTENER LA ÚLTIMA FECHA DE FACTURACIÓN
$lastDateSql = "SELECT 
               DAY(MAX(B002200FEC)) as dia,
               MONTH(MAX(B002200FEC)) as mes, 
               YEAR(MAX(B002200FEC)) as anno
               FROM dbo.VENTA_ACUMULADA 
               WHERE ANNO = ? AND MES = ?
               AND B002200CVE NOT IN ('01', '')";
               
$lastDateParams = [$year, $month];
$lastDateStmt = sqlsrv_query($conn, $lastDateSql, $lastDateParams);

$lastBillingDate = null;
if ($lastDateStmt !== false) {
    $lastDateRow = sqlsrv_fetch_array($lastDateStmt, SQLSRV_FETCH_ASSOC);
    if ($lastDateRow && $lastDateRow['ultima_fecha']) {
        $lastBillingDate = $lastDateRow['ultima_fecha'];
    }
}

// También verificar en los datos de venta por si hay fechas más recientes
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $salesData[] = $row;
    
    if (isset($row['fecha_factura']) && !empty($row['fecha_factura'])) {
        $currentDate = $row['fecha_factura'];
        
        if (!$lastBillingDate || $currentDate > $lastBillingDate) {
            $lastBillingDate = $currentDate;
        }
    }
}

// Si aún no tenemos fecha, usar la fecha actual como fallback
if (!$lastBillingDate) {
    $lastBillingDate = date('Y-m-d');
}

}
// ... resto del código ...

// ... resto del código ...
        
        // Obtener cuotas generales de vendedores
        $quotasSql = "SELECT * FROM CUOTAS_VENDEDORES WHERE ANNO = ? AND MES = ?";
        $quotaParams = [$year, $month];
        
        if ($vendor !== 'all') {
            $quotasSql .= " AND CODIGO_VENDEDOR = ?";
            $quotaParams[] = $vendor;
        }
        
        $quotasSql .= " ORDER BY CODIGO_VENDEDOR";
        
        $quotasStmt = sqlsrv_query($conn, $quotasSql, $quotaParams);
        $quotasData = [];
        
        if ($quotasStmt !== false) {
            while ($row = sqlsrv_fetch_array($quotasStmt, SQLSRV_FETCH_ASSOC)) {
                $quotasData[] = $row;
            }
        }
        
        // Obtener cuotas por clases de producto
        $classQuotasSql = "SELECT * FROM CUOTAS_CLASES_PRODUCTO WHERE ANNO = ? AND MES = ?";
        $classQuotaParams = [$year, $month];
        
        if ($vendor !== 'all') {
            $classQuotasSql .= " AND CODIGO_VENDEDOR = ?";
            $classQuotaParams[] = $vendor;
        }
        
        $classQuotasSql .= " ORDER BY CODIGO_VENDEDOR, CLASE_PRODUCTO";
        
        $classQuotasStmt = sqlsrv_query($conn, $classQuotasSql, $classQuotaParams);
        $classQuotasData = [];
        
        if ($classQuotasStmt !== false) {
            while ($row = sqlsrv_fetch_array($classQuotasStmt, SQLSRV_FETCH_ASSOC)) {
                $classQuotasData[] = $row;
            }
        }
        
        // Obtener lista de vendedores desde VENTA_ACUMULADA (excluyendo 01 y en blanco)
        $vendorsSql = "SELECT DISTINCT VENTA_ACUMULADA.B002200CVE as vendor 
                       FROM dbo.VENTA_ACUMULADA 
                       WHERE VENTA_ACUMULADA.ANNO = ? AND VENTA_ACUMULADA.MES = ?
                       AND VENTA_ACUMULADA.B002200CVE NOT IN ('01', '')
                       ORDER BY vendor";
        
        $vendorsStmt = sqlsrv_query($conn, $vendorsSql, [$year, $month]);
        $vendorsList = [];
        
        if ($vendorsStmt !== false) {
            while ($row = sqlsrv_fetch_array($vendorsStmt, SQLSRV_FETCH_ASSOC)) {
                $vendorsList[] = $row['vendor'];
            }
        }
        
        echo json_encode([
            "success" => true,
            "sales" => $salesData, 
            "quotas" => $quotasData,
            "class_quotas" => $classQuotasData,
            "vendors" => $vendorsList,
            "last_billing_date" => $lastBillingDate  // Agregamos la última fecha de facturación
        ]);
        
        // Liberar recursos
        if ($stmt) sqlsrv_free_stmt($stmt);
        if ($quotasStmt) sqlsrv_free_stmt($quotasStmt);
        if ($classQuotasStmt) sqlsrv_free_stmt($classQuotasStmt);
        if ($vendorsStmt) sqlsrv_free_stmt($vendorsStmt);
        
    } elseif ($action === 'get_last_billing_date') {
        // Acción específica para obtener solo la última fecha de facturación
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        $vendor = $_POST['vendor'] ?? 'all';
        
        $sql = "SELECT MAX(CONVERT(VARCHAR(10), B002200FEC, 120)) as ultima_fecha 
                FROM dbo.VENTA_ACUMULADA 
                WHERE ANNO = ? AND MES = ?
                AND B002200CVE NOT IN ('01', '')";
        
        $params = [$year, $month];
        
        if ($vendor !== 'all') {
            $sql .= " AND B002200CVE = ?";
            $params[] = $vendor;
        }
        
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt === false) {
            echo json_encode(["success" => false, "message" => "Error en consulta: " . print_r(sqlsrv_errors(), true)]);
            exit();
        }
        
        $lastBillingDate = null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row && $row['ultima_fecha']) {
            $lastBillingDate = $row['ultima_fecha'];
        }
        
        echo json_encode([
            "success" => true,
            "last_billing_date" => $lastBillingDate,
            "year" => $year,
            "month" => $month,
            "vendor" => $vendor
        ]);
        
        if ($stmt) sqlsrv_free_stmt($stmt);


    } elseif ($action === 'save_quota') {
        $vendor = $_POST['vendor'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        $amount = $_POST['amount'] ?? 0;
        $boxes = $_POST['boxes'] ?? 0;
        $kilos = $_POST['kilos'] ?? 0;
        $user = 'Sistema';
        
        // Verificar si ya existe una cuota
        $checkSql = "SELECT * FROM CUOTAS_VENDEDORES 
                     WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
        $checkParams = [$vendor, $year, $month];
        $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);
        
        if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
            // Actualizar cuota existente
            $updateSql = "UPDATE CUOTAS_VENDEDORES 
                          SET CUOTA_DIVISA = ?, CUOTA_CAJAS = ?, CUOTA_KILOS = ?, 
                              FECHA_ACTUALIZACION = GETDATE(), USUARIO_ACTUALIZACION = ?
                          WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
            $updateParams = [$amount, $boxes, $kilos, $user, $vendor, $year, $month];
            $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);
            
            if ($updateStmt === false) {
                echo json_encode(["success" => false, "message" => "Error al actualizar cuota: " . print_r(sqlsrv_errors(), true)]);
                exit();
            }
            
            echo json_encode(["success" => true, "message" => "Cuota actualizada correctamente"]);
        } else {
            // Insertar nueva cuota
            $insertSql = "INSERT INTO CUOTAS_VENDEDORES 
                         (CODIGO_VENDEDOR, ANNO, MES, CUOTA_DIVISA, CUOTA_CAJAS, CUOTA_KILOS, USUARIO_ACTUALIZACION)
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertParams = [$vendor, $year, $month, $amount, $boxes, $kilos, $user];
            $insertStmt = sqlsrv_query($conn, $insertSql, $insertParams);
            
            if ($insertStmt === false) {
                echo json_encode(["success" => false, "message" => "Error al insertar cuota: " . print_r(sqlsrv_errors(), true)]);
                exit();
            }
            
            echo json_encode(["success" => true, "message" => "Cuota creada correctamente"]);
        }
        
    } elseif ($action === 'save_class_quota') {
        // Nueva función para guardar cuotas por clase de producto
        $vendor = $_POST['vendor'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        $productClass = $_POST['product_class'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $boxes = $_POST['boxes'] ?? 0;
        $kilos = $_POST['kilos'] ?? 0;
        $user = 'Sistema';
        
        // Verificar si ya existe una cuota para esta clase
        $checkSql = "SELECT * FROM CUOTAS_CLASES_PRODUCTO 
                     WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ? AND CLASE_PRODUCTO = ?";
        $checkParams = [$vendor, $year, $month, $productClass];
        $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);
        
        if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
            // Actualizar cuota existente
            $updateSql = "UPDATE CUOTAS_CLASES_PRODUCTO 
                          SET CUOTA_DIVISA = ?, CUOTA_CAJAS = ?, CUOTA_KILOS = ?, 
                              FECHA_ACTUALIZACION = GETDATE(), USUARIO_ACTUALIZACION = ?
                          WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ? AND CLASE_PRODUCTO = ?";
            $updateParams = [$amount, $boxes, $kilos, $user, $vendor, $year, $month, $productClass];
            $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);
            
            if ($updateStmt === false) {
                echo json_encode(["success" => false, "message" => "Error al actualizar cuota por clase: " . print_r(sqlsrv_errors(), true)]);
                exit();
            }
            
            echo json_encode(["success" => true, "message" => "Cuota por clase actualizada correctamente"]);
        } else {
            // Insertar nueva cuota por clase
            $insertSql = "INSERT INTO CUOTAS_CLASES_PRODUCTO 
                         (CODIGO_VENDEDOR, ANNO, MES, CLASE_PRODUCTO, CUOTA_DIVISA, CUOTA_CAJAS, CUOTA_KILOS, USUARIO_ACTUALIZACION)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insertParams = [$vendor, $year, $month, $productClass, $amount, $boxes, $kilos, $user];
            $insertStmt = sqlsrv_query($conn, $insertSql, $insertParams);
            
            if ($insertStmt === false) {
                echo json_encode(["success" => false, "message" => "Error al insertar cuota por clase: " . print_r(sqlsrv_errors(), true)]);
                exit();
            }
            
            echo json_encode(["success" => true, "message" => "Cuota por clase creada correctamente"]);
        }
        
    } elseif ($action === 'delete_quota') {
        $vendor = $_POST['vendor'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        
        $deleteSql = "DELETE FROM CUOTAS_VENDEDORES 
                      WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
        $deleteParams = [$vendor, $year, $month];
        $deleteStmt = sqlsrv_query($conn, $deleteSql, $deleteParams);
        
        if ($deleteStmt === false) {
            echo json_encode(["success" => false, "message" => "Error al eliminar cuota: " . print_r(sqlsrv_errors(), true)]);
            exit();
        }
        
        echo json_encode(["success" => true, "message" => "Cuota eliminada correctamente"]);
        
    } elseif ($action === 'delete_class_quota') {
        // Nueva función para eliminar cuotas por clase de producto
        $vendor = $_POST['vendor'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        $productClass = $_POST['product_class'] ?? '';
        
        $deleteSql = "DELETE FROM CUOTAS_CLASES_PRODUCTO 
                      WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ? AND CLASE_PRODUCTO = ?";
        $deleteParams = [$vendor, $year, $month, $productClass];
        $deleteStmt = sqlsrv_query($conn, $deleteSql, $deleteParams);
        
        if ($deleteStmt === false) {
            echo json_encode(["success" => false, "message" => "Error al eliminar cuota por clase: " . print_r(sqlsrv_errors(), true)]);
            exit();
        }
        
        echo json_encode(["success" => true, "message" => "Cuota por clase eliminada correctamente"]);
        
    } elseif ($action === 'change_company') {
        echo json_encode(["success" => true, "message" => "Empresa cambiada a $empresaSeleccionada"]);
        
    } else {
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Excepción: " . $e->getMessage()
    ]);
}

// Cerrar conexión
if (isset($conn) && $conn !== false) {
    sqlsrv_close($conn);
}
?>