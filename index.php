<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Ventas - API Responsive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-title">
                <h1>Dashboard de Ventas - API</h1>
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
            Desconectado de la base de datos
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
                <select id="year-select"></select>
            </div>
            <div class="filter-item">
                <label for="month-select">Mes</label>
                <select id="month-select"></select>
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
                
                <div class="vendor-selector">
                    <button class="vendor-btn active" data-vendor="all">Todos</button>
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
        <div class="tab active" data-tab="performance">Rendimiento</div>    
        <div class="tab" data-tab="sales">Detalle de Ventas</div>
            <div class="tab" data-tab="quotas">Gestión de Cuotas</div>
            <div class="tab" data-tab="category-quotas">Cuotas por Categoría</div>
            
            <div class="tab" data-tab="api">API para Android</div>
        </div>
        

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


        <div class="tab-content">
            <div class="tab-pane " id="sales-tab">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Producto</th>
                                <th>Categoría</th>
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
                        <button id="prev-page" disabled>Anterior</button>
                        <span id="page-info">Página 1 de 1</span>
                        <button id="next-page" disabled>Siguiente</button>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane " id="quotas-tab">
                <h3>Gestión de Cuotas por Vendedor</h3>
                
                <div class="quota-form">
                    <div class="form-group">
                        <label for="quota-vendor">Vendedor</label>
                        <select id="quota-vendor"></select>
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
                    <button class="btn btn-primary" id="load-quota">Cargar Cuota</button>
                    <button class="btn btn-success" id="save-quota">Guardar Cuota</button>
                    <button class="btn btn-danger" id="delete-quota">Eliminar Cuota</button>
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
            
            <div class="tab-pane" id="category-quotas-tab">
                <h3>Gestión de Cuotas por Categoría</h3>
                
                <div class="category-selector" id="category-selector">
                    <button class="category-btn active" data-category="all">Todas las Categorías</button>
                </div>
                
                <div class="category-quota-form">
                    <h4>Cuotas para: <span id="selected-category">Todas las Categorías</span></h4>
                    
                    <div class="quota-form">
                        <div class="form-group">
                            <label for="category-quota-vendor">Vendedor</label>
                            <select id="category-quota-vendor"></select>
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
                            <select id="category-quota-linea"></select>
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
                        <button class="btn btn-primary" id="load-category-quota">Cargar Cuota</button>
                        <button class="btn btn-success" id="save-category-quota">Guardar Cuota</button>
                        <button class="btn btn-danger" id="delete-category-quota">Eliminar Cuota</button>
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
            
           
            
            <div class="tab-pane" id="api-tab">
                <div class="api-section">
                    <h3>API para Aplicaciones Android</h3>
                    
                    <div class="endpoint">
                        <span class="endpoint-method get-method">GET</span>
                        <strong>/api/sales</strong> - Obtener datos de ventas
                    </div>
                    
                    <div class="endpoint">
                        <span class="endpoint-method get-method">GET</span>
                        <strong>/api/quotas</strong> - Obtener cuotas de vendedores
                    </div>
                    
                    <div class="endpoint">
                        <span class="endpoint-method get-method">GET</span>
                        <strong>/api/category-quotas</strong> - Obtener cuotas por categoría
                    </div>
                    
                    <div class="endpoint">
                        <span class="endpoint-method post-method">POST</span>
                        <strong>/api/quotas</strong> - Guardar/actualizar cuota
                    </div>
                    
                    <div class="endpoint">
                        <span class="endpoint-method post-method">POST</span>
                        <strong>/api/category-quotas</strong> - Guardar/actualizar cuota por categoría
                    </div>
                    
                    <div class="endpoint">
                        <span class="endpoint-method post-method">POST</span>
                        <strong>/api/quotas/delete</strong> - Eliminar cuota
                    </div>
                    
                    <h4 style="margin-top: 20px;">Ejemplo de uso desde Android (Kotlin)</h4>
                    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
// Obtener datos de ventas
val client = OkHttpClient()
val request = Request.Builder()
    .url("https://tu-dominio.com/sales_api.php?action=get_sales_data&year=2023&month=10")
    .build()

client.newCall(request).enqueue(object : Callback {
    override fun onResponse(call: Call, response: Response) {
        val responseData = response.body?.string()
        // Procesar respuesta JSON
    }
    
    override fun onFailure(call: Call, e: IOException) {
        // Manejar error
    }
})
                    </pre>
                    
                    <h4 style="margin-top: 20px;">Estructura de respuesta JSON</h4>
                    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
{
    "success": true,
    "sales": [
        {
            "doc": "FAC 12345",
            "date": "2023-10-15",
            "vendor": "VEND01",
            "product": "PROD001",
            "category": "CAT001",
            "unitPrice": "10.50",
            "quantity": "5",
            "kg": "25.00",
            "total": "52.50"
        }
    ],
    "quotas": [
        {
            "CODIGO_VENDEDOR": "VEND01",
            "ANNO": "2023",
            "MES": "10",
            "CUOTA_DIVISA": "1000.00",
            "CUOTA_CAJAS": "100",
            "CUOTA_KILOS": "500.00"
        }
    ],
    "category_quotas": [
        {
            "VENDEDOR": "VEND01",
            "LINEA": "CAT001",
            "ANNO": "2023",
            "MES": "10",
            "CUOTA_LINEA": "5000.00",
            "CUOTA_ACTIVACION": "1000.00"
        }
    ],
    "vendors": ["VEND01", "VEND02"],
    "categories": ["CAT001", "CAT002"]
}
                    </pre>
                </div>
            </div>
        </div>

        <div class="debug-info" id="debug-info">
            Estado de conexión: Iniciando...
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>