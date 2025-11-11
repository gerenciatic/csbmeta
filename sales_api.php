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
                "empresa" => $empresaSeleccionada
            ]
        ]);
        exit();
    }
    
    // Determinar acción
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // ACCIÓN: OBTENER DATOS DE VENTAS
    if ($action === 'get_sales_data') {
        $year = $_POST['year'] ?? $_GET['year'] ?? date('Y');
        $month = $_POST['month'] ?? $_GET['month'] ?? date('m');
        $vendor = $_POST['vendor'] ?? $_GET['vendor'] ?? 'all';
        $productClass = $_POST['class'] ?? $_GET['class'] ?? 'all';
        
        // CONSULTA PARA OBTENER TOTALES DE VENTA EN DIVISAS POR VENDEDOR
        function getSalesTotalsByVendor($conn, $year, $month, $vendor, $productClass = 'all') {
            $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            // Determinar qué tabla usar según la clase de producto
            if ($productClass === '01') {
                $table = "VENTA_ACUMULADA_KILO_CLASE";
                $classFilter = " AND B002201CLS = '01'";
            } elseif ($productClass === '02' || $productClass === '03' || $productClass === '04' || 
                      $productClass === '05' || $productClass === '06' || $productClass === '07' ||
                      $productClass === '08' || $productClass === '09' || $productClass === '10' ||
                      $productClass === '11' || $productClass === '12' || $productClass === '13' ||
                      $productClass === '14' || $productClass === '15') {
                $table = "VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE";
                $classFilter = " AND B002201CLS = '$productClass'";
            } elseif ($productClass !== 'all') {
                $table = "VENTA_ACUMULADA_CAJA_CLASE";
                $classFilter = "";
                // Filtros especiales para clases no numéricas
                if ($productClass === '16') {
                    $classFilter = " AND B002200CVE = '03'";
                } elseif ($productClass === 'Underwood-Parmalat') {
                    $classFilter = " AND B002200CVE IN ('04','05','06','07','08','09','10','11','12','13','14','15','16','17')";
                } elseif ($productClass === 'Parmalat') {
                    $classFilter = " AND B002200CVE = 'P1'";
                }
            } else {
                $table = "VENTA_ACUMULADA";
                $classFilter = "";
            }
            
            $sql = "SELECT
                B002200CVE as vendor,
                COUNT(DISTINCT B002200CCL) as total_clientes,
                COUNT(DISTINCT CONVERT(VARCHAR(10), B002200FEC, 112)) as total_dias_facturacion,
                SUM(CAJAS_ESTADISTICAS) as total_cajas,
                SUM(TOTAL_VTA_DIVISA_DES) as total_divisas,
                SUM(KILO) as total_kilos,
                COUNT(DISTINCT B002200CCL) as activacion_clientes,
                COUNT(DISTINCT B002200FEC) as total_facturas
            FROM dbo.{$table}
            WHERE ANNO = '{$year}' 
            AND MES = '{$monthFormatted}'
            AND B002200CVE NOT IN ('01', '')
            {$classFilter}";
            
            $params = [];
            
            if ($vendor !== 'all') {
                $sql .= " AND B002200CVE = ?";
                $params[] = $vendor;
            }
            
            $sql .= " GROUP BY B002200CVE ORDER BY B002200CVE";
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                return [
                    'success' => false, 
                    'message' => "Error en consulta TOTALES: " . print_r(sqlsrv_errors(), true),
                    'totals' => []
                ];
            }
            
            $totalsData = [];
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $vendorCode = $row['vendor'];
                $totalsData[$vendorCode] = [
                    'clientes' => intval($row['total_clientes']),
                    'dias_facturacion' => intval($row['total_dias_facturacion']),
                    'cajas' => round(floatval($row['total_cajas']), 2),
                    'divisas' => round(floatval($row['total_divisas']), 2),
                    'kilos' => round(floatval($row['total_kilos']), 3),
                    'activacion' => intval($row['activacion_clientes']),
                    'facturas' => intval($row['total_facturas'])
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            
            return [
                'success' => true,
                'totals' => $totalsData
            ];
        }
        
        // CONSULTA ORIGINAL PARA CAJAS
        function getSalesForMonthCajas($conn, $year, $month, $vendor, $productClass = 'all') {
            $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $sql = "SELECT
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
            WHERE VENTA_ACUMULADA_CAJA_CLASE.ANNO = '{$year}' 
            AND VENTA_ACUMULADA_CAJA_CLASE.MES = '{$monthFormatted}'
            AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE NOT IN ('01', '')";
            
            $params = [];
            
            if ($vendor !== 'all') {
                $sql .= " AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE = ?";
                $params[] = $vendor;
            }
            
            // FILTRO POR CLASE ESPECÍFICA PARA CAJAS
            if ($productClass !== 'all') {
                if ($productClass === '16') {
                    $sql .= " AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE = '03'";
                } elseif ($productClass === 'Underwood-Parmalat') {
                    $sql .= " AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE IN ('04','05','06','07','08','09','10','11','12','13','14','15','16','17')";
                } elseif ($productClass === 'Parmalat') {
                    $sql .= " AND VENTA_ACUMULADA_CAJA_CLASE.B002200CVE = 'P1'";
                }
            }
            
            $sql .= " ORDER BY VENTA_ACUMULADA_CAJA_CLASE.B002200FEC DESC";
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                return [
                    'success' => false, 
                    'message' => "Error en consulta CAJAS: " . print_r(sqlsrv_errors(), true),
                    'sales' => [],
                    'last_date' => null
                ];
            }
            
            $salesData = [];
            $lastBillingDate = null;
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['kg'] = round(floatval($row['kg']), 3);
                $row['boxes'] = round(floatval($row['boxes']), 2);
                $row['total'] = round(floatval($row['total']), 2);
                
                $salesData[] = $row;
                
                if (isset($row['fecha_factura']) && !empty($row['fecha_factura'])) {
                    $currentDate = $row['fecha_factura'];
                    if (!$lastBillingDate || $currentDate > $lastBillingDate) {
                        $lastBillingDate = $currentDate;
                    }
                }
            }
            
            sqlsrv_free_stmt($stmt);
            
            return [
                'success' => true,
                'sales' => $salesData,
                'last_date' => $lastBillingDate
            ];
        }
        
        // CONSULTA PARA KILOS
        function getSalesForMonthKilos($conn, $year, $month, $vendor, $productClass = 'all') {
            $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $sql = "SELECT
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
            WHERE VENTA_ACUMULADA_KILO_CLASE.ANNO = '{$year}' 
            AND VENTA_ACUMULADA_KILO_CLASE.MES = '{$monthFormatted}'
            AND VENTA_ACUMULADA_KILO_CLASE.B002200CVE NOT IN ('01', '')";
            
            $params = [];
            
            if ($vendor !== 'all') {
                $sql .= " AND VENTA_ACUMULADA_KILO_CLASE.B002200CVE = ?";
                $params[] = $vendor;
            }
            
            if ($productClass === '01') {
                $sql .= " AND VENTA_ACUMULADA_KILO_CLASE.B002201CLS = '01'";
            }
            
            $sql .= " ORDER BY VENTA_ACUMULADA_KILO_CLASE.B002200CVE ASC";
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                return [
                    'success' => false, 
                    'message' => "Error en consulta KILOS: " . print_r(sqlsrv_errors(), true),
                    'sales' => [],
                    'last_date' => null
                ];
            }
            
            $salesData = [];
            $lastBillingDate = null;
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['kg'] = round(floatval($row['kg']), 3);
                $row['boxes'] = round(floatval($row['boxes']), 2);
                $row['total'] = round(floatval($row['total']), 2);
                
                $salesData[] = $row;
                
                if (isset($row['fecha_factura']) && !empty($row['fecha_factura'])) {
                    $currentDate = $row['fecha_factura'];
                    if (!$lastBillingDate || $currentDate > $lastBillingDate) {
                        $lastBillingDate = $currentDate;
                    }
                }
            }
            
            sqlsrv_free_stmt($stmt);
            
            return [
                'success' => true,
                'sales' => $salesData,
                'last_date' => $lastBillingDate
            ];
        }
        
        // CONSULTA PARA KILOS POR CLASE ESPECÍFICA
        function getSalesForMonthKilosClase($conn, $year, $month, $vendor, $productClass = 'all') {
            $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $sql = "SELECT
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE as vendor,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002201CPR as product,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.KILO as kg,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.UNIDAD_VENDIDA as quantity,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.TOTAL_VTA_DIVISA_DES as total,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.CAJAS_ESTADISTICAS as boxes,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.MES as month,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.ANNO as year,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200RSO as customer_name,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CCL as customer_code,
                CONVERT(VARCHAR(10), VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200FEC, 103) as fecha_factura,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002201CLS as product_class,
                VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.A00212DEL as product_line
            FROM dbo.VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE
            WHERE VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.ANNO = '{$year}' 
            AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.MES = '{$monthFormatted}'
            AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE NOT IN ('01', '')";
            
            $params = [];
            
            if ($vendor !== 'all') {
                $sql .= " AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE = ?";
                $params[] = $vendor;
            }
            
            if ($productClass !== 'all') {
                if ($productClass === '16') {
                    $sql .= " AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE = '03'";
                } elseif ($productClass === 'Underwood-Parmalat') {
                    $sql .= " AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE IN ('04','05','06','07','08','09','10','11','12','13','14','15','16','17')";
                } elseif ($productClass === 'Parmalat') {
                    $sql .= " AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE = 'P1'";
                } else {
                    $sql .= " AND VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002201CLS = ?";
                    $params[] = $productClass;
                }
            }
            
            $sql .= " ORDER BY VENTA_ACUMULADA_KILO_CLASE_CAJA_CLASE.B002200CVE DESC";
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                return [
                    'success' => false, 
                    'message' => "Error en consulta KILOS POR CLASE: " . print_r(sqlsrv_errors(), true),
                    'sales' => [],
                    'last_date' => null
                ];
            }
            
            $salesData = [];
            $lastBillingDate = null;
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['kg'] = round(floatval($row['kg']), 3);
                $row['boxes'] = round(floatval($row['boxes']), 2);
                $row['total'] = round(floatval($row['total']), 2);
                
                $salesData[] = $row;
                
                if (isset($row['fecha_factura']) && !empty($row['fecha_factura'])) {
                    $currentDate = $row['fecha_factura'];
                    if (!$lastBillingDate || $currentDate > $lastBillingDate) {
                        $lastBillingDate = $currentDate;
                    }
                }
            }
            
            sqlsrv_free_stmt($stmt);
            
            return [
                'success' => true,
                'sales' => $salesData,
                'last_date' => $lastBillingDate
            ];
        }
        
        // CONSULTA ORIGINAL
        function getSalesForMonthOriginal($conn, $year, $month, $vendor) {
            $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $sql = "SELECT
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
            WHERE VENTA_ACUMULADA.ANNO = '{$year}' AND VENTA_ACUMULADA.MES = '{$monthFormatted}'
            AND VENTA_ACUMULADA.B002200CVE NOT IN ('01', '')";
            
            $params = [];
            
            if ($vendor !== 'all') {
                $sql .= " AND VENTA_ACUMULADA.B002200CVE = ?";
                $params[] = $vendor;
            }
            
            $sql .= " ORDER BY VENTA_ACUMULADA.B002200CVE ASC";
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                return [
                    'success' => false, 
                    'message' => "Error en consulta ORIGINAL: " . print_r(sqlsrv_errors(), true),
                    'sales' => [],
                    'last_date' => null
                ];
            }
            
            $salesData = [];
            $lastBillingDate = null;
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['kg'] = round(floatval($row['kg']), 3);
                $row['boxes'] = round(floatval($row['boxes']), 2);
                $row['total'] = round(floatval($row['total']), 2);
                
                $salesData[] = $row;
                
                if (isset($row['fecha_factura']) && !empty($row['fecha_factura'])) {
                    $currentDate = $row['fecha_factura'];
                    if (!$lastBillingDate || $currentDate > $lastBillingDate) {
                        $lastBillingDate = $currentDate;
                    }
                }
            }
            
            sqlsrv_free_stmt($stmt);
            
            return [
                'success' => true,
                'sales' => $salesData,
                'last_date' => $lastBillingDate
            ];
        }
        
        // DETERMINAR QUÉ CONSULTA USAR SEGÚN LA CLASE
        $queryType = 'ORIGINAL';
        
        if ($productClass === '01') {
            $result = getSalesForMonthKilos($conn, $year, $month, $vendor, $productClass);
            $queryType = 'KILOS';
        } elseif ($productClass === '02' || $productClass === '03' || $productClass === '04' || 
                  $productClass === '05' || $productClass === '06' || $productClass === '07' ||
                  $productClass === '08' || $productClass === '09' || $productClass === '10' ||
                  $productClass === '11' || $productClass === '12' || $productClass === '13' ||
                  $productClass === '14' || $productClass === '15') {
            $result = getSalesForMonthKilosClase($conn, $year, $month, $vendor, $productClass);
            $queryType = 'KILOS_POR_CLASE';
        } elseif ($productClass !== 'all') {
            $result = getSalesForMonthCajas($conn, $year, $month, $vendor, $productClass);
            $queryType = 'CAJAS';
        } else {
            $result = getSalesForMonthOriginal($conn, $year, $month, $vendor);
            $queryType = 'ORIGINAL';
        }
        
        $autoSwitched = false;
        $originalYear = $year;
        $originalMonth = $month;
        
        // Si no hay datos, intentar con el mes anterior
        if ($result['success'] && count($result['sales']) === 0) {
            $prevMonth = $month - 1;
            $prevYear = $year;
            
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear = $year - 1;
            }
            
            if ($productClass === '01') {
                $result = getSalesForMonthKilos($conn, $prevYear, $prevMonth, $vendor, $productClass);
            } elseif ($productClass === '02' || $productClass === '03' || $productClass === '04' || 
                      $productClass === '05' || $productClass === '06' || $productClass === '07' ||
                      $productClass === '08' || $productClass === '09' || $productClass === '10' ||
                      $productClass === '11' || $productClass === '12' || $productClass === '13' ||
                      $productClass === '14' || $productClass === '15') {
                $result = getSalesForMonthKilosClase($conn, $prevYear, $prevMonth, $vendor, $productClass);
            } elseif ($productClass !== 'all') {
                $result = getSalesForMonthCajas($conn, $prevYear, $prevMonth, $vendor, $productClass);
            } else {
                $result = getSalesForMonthOriginal($conn, $prevYear, $prevMonth, $vendor);
            }
            $autoSwitched = true;
        }
        
        if (!$result['success']) {
            echo json_encode(["success" => false, "message" => $result['message']]);
            exit();
        }
        
        $salesData = $result['sales'];
        $lastBillingDate = $result['last_date'];
        $actualYear = $autoSwitched ? $prevYear : $year;
        $actualMonth = $autoSwitched ? $prevMonth : $month;
        
        // OBTENER TOTALES CONSOLIDADOS POR VENDEDOR
        $totalsResult = getSalesTotalsByVendor($conn, $actualYear, $actualMonth, $vendor, $productClass);
        
        if (!$totalsResult['success']) {
            echo json_encode(["success" => false, "message" => $totalsResult['message']]);
            exit();
        }
        
        $vendorTotals = $totalsResult['totals'];
        
        // CONSULTA ESPECÍFICA PARA KILOS CLASE 01 POR VENDEDOR
        $kilosClase01Data = [];
        
        $kilosClase01Sql = "SELECT 
            B002200CVE as vendor,
            SUM(KILO) as total_kg_clase_01
        FROM dbo.VENTA_ACUMULADA_KILO_CLASE
        WHERE ANNO = ? AND MES = ?
        AND B002201CLS = '01'
        AND B002200CVE NOT IN ('01', '')";
        
        if ($vendor !== 'all') {
            $kilosClase01Sql .= " AND B002200CVE = ?";
        }
        
        $kilosClase01Sql .= " GROUP BY B002200CVE ORDER BY B002200CVE";
        
        $kilosClase01Params = [$actualYear, $actualMonth];
        if ($vendor !== 'all') {
            $kilosClase01Params[] = $vendor;
        }
        
        $kilosClase01Stmt = sqlsrv_query($conn, $kilosClase01Sql, $kilosClase01Params);
        
        if ($kilosClase01Stmt !== false) {
            while ($row = sqlsrv_fetch_array($kilosClase01Stmt, SQLSRV_FETCH_ASSOC)) {
                $kilosClase01Data[$row['vendor']] = round(floatval($row['total_kg_clase_01']), 3);
            }
            sqlsrv_free_stmt($kilosClase01Stmt);
        }
        
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
        
        // ========== CONSULTA MEJORADA: OBTENER META TOTAL DE CAJAS POR VENDEDOR ==========
$productLinesSql = "SELECT 
    VENDEDOR as vendor,
    LINEA as product_line,
    MES as month,
    ANNO as year,
    CUOTA_LINEA as quota_amount,
    CUOTA_ACTIVACION as activation_quota,
    CUOTA_CAJAS as quota_boxes,  // ← NUEVO: Obtener cuota de cajas
    COD_EMPRESA as company_code
FROM dbo.CUOTAS
WHERE ANNO = ? AND MES = ?
AND STATUS = '0'";

$productLinesParams = [$year, $month];

if ($vendor !== 'all') {
    $productLinesSql .= " AND VENDEDOR = ?";
    $productLinesParams[] = $vendor;
}

$productLinesSql .= " ORDER BY VENDEDOR, LINEA";

$productLinesStmt = sqlsrv_query($conn, $productLinesSql, $productLinesParams);
$productLinesData = [];

if ($productLinesStmt !== false) {
    while ($row = sqlsrv_fetch_array($productLinesStmt, SQLSRV_FETCH_ASSOC)) {
        $productLinesData[] = $row;
    }
    sqlsrv_free_stmt($productLinesStmt);
}

// Calcular total de clases únicas por vendedor Y suma de cajas
$vendorClassCount = [];
$vendorBoxesTarget = []; // ← NUEVO: Meta total de cajas por vendedor

foreach ($productLinesData as $line) {
    $vendorCode = $line['vendor'];
    
    // Contar clases únicas
    if (!isset($vendorClassCount[$vendorCode])) {
        $vendorClassCount[$vendorCode] = [
            'total_classes' => 0,
            'classes' => []
        ];
    }
    
    if (!in_array($line['product_line'], $vendorClassCount[$vendorCode]['classes'])) {
        $vendorClassCount[$vendorCode]['classes'][] = $line['product_line'];
        $vendorClassCount[$vendorCode]['total_classes']++;
    }
    
    // Sumar meta de cajas
    if (!isset($vendorBoxesTarget[$vendorCode])) {
        $vendorBoxesTarget[$vendorCode] = 0;
    }
    $vendorBoxesTarget[$vendorCode] += floatval($line['quota_boxes'] ?? 0);
}

// ========== CONSULTA PARA OBTENER VENTAS FILTRADAS POR CLASES DEL VENDEDOR ==========
$filteredSalesData = [];
$vendorActualBoxes = []; // ← CAJAS REALES por vendedor

foreach ($vendorClassCount as $vendorCode => $classInfo) {
    $classes = $classInfo['classes'];
    
    // Construir consulta para obtener ventas de ESTE vendedor con SUS clases
    $salesByClassSql = "SELECT 
        B002200CVE as vendor,
        SUM(CAJAS_ESTADISTICAS) as total_cajas
    FROM dbo.VENTA_ACUMULADA
    WHERE ANNO = ? AND MES = ?
    AND B002200CVE = ?
    AND B002201CLS IN (" . implode(',', array_fill(0, count($classes), '?')) . ")
    GROUP BY B002200CVE";
    
    $salesParams = [$actualYear, $actualMonth, $vendorCode];
    $salesParams = array_merge($salesParams, $classes);
    
    $salesStmt = sqlsrv_query($conn, $salesByClassSql, $salesParams);
    
    if ($salesStmt !== false) {
        while ($row = sqlsrv_fetch_array($salesStmt, SQLSRV_FETCH_ASSOC)) {
            $vendorActualBoxes[$vendorCode] = floatval($row['total_cajas']);
        }
        sqlsrv_free_stmt($salesStmt);
    }
}
// ========== FIN CONSULTAS MEJORADAS ==========





        // Obtener lista de vendedores
        $vendorsSql = "SELECT DISTINCT B002200CVE as vendor 
                       FROM dbo.VENTA_ACUMULADA 
                       WHERE ANNO = ? AND MES = ?
                       AND B002200CVE NOT IN ('01', '')
                       ORDER BY vendor";
        
        $vendorsStmt = sqlsrv_query($conn, $vendorsSql, [$actualYear, $actualMonth]);
        $vendorsList = [];
        
        if ($vendorsStmt !== false) {
            while ($row = sqlsrv_fetch_array($vendorsStmt, SQLSRV_FETCH_ASSOC)) {
                $vendorsList[] = $row['vendor'];
            }
        }
        
        echo json_encode([
            "success" => true,
            "sales" => $salesData, 
            "vendor_totals" => $vendorTotals,
            "quotas" => $quotasData,
            "class_quotas" => $classQuotasData,
            "vendors" => $vendorsList,
            "last_billing_date" => $lastBillingDate,
            "kilos_clase_01" => $kilosClase01Data,
            "product_lines" => $productLinesData,
            "vendor_class_count" => $vendorClassCount,
            "search_info" => [
                "requested_year" => $originalYear,
                "requested_month" => $originalMonth,
                "actual_year" => $actualYear,
                "actual_month" => $actualMonth,
                "auto_switched" => $autoSwitched,
                "records_found" => count($salesData),
                "class_used" => $productClass,
                "query_type" => $queryType
            ]
        ]);
        
    }
    // OTRAS ACCIONES
    else {
        echo json_encode(["success" => false, "message" => "Acción no válida: $action"]);
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