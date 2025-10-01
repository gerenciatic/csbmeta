<?php

 
date_default_timezone_set('America/Caracas');

// Obtener el mes actual (variable local no usada directamente en flujo principal, pero se mantiene)
$mes = date('n');

// Configuración de tiempo para scripts largos
set_time_limit(300);
ini_set('max_execution_time', 300);

// Incluir el archivo de conexión. Este archivo establecerá la conexión $conn usando sqlsrv_connect().
// También define $basededatos según la empresa seleccionada.
require_once 'conexsql.php';

// El archivo config.php se incluye si tiene otras configuraciones necesarias
// include 'config.php'; 

// Obtener el año y mes actual por defecto
$annoActual = date('Y');
$mesActual = date('n');

// Inicializar variables para mensajes de usuario
$mensaje = '';
$error = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigoVendedor = $_POST['codigo_vendedor'];
    $anno = $_POST['anno'];
    $mes = $_POST['mes'];
    $cuotaCajas = $_POST['cuota_cajas'];
    $cuotaKilos = $_POST['cuota_kilos'];
    $cuotaDivisa = $_POST['cuota_divisa'];
    // Asegurarse de que $_SESSION['usuario'] exista, si no, asignar un valor por defecto.
    $usuario = $_SESSION['usuario'] ?? 'Sistema'; 

    // --- Inicia la lógica de la base de datos con sqlsrv ---

    // 1. Verificar si ya existe una cuota para este vendedor en el mes/año
    // Se usa $basededatos para apuntar a la tabla correcta
    $sqlCheck = "SELECT CODIGO_VENDEDOR FROM $basededatos.dbo.CUOTAS_VENDEDORES WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
    $paramsCheck = [$codigoVendedor, $anno, $mes]; // Parámetros para la consulta preparada
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck); // Ejecutar la consulta

    if ($stmtCheck === false) {
        // Manejar error en la consulta de verificación
        $error = "Error al verificar la cuota existente: " . print_r(sqlsrv_errors(), true);
    } else {
        // Verificar si se encontraron filas (si la cuota ya existe)
        if (sqlsrv_has_rows($stmtCheck)) { 
            // 2. Actualizar cuota existente
            // Se usa $basededatos para apuntar a la tabla correcta
            $sqlUpdate = "UPDATE $basededatos.dbo.CUOTAS_VENDEDORES
                          SET CUOTA_CAJAS = ?, CUOTA_KILOS = ?, CUOTA_DIVISA = ?,
                              FECHA_ACTUALIZACION = GETDATE(), USUARIO_ACTUALIZACION = ?
                          WHERE CODIGO_VENDEDOR = ? AND ANNO = ? AND MES = ?";
            $paramsUpdate = [$cuotaCajas, $cuotaKilos, $cuotaDivisa, $usuario, $codigoVendedor, $anno, $mes];
            $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate); // Ejecutar la actualización

            if ($stmtUpdate === false) {
                $error = "Error al actualizar la cuota: " . print_r(sqlsrv_errors(), true);
            } else {
                $mensaje = "Cuota actualizada correctamente.";
            }
        } else {
            // 3. Insertar nueva cuota
            // Se usa $basededatos para apuntar a la tabla correcta
            $sqlInsert = "INSERT INTO $basededatos.dbo.CUOTAS_VENDEDORES
                          (CODIGO_VENDEDOR, ANNO, MES, CUOTA_CAJAS, CUOTA_KILOS,
                           CUOTA_DIVISA, USUARIO_ACTUALIZACION)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $paramsInsert = [$codigoVendedor, $anno, $mes, $cuotaCajas, $cuotaKilos, $cuotaDivisa, $usuario];
            $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert); // Ejecutar la inserción

            if ($stmtInsert === false) {
                $error = "Error al registrar la cuota: " . print_r(sqlsrv_errors(), true);
            } else {
                $mensaje = "Cuota registrada correctamente.";
            }
        }
    }
    // --- Fin de la lógica de la base de datos con sqlsrv ---
}

// Obtener lista de vendedores para el dropdown
$vendedores = [];
try {
    // Consulta SQL para obtener vendedores de la tabla XDTA002.dbo.X002AF01
    // Mapeamos A00201CVE a B002200CVE y A00201NVE a B002200NOM para compatibilidad con el resto del código
    $sqlVendedores = "
        SELECT 
            XDTA002.dbo.X002AF01.A00201CVE AS B002200CVE, 
            XDTA002.dbo.X002AF01.A00201NVE AS B002200NOM
        FROM XDTA002.dbo.X002AF01
        WHERE XDTA002.dbo.X002AF01.A00201CVE NOT IN ('01', '02')
        ORDER BY B002200NOM;
    ";
    $stmtVendedores = sqlsrv_query($conn, $sqlVendedores);

    if ($stmtVendedores === false) {
        $error = "Error al obtener vendedores: " . print_r(sqlsrv_errors(), true);
    } else {
        // Recorrer los resultados y almacenarlos en el array $vendedores
        while ($row = sqlsrv_fetch_array($stmtVendedores, SQLSRV_FETCH_ASSOC)) {
            $vendedores[] = $row;
        }
    }
} catch (Throwable $e) { // Captura cualquier tipo de excepción/error
    $error = "Error inesperado al obtener la lista de vendedores: " . $e->getMessage();
}

// Obtener cuotas existentes para mostrar en tabla
$cuotas = [];
try {
    // Consulta SQL para obtener cuotas existentes, uniendo con los nombres de vendedor de la CTE
    // Aquí mantenemos la CTE para obtener los nombres de vendedores desde FAC_NC y F_FAC_NC
    // ya que la tabla CUOTAS_VENDEDORES solo tiene el código del vendedor.
    $sqlCuotas = "
        WITH VentasAcumuladas AS (
            SELECT
                dbo.FAC_NC.B002200CCL,
                dbo.FAC_NC.B002200RSO,
                TRIM(dbo.FAC_NC.B002200CVE) AS B002200CVE,
                dbo.FAC_NC.B002201CPR,
                SUM(FAC_NC.B002201UVE * vwX002AF03.A00203FES) AS CAJAS_ESTADISTICAS,
                SUM(dbo.FAC_NC.B002201KIL) AS KILO,
                SUM(dbo.FAC_NC.B002201UVE) AS UNIDAD_VENDIDA,
                ROUND(SUM(
                    IIF(FAC_NC.B002200TDO = 'FA', ( ((FAC_NC.B002201MXD * FAC_NC.B002201PPR) - FAC_NC.B002201MXD) * FAC_NC.B002201UVE * -1) ,
                    IIF(FAC_NC.B002200TDO = 'NT', ( ((FAC_NC.B002201MXD * FAC_NC.B002201PPR) - FAC_NC.B002201MXD) * FAC_NC.B002201UVE * -1) ,
                    IIF(FAC_NC.B002200TDO = 'ND', ( ((FAC_NC.B002201MXD * FAC_NC.B002201PPR) - FAC_NC.B002201MXD) * FAC_NC.B002201UVE * -1) ,
                    IIF(FAC_NC.B002200TDO = 'NC', ( (((FAC_NC.B002201MXD * FAC_NC.B002201PPR) + FAC_NC.B002201MXD) * FAC_NC.B002201UVE * -1) ) ,0)))) ),2) AS TOTAL_VTA_DIVISA_DES,
                dbo.FAC_NC.MES,
                dbo.FAC_NC.ANNO,
                dbo.FAC_NC.B002201CLS,
                dbo.vwX002AF12.A00212DEL,
                vwX002AF03.A00203DEL
            FROM dbo.FAC_NC
            LEFT OUTER JOIN dbo.vwX002AF12 ON FAC_NC.B002201CLS = vwX002AF12.A00212CLS
            LEFT OUTER JOIN dbo.vwX002AF03 ON FAC_NC.B002201CPR = vwX002AF03.A00203CPR
            WHERE dbo.FAC_NC.ANNO >= YEAR(GETDATE())
            GROUP BY FAC_NC.B002200CCL, FAC_NC.B002200RSO, FAC_NC.B002200CVE, FAC_NC.B002201CPR, FAC_NC.B002201KIL,
                     FAC_NC.MES, FAC_NC.ANNO, FAC_NC.B002201CLS, vwX002AF12.A00212DEL, vwX002AF03.A00203DEL
            
            UNION ALL
            
            SELECT
                dbo.F_FAC_NC.B002200CCL,
                dbo.F_FAC_NC.B002200RSO,
                TRIM(dbo.F_FAC_NC.B002200CVE) AS B002200CVE,
                dbo.F_FAC_NC.B002201CPR,
                SUM(F_FAC_NC.B002201UVE * vwX002AF03.A00203FES) AS CAJAS_ESTADISTICAS,
                SUM(dbo.F_FAC_NC.B002201UVE) AS UNIDAD_VENDIDA,
                SUM(dbo.F_FAC_NC.B002201KIL) AS KILO,
                ROUND(SUM(
                    IIF(F_FAC_NC.B002200TDO = 'FA', ( ((F_FAC_NC.B002201MXD * F_FAC_NC.B002201PPR) - F_FAC_NC.B002201MXD) * F_FAC_NC.B002201UVE * -1) ,
                    IIF(F_FAC_NC.B002200TDO = 'NT', ( ((F_FAC_NC.B002201MXD * F_FAC_NC.B002201PPR) - F_FAC_NC.B002201MXD) * F_FAC_NC.B002201UVE * -1) ,
                    IIF(F_FAC_NC.B002200TDO = 'ND', ( ((F_FAC_NC.B002201MXD * F_FAC_NC.B002201PPR) - F_FAC_NC.B002201MXD) * F_FAC_NC.B002201UVE * -1) ,
                    IIF(F_FAC_NC.B002200TDO = 'NC', ( (((F_FAC_NC.B002201MXD * F_FAC_NC.B002201PPR) + F_FAC_NC.B002201MXD) * F_FAC_NC.B002201UVE * -1) ) ,0)
                    ))) ),2) AS TOTAL_VTA_DIVISA_DES,
                dbo.F_FAC_NC.MES,
                dbo.F_FAC_NC.ANNO,
                dbo.F_FAC_NC.B002201CLS,
                dbo.vwX002AF12.A00212DEL,
                vwX002AF03.A00203DEL
            FROM dbo.F_FAC_NC
            LEFT OUTER JOIN dbo.vwX002AF12 ON dbo.F_FAC_NC.B002201CLS = dbo.vwX002AF12.A00212CLS
            LEFT OUTER JOIN dbo.vwX002AF03 ON F_FAC_NC.B002201CPR = vwX002AF03.A00203CPR
            WHERE dbo.F_FAC_NC.ANNO >= YEAR(GETDATE())
            AND dbo.F_FAC_NC.B002200TDO IN ('NT')
            GROUP BY dbo.F_FAC_NC.B002200CCL, dbo.F_FAC_NC.B002200RSO, dbo.F_FAC_NC.B002200CVE, dbo.F_FAC_NC.B002201CPR,
                     dbo.F_FAC_NC.B002201KIL, dbo.F_FAC_NC.MES, dbo.F_FAC_NC.ANNO, dbo.F_FAC_NC.B002201CLS,
                     dbo.vwX002AF12.A00212DEL, vwX002AF03.A00203DEL
        ),
        -- Obtenemos los nombres de los vendedores de la tabla X002AF01 para unirlos con las cuotas
        NombresVendedores AS (
            SELECT 
                XDTA002.dbo.X002AF01.A00201CVE AS CODIGO_VENDEDOR, 
                XDTA002.dbo.X002AF01.A00201NVE AS NOMBRE_VENDEDOR
            FROM XDTA002.dbo.X002AF01
            WHERE XDTA002.dbo.X002AF01.A00201CVE NOT IN ('01', '02')
        )
        SELECT c.CODIGO_VENDEDOR, nv.NOMBRE_VENDEDOR, 
               c.ANNO, c.MES, c.CUOTA_CAJAS, c.CUOTA_KILOS, c.CUOTA_DIVISA,
               c.FECHA_ACTUALIZACION, c.USUARIO_ACTUALIZACION
        FROM $basededatos.dbo.CUOTAS_VENDEDORES c
        LEFT JOIN NombresVendedores nv ON c.CODIGO_VENDEDOR = nv.CODIGO_VENDEDOR
        ORDER BY c.ANNO DESC, c.MES DESC, nv.NOMBRE_VENDEDOR;
    ";
    $stmtCuotas = sqlsrv_query($conn, $sqlCuotas);

    if ($stmtCuotas === false) {
        $error = "Error al obtener cuotas: " . print_r(sqlsrv_errors(), true);
    } else {
        // Recorrer los resultados y almacenarlos en el array $cuotas
        while ($row = sqlsrv_fetch_array($stmtCuotas, SQLSRV_FETCH_ASSOC)) {
            $cuotas[] = $row;
        }
    }
} catch (Throwable $e) { // Captura cualquier tipo de excepción/error
    $error = "Error inesperado al obtener la lista de cuotas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Cuotas por Vendedor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/index_style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7fa; color: #333; margin: 0; padding: 0; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .main-content { flex-grow: 1; padding: 20px; }

        .cuotas-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        select, input[type="number"] { /* Específico para input de tipo number */
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box; /* Asegura que padding no aumente el width total */
        }
        
        button {
            background-color: #4361ee;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s ease; /* Transición suave al hover */
        }
        
        button:hover {
            background-color: #3a56d4;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: 600; /* Un poco más de peso para los encabezados */
            color: #555;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            font-size: 12px;
            transition: background-color 0.3s ease;
        }
        
        .edit-btn {
            background-color: #4CAF50; /* Verde */
        }
        
        .edit-btn:hover {
            background-color: #45a049;
        }
        
        .delete-btn {
            background-color: #f44336; /* Rojo */
        }

        .delete-btn:hover {
            background-color: #da190b;
        }

        /* Estilos para el layout del formulario */
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas pequeñas */
        }

        .form-row > .form-group {
            flex: 1;
            min-width: 200px; /* Ancho mínimo para cada grupo de formulario en la fila */
        }

        /* Estilos para el sidebar si es necesario */
        .sidebar { /* Asegúrate de que tu index.php defina esta clase si es un sidebar */
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        /* Media queries para responsividad */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column; /* Apila los elementos del formulario en pantallas pequeñas */
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir sidebar. Asegúrate de que ../index.php es el path correcto al archivo de tu sidebar/dashboard. -->
        <?php //include '../index.php'; ?> 
        
        <main class="main-content">
            <div class="cuotas-container">
                <h2>Registrar Cuotas por Vendedor</h2>
                
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="codigo_vendedor">Vendedor:</label>
                        <select id="codigo_vendedor" name="codigo_vendedor" required>
                            <option value="">Seleccione un vendedor</option>
                            <?php foreach ($vendedores as $vendedor): ?>
                                <option value="<?php echo htmlspecialchars($vendedor['B002200CVE']); ?>">
                                    <?php echo htmlspecialchars($vendedor['B002200NOM']); ?> (<?php echo htmlspecialchars($vendedor['B002200CVE']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="anno">Año:</label>
                            <select id="anno" name="anno" required>
                                <?php for ($year = $annoActual - 1; $year <= $annoActual + 1; $year++): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $annoActual ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="mes">Mes:</label>
                            <select id="mes" name="mes" required>
                                <?php 
                                $meses = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                foreach ($meses as $num => $nombre): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $num == $mesActual ? 'selected' : ''; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cuota_cajas">Cuota de Cajas:</label>
                            <input type="number" id="cuota_cajas" name="cuota_cajas" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cuota_kilos">Cuota de Kilos:</label>
                            <input type="number" id="cuota_kilos" name="cuota_kilos" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cuota_divisa">Cuota en Divisa:</label>
                            <input type="number" id="cuota_divisa" name="cuota_divisa" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <button type="submit">Guardar Cuota</button>
                </form>
                
                <h3 style="margin-top: 30px;">Cuotas Registradas</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Código</th>
                                <th>Año</th>
                                <th>Mes</th>
                                <th>Cajas</th>
                                <th>Kilos</th>
                                <th>Divisa</th>
                                <th>Actualizado</th>
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuotas as $cuota): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cuota['NOMBRE_VENDEDOR']); ?></td>
                                    <td><?php echo htmlspecialchars($cuota['CODIGO_VENDEDOR']); ?></td>
                                    <td><?php echo htmlspecialchars($cuota['ANNO']); ?></td>
                                    <td><?php echo htmlspecialchars($meses[$cuota['MES']]); ?></td>
                                    <td><?php echo number_format($cuota['CUOTA_CAJAS'], 2); ?></td>
                                    <td><?php echo number_format($cuota['CUOTA_KILOS'], 2); ?></td>
                                    <td><?php echo number_format($cuota['CUOTA_DIVISA'], 2); ?></td>
                                    <td><?php echo $cuota['FECHA_ACTUALIZACION']->format('d/m/Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($cuota['USUARIO_ACTUALIZACION']); ?></td>
                                    <td class="actions">
                                        <button class="edit-btn" onclick="editarCuota(
                                            '<?php echo htmlspecialchars($cuota['CODIGO_VENDEDOR']); ?>',
                                            <?php echo htmlspecialchars($cuota['ANNO']); ?>,
                                            <?php echo htmlspecialchars($cuota['MES']); ?>,
                                            <?php echo htmlspecialchars($cuota['CUOTA_CAJAS']); ?>,
                                            <?php echo htmlspecialchars($cuota['CUOTA_KILOS']); ?>,
                                            <?php echo htmlspecialchars($cuota['CUOTA_DIVISA']); ?>
                                        )">Editar</button>
                                        <button class="delete-btn" onclick="eliminarCuota(
                                            '<?php echo htmlspecialchars($cuota['CODIGO_VENDEDOR']); ?>',
                                            <?php echo htmlspecialchars($cuota['ANNO']); ?>,
                                            <?php echo htmlspecialchars($cuota['MES']); ?>
                                        )">Eliminar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cuotas)): ?>
                                <tr><td colspan="10" style="text-align: center;">No hay cuotas registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Función para editar una cuota (rellena el formulario)
        function editarCuota(codigo, anno, mes, cajas, kilos, divisa) {
            document.getElementById('codigo_vendedor').value = codigo;
            document.getElementById('anno').value = anno;
            document.getElementById('mes').value = mes;
            document.getElementById('cuota_cajas').value = cajas;
            document.getElementById('cuota_kilos').value = kilos;
            document.getElementById('cuota_divisa').value = divisa;
            
            // Desplazarse al formulario para facilitar la edición
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Función para eliminar una cuota (requiere un archivo eliminar_cuota.php)
        function eliminarCuota(codigo, anno, mes) {
            // Nota: Aquí se está usando window.confirm y alert. 
            // Para una interfaz de usuario más moderna, considerar un modal personalizado.
            if (confirm(`¿Está seguro que desea eliminar la cuota del vendedor ${codigo} para el mes ${mes} del año ${anno}?`)) {
                // Enviar petición AJAX para eliminar al script eliminar_cuota.php
                fetch('eliminar_cuota.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    // Codificar los datos para la URL
                    body: `codigo_vendedor=${encodeURIComponent(codigo)}&anno=${encodeURIComponent(anno)}&mes=${encodeURIComponent(mes)}`
                })
                .then(response => {
                    // Verificar si la respuesta es JSON o texto plano
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json(); // Si es JSON, parsear como JSON
                    } else {
                        return response.text(); // Si no es JSON, obtener como texto
                    }
                })
                .then(data => {
                    // Si data es un string, significa que no fue JSON (ej. un error de PHP)
                    if (typeof data === 'string') {
                        alert('Error en el servidor (respuesta no JSON). Revisa los logs: ' + data);
                    } else if (data.success) {
                        alert('Cuota eliminada correctamente');
                        location.reload(); // Recargar la página para ver los cambios
                    } else {
                        alert('Error al eliminar la cuota: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    alert('Error en la conexión o al procesar la respuesta: ' + error);
                    console.error('Fetch error:', error); // Mostrar error en consola para depuración
                });
            }
        }
    </script>
</body>
</html>
