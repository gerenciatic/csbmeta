<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Ventas - CSB Meta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- TESTS INTEGRADOS - SOLO VISIBLE EN MODO DEBUG -->
        <div id="debug-panel" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #3498db;">
            <h3>🔧 Panel de Pruebas</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                <button onclick="runConnectionTest()" class="btn-test">🧪 Probar Conexión</button>
                <button onclick="runDataTest()" class="btn-test">📊 Probar Datos</button>
                <button onclick="runAllTests()" class="btn-test">🚀 Ejecutar Todas las Pruebas</button>
                <button onclick="toggleDebugPanel()" class="btn-test">❌ Cerrar Panel</button>
            </div>
            <div id="test-results" style="background: white; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;"></div>
        </div>

        <div class="dashboard-header">
            <div class="header-title">
                <h1>Dashboard de Ventas - CSB Meta</h1>
                <div class="time-display" id="current-time">Cargando...</div>
                <div class="last-billing-display">
                    <span class="last-billing-label">Última factura: </span>
                    <span class="last-billing-value" id="last-billing-date">Cargando...</span>
                </div>
            </div>
            <div class="company-selector">
                <button class="company-btn active" data-company="A">CSB</button>
                <button class="company-btn" data-company="B">MAXI</button>
                <button class="company-btn" data-company="C">MERIDA</button>
            </div>
        </div>

        <div id="connection-status" class="connection-status status-disconnected">
            ⏳ Conectando a la base de datos...
        </div>
        
        <div class="filters">
            <div class="filter-item">
                <label for="vendor-select">Vendedor</label>
                <select id="vendor-select">
                    <option value="all">Todos los vendedores</option>
                </select>
            </div>
            
            <div class="filter-item">
                <label for="year-select">Año</label>
                <select id="year-select">
                    <!-- Se llena dinámicamente -->
                </select>
            </div>
            <div class="filter-item">
                <label for="month-select">Mes</label>
                <select id="month-select">
                    <!-- Se llena dinámicamente -->
                </select>
            </div>
            <div class="filter-item">
                <label for="product-filter">Producto</label>
                <input type="text" id="product-filter" placeholder="Filtrar por producto...">
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Ventas</div>
                <div class="stat-value" id="total-sales">$0.00</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Promedio Kg/Dia</div>
                <div class="stat-value" id="avg-kg">0.00</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Cumplimiento Meta</div>
                <div class="stat-value" id="budget-compliance">0%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Transacciones</div>
                <div class="stat-value" id="transactions">0</div>
            </div>
        </div>
        
        <div class="charts-container">
            <div class="chart-card">
                <h3 class="chart-title">Cumplimiento de Metas</h3>
                <div class="half-doughnut-container">
                    <canvas id="halfDoughnutChart"></canvas>
                    <div class="half-doughnut-value" id="achievement-percentage">0%</div>
                </div>
                <div id="chart-additional-info" class="chart-info-panel">
                    <div class="chart-info-item">Ventas Acumuladas: <span class="chart-info-value" id="accumulated-sales">$0.00</span></div>
                    <div class="chart-info-item">Meta: <span class="chart-info-value" id="sales-target">$0.00</span></div>
                    <div class="chart-info-item">Faltante: <span class="chart-info-value" id="remaining-amount">$0.00</span></div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3 class="chart-title">Resumen de Ventas por Vendedor</h3>
                <div id="sales-summary" style="height: 300px; overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Ventas</th>
                                <th>Meta</th>
                                <th>% Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody id="sales-summary-body">
                            <tr>
                                <td colspan="4" class="loading">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="performance">📊 Rendimiento</div>    
            <div class="tab" data-tab="sales">📋 Detalle de Ventas</div>
            <div class="tab" data-tab="quotas">🎯 Gestión de Cuotas</div>
            <div class="tab" data-tab="category-quotas">📁 Cuotas por Categoría</div>
            <div class="tab" data-tab="api">🔌 API para Android</div>
            <div class="tab" data-tab="tests">🧪 Pruebas</div>
        </div>
        
        <div class="tab-content">
            <!-- Pestaña Rendimiento -->
            <div class="tab-pane active" id="performance-tab">
                <h3>Rendimiento vs Cuotas</h3>
                <div class="performance-grid">
                    <div class="performance-card">
                        <h4>Progreso de Ventas vs Meta</h4>
                        <canvas id="sales-progress-chart"></canvas>
                    </div>
                    <div class="performance-card">
                        <h4>Distribución de Ventas por Vendedor</h4>
                        <canvas id="vendor-distribution-chart"></canvas>
                    </div>
                    <div class="performance-card">
                        <h4>Evolución Mensual de Ventas</h4>
                        <canvas id="monthly-sales-chart"></canvas>
                    </div>
                    <div class="performance-card">
                        <h4>Top Productos por Ventas</h4>
                        <canvas id="top-products-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pestaña Ventas -->
            <div class="tab-pane" id="sales-tab">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Producto</th>
                                <th>Cliente</th>
                                <th>Precio Unitario</th>
                                <th>Cantidad</th>
                                <th>Kg</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="sales-data-body">
                            <tr>
                                <td colspan="9" class="loading">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <button id="prev-page" disabled>← Anterior</button>
                        <span id="page-info">Página 1 de 1</span>
                        <button id="next-page" disabled>Siguiente →</button>
                    </div>
                </div>
            </div>
            
            <!-- Pestaña Cuotas -->
            <div class="tab-pane" id="quotas-tab">
                <h3>Gestión de Cuotas por Vendedor</h3>
                
                <div class="quota-form">
                    <div class="form-group">
                        <label for="quota-vendor">Vendedor</label>
                        <select id="quota-vendor">
                            <option value="">Seleccionar vendedor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quota-year">Año</label>
                        <select id="quota-year"></select>
                    </div>
                    <div class="form-group">
                        <label for="quota-month">Mes</label>
                        <select id="quota-month"></select>
                    </div>
                    <div class="form-group">
                        <label for="quota-amount">Cuota en Divisas ($)</label>
                        <input type="number" id="quota-amount" placeholder="0.00" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="quota-boxes">Cuota en Cajas</label>
                        <input type="number" id="quota-boxes" placeholder="0" step="1">
                    </div>
                    <div class="form-group">
                        <label for="quota-kilos">Cuota en Kilos</label>
                        <input type="number" id="quota-kilos" placeholder="0.00" step="0.01">
                    </div>
                </div>
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <button class="btn btn-primary" id="load-quota">📥 Cargar Cuota</button>
                    <button class="btn btn-success" id="save-quota">💾 Guardar Cuota</button>
                    <button class="btn btn-danger" id="delete-quota">🗑️ Eliminar Cuota</button>
                </div>
                
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Año</th>
                                <th>Mes</th>
                                <th>Cuota ($)</th>
                                <th>Cuota (Cajas)</th>
                                <th>Cuota (Kilos)</th>
                                <th>Actualizado</th>
                            </tr>
                        </thead>
                        <tbody id="quotas-data-body">
                            <tr>
                                <td colspan="7" class="loading">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pestaña Cuotas por Categoría -->
            <div class="tab-pane" id="category-quotas-tab">
                <h3>Gestión de Cuotas por Categoría</h3>
                
                <div class="category-quota-form">
                    <div class="quota-form">
                        <div class="form-group">
                            <label for="category-quota-vendor">Vendedor</label>
                            <select id="category-quota-vendor">
                                <option value="">Seleccionar vendedor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category-quota-year">Año</label>
                            <select id="category-quota-year"></select>
                        </div>
                        <div class="form-group">
                            <label for="category-quota-month">Mes</label>
                            <select id="category-quota-month"></select>
                        </div>
                        <div class="form-group">
                            <label for="category-quota-linea">Línea/Categoría</label>
                            <select id="category-quota-linea">
                                <option value="">Seleccionar línea/categoría</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category-quota-amount">Cuota en Divisas ($)</label>
                            <input type="number" id="category-quota-amount" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="category-quota-activacion">Cuota de Activación</label>
                            <input type="number" id="category-quota-activacion" placeholder="0.00" step="0.01">
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 20px;">
                        <button class="btn btn-primary" id="load-category-quota">📥 Cargar Cuota</button>
                        <button class="btn btn-success" id="save-category-quota">💾 Guardar Cuota</button>
                        <button class="btn btn-danger" id="delete-category-quota">🗑️ Eliminar Cuota</button>
                    </div>
                </div>
                
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Línea/Categoría</th>
                                <th>Año</th>
                                <th>Mes</th>
                                <th>Cuota ($)</th>
                                <th>Cuota Activación</th>
                                <th>Actualizado</th>
                            </tr>
                        </thead>
                        <tbody id="category-quotas-data-body">
                            <tr>
                                <td colspan="7" class="loading">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pestaña API -->
            <div class="tab-pane" id="api-tab">
                <div class="api-section">
                    <h3>🔌 API para Aplicaciones Android</h3>
                    
                    <div class="endpoint">
                        <span class="endpoint-method get-method">POST</span>
                        <strong>/sales_api.php?action=get_sales_data</strong> - Obtener datos de ventas
                    </div>
                    
                    <div class="endpoint">
                        <span class="endpoint-method post-method">POST</span>
                        <strong>/sales_api.php?action=save_quota</strong> - Guardar/actualizar cuota
                    </div>
                    
                    <h4 style="margin-top: 20px;">Estructura de respuesta JSON</h4>
                    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
{
    "success": true,
    "message": "Datos cargados correctamente",
    "data": {
        "sales": [...],
        "quotas": [...],
        "vendors": ["03", "05", ...],
        "last_billing_date": "2024-01-15"
    }
}</pre>
                </div>
            </div>

            <!-- Pestaña Pruebas -->
            <div class="tab-pane" id="tests-tab">
                <h3>🧪 Panel de Pruebas del Sistema</h3>
                
                <div class="tests-panel">
                    <div class="test-section">
                        <h4>🔗 Pruebas de Conexión</h4>
                        <div class="test-buttons">
                            <button class="btn-test" onclick="runConnectionTest()">
                                🧪 Probar Conexión BD
                            </button>
                            <button class="btn-test" onclick="runAPITest()">
                                🔌 Probar API
                            </button>
                        </div>
                    </div>
                    
                    <div class="test-section">
                        <h4>📊 Pruebas de Datos</h4>
                        <div class="test-buttons">
                            <button class="btn-test" onclick="runDataTest()">
                                📋 Probar Carga de Datos
                            </button>
                            <button class="btn-test" onclick="runFilterTest()">
                                🔍 Probar Filtros
                            </button>
                        </div>
                    </div>
                    
                    <div class="test-section">
                        <h4>🚀 Pruebas Completas</h4>
                        <div class="test-buttons">
                            <button class="btn-test" onclick="runAllTests()">
                                ✅ Ejecutar Todas las Pruebas
                            </button>
                            <button class="btn-test" onclick="clearTestResults()">
                                🗑️ Limpiar Resultados
                            </button>
                        </div>
                    </div>
                    
                    <div class="test-results-container">
                        <h4>📋 Resultados de las Pruebas</h4>
                        <div id="test-results-panel" class="test-results">
                            <div class="test-result-item info">
                                <span class="test-icon">ℹ️</span>
                                <span class="test-message">Presiona cualquier botón de prueba para comenzar...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="debug-info" id="debug-info">
            Estado: Iniciando sistema...
        </div>
    </div>

    <script src="js/script.js"></script>
    
    <script>
    // Funciones de prueba integradas en el HTML
    function toggleDebugPanel() {
        const panel = document.getElementById('debug-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
    
    function logTestResult(message, type = 'info') {
        const results = document.getElementById('test-results');
        const resultsPanel = document.getElementById('test-results-panel');
        const timestamp = new Date().toLocaleTimeString();
        
        const logEntry = document.createElement('div');
        logEntry.className = `test-result-item ${type}`;
        logEntry.innerHTML = `
            <span class="test-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
            <span class="test-timestamp">[${timestamp}]</span>
            <span class="test-message">${message}</span>
        `;
        
        if (results) results.appendChild(logEntry);
        if (resultsPanel) resultsPanel.appendChild(logEntry.cloneNode(true));
        
        // Scroll to bottom
        if (results) results.scrollTop = results.scrollHeight;
        if (resultsPanel) resultsPanel.scrollTop = resultsPanel.scrollHeight;
    }
    
    function runConnectionTest() {
        logTestResult('Iniciando prueba de conexión...', 'info');
        
        fetch('sales_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_sales_data&year=2024&month=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                logTestResult(`✅ Conexión exitosa! ${data.data.sales.length} registros cargados`, 'success');
            } else {
                logTestResult(`❌ Error: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            logTestResult(`❌ Error de red: ${error.message}`, 'error');
        });
    }
    
    function runDataTest() {
        logTestResult('Probando carga de datos...', 'info');
        // Esta función se implementa en script.js
        if (window.runDataTest) {
            window.runDataTest();
        } else {
            logTestResult('❌ Función de prueba no disponible', 'error');
        }
    }
    
    function runAPITest() {
        logTestResult('Probando endpoints API...', 'info');
        // Esta función se implementa en script.js
        if (window.runAPITest) {
            window.runAPITest();
        } else {
            logTestResult('❌ Función de prueba no disponible', 'error');
        }
    }
    
    function runFilterTest() {
        logTestResult('Probando sistema de filtros...', 'info');
        // Esta función se implementa en script.js
        if (window.runFilterTest) {
            window.runFilterTest();
        } else {
            logTestResult('❌ Función de prueba no disponible', 'error');
        }
    }
    
    function runAllTests() {
        logTestResult('🚀 INICIANDO SUITE COMPLETA DE PRUEBAS...', 'info');
        setTimeout(() => runConnectionTest(), 100);
        setTimeout(() => { if (window.runDataTest) window.runDataTest(); }, 500);
        setTimeout(() => { if (window.runAPITest) window.runAPITest(); }, 1000);
        setTimeout(() => { if (window.runFilterTest) window.runFilterTest(); }, 1500);
    }
    
    function clearTestResults() {
        document.getElementById('test-results').innerHTML = '';
        document.getElementById('test-results-panel').innerHTML = `
            <div class="test-result-item info">
                <span class="test-icon">ℹ️</span>
                <span class="test-message">Resultados limpiados. Ejecuta nuevas pruebas...</span>
            </div>
        `;
    }
    
    // Mostrar panel de debug si hay parámetro en URL
    if (window.location.search.includes('debug=true')) {
        document.getElementById('debug-panel').style.display = 'block';
    }
    </script>

    <style>
    .btn-test {
        background: #3498db;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        margin: 5px;
        transition: background 0.3s;
    }
    
    .btn-test:hover {
        background: #2980b9;
    }
    
    .test-section {
        background: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .test-results-container {
        margin-top: 20px;
    }
    
    .test-results {
        background: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        max-height: 300px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 12px;
    }
    
    .test-result-item {
        padding: 8px;
        margin-bottom: 5px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .test-result-item.success { background: #d4edda; color: #155724; }
    .test-result-item.error { background: #f8d7da; color: #721c24; }
    .test-result-item.warning { background: #fff3cd; color: #856404; }
    .test-result-item.info { background: #d1ecf1; color: #0c5460; }
    
    .test-icon { font-size: 14px; }
    .test-timestamp { color: #6c757d; font-size: 10px; }
    .test-message { flex: 1; }
    </style>
</body>
</html>