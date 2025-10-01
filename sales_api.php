<?php
session_start();
date_default_timezone_set('America/Caracas');
header('Content-Type: application/json');

// Incluir conexión
include 'includes/conexsql.php';

// Función para respuesta estandarizada
function sendResponse($success, $data = [], $message = '') {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'empresa' => $_SESSION['empresa_seleccionada'] ?? 'A',
            'basededatos' => $GLOBALS['basededatos'] ?? 'REPORT'
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

// Función para ejecutar consultas seguras
function executeQuery($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $errorMessage = "Error en consulta SQL: ";
        foreach ($errors as $error) {
            $errorMessage .= "SQLSTATE: " . $error['SQLSTATE'] . ", ";
            $errorMessage .= "Mensaje: " . $error['message'];
        }
        throw new Exception($errorMessage);
    }
    
    return $stmt;
}

// Función para obtener datos de ventas
function getSalesData($conn, $year, $month, $vendor = 'all') {
    // CONSULTA PRINCIPAL CON LAS COLUMNAS CORRECTAS
    $sql = "SELECT 
            VA.B002200CVE as vendor,
            VA.B002201CPR as product,
            VA.KILO as kg,
            VA.UNIDAD_VENDIDA as quantity,
            VA.TOTAL_VTA_DIVISA_DES as total,
            VA.CAJAS_ESTADISTICAS as boxes,
            VA.MES as month,
            VA.ANNO as year,
            VA.B002200RSO as customer_name,
            VA.B002200CCL as customer_code,
            CONVERT(VARCHAR(10), VA.B002200FEC, 120) as date,
            VA.B002200DOC as doc,
            VA.B002201CLS as category
        FROM VENTA_ACUMULADA VA
        WHERE VA.ANNO = ? 
          AND VA.MES = ?
          AND VA.B002200CVE NOT IN ('01', '')";
    
    $params = [$year, $month];
    
    if ($vendor !== 'all') {
        $sql .= " AND VA.B002200CVE = ?";
        $params[] = $vendor;
    }
    
    $sql .= " ORDER BY VA.B002200FEC DESC";
    
    $stmt = executeQuery($conn, $sql, $params);
    $salesData = [];
    $lastBillingDate = null;
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Limpiar y formatear datos
        $row['total'] = floatval($row['total'] ?? 0);
        $row['kg'] = floatval($row['kg'] ?? 0);
        $row['quantity'] = intval($row['quantity'] ?? 0);
        $row['unitPrice'] = $row['quantity'] > 0 ? $row['total'] / $row['quantity'] : 0;
        
        $salesData[] = $row;
        
        // Obtener última fecha
        if (!empty($row['date'])) {
            if (!$lastBillingDate || $row['date'] > $lastBillingDate) {
                $lastBillingDate = $row['date'];
            }
        }
    }
    
    sqlsrv_free_stmt($stmt);
    return ['sales' => $salesData, 'last_date' => $lastBillingDate];
}

// Función para obtener cuotas
function getQuotasData($conn, $year, $month, $vendor = 'all') {
    $sql = "SELECT * FROM CUOTAS_VENDEDORES WHERE ANNO = ? AND MES = ?";
    $params = [$year, $month];
    
    if ($vendor !== 'all') {
        $sql .= " AND CODIGO_VENDEDOR = ?";
        $params[] = $vendor;
    }
    
    $sql .= " ORDER BY CODIGO_VENDEDOR";
    
    try {
        $stmt = executeQuery($conn, $sql, $params);
        $quotasData = [];
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $quotasData[] = $row;
        }
        
        sqlsrv_free_stmt($stmt);
        return $quotasData;
    } catch (Exception $e) {
        // Si la tabla no existe, retornar array vacío
        return [];
    }
}

// Función para obtener cuotas por categoría
function getClassQuotasData($conn, $year, $month, $vendor = 'all') {
    $sql = "SELECT * FROM CUOTAS_CLASES_PRODUCTO WHERE ANNO = ? AND MES = ?";
    $params = [$year, $month];
    
    if ($vendor !== 'all') {
        $sql .= " AND CODIGO_VENDEDOR = ?";
        $params[] = $vendor;
    }
    
    $sql .= " ORDER BY CODIGO_VENDEDOR, CLASE_PRODUCTO";
    
    try {
        $stmt = executeQuery($conn, $sql, $params);
        $classQuotasData = [];
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $classQuotasData[] = $row;
        }
        
        sqlsrv_free_stmt($stmt);
        return $classQuotasData;
    } catch (Exception $e) {
        // Si la tabla no existe, retornar array vacío
        return [];
    }
}

// Función para obtener lista de vendedores
function getVendorsList($conn, $year, $month) {
    $sql = "SELECT DISTINCT B002200CVE as vendor 
            FROM VENTA_ACUMULADA 
            WHERE ANNO = ? AND MES = ?
            AND B002200CVE NOT IN ('01', '')
            ORDER BY vendor";
    
    $stmt = executeQuery($conn, $sql, [$year, $month]);
    $vendorsList = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $vendorsList[] = $row['vendor'];
    }
    
    sqlsrv_free_stmt($stmt);
    return $vendorsList;
}

// MANEJO PRINCIPAL DE PETICIONES
try {
    // Verificar conexión
    if ($conn === false) {
        sendResponse(false, [], 'No se pudo conectar a la base de datos. Verifique la configuración.');
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Log de la acción recibida (para debugging)
    error_log("API Action: " . $action . " - Empresa: " . ($_SESSION['empresa_seleccionada'] ?? 'A'));
    
    switch($action) {
        case 'get_sales_data':
            $year = $_POST['year'] ?? date('Y');
            $month = $_POST['month'] ?? date('m');
            $vendor = $_POST['vendor'] ?? 'all';
            
            // Validar parámetros
            if (!is_numeric($year) || !is_numeric($month)) {
                sendResponse(false, [], "Parámetros inválidos: año=$year, mes=$month");
            }
            
            // Obtener todos los datos
            $salesResult = getSalesData($conn, $year, $month, $vendor);
            $quotasData = getQuotasData($conn, $year, $month, $vendor);
            $classQuotasData = getClassQuotasData($conn, $year, $month, $vendor);
            $vendorsList = getVendorsList($conn, $year, $month);
            
            sendResponse(true, [
                "sales" => $salesResult['sales'],
                "quotas" => $quotasData,
                "class_quotas" => $classQuotasData,
                "vendors" => $vendorsList,
                "last_billing_date" => $salesResult['last_date'] ?: "No disponible",
                "filters" => [
                    "year" => $year,
                    "month" => $month,
                    "vendor" => $vendor
                ]
            ], "Datos cargados correctamente: " . count($salesResult['sales']) . " registros");
            break;
            
        case 'save_quota':
            $required = ['vendor', 'year', 'month'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    sendResponse(false, [], "Campo requerido faltante: $field");
                }
            }
            
            $vendor = $_POST['vendor'];
            $year = $_POST['year'];
            $month = $_POST['month'];
            $amount = floatval($_POST['amount'] ?? 0);
            $boxes = intval($_POST['boxes'] ?? 0);
            $kilos = floatval($_POST['kilos'] ?? 0);
            $user = 'Sistema';
            
            // Verificar si ya existe
            $checkSql = "SELECT COUNT(*) as existe FROM CUOTAS_VENDEDORES 
                         WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
            $checkStmt = executeQuery($conn, $checkSql, [$vendor, $year, $month]);
            $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            $existe = $row['existe'] > 0;
            sqlsrv_free_stmt($checkStmt);
            
            if ($existe) {
                // Actualizar
                $updateSql = "UPDATE CUOTAS_VENDEDORES 
                              SET CUOTA_DIVISA = ?, CUOTA_CAJAS = ?, CUOTA_KILOS = ?, 
                                  FECHA_ACTUALIZACION = GETDATE(), USUARIO_ACTUALIZACION = ?
                              WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
                executeQuery($conn, $updateSql, [$amount, $boxes, $kilos, $user, $vendor, $year, $month]);
                sendResponse(true, [], "Cuota actualizada correctamente");
            } else {
                // Insertar
                $insertSql = "INSERT INTO CUOTAS_VENDEDORES 
                             (CODIGO_VENDEDOR, ANNO, MES, CUOTA_DIVISA, CUOTA_CAJAS, CUOTA_KILOS, USUARIO_ACTUALIZACION)
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                executeQuery($conn, $insertSql, [$vendor, $year, $month, $amount, $boxes, $kilos, $user]);
                sendResponse(true, [], "Cuota creada correctamente");
            }
            break;
            
        case 'save_class_quota':
            $required = ['vendor', 'year', 'month', 'product_class'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    sendResponse(false, [], "Campo requerido faltante: $field");
                }
            }
            
            $vendor = $_POST['vendor'];
            $year = $_POST['year'];
            $month = $_POST['month'];
            $productClass = $_POST['product_class'];
            $amount = floatval($_POST['amount'] ?? 0);
            $activacion = floatval($_POST['activacion'] ?? 0);
            $user = 'Sistema';
            
            // Verificar si ya existe
            $checkSql = "SELECT COUNT(*) as existe FROM CUOTAS_CLASES_PRODUCTO 
                         WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ? AND CLASE_PRODUCTO = ?";
            $checkStmt = executeQuery($conn, $checkSql, [$vendor, $year, $month, $productClass]);
            $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            $existe = $row['existe'] > 0;
            sqlsrv_free_stmt($checkStmt);
            
            if ($existe) {
                // Actualizar
                $updateSql = "UPDATE CUOTAS_CLASES_PRODUCTO 
                              SET CUOTA_DIVISA = ?, CUOTA_ACTIVACION = ?, 
                                  FECHA_ACTUALIZACION = GETDATE(), USUARIO_ACTUALIZACION = ?
                              WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ? AND CLASE_PRODUCTO = ?";
                executeQuery($conn, $updateSql, [$amount, $activacion, $user, $vendor, $year, $month, $productClass]);
                sendResponse(true, [], "Cuota por categoría actualizada correctamente");
            } else {
                // Insertar
                $insertSql = "INSERT INTO CUOTAS_CLASES_PRODUCTO 
                             (CODIGO_VENDEDOR, ANNO, MES, CLASE_PRODUCTO, CUOTA_DIVISA, CUOTA_ACTIVACION, USUARIO_ACTUALIZACION)
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                executeQuery($conn, $insertSql, [$vendor, $year, $month, $productClass, $amount, $activacion, $user]);
                sendResponse(true, [], "Cuota por categoría creada correctamente");
            }
            break;
            
        case 'delete_quota':
            $vendor = $_POST['vendor'] ?? '';
            $year = $_POST['year'] ?? '';
            $month = $_POST['month'] ?? '';
            
            if (empty($vendor) || empty($year) || empty($month)) {
                sendResponse(false, [], "Datos incompletos para eliminar cuota");
            }
            
            $deleteSql = "DELETE FROM CUOTAS_VENDEDORES 
                          WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
            executeQuery($conn, $deleteSql, [$vendor, $year, $month]);
            sendResponse(true, [], "Cuota eliminada correctamente");
            break;
            
        case 'delete_class_quota':
            $vendor = $_POST['vendor'] ?? '';
            $year = $_POST['year'] ?? '';
            $month = $_POST['month'] ?? '';
            $productClass = $_POST['product_class'] ?? '';
            
            if (empty($vendor) || empty($year) || empty($month) || empty($productClass)) {
                sendResponse(false, [], "Datos incompletos para eliminar cuota por categoría");
            }
            
            $deleteSql = "DELETE FROM CUOTAS_CLASES_PRODUCTO 
                          WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ? AND CLASE_PRODUCTO = ?";
            executeQuery($conn, $deleteSql, [$vendor, $year, $month, $productClass]);
            sendResponse(true, [], "Cuota por categoría eliminada correctamente");
            break;
            
        case 'change_company':
            $empresa = $_POST['empresa'] ?? 'A';
            $_SESSION['empresa_seleccionada'] = $empresa;
            
            $empresasNombres = [
                'A' => 'CSB',
                'B' => 'MAXI', 
                'C' => 'MERIDA'
            ];
            
            sendResponse(true, [], "Empresa cambiada a " . ($empresasNombres[$empresa] ?? 'Desconocida'));
            break;
            
        case 'test_connection':
            // Prueba simple de conexión
            $test_sql = "SELECT TOP 1 B002200CVE as vendor FROM VENTA_ACUMULADA";
            $test_stmt = executeQuery($conn, $test_sql);
            $test_data = sqlsrv_fetch_array($test_stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($test_stmt);
            
            sendResponse(true, [
                'connection' => 'success',
                'test_data' => $test_data,
                'server' => 'SRV-PROFIT\CATA',
                'database' => $GLOBALS['basededatos'] ?? 'REPORT',
                'empresa' => $_SESSION['empresa_seleccionada'] ?? 'A'
            ], "Conexión de prueba exitosa");
            break;
            
        default:
            sendResponse(false, [], "Acción no válida: $action");
    }

} catch (Exception $e) {
    sendResponse(false, [], "Error: " . $e->getMessage());
} finally {
    // Cerrar conexión
    if (isset($conn) && $conn !== false) {
        sqlsrv_close($conn);
    }
}
?>