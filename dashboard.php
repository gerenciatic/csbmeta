<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Facturación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .metric-card {
            text-align: center;
            padding: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .metric-title {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .positive-change {
            color: var(--success-color);
        }
        
        .negative-change {
            color: var(--accent-color);
        }
        
        .filter-section {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .spinner {
            width: 3rem;
            height: 3rem;
        }
        
        .error-message {
            color: var(--accent-color);
            text-align: center;
            padding: 20px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-connected {
            background-color: var(--success-color);
        }
        
        .status-disconnected {
            background-color: var(--accent-color);
        }
        
        .connection-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .comparison-badge {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .comparison-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .debug-info {
            font-size: 12px;
            color: #6c757d;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="text-center">Dashboard de Facturación</h1>
            <p class="text-center">Monitoreo en tiempo real con comparación de períodos</p>
        </div>
    </div>

    <div class="container">
        <!-- Filtros -->
        <div class="row filter-section">
            <div class="col-md-3">
                <label for="fechaInicio" class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" id="fechaInicio" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="col-md-3">
                <label for="fechaFin" class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" id="fechaFin" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label for="metaNotas" class="form-label">Meta de Notas</label>
                <input type="number" class="form-control" id="metaNotas" value="50">
            </div>
            <div class="col-md-2">
                <label for="metaRatio" class="form-label">Meta Ratio (%)</label>
                <input type="number" class="form-control" id="metaRatio" value="5" step="0.1">
            </div>
            <div class="col-md-2">
                <label for="agrupacion" class="form-label">Agrupación</label>
                <select class="form-select" id="agrupacion">
                    <option value="diaria" selected>Diaria</option>
                    <option value="semanal">Semanal</option>
                    <option value="mensual">Mensual</option>
                </select>
            </div>
            <div class="col-md-12 mt-3">
                <button class="btn btn-primary" onclick="cargarDatos()">Cargar Datos</button>
                <button class="btn btn-info" onclick="probarConexion()">Probar Conexión</button>
                <button class="btn btn-secondary" onclick="toggleDebug()">Info Depuración</button>
            </div>
        </div>

        <!-- Estado de carga -->
        <div class="row" id="estadoCarga">
            <div class="col-12">
                <div class="loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <span class="ms-2" id="textoCarga">Conectando a la base de datos...</span>
                </div>
            </div>
        </div>

        <!-- Información de depuración -->
        <div class="row debug-info" id="debugInfo">
            <div class="col-12">
                <h5>Información de Depuración</h5>
                <div id="debugContent">
                    <p>Esperando datos de depuración...</p>
                </div>
            </div>
        </div>

        <!-- Métricas principales -->
        <div class="row" id="metricasPrincipales" style="display: none;">
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <h5 class="metric-title">Total Facturas</h5>
                        <div class="metric-value" id="totalFacturas">0</div>
                        <div id="variacionFacturas" class="positive-change">-</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <h5 class="metric-title">Total Notas</h5>
                        <div class="metric-value" id="totalNotas">0</div>
                        <div id="variacionNotas" class="negative-change">-</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <h5 class="metric-title">Ratio Notas/Facturas</h5>
                        <div class="metric-value" id="ratioNotas">0%</div>
                        <div id="estadoRatio" class="positive-change">-</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <h5 class="metric-title">Meta de Notas</h5>
                        <div class="metric-value" id="metaNotasValor">0</div>
                        <div id="porcentajeMeta" class="positive-change">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row" id="seccionGraficos" style="display: none;">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        Evolución - Facturas vs Notas
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="evolucionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        Distribución por Tipo
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="distribucionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de datos -->
        <div class="row mt-4" id="seccionTabla" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Detalle por Fecha</span>
                        <span class="badge bg-info" id="contadorRegistros">0 registros</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Año</th>
                                        <th>Mes</th>
                                        <th>Día</th>
                                        <th>Facturas</th>
                                        <th>Notas Crédito</th>
                                        <th>Total Documentos</th>
                                        <th>Ratio Notas/Facturas</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaDatos">
                                    <!-- Los datos se llenarán con JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje de error -->
        <div class="row" id="mensajeError" style="display: none;">
            <div class="col-12">
                <div class="error-message">
                    <h4>Error al cargar los datos</h4>
                    <p id="textoError">Por favor, verifique la conexión e intente nuevamente.</p>
                    <button class="btn btn-primary" onclick="cargarDatos()">Reintentar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicador de estado de conexión -->
    <div class="connection-status" id="connectionStatus">
        <span class="status-indicator status-disconnected" id="statusIndicator"></span>
        <span id="statusText">Desconectado</span>
    </div>

    <script>
        // Variables globales
        let evolucionChart, distribucionChart;
        let intervaloActualizacion;
        let datosCache = [];

        // Función para probar la conexión
        function probarConexion() {
            document.getElementById('textoCarga').textContent = "Probando conexión a la base de datos...";
            document.getElementById('estadoCarga').style.display = 'flex';
            
            fetch('probar_conexion.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Conexión exitosa: ' + data.message);
                        updateConnectionStatus('Conectado', 'status-connected');
                    } else {
                        alert('Error de conexión: ' + data.message);
                        updateConnectionStatus('Error de conexión', 'status-disconnected');
                    }
                    document.getElementById('estadoCarga').style.display = 'none';
                })
                .catch(error => {
                    alert('Error al probar la conexión: ' + error.message);
                    document.getElementById('estadoCarga').style.display = 'none';
                    updateConnectionStatus('Error de conexión', 'status-disconnected');
                });
        }

        // Función para cargar datos desde el servidor
        function cargarDatos() {
            // Mostrar estado de carga
            document.getElementById('estadoCarga').style.display = 'flex';
            document.getElementById('metricasPrincipales').style.display = 'none';
            document.getElementById('seccionGraficos').style.display = 'none';
            document.getElementById('seccionTabla').style.display = 'none';
            document.getElementById('mensajeError').style.display = 'none';

            // Actualizar estado de conexión
            updateConnectionStatus('Conectando...', 'status-disconnected');

            // Obtener parámetros del formulario
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;
            const metaNotas = document.getElementById('metaNotas').value;
            const metaRatio = document.getElementById('metaRatio').value;
            const agrupacion = document.getElementById('agrupacion').value;

            // Realizar solicitud al servidor
            fetch('obtener_datos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fechaInicio: fechaInicio,
                    fechaFin: fechaFin,
                    metaNotas: metaNotas,
                    metaRatio: metaRatio,
                    tipoMetrica: agrupacion
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Guardar datos en caché
                    datosCache = data;
                    
                    // Actualizar la interfaz con los datos recibidos
                    actualizarMetricas(data.metricas);
                    actualizarGraficos(data.graficos);
                    actualizarTabla(data.tabla);
                    
                    // Mostrar secciones de contenido
                    document.getElementById('estadoCarga').style.display = 'none';
                    document.getElementById('metricasPrincipales').style.display = 'flex';
                    document.getElementById('seccionGraficos').style.display = 'flex';
                    document.getElementById('seccionTabla').style.display = 'flex';
                    
                    // Actualizar estado de conexión
                    updateConnectionStatus('Conectado', 'status-connected');
                    
                    // Simular variaciones (en un sistema real, estos datos vendrían del backend)
                    document.getElementById('variacionFacturas').textContent = '+12.5% vs período anterior';
                    document.getElementById('variacionNotas').textContent = '-3.2% vs período anterior';
                    
                } else {
                    throw new Error(data.error || 'Error desconocido del servidor');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('estadoCarga').style.display = 'none';
                document.getElementById('mensajeError').style.display = 'block';
                document.getElementById('textoError').textContent = error.message;
                
                // Actualizar estado de conexión
                updateConnectionStatus('Error de conexión', 'status-disconnected');
                
                // Mostrar datos de depuración
                document.getElementById('debugContent').innerHTML = `
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p><strong>URL:</strong> obtener_datos.php</p>
                    <p><strong>Método:</strong> POST</p>
                    <p><strong>Fecha:</strong> ${new Date().toLocaleString()}</p>
                `;
            });
        }

        // Función para actualizar el estado de conexión
        function updateConnectionStatus(text, statusClass) {
            const indicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            
            // Eliminar clases anteriores
            indicator.classList.remove('status-connected', 'status-disconnected');
            // Agregar nueva clase
            indicator.classList.add(statusClass);
            
            statusText.textContent = text;
        }

        // Función para actualizar las métricas
        function actualizarMetricas(metricas) {
            document.getElementById('totalFacturas').textContent = metricas.total_facturas.toLocaleString();
            document.getElementById('totalNotas').textContent = metricas.total_notas.toLocaleString();
            document.getElementById('ratioNotas').textContent = `${metricas.ratio_notas.toFixed(2)}%`;
            document.getElementById('metaNotasValor').textContent = metricas.meta_notas;
            document.getElementById('porcentajeMeta').textContent = `${metricas.porcentaje_meta.toFixed(1)}% de la meta`;
            document.getElementById('estadoRatio').innerHTML = metricas.estado_ratio;
        }

        // Función para actualizar los gráficos
        function actualizarGraficos(graficos) {
            // Destruir gráficos existentes si los hay
            if (evolucionChart) evolucionChart.destroy();
            if (distribucionChart) distribucionChart.destroy();
            
            // Gráfico de evolución
            const evolucionCtx = document.getElementById('evolucionChart').getContext('2d');
            evolucionChart = new Chart(evolucionCtx, {
                type: 'line',
                data: {
                    labels: graficos.evolution.labels,
                    datasets: [
                        {
                            label: 'Facturas',
                            data: graficos.evolution.facturas,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Notas de Crédito',
                            data: graficos.evolution.notas,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolución de Facturas vs Notas de Crédito'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Gráfico de distribución
            const distribucionCtx = document.getElementById('distribucionChart').getContext('2d');
            distribucionChart = new Chart(distribucionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Facturas', 'Notas de Crédito'],
                    datasets: [{
                        data: [graficos.distribution.facturas, graficos.distribution.notas],
                        backgroundColor: ['#3498db', '#e74c3c']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Función para actualizar la tabla
        function actualizarTabla(datos) {
            const tablaBody = document.getElementById('tablaDatos');
            tablaBody.innerHTML = '';
            
            if (datos.length === 0) {
                tablaBody.innerHTML = '<tr><td colspan="8" class="text-center">No hay datos para mostrar</td></tr>';
                document.getElementById('contadorRegistros').textContent = '0 registros';
                return;
            }
            
            // Actualizar contador de registros
            document.getElementById('contadorRegistros').textContent = `${datos.length} registros`;
            
            datos.forEach(item => {
                const ratio = item.Total_Facturas > 0 ? 
                    (item.Total_Notas_Credito / item.Total_Facturas) * 100 : 0;
                    
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${item.Fecha}</td>
                    <td>${item.ANNO}</td>
                    <td>${item.MES}</td>
                    <td>${item.DIA}</td>
                    <td>${item.Total_Facturas.toLocaleString()}</td>
                    <td>${item.Total_Notas_Credito.toLocaleString()}</td>
                    <td>${item.Total_Documentos.toLocaleString()}</td>
                    <td>${ratio.toFixed(2)}%</td>
                `;
                tablaBody.appendChild(fila);
            });
        }

        // Función para mostrar/ocultar información de depuración
        function toggleDebug() {
            const debugInfo = document.getElementById('debugInfo');
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
        }

        // Configurar auto-actualización
        document.getElementById('autoActualizar').addEventListener('change', function() {
            if (this.checked) {
                intervaloActualizacion = setInterval(cargarDatos, 120000); // 2 minutos
            } else {
                clearInterval(intervaloActualizacion);
            }
        });

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            // Probar la conexión al cargar la página
            probarConexion();
            
            // Cargar datos después de un breve delay
            setTimeout(cargarDatos, 1000);
        });
    </script>
</body>
</html>