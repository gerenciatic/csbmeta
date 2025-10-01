// Variables globales
let salesData = [];
let quotasData = [];
let categoryQuotasData = [];
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
let halfDoughnutChart = null;

// ==================== FUNCIONES DE PRUEBA ====================

// Función para registrar resultados de pruebas
function logTestResult(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
    
    console.log(`[${timestamp}] ${icon} ${message}`);
    
    // También mostrar en la interfaz si existe el elemento
    const debugInfo = document.getElementById('debug-info');
    if (debugInfo) {
        debugInfo.innerHTML = `[${timestamp}] ${icon} ${message}`;
    }
}

// Prueba de carga de datos
window.runDataTest = function() {
    logTestResult('Iniciando prueba de carga de datos...', 'info');
    
    const testData = {
        year: currentYear,
        month: currentMonth,
        company: currentCompany
    };
    
    logTestResult(`Parámetros: Año=${testData.year}, Mes=${testData.month}, Empresa=${testData.company}`, 'info');
    
    // Simular carga de datos
    const startTime = Date.now();
    
    fetch('sales_api.php', {
        method: 'POST',
        body: new URLSearchParams({
            action: 'get_sales_data',
            year: testData.year,
            month: testData.month,
            empresa: testData.company
        })
    })
    .then(response => {
        const responseTime = Date.now() - startTime;
        logTestResult(`Respuesta recibida en ${responseTime}ms`, 'info');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const recordCount = data.data.sales?.length || 0;
            const vendorCount = data.data.vendors?.length || 0;
            
            logTestResult(`✅ Datos cargados: ${recordCount} registros, ${vendorCount} vendedores`, 'success');
            logTestResult(`Última factura: ${data.data.last_billing_date || 'N/A'}`, 'info');
            
            // Probar procesamiento de datos
            testDataProcessing(data.data);
        } else {
            logTestResult(`❌ Error en datos: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        logTestResult(`❌ Error de conexión: ${error.message}`, 'error');
    });
};

// Prueba de procesamiento de datos
function testDataProcessing(data) {
    logTestResult('Probando procesamiento de datos...', 'info');
    
    try {
        // Test 1: Verificar estructura de datos
        const requiredFields = ['vendor', 'product', 'total', 'date'];
        const sampleItem = data.sales[0];
        
        if (sampleItem) {
            const missingFields = requiredFields.filter(field => !(field in sampleItem));
            if (missingFields.length === 0) {
                logTestResult('✅ Estructura de datos correcta', 'success');
            } else {
                logTestResult(`⚠️ Campos faltantes: ${missingFields.join(', ')}`, 'warning');
            }
        }
        
        // Test 2: Verificar cálculos
        const metrics = calculateMetrics(data.sales);
        logTestResult(`Cálculos: Ventas=$${metrics.totalSales}, Transacciones=${metrics.transactions}`, 'info');
        
        // Test 3: Verificar filtros
        const filtered = data.sales.filter(item => item.vendor === '03');
        logTestResult(`Filtro vendedor 03: ${filtered.length} registros`, 'info');
        
    } catch (error) {
        logTestResult(`❌ Error en procesamiento: ${error.message}`, 'error');
    }
}

// Prueba de API
window.runAPITest = function() {
    logTestResult('Iniciando pruebas de API...', 'info');
    
    const endpoints = [
        { action: 'get_sales_data', params: { year: currentYear, month: currentMonth } },
        { action: 'change_company', params: { empresa: 'A' } }
    ];
    
    let completed = 0;
    
    endpoints.forEach(endpoint => {
        fetch('sales_api.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: endpoint.action,
                ...endpoint.params
            })
        })
        .then(response => response.json())
        .then(data => {
            completed++;
            if (data.success) {
                logTestResult(`✅ ${endpoint.action}: ${data.message}`, 'success');
            } else {
                logTestResult(`❌ ${endpoint.action}: ${data.message}`, 'error');
            }
            
            if (completed === endpoints.length) {
                logTestResult('✅ Todas las pruebas de API completadas', 'success');
            }
        })
        .catch(error => {
            completed++;
            logTestResult(`❌ ${endpoint.action}: Error de red - ${error.message}`, 'error');
        });
    });
};

// Prueba de filtros
window.runFilterTest = function() {
    logTestResult('Probando sistema de filtros...', 'info');
    
    if (salesData.length === 0) {
        logTestResult('⚠️ No hay datos para probar filtros', 'warning');
        return;
    }
    
    // Test filtro por vendedor
    const testVendor = salesData[0]?.vendor;
    if (testVendor) {
        const vendorFiltered = salesData.filter(item => item.vendor === testVendor);
        logTestResult(`Filtro vendedor "${testVendor}": ${vendorFiltered.length} registros`, 'info');
    }
    
    // Test filtro por producto
    const testProduct = salesData[0]?.product;
    if (testProduct) {
        const productFiltered = salesData.filter(item => 
            item.product && item.product.toLowerCase().includes(testProduct.substring(0, 3).toLowerCase())
        );
        logTestResult(`Filtro producto "${testProduct.substring(0, 3)}": ${productFiltered.length} registros`, 'info');
    }
    
    logTestResult('✅ Pruebas de filtros completadas', 'success');
};

// ==================== FUNCIONES PRINCIPALES ====================

// Función para determinar tipo de transacción
function getTransactionType(item) {
    const doc = (item.doc || '').toString().toUpperCase();
    const total = parseFloat(item.total) || 0;
    
    if (doc.includes('NOTA') || doc.includes('NC') || doc.includes('ND') || total < 0) {
        return 'nota';
    }
    return 'factura';
}

// Función para formatear números
function formatNumber(number, decimals = 2, isCurrency = false) {
    if (isNaN(number) || number === null || number === undefined) {
        return isCurrency ? '$0.00' : '0.00';
    }
    
    const fixedNum = Math.abs(Number(number)).toFixed(decimals);
    const parts = fixedNum.toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    return isCurrency ? '$' + parts.join('.') : parts.join('.');
}

// Actualizar hora
function updateTime() {
    const now = new Date();
    const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                   now.getMinutes().toString().padStart(2, '0');
    document.getElementById('current-time').textContent = timeStr;
}

// Mostrar mensajes al usuario
function showUserMessage(type, message, duration = 5000) {
    logTestResult(`${type.toUpperCase()}: ${message}`, type === 'error' ? 'error' : 'info');
    
    // Implementación simple con alert (puedes mejorarla con un sistema de notificaciones)
    if (type === 'error') {
        alert(`❌ Error: ${message}`);
    }
}

// Calcular métricas
function calculateMetrics(data) {
    if (!data || data.length === 0) {
        return {
            totalSales: 0,
            avgKg: 0,
            achievement: 0,
            transactions: 0,
            salesTarget: 0,
            facturasTotal: 0,
            notasTotal: 0,
            netSales: 0
        };
    }
    
    let facturasTotal = 0;
    let notasTotal = 0;
    let totalKg = 0;
    
    data.forEach(item => {
        const transactionType = getTransactionType(item);
        const amount = Math.abs(parseFloat(item.total) || 0);
        const kgValue = Math.abs(parseFloat(item.kg) || 0);
        
        if (transactionType === 'factura') {
            facturasTotal += amount;
        } else {
            notasTotal += amount;
        }
        
        totalKg += kgValue;
    });
    
    const netSales = Math.max(0, facturasTotal - notasTotal);
    const transactions = data.length;
    
    // Calcular meta
    const vendor = document.getElementById('vendor-select').value;
    let salesTarget = 0;
    
    if (vendor !== 'all') {
        const vendorQuota = quotasData.find(q => 
            q.CODIGO_VENDEDOR === vendor && 
            parseInt(q.ANNO) === currentYear && 
            parseInt(q.MES) === currentMonth
        );
        salesTarget = vendorQuota ? Math.abs(parseFloat(vendorQuota.CUOTA_DIVISA)) : 0;
    } else {
        salesTarget = quotasData
            .filter(q => parseInt(q.ANNO) === currentYear && parseInt(q.MES) === currentMonth)
            .reduce((sum, q) => sum + Math.abs(parseFloat(q.CUOTA_DIVISA || 0)), 0);
    }
    
    const achievement = salesTarget > 0 ? Math.min(100, (netSales / salesTarget) * 100) : 0;
    
    return {
        totalSales: netSales,
        avgKg: transactions > 0 ? totalKg / transactions : 0,
        achievement: achievement,
        transactions: transactions,
        salesTarget: salesTarget,
        facturasTotal: facturasTotal,
        notasTotal: notasTotal,
        netSales: netSales
    };
}

// Actualizar gráfica de media torta
function updateHalfDoughnutChart(percentage, totalSales, salesTarget) {
    const ctx = document.getElementById('halfDoughnutChart');
    if (!ctx) return;
    
    if (halfDoughnutChart) {
        halfDoughnutChart.destroy();
    }
    
    const achieved = Math.min(percentage, 100);
    const remaining = Math.max(0, 100 - achieved);
    
    halfDoughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Alcanzado', 'Restante'],
            datasets: [{
                data: [achieved, remaining],
                backgroundColor: [
                    achieved >= 100 ? '#2ecc71' : 
                    achieved >= 80 ? '#3498db' : 
                    achieved >= 60 ? '#f39c12' : '#e74c3c',
                    '#e3e6f0'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            circumference: 180,
            rotation: -90,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw.toFixed(1) + '%';
                        }
                    }
                }
            }
        }
    });
    
    // Actualizar valores
    const achievementElement = document.getElementById('achievement-percentage');
    if (achievementElement) {
        achievementElement.textContent = formatNumber(percentage, 1) + '%';
    }
    
    updateChartInfoPanel(totalSales, salesTarget);
}

// Actualizar panel de información del gráfico
function updateChartInfoPanel(totalSales, salesTarget) {
    const elements = {
        'accumulated-sales': formatNumber(totalSales, 2, true),
        'sales-target': formatNumber(salesTarget, 2, true),
        'remaining-amount': formatNumber(Math.max(0, salesTarget - totalSales), 2, true)
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });
}

// Actualizar estado de conexión
function updateConnectionStatus(connected) {
    const statusElement = document.getElementById('connection-status');
    if (statusElement) {
        if (connected) {
            statusElement.textContent = '✅ Conectado a la base de datos';
            statusElement.className = 'connection-status status-connected';
        } else {
            statusElement.textContent = '❌ Desconectado de la base de datos';
            statusElement.className = 'connection-status status-disconnected';
        }
    }
}

// Actualizar información de depuración
function updateDebugInfo(message) {
    const debugElement = document.getElementById('debug-info');
    if (debugElement) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString();
        debugElement.textContent = `[${timeStr}] ${message}`;
    }
}

// Cargar datos de cuotas
function loadQuotasData() {
    const quotasBody = document.getElementById('quotas-data-body');
    if (!quotasBody) return;
    
    if (!quotasData || quotasData.length === 0) {
        quotasBody.innerHTML = '<tr><td colspan="7" class="no-data">No hay cuotas definidas</td></tr>';
        return;
    }
    
    quotasBody.innerHTML = '';
    quotasData.forEach(quota => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${quota.CODIGO_VENDEDOR || 'N/A'}</td>
            <td>${quota.ANNO || 'N/A'}</td>
            <td>${quota.MES || 'N/A'}</td>
            <td>${formatNumber(parseFloat(quota.CUOTA_DIVISA || 0), 2, true)}</td>
            <td>${formatNumber(parseFloat(quota.CUOTA_CAJAS || 0), 0)}</td>
            <td>${formatNumber(parseFloat(quota.CUOTA_KILOS || 0), 2)}</td>
            <td>${quota.FECHA_ACTUALIZACION ? new Date(quota.FECHA_ACTUALIZACION).toLocaleDateString() : 'N/A'}</td>
        `;
        quotasBody.appendChild(row);
    });
}

// Cargar datos de cuotas por categoría
function loadCategoryQuotasData() {
    const categoryQuotasBody = document.getElementById('category-quotas-data-body');
    if (!categoryQuotasBody) return;
    
    if (!categoryQuotasData || categoryQuotasData.length === 0) {
        categoryQuotasBody.innerHTML = '<tr><td colspan="7" class="no-data">No hay cuotas por categoría</td></tr>';
        return;
    }
    
    categoryQuotasBody.innerHTML = '';
    categoryQuotasData.forEach(quota => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${quota.CODIGO_VENDEDOR || 'N/A'}</td>
            <td>${quota.CLASE_PRODUCTO || 'N/A'}</td>
            <td>${quota.ANNO || 'N/A'}</td>
            <td>${quota.MES || 'N/A'}</td>
            <td>${formatNumber(parseFloat(quota.CUOTA_DIVISA || 0), 2, true)}</td>
            <td>${formatNumber(parseFloat(quota.CUOTA_ACTIVACION || 0), 2, true)}</td>
            <td>${quota.FECHA_ACTUALIZACION ? new Date(quota.FECHA_ACTUALIZACION).toLocaleDateString() : 'N/A'}</td>
        `;
        categoryQuotasBody.appendChild(row);
    });
}

// Agrupar datos por vendedor
function groupDataByVendor(data) {
    if (!data || data.length === 0) return [];
    
    const grouped = {};
    
    data.forEach(item => {
        const vendor = item.vendor || 'N/A';
        if (!grouped[vendor]) {
            grouped[vendor] = {
                vendor: vendor,
                totalSales: 0,
                facturasTotal: 0,
                notasTotal: 0,
                totalKg: 0,
                transactions: 0
            };
        }
        
        const transactionType = getTransactionType(item);
        const amount = Math.abs(parseFloat(item.total) || 0);
        const kgValue = Math.abs(parseFloat(item.kg) || 0);
        
        if (transactionType === 'factura') {
            grouped[vendor].facturasTotal += amount;
            grouped[vendor].totalSales += amount;
        } else {
            grouped[vendor].notasTotal += amount;
            grouped[vendor].totalSales -= amount;
        }
        
        grouped[vendor].totalKg += kgValue;
        grouped[vendor].transactions += 1;
    });
    
    return Object.values(grouped);
}

// Renderizar tabla de resumen
function renderSalesSummary(data) {
    const summaryBody = document.getElementById('sales-summary-body');
    if (!summaryBody) return;
    
    summaryBody.innerHTML = '';
    
    if (!data || data.length === 0) {
        summaryBody.innerHTML = '<tr><td colspan="4" class="no-data">No hay datos disponibles</td></tr>';
        return;
    }
    
    data.forEach(item => {
        const vendorQuota = quotasData.find(q => 
            q.CODIGO_VENDEDOR === item.vendor && 
            parseInt(q.ANNO) === currentYear && 
            parseInt(q.MES) === currentMonth
        );
        
        const salesTarget = vendorQuota ? Math.abs(parseFloat(vendorQuota.CUOTA_DIVISA) || 0) : 0;
        const achievement = salesTarget > 0 ? Math.max(0, (item.totalSales / salesTarget) * 100) : 0;
        
        let complianceClass = '';
        if (achievement >= 100) complianceClass = 'compliance-excellent';
        else if (achievement >= 80) complianceClass = 'compliance-good';
        else if (achievement >= 60) complianceClass = 'compliance-fair';
        else complianceClass = 'compliance-poor';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.vendor}</td>
            <td>${formatNumber(item.totalSales, 2, true)}</td>
            <td>${formatNumber(salesTarget, 2, true)}</td>
            <td class="${complianceClass}">${formatNumber(achievement, 1)}%</td>
        `;
        summaryBody.appendChild(row);
    });
}

// Renderizar tabla de datos con paginación
function renderSalesData(data, page, rowsPerPage) {
    const tableBody = document.getElementById('sales-data-body');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="9" class="no-data">No hay datos para mostrar</td></tr>';
        updatePaginationControls(0, page);
        return;
    }
    
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = data.slice(start, end);
    
    paginatedData.forEach(item => {
        const transactionType = getTransactionType(item);
        const total = Math.abs(parseFloat(item.total) || 0);
        const kg = Math.abs(parseFloat(item.kg) || 0);
        const unitPrice = Math.abs(parseFloat(item.unitPrice) || 0);
        
        const rowClass = transactionType === 'nota' ? 'nota-row' : 'factura-row';
        
        const row = document.createElement('tr');
        row.className = rowClass;
        row.innerHTML = `
            <td>${item.doc || 'N/A'}</td>
            <td>${item.date || 'N/A'}</td>
            <td>${item.vendor || 'N/A'}</td>
            <td>${item.product || 'N/A'}</td>
            <td>${item.customer_name || 'N/A'}</td>
            <td>${formatNumber(unitPrice, 4, true)}</td>
            <td>${item.quantity || '0'}</td>
            <td>${formatNumber(kg, 2)}</td>
            <td class="${transactionType === 'nota' ? 'negative-amount' : 'positive-amount'}">
                ${transactionType === 'nota' ? '-' : ''}${formatNumber(total, 4, true)}
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    updatePaginationControls(data.length, page, rowsPerPage);
}

// Actualizar controles de paginación
function updatePaginationControls(totalItems, currentPage, rowsPerPage) {
    const pageInfo = document.getElementById('page-info');
    const prevButton = document.getElementById('prev-page');
    const nextButton = document.getElementById('next-page');
    
    if (!pageInfo || !prevButton || !nextButton) return;
    
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    
    pageInfo.textContent = `Página ${currentPage} de ${totalPages} (${totalItems} registros)`;
    prevButton.disabled = currentPage <= 1;
    nextButton.disabled = currentPage >= totalPages;
}

// Actualizar gráficas
function updateCharts() {
    try {
        const metrics = calculateMetrics(filteredData);
        updateHalfDoughnutChart(metrics.achievement, metrics.totalSales, metrics.salesTarget);
        updateSalesProgressChart();
        updateVendorDistributionChart();
    } catch (error) {
        console.error('Error actualizando gráficas:', error);
        logTestResult(`Error en gráficas: ${error.message}`, 'error');
    }
}

// Gráfica de progreso de ventas vs meta
function updateSalesProgressChart() {
    const ctx = document.getElementById('sales-progress-chart');
    if (!ctx) return;
    
    if (salesProgressChart) {
        salesProgressChart.destroy();
    }
    
    const metrics = calculateMetrics(filteredData);
    
    salesProgressChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ventas Realizadas', 'Meta'],
            datasets: [{
                label: 'Monto ($)',
                data: [metrics.totalSales, metrics.salesTarget],
                backgroundColor: [
                    metrics.achievement >= 100 ? '#2ecc71' : 
                    metrics.achievement >= 80 ? '#3498db' : 
                    metrics.achievement >= 60 ? '#f39c12' : '#e74c3c',
                    '#e3e6f0'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
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

// Gráfica de distribución por vendedor
function updateVendorDistributionChart() {
    const ctx = document.getElementById('vendor-distribution-chart');
    if (!ctx) return;

    if (vendorDistributionChart) {
        vendorDistributionChart.destroy();
    }

    const vendorSalesData = groupDataByVendor(filteredData);
    
    if (!vendorSalesData || vendorSalesData.length === 0) {
        return;
    }

    vendorDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: vendorSalesData.map(v => v.vendor),
            datasets: [{
                label: 'Ventas ($)',
                data: vendorSalesData.map(v => v.totalSales),
                backgroundColor: '#3498db',
                borderWidth: 1
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

// Filtrar datos
function filterData() {
    const vendor = document.getElementById('vendor-select').value;
    const productFilter = document.getElementById('product-filter').value.toLowerCase();
    const year = document.getElementById('year-select').value;
    const month = document.getElementById('month-select').value;
    
    // Si cambian año o mes, recargar desde servidor
    if (parseInt(year) !== currentYear || parseInt(month) !== currentMonth) {
        currentYear = parseInt(year);
        currentMonth = parseInt(month);
        loadDataFromServer();
        return;
    }
    
    filteredData = salesData.filter(item => {
        const vendorMatch = vendor === 'all' || item.vendor === vendor;
        const product = item.product || '';
        const productMatch = product.toLowerCase().includes(productFilter);
        return vendorMatch && productMatch;
    });
    
    currentPage = 1;
    updateDashboard();
    updateCharts();
}

// Mostrar estado de carga
function showLoading() {
    const elements = {
        'sales-summary-body': '<tr><td colspan="4" class="loading">Cargando datos...</td></tr>',
        'sales-data-body': '<tr><td colspan="9" class="loading">Cargando datos...</td></tr>',
        'quotas-data-body': '<tr><td colspan="7" class="loading">Cargando datos...</td></tr>',
        'category-quotas-data-body': '<tr><td colspan="7" class="loading">Cargando datos...</td></tr>'
    };
    
    Object.entries(elements).forEach(([id, html]) => {
        const element = document.getElementById(id);
        if (element) element.innerHTML = html;
    });
    
    // Resetear métricas
    const metrics = {
        'total-sales': '$0.00',
        'avg-kg': '0.00',
        'transactions': '0',
        'budget-compliance': '0%'
    };
    
    Object.entries(metrics).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });
}

// Actualizar dashboard completo
function updateDashboard() {
    const metrics = calculateMetrics(filteredData);
    
    // Actualizar métricas
    updateMetric('total-sales', formatNumber(metrics.totalSales, 2, true));
    updateMetric('avg-kg', formatNumber(metrics.avgKg, 2));
    updateMetric('transactions', formatNumber(metrics.transactions, 0));
    updateMetric('budget-compliance', formatNumber(metrics.achievement, 1) + '%');
    
    // Actualizar gráficas
    updateHalfDoughnutChart(metrics.achievement, metrics.totalSales, metrics.salesTarget);
    
    // Actualizar resumen por vendedor
    const vendorSummary = groupDataByVendor(filteredData);
    renderSalesSummary(vendorSummary);
    
    // Actualizar tabla de datos
    renderSalesData(filteredData, currentPage, rowsPerPage);
    
    // Actualizar selectores
    updateSelectors();
    
    logTestResult(`Dashboard actualizado: ${filteredData.length} registros`, 'info');
}

// Actualizar una métrica individual
function updateMetric(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

// Actualizar selectores
function updateSelectors() {
    updateVendorSelectors();
    updateYearSelectors();
    updateMonthSelectors();
}

// Actualizar selectores de vendedor
function updateVendorSelectors() {
    const vendors = [...new Set(salesData.map(item => item.vendor).filter(v => v))];
    const vendorSelect = document.getElementById('vendor-select');
    const quotaVendorSelect = document.getElementById('quota-vendor');
    const categoryQuotaVendorSelect = document.getElementById('category-quota-vendor');
    
    if (vendorSelect) {
        const currentSelection = vendorSelect.value;
        vendorSelect.innerHTML = '<option value="all">Todos los vendedores</option>';
        vendors.forEach(vendor => {
            const option = document.createElement('option');
            option.value = vendor;
            option.textContent = vendor;
            vendorSelect.appendChild(option);
        });
        if (vendors.includes(currentSelection)) {
            vendorSelect.value = currentSelection;
        }
    }
    
    [quotaVendorSelect, categoryQuotaVendorSelect].forEach(select => {
        if (select) {
            select.innerHTML = '<option value="">Seleccionar vendedor</option>';
            vendors.forEach(vendor => {
                const option = document.createElement('option');
                option.value = vendor;
                option.textContent = vendor;
                select.appendChild(option);
            });
        }
    });
}

// Actualizar selectores de año
function updateYearSelectors() {
    const yearSelects = [
        'year-select', 'quota-year', 'category-quota-year'
    ];
    const currentYear = new Date().getFullYear();
    
    yearSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            select.innerHTML = '';
            for (let year = currentYear - 2; year <= currentYear + 1; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                option.selected = year === currentYear;
                select.appendChild(option);
            }
        }
    });
}

// Actualizar selectores de mes
function updateMonthSelectors() {
    const monthSelects = [
        'month-select', 'quota-month', 'category-quota-month'
    ];
    const currentMonth = new Date().getMonth() + 1;
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    monthSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            select.innerHTML = '';
            for (let month = 1; month <= 12; month++) {
                const option = document.createElement('option');
                option.value = month;
                option.textContent = monthNames[month - 1];
                option.selected = month === currentMonth;
                select.appendChild(option);
            }
        }
    });
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
            showUserMessage('success', data.message);
            loadDataFromServer();
        } else {
            showUserMessage('error', 'Error al cambiar empresa: ' + data.message);
        }
    })
    .catch(error => {
        showUserMessage('error', "Error al cambiar empresa: " + error.message);
    });
}

// Configurar pestañas
function setupTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.dataset.tab;
            
            // Activar pestaña
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Mostrar contenido
            tabPanes.forEach(pane => pane.classList.remove('active'));
            const targetPane = document.getElementById(`${tabId}-tab`);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
}

// Configurar event listeners
function setupEventListeners() {
    // Filtros
    const vendorSelect = document.getElementById('vendor-select');
    const yearSelect = document.getElementById('year-select');
    const monthSelect = document.getElementById('month-select');
    const productFilter = document.getElementById('product-filter');
    
    if (vendorSelect) vendorSelect.addEventListener('change', filterData);
    if (yearSelect) yearSelect.addEventListener('change', filterData);
    if (monthSelect) monthSelect.addEventListener('change', filterData);
    if (productFilter) productFilter.addEventListener('input', filterData);
    
    // Paginación
    const prevPage = document.getElementById('prev-page');
    const nextPage = document.getElementById('next-page');
    
    if (prevPage) prevPage.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updateDashboard();
        }
    });
    
    if (nextPage) nextPage.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateDashboard();
        }
    });
    
    // Empresas
    document.querySelectorAll('.company-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            changeCompany(this.dataset.company);
        });
    });
}

// Cargar datos desde servidor
function loadDataFromServer() {
    showLoading();
    updateConnectionStatus(false);
    updateDebugInfo("Solicitando datos al servidor...");
    
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
        if (data.success) {
            updateConnectionStatus(true);
            salesData = data.data.sales || [];
            quotasData = data.data.quotas || [];
            categoryQuotasData = data.data.class_quotas || [];
            filteredData = [...salesData];
            
            logTestResult(`Datos cargados: ${salesData.length} registros`, 'success');
            updateDebugInfo(`Datos cargados: ${salesData.length} registros`);
            
            updateDashboard();
            loadQuotasData();
            loadCategoryQuotasData();
            updateCharts();
            
            // Actualizar última fecha
            const lastBillingElement = document.getElementById('last-billing-date');
            if (lastBillingElement) {
                lastBillingElement.textContent = data.data.last_billing_date || 'No disponible';
            }
        } else {
            updateConnectionStatus(false);
            showUserMessage('error', data.message);
            updateDebugInfo("Error: " + data.message);
        }
    })
    .catch(error => {
        updateConnectionStatus(false);
        showUserMessage('error', 'Error de conexión: ' + error.message);
        updateDebugInfo("Error: " + error.message);
    });
}

// Inicializar dashboard
function initDashboard() {
    logTestResult('Inicializando dashboard...', 'info');
    
    try {
        setupTabs();
        setupEventListeners();
        updateTime();
        setInterval(updateTime, 60000);
        loadDataFromServer();
        
        logTestResult('Dashboard inicializado correctamente', 'success');
    } catch (error) {
        logTestResult(`Error inicializando: ${error.message}`, 'error');
        showUserMessage('error', 'Error inicializando el dashboard');
    }
}

// Iniciar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}