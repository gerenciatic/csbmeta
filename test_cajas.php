<?php
// Configuración de tiempo
set_time_limit(800);
ini_set('max_execution_time', 800);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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
$empresaSeleccionada = $_POST['empresa'] ?? $_GET['empresa'] ?? $_SESSION['empresa_seleccionada'] ?? 'A';
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

// Función para debug que guarda en variable en lugar de imprimir
$debug_log = "";

function debug_log($message) {
    global $debug_log;
    $debug_log .= $message . "\n";
}

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
            "message" => $errorMessage
        ]);
        exit();
    }

    // Obtener parámetros
    $year = $_POST['year'] ?? $_GET['year'] ?? date('Y');
    $month = $_POST['month'] ?? $_GET['month'] ?? date('m');
    $vendor = $_POST['vendor'] ?? $_GET['vendor'] ?? 'all';
    $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);

    debug_log("=== INICIANDO PRUEBA DE CAJAS ===");
    debug_log("Año: $year, Mes: $monthFormatted, Vendedor: $vendor");
    debug_log("Base de datos: $basededatos");

    // PASO 1: OBTENER METAS DE CAJAS DESDE LA TABLA CUOTAS (CON COLUMNAS CORRECTAS)
    debug_log("=== PASO 1: CONSULTANDO METAS DE CAJAS ===");
    
    $quotasSql = "SELECT 
        VENDEDOR as vendor,
        LINEA as product_line,
        CUOTA_LINEA as quota_boxes,
        CUOTA_ACTIVACION as quota_activation
    FROM dbo.CUOTAS 
    WHERE ANNO = ? AND MES = ?
    AND STATUS = '0'
    AND VENDEDOR NOT IN ('01', '')";
    
    $quotaParams = [$year, $monthFormatted];
    
    if ($vendor !== 'all') {
        $quotasSql .= " AND VENDEDOR = ?";
        $quotaParams[] = $vendor;
    }
    
    $quotasSql .= " ORDER BY VENDEDOR, LINEA";
    
    debug_log("SQL Metas: $quotasSql");
    debug_log("Parámetros: " . implode(', ', $quotaParams));
    
    $quotasStmt = sqlsrv_query($conn, $quotasSql, $quotaParams);
    
    if ($quotasStmt === false) {
        $error = print_r(sqlsrv_errors(), true);
        debug_log("ERROR en consulta de metas: $error");
        echo json_encode([
            "success" => false,
            "message" => "Error en consulta de metas: $error"
        ]);
        exit();
    }

    $vendorQuotas = [];
    $vendorClasses = [];
    
    debug_log("--- RESULTADOS METAS ---");
    $totalRegistros = 0;
    while ($row = sqlsrv_fetch_array($quotasStmt, SQLSRV_FETCH_ASSOC)) {
        $vendorCode = $row['vendor'];
        $linea = $row['product_line'];
        $quotaBoxes = floatval($row['quota_boxes']);
        
        debug_log("Vendedor: $vendorCode, Línea: $linea, Meta Cajas: $quotaBoxes");
        
        // Agrupar por vendedor
        if (!isset($vendorQuotas[$vendorCode])) {
            $vendorQuotas[$vendorCode] = 0;
            $vendorClasses[$vendorCode] = [];
        }
        
        $vendorQuotas[$vendorCode] += $quotaBoxes;
        $vendorClasses[$vendorCode][] = $linea;
        $totalRegistros++;
    }
    
    sqlsrv_free_stmt($quotasStmt);
    
    debug_log("Total registros encontrados en CUOTAS: $totalRegistros");
    
    if ($totalRegistros === 0) {
        debug_log("NO SE ENCONTRARON METAS PARA LOS FILTROS SELECCIONADOS");
        echo json_encode([
            "success" => true,
            "summary" => [],
            "total_quota_boxes" => 0,
            "total_actual_boxes" => 0,
            "total_remaining_boxes" => 0,
            "total_achievement_percentage" => 0,
            "debug_log" => $debug_log
        ]);
        exit();
    }
    
    debug_log("--- METAS CONSOLIDADAS ---");
    foreach ($vendorQuotas as $vendorCode => $totalQuota) {
        $classes = $vendorClasses[$vendorCode];
        debug_log("Vendedor $vendorCode: Meta Total = $totalQuota, Clases: " . implode(', ', $classes));
    }

    // PASO 2: OBTENER CAJAS REALES FILTRADAS POR CLASES DE CADA VENDEDOR
    debug_log("=== PASO 2: CONSULTANDO CAJAS REALES ===");
    
    $vendorActualBoxes = [];
    
    foreach ($vendorClasses as $vendorCode => $classes) {
        if (empty($classes)) {
            $vendorActualBoxes[$vendorCode] = 0;
            continue;
        }
        
        debug_log("--- Consultando cajas para vendedor $vendorCode ---");
        debug_log("Clases a filtrar: " . implode(', ', $classes));
        
        // Crear placeholders para las clases
        $placeholders = implode(',', array_fill(0, count($classes), '?'));
        
        $boxesSql = "SELECT 
            B002200CVE as vendor,
            SUM(CAJAS_ESTADISTICAS) as total_boxes
        FROM dbo.VENTA_ACUMULADA
        WHERE ANNO = ? AND MES = ?
        AND B002200CVE = ?
        AND B002201CLS IN ($placeholders)
        GROUP BY B002200CVE";
        
        $boxesParams = [$year, $monthFormatted, $vendorCode];
        $boxesParams = array_merge($boxesParams, $classes);
        
        debug_log("SQL Cajas: $boxesSql");
        debug_log("Parámetros: " . implode(', ', $boxesParams));
        
        $boxesStmt = sqlsrv_query($conn, $boxesSql, $boxesParams);
        
        if ($boxesStmt === false) {
            $error = print_r(sqlsrv_errors(), true);
            debug_log("ERROR en consulta de cajas: $error");
            $vendorActualBoxes[$vendorCode] = 0;
        } else {
            $row = sqlsrv_fetch_array($boxesStmt, SQLSRV_FETCH_ASSOC);
            $actualBoxes = $row ? floatval($row['total_boxes']) : 0;
            $vendorActualBoxes[$vendorCode] = $actualBoxes;
            debug_log("Cajas encontradas: $actualBoxes");
            sqlsrv_free_stmt($boxesStmt);
        }
    }

    // PASO 3: CALCULAR RESUMEN
    debug_log("=== PASO 3: RESUMEN FINAL ===");
    
    $summary = [];
    $totalQuota = 0;
    $totalActual = 0;
    
    foreach ($vendorQuotas as $vendorCode => $quota) {
        $actual = $vendorActualBoxes[$vendorCode] ?? 0;
        $remaining = $quota - $actual;
        $achievement = $quota > 0 ? ($actual / $quota) * 100 : 0;
        
        $summary[$vendorCode] = [
            'quota_boxes' => $quota,
            'actual_boxes' => $actual,
            'remaining_boxes' => $remaining,
            'achievement_percentage' => round($achievement, 2),
            'classes' => $vendorClasses[$vendorCode]
        ];
        
        $totalQuota += $quota;
        $totalActual += $actual;
        
        debug_log("--- Vendedor: $vendorCode ---");
        debug_log("Meta: $quota cajas");
        debug_log("Real: $actual cajas");
        debug_log("Faltante: $remaining cajas");
        debug_log("Cumplimiento: " . round($achievement, 2) . "%");
        debug_log("Clases: " . implode(', ', $vendorClasses[$vendorCode]));
    }
    
    $totalRemaining = $totalQuota - $totalActual;
    $totalAchievement = $totalQuota > 0 ? ($totalActual / $totalQuota) * 100 : 0;
    
    debug_log("=== TOTAL GENERAL ===");
    debug_log("Meta Total: $totalQuota cajas");
    debug_log("Real Total: $totalActual cajas");
    debug_log("Faltante Total: $totalRemaining cajas");
    debug_log("Cumplimiento Total: " . round($totalAchievement, 2) . "%");

    // Devolver resultado en JSON
    echo json_encode([
        "success" => true,
        "summary" => $summary,
        "total_quota_boxes" => $totalQuota,
        "total_actual_boxes" => $totalActual,
        "total_remaining_boxes" => $totalRemaining,
        "total_achievement_percentage" => round($totalAchievement, 2),
        "debug_info" => [
            "year" => $year,
            "month" => $monthFormatted,
            "vendor" => $vendor,
            "vendors_with_quotas" => array_keys($vendorQuotas),
            "total_vendors" => count($vendorQuotas)
        ],
        "debug_log" => $debug_log
    ]);

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