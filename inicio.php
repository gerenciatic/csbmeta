<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Ventas con Gestión de Cuotas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .dashboard-header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .time-display {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .company-selector {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .company-btn {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .company-btn:hover {
            background-color: #2980b9;
        }
        
        .company-btn.active {
            background-color: #2c3e50;
        }
        
        .filters {
            background-color: #ecf0f1;
            padding: 15px 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }
        
        .filter-item label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #7f8c8d;
        }
        
        select, input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            background-color: white;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }
        
        .chart-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            min-height: 380px;
            display: flex;
            flex-direction: column;
        }
        
        .chart-title {
            margin-bottom: 15px;
            color: #2c3e50;
            text-align: center;
            font-size: 1.2rem;
        }
        
        .speedometer-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }
        
        .gauge {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                #2ecc71 0%, 
                #2ecc71 70%, 
                #f1c40f 70%, 
                #f1c40f 90%, 
                #e74c3c 90%, 
                #e74c3c 100%
            );
            mask: radial-gradient(white 35%, transparent 36%);
            -webkit-mask: radial-gradient(white 35%, transparent 36%);
        }
        
        .needle {
            position: absolute;
            bottom: 50%;
            left: 50%;
            width: 4px;
            height: 45%;
            background: #34495e;
            transform-origin: bottom center;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            transform: translateX(-50%) rotate(-90deg);
            transition: transform 1s ease-in-out;
            z-index: 10;
        }
        
        .center-circle {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            background: #34495e;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            z-index: 11;
        }
        
        .gauge-labels {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
        }
        
        .gauge-label {
            position: absolute;
            font-size: 0.8rem;
            color: #7f8c8d;
            font-weight: bold;
        }
        
        .percentage-display {
            position: absolute;
            bottom: 20%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .vendor-selector {
            display: flex;
            justify-content: center;
            margin-top: 15px;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .vendor-btn {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .vendor-btn:hover {
            background-color: #2980b9;
        }
        
        .vendor-btn.active {
            background-color: #2c3e50;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin: 0 20px;
        }
        
        .tab {
            padding: 12px 20px;
            cursor: pointer;
            background-color: #f8f9fa;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        
        .tab.active {
            background-color: white;
            border-color: #ddd;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .tab-content {
            padding: 20px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .data-table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .data-table th, .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background-color: #2c3e50;
            color: white;
            position: sticky;
            top: 0;
        }
        
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .quota-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #2c3e50;
        }
        
        .btn-primary:hover {
            background-color: #1a252f;
        }
        
        .btn-success {
            background-color: #27ae60;
        }
        
        .btn-success:hover {
            background-color: #219653;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }
        
        .pagination button {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .pagination button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
            font-size: 1.2rem;
            color: #7f8c8d;
        }
        
        .progress-container {
            margin: 10px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease-in-out;
        }
        
        .performance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .performance-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 1024px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .performance-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .quota-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-title">
                <h1>Dashboard de Ventas con Gestión de Cuotas</h1>
                <div class="time-display" id="current-time">Cargando...</div>
            </div>
            <div class="company-selector">
                <button class="company-btn active" data-company="A">CSB</button>
                <button class="company-btn" data-company="B">MAXI</button>
                <button class="company-btn" data-company="C">MERIDA</button>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-item">
                <label for="vendor-select">Vendedor</label>
                <select id="vendor-select">
                    <option value="all">Todos los vendedores</option>
                    <!-- Opciones se cargarán dinámicamente -->
                </select>
            </div>
            <div class="filter-item">
                <label for="year-select">Año</label>
                <select id="year-select">
                    <!-- Opciones se cargarán dinámicamente -->
                </select>
            </div>
            <div class="filter-item">
                <label for="month-select">Mes</label>
                <select id="month-select">
                    <!-- Opciones se cargarán dinámicamente -->
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
                <h3 class="chart-title">Cumplimiento de Metas por Vendedor</h3>
                <div class="speedometer-container">
                    <div class="gauge"></div>
                    <div class="needle" id="needle"></div>
                    <div class="center-circle"></div>
                    <div class="gauge-labels">
                        <div class="gauge-label" style="top: 15%; right: 25%;">0%</div>
                        <div class="gauge-label" style="top: 5%; left: 50%; transform: translateX(-50%);">50%</div>
                        <div class="gauge-label" style="top: 15%; left: 25%;">100%</div>
                    </div>
                    <div class="percentage-display" id="achievement-percentage">0%</div>
                </div>
                
                <div class="vendor-selector">
                    <button class="vendor-btn active" data-vendor="all">Todos</button>
                    <!-- Los botones de vendedores se cargarán dinámicamente -->
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
            <div class="tab active" data-tab="sales">Detalle de Ventas</div>
            <div class="tab" data-tab="quotas">Gestión de Cuotas</div>
            <div class="tab" data-tab="performance">Rendimiento</div>
        </div>
        
        <div class="tab-content">
            <div class="tab-pane active" id="sales-tab">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Producto</th>
                                <th>Precio Unitario</th>
                                <th>Cantidad</th>
                                <th>Kg</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="sales-data-body">
                            <tr>
                                <td colspan="8" class="loading">Cargando datos...</td>
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
            
            <div class="tab-pane" id="quotas-tab">
                <h3>Gestión de Cuotas por Vendedor</h3>
                
                <div class="quota-form">
                    <div class="form-group">
                        <label for="quota-vendor">Vendedor</label>
                        <select id="quota-vendor">
                            <!-- Opciones se cargarán dinámicamente -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quota-year">Año</label>
                        <select id="quota-year">
                            <!-- Opciones se cargarán dinámicamente -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quota-month">Mes</label>
                        <select id="quota-month">
                            <!-- Opciones se cargarán dinámicamente -->
                        </select>
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
            
            <div class="tab-pane" id="performance-tab">
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
        </div>
    </div>

    <script>
        // Variables globales
        let salesData = [];
        let quotasData = [];
        let currentPage = 1;
        let rowsPerPage = 25;
        let filteredData = [];
        let currentCompany = 'A';
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth() + 1;
        let salesProgressChart = null;
        let vendorDistributionChart = null;
        let monthlySalesChart = null;
        let topProductsChart = null;

        // Actualizar la hora en tiempo real
        function updateTime() {
            const now = new Date();
            const timeStr = now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
            document.getElementById('current-time').textContent = timeStr;
        }
        
        setInterval(updateTime, 60000);
        updateTime();
        
        // Función para actualizar el speedometer
        function updateSpeedometer(percentage) {
            const needle = document.getElementById('needle');
            // Convertir el porcentaje a un ángulo entre -90° y 90°
            const rotation = -90 + (percentage * 180 / 100);
            needle.style.transform = `translateX(-50%) rotate(${rotation}deg)`;
            document.getElementById('achievement-percentage').textContent = percentage.toFixed(1) + '%';
            document.getElementById('budget-compliance').textContent = percentage.toFixed(1) + '%';
        }
        
        // Cargar datos desde el servidor
        function loadDataFromServer() {
            showLoading();
            
            // Enviar solicitud al servidor para obtener datos
            const formData = new FormData();
            formData.append('empresa', currentCompany);
            formData.append('year', currentYear);
            formData.append('month', currentMonth);
            formData.append('action', 'get_sales_data');
            
            fetch('sales_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                salesData = data.sales || [];
                quotasData = data.quotas || [];
                filteredData = [...salesData];
                updateDashboard();
                loadQuotasData();
                updateCharts();
            })
            .catch(error => {
                console.error('Error al cargar datos:', error);
                alert('Error al cargar datos. Verifica la conexión.');
            });
        }
        
        // Cargar datos de cuotas
        function loadQuotasData() {
            const quotasBody = document.getElementById('quotas-data-body');
            if (quotasData.length === 0) {
                quotasBody.innerHTML = '<tr><td colspan="7">No hay cuotas definidas</td></tr>';
                return;
            }
            
            quotasBody.innerHTML = '';
            quotasData.forEach(quota => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${quota.CODIGO_VENDEDOR}</td>
                    <td>${quota.ANNO}</td>
                    <td>${quota.MES}</td>
                    <td>$${parseFloat(quota.CUOTA_DIVISA).toFixed(2)}</td>
                    <td>${parseFloat(quota.CUOTA_CAJAS).toFixed(0)}</td>
                    <td>${parseFloat(quota.CUOTA_KILOS).toFixed(2)}</td>
                    <td>${new Date(quota.FECHA_ACTUALIZACION).toLocaleDateString()}</td>
                `;
                quotasBody.appendChild(row);
            });
        }
        
        // Calcular métricas basadas en los datos
        function calculateMetrics(data) {
            if (data.length === 0) {
                return {
                    totalSales: 0,
                    avgKg: 0,
                    achievement: 0,
                    transactions: 0
                };
            }
            
            const totalSales = data.reduce((sum, item) => sum + parseFloat(item.total), 0);
            const totalKg = data.reduce((sum, item) => sum + parseFloat(item.kg), 0);
            const transactions = data.length;
            
            // Obtener la cuota del vendedor seleccionado para el mes y año actual
            const vendor = document.getElementById('vendor-select').value;
            let salesTarget = 10000; // Valor por defecto
            let kgTarget = 100; // Valor por defecto
            
            if (vendor !== 'all') {
                const vendorQuota = quotasData.find(q => 
                    q.CODIGO_VENDEDOR === vendor && 
                    q.ANNO === currentYear && 
                    q.MES === currentMonth
                );
                
                if (vendorQuota) {
                    salesTarget = parseFloat(vendorQuota.CUOTA_DIVISA);
                    kgTarget = parseFloat(vendorQuota.CUOTA_KILOS);
                }
            }
            
            const salesAchievement = salesTarget > 0 ? Math.min(100, (totalSales / salesTarget) * 100) : 0;
            const kgAchievement = kgTarget > 0 ? Math.min(100, (totalKg / kgTarget) * 100) : 0;
            
            // Promedio ponderado de logros (70% ventas, 30% kg)
            const overallAchievement = (salesAchievement * 0.7) + (kgAchievement * 0.3);
            
            return {
                totalSales,
                avgKg: transactions > 0 ? totalKg / transactions : 0,
                achievement: overallAchievement,
                transactions,
                salesTarget,
                kgTarget
            };
        }
        
        // Agrupar datos por vendedor
        function groupDataByVendor(data) {
            const grouped = {};
            
            data.forEach(item => {
                if (!grouped[item.vendor]) {
                    grouped[item.vendor] = {
                        vendor: item.vendor,
                        totalSales: 0,
                        totalKg: 0,
                        transactions: 0
                    };
                }
                
                grouped[item.vendor].totalSales += parseFloat(item.total);
                grouped[item.vendor].totalKg += parseFloat(item.kg);
                grouped[item.vendor].transactions += 1;
            });
            
            return Object.values(grouped);
        }
        
        // Renderizar tabla de resumen
        function renderSalesSummary(data) {
            const summaryBody = document.getElementById('sales-summary-body');
            summaryBody.innerHTML = '';
            
            if (data.length === 0) {
                summaryBody.innerHTML = '<tr><td colspan="4">No hay datos disponibles</td></tr>';
                return;
            }
            
            data.forEach(item => {
                // Obtener la cuota para este vendedor
                const vendorQuota = quotasData.find(q => 
                    q.CODIGO_VENDEDOR === item.vendor && 
                    q.ANNO === currentYear && 
                    q.MES === currentMonth
                );
                
                const salesTarget = vendorQuota ? parseFloat(vendorQuota.CUOTA_DIVISA) : 0;
                const achievement = salesTarget > 0 ? (item.totalSales / salesTarget) * 100 : 0;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.vendor}</td>
                    <td>$${item.totalSales.toFixed(2)}</td>
                    <td>$${salesTarget.toFixed(2)}</td>
                    <td>${achievement.toFixed(1)}%</td>
                `;
                summaryBody.appendChild(row);
            });
        }
        
        // Renderizar tabla de datos con paginación
        function renderSalesData(data, page, rowsPerPage) {
            const tableBody = document.getElementById('sales-data-body');
            tableBody.innerHTML = '';
            
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);
            
            if (paginatedData.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="8" style="text-align: center;">No hay datos para mostrar</td>';
                tableBody.appendChild(row);
            } else {
                paginatedData.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.doc}</td>
                        <td>${item.date}</td>
                        <td>${item.vendor}</td>
                        <td>${item.product}</td>
                        <td>$${parseFloat(item.unitPrice).toFixed(4)}</td>
                        <td>${item.quantity}</td>
                        <td>${parseFloat(item.kg).toFixed(2)}</td>
                        <td>$${parseFloat(item.total).toFixed(4)}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }
            
            // Actualizar controles de paginación
            const totalPages = Math.ceil(data.length / rowsPerPage);
            document.getElementById('page-info').textContent = `Página ${page} de ${totalPages}`;
            document.getElementById('prev-page').disabled = page <= 1;
            document.getElementById('next-page').disabled = page >= totalPages;
        }
        
        // Actualizar gráficas
        function updateCharts() {
            updateSalesProgressChart();
            updateVendorDistributionChart();
            updateMonthlySalesChart();
            updateTopProductsChart();
        }
        
        // Gráfica de progreso de ventas vs meta
        function updateSalesProgressChart() {
            const ctx = document.getElementById('sales-progress-chart').getContext('2d');
            const metrics = calculateMetrics(filteredData);
            
            if (salesProgressChart) {
                salesProgressChart.destroy();
            }
            
            salesProgressChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Ventas Realizadas', 'Meta Pendiente'],
                    datasets: [{
                        data: [metrics.totalSales, Math.max(0, metrics.salesTarget - metrics.totalSales)],
                        backgroundColor: [
                            '#2ecc71',
                            '#e74c3c'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += '$' + context.raw.toFixed(2);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfica de distribución por vendedor
        function updateVendorDistributionChart() {
            const ctx = document.getElementById('vendor-distribution-chart').getContext('2d');
            const vendorData = groupDataByVendor(filteredData);
            
            if (vendorDistributionChart) {
                vendorDistributionChart.destroy();
            }
            
            // Ordenar vendedores por ventas (mayor a menor) y tomar los primeros 10
            const sortedVendors = vendorData.sort((a, b) => b.totalSales - a.totalSales).slice(0, 10);
            
            vendorDistributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: sortedVendors.map(v => v.vendor),
                    datasets: [{
                        label: 'Ventas por Vendedor',
                        data: sortedVendors.map(v => v.totalSales),
                        backgroundColor: '#3498db',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfica de evolución mensual (simulada)
        function updateMonthlySalesChart() {
            const ctx = document.getElementById('monthly-sales-chart').getContext('2d');
            
            if (monthlySalesChart) {
                monthlySalesChart.destroy();
            }
            
            // Datos simulados para la evolución mensual
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const currentMonthIndex = new Date().getMonth();
            const monthlySales = Array(12).fill(0);
            
            // Simular datos de ventas mensuales
            filteredData.forEach(item => {
                const saleDate = new Date(item.date);
                const monthIndex = saleDate.getMonth();
                monthlySales[monthIndex] += parseFloat(item.total);
            });
            
            monthlySalesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Ventas Mensuales',
                        data: monthlySales,
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfica de top productos
        function updateTopProductsChart() {
            const ctx = document.getElementById('top-products-chart').getContext('2d');
            
            if (topProductsChart) {
                topProductsChart.destroy();
            }
            
            // Agrupar ventas por producto
            const productSales = {};
            filteredData.forEach(item => {
                if (!productSales[item.product]) {
                    productSales[item.product] = 0;
                }
                productSales[item.product] += parseFloat(item.total);
            });
            
            // Convertir a array y ordenar
            const productArray = Object.keys(productSales).map(product => {
                return { product, sales: productSales[product] };
            });
            
            // Ordenar y tomar los primeros 8
            const topProducts = productArray.sort((a, b) => b.sales - a.sales).slice(0, 8);
            
            topProductsChart = new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: topProducts.map(p => {
                        // Acortar nombres largos de productos
                        return p.product.length > 20 ? p.product.substring(0, 20) + '...' : p.product;
                    }),
                    datasets: [{
                        label: 'Ventas por Producto',
                        data: topProducts.map(p => p.sales),
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f39c12',
                            '#9b59b6', '#1abc9c', '#d35400', '#34495e'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    }
                }
            });
        }
        
        // Filtrar datos basados en los filtros seleccionados
        function filterData() {
            const vendor = document.getElementById('vendor-select').value;
            const productFilter = document.getElementById('product-filter').value.toLowerCase();
            
            filteredData = salesData.filter(item => {
                const vendorMatch = vendor === 'all' || item.vendor === vendor;
                const productMatch = item.product.toLowerCase().includes(productFilter);
                return vendorMatch && productMatch;
            });
            
            currentPage = 1;
            updateDashboard();
            updateCharts();
        }
        
        // Mostrar estado de carga
        function showLoading() {
            document.getElementById('sales-summary-body').innerHTML = '<tr><td colspan="4" class="loading">Cargando datos...</td></tr>';
            document.getElementById('sales-data-body').innerHTML = '<tr><td colspan="8" class="loading">Cargando datos...</td></tr>';
        }
        
        // Actualizar todo el dashboard
        function updateDashboard() {
            const metrics = calculateMetrics(filteredData);
            
            // Actualizar métricas
            document.getElementById('total-sales').textContent = `$${metrics.totalSales.toFixed(2)}`;
            document.getElementById('avg-kg').textContent = metrics.avgKg.toFixed(2);
            document.getElementById('transactions').textContent = metrics.transactions;
            
            // Actualizar speedometer
            updateSpeedometer(metrics.achievement);
            
            // Actualizar resumen por vendedor
            const vendorSummary = groupDataByVendor(filteredData);
            renderSalesSummary(vendorSummary);
            
            // Actualizar tabla de datos
            renderSalesData(filteredData, currentPage, rowsPerPage);
            
            // Actualizar selectores
            updateSelectors();
        }
        
        // Actualizar selectores
        function updateSelectors() {
            const vendors = [...new Set(salesData.map(item => item.vendor))];
            const vendorSelect = document.getElementById('vendor-select');
            const quotaVendorSelect = document.getElementById('quota-vendor');
            
            // Guardar selección actual
            const currentSelection = vendorSelect.value;
            
            // Limpiar y agregar opciones
            vendorSelect.innerHTML = '<option value="all">Todos los vendedores</option>';
            quotaVendorSelect.innerHTML = '<option value="">Seleccionar vendedor</option>';
            
            vendors.forEach(vendor => {
                const option = document.createElement('option');
                option.value = vendor;
                option.textContent = vendor;
                vendorSelect.appendChild(option);
                
                const quotaOption = option.cloneNode(true);
                quotaVendorSelect.appendChild(quotaOption);
            });
            
            // Restaurar selección si existe
            if (vendors.includes(currentSelection)) {
                vendorSelect.value = currentSelection;
            }
            
            // Actualizar años
            const yearSelect = document.getElementById('year-select');
            const quotaYearSelect = document.getElementById('quota-year');
            const currentYear = new Date().getFullYear();
            
            yearSelect.innerHTML = '';
            quotaYearSelect.innerHTML = '';
            
            for (let year = currentYear - 2; year <= currentYear + 1; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                option.selected = year === currentYear;
                yearSelect.appendChild(option);
                
                const quotaOption = option.cloneNode(true);
                quotaYearSelect.appendChild(quotaOption);
            }
            
            // Actualizar meses
            const monthSelect = document.getElementById('month-select');
            const quotaMonthSelect = document.getElementById('quota-month');
            const currentMonth = new Date().getMonth() + 1;
            const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            monthSelect.innerHTML = '';
            quotaMonthSelect.innerHTML = '';
            
            for (let month = 1; month <= 12; month++) {
                const option = document.createElement('option');
                option.value = month;
                option.textContent = monthNames[month - 1];
                option.selected = month === currentMonth;
                monthSelect.appendChild(option);
                
                const quotaOption = option.cloneNode(true);
                quotaMonthSelect.appendChild(quotaOption);
            }
            
            // Actualizar botones de vendedores
            const vendorSelector = document.querySelector('.vendor-selector');
            vendorSelector.innerHTML = '';
            
            // Botón para todos
            const allBtn = document.createElement('button');
            allBtn.className = 'vendor-btn active';
            allBtn.textContent = 'Todos';
            allBtn.dataset.vendor = 'all';
            allBtn.addEventListener('click', vendorButtonHandler);
            vendorSelector.appendChild(allBtn);
            
            // Botones para cada vendedor
            vendors.forEach(vendor => {
                const btn = document.createElement('button');
                btn.className = 'vendor-btn';
                btn.textContent = vendor;
                btn.dataset.vendor = vendor;
                btn.addEventListener('click', vendorButtonHandler);
                vendorSelector.appendChild(btn);
            });
        }
        
        // Manejador para botones de vendedores
        function vendorButtonHandler() {
            document.querySelectorAll('.vendor-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const vendor = this.dataset.vendor;
            document.getElementById('vendor-select').value = vendor;
            filterData();
        }
        
        // Cambiar empresa
        function changeCompany(company) {
            currentCompany = company;
            document.querySelectorAll('.company-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.company === company) {
                    btn.classList.add('active');
                }
            });
            
            // Enviar cambio de empresa al servidor
            const formData = new FormData();
            formData.append('empresa', company);
            formData.append('action', 'change_company');
            
            fetch('sales_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadDataFromServer();
                } else {
                    alert('Error al cambiar de empresa: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al cambiar empresa:', error);
            });
        }
        
        // Cambiar pestañas
        function setupTabs() {
            const tabs = document.querySelectorAll('.tab');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.dataset.tab;
                    
                    // Activar pestaña
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Mostrar contenido correspondiente
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                    
                    // Si es la pestaña de rendimiento, actualizar gráficas
                    if (tabId === 'performance') {
                        updateCharts();
                    }
                });
            });
        }
        
        // Configurar event listeners
        function setupEventListeners() {
            document.getElementById('vendor-select').addEventListener('change', filterData);
            document.getElementById('year-select').addEventListener('change', function() {
                currentYear = parseInt(this.value);
                loadDataFromServer();
            });
            document.getElementById('month-select').addEventListener('change', function() {
                currentMonth = parseInt(this.value);
                loadDataFromServer();
            });
            document.getElementById('product-filter').addEventListener('input', filterData);
            
            document.getElementById('prev-page').addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    updateDashboard();
                }
            });
            
            document.getElementById('next-page').addEventListener('click', function() {
                const totalPages = Math.ceil(filteredData.length / rowsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    updateDashboard();
                }
            });
            
            // Configurar botones de empresa
            document.querySelectorAll('.company-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    changeCompany(this.dataset.company);
                });
            });
            
            // Configurar botones de cuotas
            document.getElementById('load-quota').addEventListener('click', loadQuota);
            document.getElementById('save-quota').addEventListener('click', saveQuota);
            document.getElementById('delete-quota').addEventListener('click', deleteQuota);
        }
        
        // Cargar cuota específica
        function loadQuota() {
            const vendor = document.getElementById('quota-vendor').value;
            const year = document.getElementById('quota-year').value;
            const month = document.getElementById('quota-month').value;
            
            if (!vendor) {
                alert('Seleccione un vendedor');
                return;
            }
            
            const quota = quotasData.find(q => 
                q.CODIGO_VENDEDOR === vendor && 
                q.ANNO === parseInt(year) && 
                q.MES === parseInt(month)
            );
            
            if (quota) {
                document.getElementById('quota-amount').value = quota.CUOTA_DIVISA;
                document.getElementById('quota-boxes').value = quota.CUOTA_CAJAS;
                document.getElementById('quota-kilos').value = quota.CUOTA_KILOS;
            } else {
                document.getElementById('quota-amount').value = '';
                document.getElementById('quota-boxes').value = '';
                document.getElementById('quota-kilos').value = '';
            }
        }
        
        // Guardar cuota
        function saveQuota() {
            const vendor = document.getElementById('quota-vendor').value;
            const year = document.getElementById('quota-year').value;
            const month = document.getElementById('quota-month').value;
            const amount = document.getElementById('quota-amount').value;
            const boxes = document.getElementById('quota-boxes').value;
            const kilos = document.getElementById('quota-kilos').value;
            
            if (!vendor || !year || !month) {
                alert('Complete todos los campos obligatorios');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_quota');
            formData.append('vendor', vendor);
            formData.append('year', year);
            formData.append('month', month);
            formData.append('amount', amount);
            formData.append('boxes', boxes);
            formData.append('kilos', kilos);
            
            fetch('sales_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cuota guardada correctamente');
                    loadDataFromServer();
                } else {
                    alert('Error al guardar la cuota: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al guardar cuota:', error);
                alert('Error al guardar la cuota');
            });
        }
        
        // Eliminar cuota
        function deleteQuota() {
            const vendor = document.getElementById('quota-vendor').value;
            const year = document.getElementById('quota-year').value;
            const month = document.getElementById('quota-month').value;
            
            if (!vendor || !year || !month) {
                alert('Seleccione un vendedor, año y mes');
                return;
            }
            
            if (!confirm('¿Está seguro de que desea eliminar esta cuota?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_quota');
            formData.append('vendor', vendor);
            formData.append('year', year);
            formData.append('month', month);
            
            fetch('sales_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cuota eliminada correctamente');
                    loadDataFromServer();
                } else {
                    alert('Error al eliminar la cuota: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al eliminar cuota:', error);
                alert('Error al eliminar la cuota');
            });
        }
        
        // Inicializar el dashboard
        function initDashboard() {
            setupTabs();
            setupEventListeners();
            loadDataFromServer();
        }
        
        // Iniciar cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', initDashboard);
    </script>
</body>
</html>