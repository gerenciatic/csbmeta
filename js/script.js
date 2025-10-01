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
let currentCategory = 'all';
let lastBillingDate = null;

// Función para determinar si un registro es Factura o Nota
function getTransactionType(item) {
    const doc = (item.doc || '').toString().toUpperCase();
    const total = parseFloat(item.total) || 0;
    
    // Detectar notas de crédito
    if (doc.includes('NOTA') || doc.includes('NC') || doc.includes('ND')) {
        return 'nota';
    }
    
    // Si el total es negativo, es una nota
    if (total < 0) {
        return 'nota';
    }
    
    // Por defecto es factura
    return 'factura';
}

// Función para formatear números con separadores de miles
function formatNumber(number, decimals = 2, isCurrency = false) {
    if (isNaN(number) || number === null || number === undefined) {
        return isCurrency ? '$0.00' : '0.00';
    }
    
    const fixedNum = Math.abs(Number(number)).toFixed(decimals);
    const parts = fixedNum.toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    return isCurrency ? '$' + parts.join('.') : parts.join('.');
}

// Actualizar la hora en tiempo real
function updateTime() {
    const now = new Date();
    const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                   now.getMinutes().toString().padStart(2, '0');
    document.getElementById('current-time').textContent = timeStr;
}

// Mostrar mensaje al usuario
function showUserMessage(type, message, duration = 5000) {
    // Remover mensajes existentes
    const existingMsg = document.getElementById('user-message');
    if (existingMsg) {
        existingMsg.remove();
    }
    
    const messageEl = document.createElement('div');
    messageEl.id = 'user-message';
    messageEl.className = `user-message ${type}`;
    messageEl.innerHTML = `
        <div class="message-content">
            <span class="message-icon">${type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
            <span class="message-text">${message}</span>
            <button class="message-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    document.body.appendChild(messageEl);
    
    // Auto-remover después del tiempo especificado
    if (duration > 0) {
        setTimeout(() => {
            if (messageEl.parentElement) {
                messageEl.remove();
            }
        }, duration);
    }
}

// Calcular métricas basadas en los datos
function calculateMetrics(data) {
    if (!data || data.length === 0) {
        return {
            totalSales: 0,
            avgKg: 0,
            achievement: 0,
            transactions: 0,
            salesTarget: 0,
            kgTarget: 0,
            facturasTotal: 0,
            notasTotal: 0,
            netSales: 0
        };
    }
    
    let facturasTotal = 0;
    let notasTotal = 0;
    let totalKg = 0;
    let facturasCount = 0;
    let notasCount = 0;
    
    data.forEach(item => {
        const transactionType = getTransactionType(item);
        const amount = Math.abs(parseFloat(item.total) || 0);
        const kgValue = Math.abs(parseFloat(item.kg) || 0);
        
        if (transactionType === 'factura') {
            facturasTotal += amount;
            facturasCount++;
        } else {
            notasTotal += amount;
            notasCount++;
        }
        
        totalKg += kgValue;
    });
    
    // Ventas netas = Facturas - Notas
    const netSales = Math.max(0, facturasTotal - notasTotal);
    const transactions = data.length;
    
    // Obtener vendedor seleccionado
    const vendor = document.getElementById('vendor-select').value;
    let salesTarget = 0;
    
    // Calcular meta según selección
    if (vendor !== 'all') {
        const vendorQuota = quotasData.find(q => 
            q.CODIGO_VENDEDOR === vendor && 
            parseInt(q.ANNO) === currentYear && 
            parseInt(q.MES) === currentMonth
        );
        
        if (vendorQuota && vendorQuota.CUOTA_DIVISA) {
            salesTarget = Math.abs(parseFloat(vendorQuota.CUOTA_DIVISA));
        }
    } else {
        const relevantQuotas = quotasData.filter(q => 
            parseInt(q.ANNO) === currentYear && 
            parseInt(q.MES) === currentMonth
        );
        
        salesTarget = relevantQuotas.reduce((sum, q) => {
            const quotaValue = q.CUOTA_DIVISA ? Math.abs(parseFloat(q.CUOTA_DIVISA)) : 0;
            return sum + quotaValue;
        }, 0);
    }
    
    const achievement = salesTarget > 0 ? Math.min(100, (netSales / salesTarget) * 100) : 0;
    
    return {
        totalSales: netSales,
        avgKg: transactions > 0 ? totalKg / transactions : 0,
        achievement: achievement,
        transactions,
        salesTarget,
        kgTarget: 0,
        facturasTotal,
        notasTotal,
        netSales
    };
}

// Crear o actualizar la gráfica de media torta
function updateHalfDoughnutChart(percentage, totalSales, salesTarget) {
    const ctx = document.getElementById('halfDoughnutChart');
    
    if (!ctx) {
        console.error("Canvas halfDoughnutChart no encontrado");
        return;
    }
    
    // Si ya existe un gráfico, destruirlo primero
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
                borderWidth: 0,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            circumference: 180,
            rotation: -90,
            plugins: {
                legend: {
                    display: false
                },
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
    
    // Actualizar el valor en el centro del gráfico
    const achievementElement = document.getElementById('achievement-percentage');
    if (achievementElement) {
        achievementElement.textContent = formatNumber(percentage, 1) + '%';
        achievementElement.style.color = 
            percentage >= 100 ? '#2ecc71' : 
            percentage >= 80 ? '#3498db' : 
            percentage >= 60 ? '#f39c12' : '#e74c3c';
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
        quotasBody.innerHTML = '<tr><td colspan="7" class="no-data">No hay cuotas definidas para este período</td></tr>';
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
        categoryQuotasBody.innerHTML = '<tr><td colspan="7" class="no-data">No hay cuotas por categoría definidas</td></tr>';
        return;
    }
    
    categoryQuotasBody.innerHTML = '';
    categoryQuotasData.forEach(quota => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${quota.VENDEDOR || quota.CODIGO_VENDEDOR || 'N/A'}</td>
            <td>${quota.LINEA || quota.CLASE_PRODUCTO || 'N/A'}</td>
            <td>${quota.ANNO || 'N/A'}</td>
            <td>${quota.MES || 'N/A'}</td>
            <td>${formatNumber(parseFloat(quota.CUOTA_LINEA || quota.CUOTA_DIVISA || 0), 2, true)}</td>
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
                transactions: 0,
                facturasCount: 0,
                notasCount: 0
            };
        }
        
        const transactionType = getTransactionType(item);
        const amount = Math.abs(parseFloat(item.total) || 0);
        const kgValue = Math.abs(parseFloat(item.kg) || 0);
        
        if (transactionType === 'factura') {
            grouped[vendor].facturasTotal += amount;
            grouped[vendor].facturasCount++;
            grouped[vendor].totalSales += amount;
        } else {
            grouped[vendor].notasTotal += amount;
            grouped[vendor].notasCount++;
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
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="9" class="no-data">No hay datos para mostrar con los filtros aplicados</td>';
        tableBody.appendChild(row);
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
            <td>${item.date || item.fecha_factura || 'N/A'}</td>
            <td>${item.vendor || 'N/A'}</td>
            <td>${item.product || 'N/A'}</td>
            <td>${item.category || item.customer_name || 'N/A'}</td>
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
        updateMonthlySalesChart();
        updateTopProductsChart();
    } catch (error) {
        console.error('Error actualizando gráficas:', error);
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
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.raw.toFixed(2);
                        }
                    }
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

// Gráfica de distribución por Vendedor
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

    const vendorAchievementData = vendorSalesData.map(vendor => {
        const quotaInfo = quotasData.find(q => 
            q.CODIGO_VENDEDOR === vendor.vendor &&
            parseInt(q.ANNO) === currentYear &&
            parseInt(q.MES) === currentMonth
        );

        const salesTarget = quotaInfo ? parseFloat(quotaInfo.CUOTA_DIVISA) || 0 : 0;
        const achievement = salesTarget > 0 ? (vendor.totalSales / salesTarget) * 100 : 0;

        return {
            vendor: vendor.vendor,
            achievement: Math.min(achievement, 100),
            sales: vendor.totalSales
        };
    });

    const sortedVendors = vendorAchievementData.sort((a, b) => b.achievement - a.achievement);
    
    vendorDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sortedVendors.map(v => v.vendor),
            datasets: [{
                label: '% Cumplimiento',
                data: sortedVendors.map(v => v.achievement),
                backgroundColor: sortedVendors.map(v => 
                    v.achievement >= 100 ? '#2ecc71' : 
                    v.achievement >= 80 ? '#3498db' : 
                    v.achievement >= 60 ? '#f39c12' : '#e74c3c'
                ),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const vendor = sortedVendors[context.dataIndex];
                            return [
                                `Cumplimiento: ${context.parsed.y.toFixed(1)}%`,
                                `Ventas: $${formatNumber(vendor.sales, 2)}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 110,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// Gráfica de evolución mensual
function updateMonthlySalesChart() {
    const ctx = document.getElementById('monthly-sales-chart');
    if (!ctx) return;
    
    if (monthlySalesChart) {
        monthlySalesChart.destroy();
    }
    
    // Datos de ejemplo para la evolución mensual
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const monthlySales = Array(12).fill(0);
    
    // Simular datos mensuales basados en los datos actuales
    const metrics = calculateMetrics(filteredData);
    const currentMonthIndex = currentMonth - 1;
    
    // Distribuir las ventas actuales en el mes correspondiente
    if (currentMonthIndex >= 0 && currentMonthIndex < 12) {
        monthlySales[currentMonthIndex] = metrics.totalSales;
    }
    
    monthlySalesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Ventas Mensuales ($)',
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
    const ctx = document.getElementById('top-products-chart');
    if (!ctx) return;
    
    if (topProductsChart) {
        topProductsChart.destroy();
    }
    
    if (!filteredData || filteredData.length === 0) {
        return;
    }
    
    const productSales = {};
    filteredData.forEach(item => {
        const transactionType = getTransactionType(item);
        const amount = Math.abs(parseFloat(item.total) || 0);
        const product = item.product || 'Sin nombre';
        
        if (!productSales[product]) {
            productSales[product] = 0;
        }
        
        if (transactionType === 'factura') {
            productSales[product] += amount;
        } else {
            productSales[product] -= amount;
        }
    });
    
    const productArray = Object.keys(productSales)
        .map(product => ({ product, sales: Math.max(0, productSales[product]) }))
        .filter(item => item.sales > 0);
    
    const topProducts = productArray.sort((a, b) => b.sales - a.sales).slice(0, 8);
    
    if (topProducts.length === 0) {
        return;
    }
    
    topProductsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topProducts.map(p => {
                return p.product.length > 20 ? p.product.substring(0, 20) + '...' : p.product;
            }),
            datasets: [{
                label: 'Ventas por Producto ($)',
                data: topProducts.map(p => p.sales),
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

// Filtrar datos basados en los filtros seleccionados
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
        'budget-compliance': '0%',
        'achievement-percentage': '0%',
        'accumulated-sales': '$0.00',
        'sales-target': '$0.00',
        'remaining-amount': '$0.00'
    };
    
    Object.entries(metrics).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });
}

// Actualizar todo el dashboard
function updateDashboard() {
    const metrics = calculateMetrics(filteredData);
    
    console.log("Métricas calculadas:", metrics);
    
    // Actualizar métricas
    updateMetric('total-sales', formatNumber(metrics.totalSales, 2, true));
    updateMetric('avg-kg', formatNumber(metrics.avgKg, 2));
    updateMetric('transactions', formatNumber(metrics.transactions, 0));
    updateMetric('budget-compliance', formatNumber(metrics.achievement, 1) + '%');
    
    // Actualizar gráfica de media torta
    updateHalfDoughnutChart(metrics.achievement, metrics.totalSales, metrics.salesTarget);
    
    // Actualizar resumen por vendedor
    const vendorSummary = groupDataByVendor(filteredData);
    renderSalesSummary(vendorSummary);
    
    // Actualizar tabla de datos
    renderSalesData(filteredData, currentPage, rowsPerPage);
    
    // Actualizar selectores
    updateSelectors();
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
    updateCategorySelectors();
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

// Actualizar selectores de categoría
function updateCategorySelectors() {
    const categories = [...new Set(salesData.map(item => item.category).filter(c => c))];
    const categoryQuotaLineaSelect = document.getElementById('category-quota-linea');
    
    if (categoryQuotaLineaSelect) {
        categoryQuotaLineaSelect.innerHTML = '<option value="">Seleccionar línea/categoría</option>';
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categoryQuotaLineaSelect.appendChild(option);
        });
    }
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
            showUserMessage('success', `Empresa cambiada a: ${company}`);
            loadDataFromServer();
        } else {
            showUserMessage('error', 'Error al cambiar de empresa: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al cambiar empresa:', error);
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
            
            // Mostrar contenido correspondiente
            tabPanes.forEach(pane => pane.classList.remove('active'));
            const targetPane = document.getElementById(`${tabId}-tab`);
            if (targetPane) {
                targetPane.classList.add('active');
            }
            
            // Si es la pestaña de rendimiento, actualizar gráficas
            if (tabId === 'performance') {
                setTimeout(updateCharts, 100);
            }
        });
    });
}

// Configurar event listeners
function setupEventListeners() {
    // Filtros principales
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
    
    if (prevPage) prevPage.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updateDashboard();
        }
    });
    
    if (nextPage) nextPage.addEventListener('click', function() {
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateDashboard();
        }
    });
    
    // Botones de empresa
    document.querySelectorAll('.company-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            changeCompany(this.dataset.company);
        });
    });
    
    // Botones de cuotas
    const loadQuotaBtn = document.getElementById('load-quota');
    const saveQuotaBtn = document.getElementById('save-quota');
    const deleteQuotaBtn = document.getElementById('delete-quota');
    
    if (loadQuotaBtn) loadQuotaBtn.addEventListener('click', loadQuota);
    if (saveQuotaBtn) saveQuotaBtn.addEventListener('click', saveQuota);
    if (deleteQuotaBtn) deleteQuotaBtn.addEventListener('click', deleteQuota);
    
    // Botones de cuotas por categoría
    const loadCategoryQuotaBtn = document.getElementById('load-category-quota');
    const saveCategoryQuotaBtn = document.getElementById('save-category-quota');
    const deleteCategoryQuotaBtn = document.getElementById('delete-category-quota');
    
    if (loadCategoryQuotaBtn) loadCategoryQuotaBtn.addEventListener('click', loadCategoryQuota);
    if (saveCategoryQuotaBtn) saveCategoryQuotaBtn.addEventListener('click', saveCategoryQuota);
    if (deleteCategoryQuotaBtn) deleteCategoryQuotaBtn.addEventListener('click', deleteCategoryQuota);
}

// Cargar cuota específica
function loadQuota() {
    const vendor = document.getElementById('quota-vendor').value;
    const year = document.getElementById('quota-year').value;
    const month = document.getElementById('quota-month').value;
    
    if (!vendor) {
        showUserMessage('warning', 'Seleccione un vendedor');
        return;
    }
    
    const quota = quotasData.find(q => 
        q.CODIGO_VENDEDOR === vendor && 
        parseInt(q.ANNO) === parseInt(year) && 
        parseInt(q.MES) === parseInt(month)
    );
    
    const amountInput = document.getElementById('quota-amount');
    const boxesInput = document.getElementById('quota-boxes');
    const kilosInput = document.getElementById('quota-kilos');
    
    if (quota) {
        if (amountInput) amountInput.value = quota.CUOTA_DIVISA || '';
        if (boxesInput) boxesInput.value = quota.CUOTA_CAJAS || '';
        if (kilosInput) kilosInput.value = quota.CUOTA_KILOS || '';
        showUserMessage('success', 'Cuota cargada correctamente');
    } else {
        if (amountInput) amountInput.value = '';
        if (boxesInput) boxesInput.value = '';
        if (kilosInput) kilosInput.value = '';
        showUserMessage('info', 'No se encontró cuota para este vendedor y período');
    }
}

// Guardar cuota
function saveQuota() {
    const vendor = document.getElementById('quota-vendor').value;
    const year = document.getElementById('quota-year').value;
    const month = document.getElementById('quota-month').value;
    const amount = document.getElementById('quota-amount').value;
    
    if (!vendor || !year || !month) {
        showUserMessage('warning', 'Complete todos los campos obligatorios');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_quota');
    formData.append('vendor', vendor);
    formData.append('year', year);
    formData.append('month', month);
    formData.append('amount', amount);
    formData.append('boxes', document.getElementById('quota-boxes').value || 0);
    formData.append('kilos', document.getElementById('quota-kilos').value || 0);
    
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
            showUserMessage('error', 'Error al guardar la cuota: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al guardar cuota:', error);
        showUserMessage('error', 'Error al guardar la cuota');
    });
}

// Eliminar cuota
function deleteQuota() {
    const vendor = document.getElementById('quota-vendor').value;
    const year = document.getElementById('quota-year').value;
    const month = document.getElementById('quota-month').value;
    
    if (!vendor || !year || !month) {
        showUserMessage('warning', 'Seleccione un vendedor, año y mes');
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
            showUserMessage('success', data.message);
            loadDataFromServer();
        } else {
            showUserMessage('error', 'Error al eliminar la cuota: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al eliminar cuota:', error);
        showUserMessage('error', 'Error al eliminar la cuota');
    });
}

// Cargar cuota por categoría
function loadCategoryQuota() {
    const vendor = document.getElementById('category-quota-vendor').value;
    const year = document.getElementById('category-quota-year').value;
    const month = document.getElementById('category-quota-month').value;
    const linea = document.getElementById('category-quota-linea').value;
    
    if (!vendor || !linea) {
        showUserMessage('warning', 'Seleccione un vendedor y una línea/categoría');
        return;
    }
    
    const quota = categoryQuotasData.find(q => 
        (q.VENDEDOR === vendor || q.CODIGO_VENDEDOR === vendor) && 
        (q.LINEA === linea || q.CLASE_PRODUCTO === linea) && 
        parseInt(q.ANNO) === parseInt(year) && 
        parseInt(q.MES) === parseInt(month)
    );
    
    const amountInput = document.getElementById('category-quota-amount');
    const activacionInput = document.getElementById('category-quota-activacion');
    
    if (quota) {
        if (amountInput) amountInput.value = quota.CUOTA_LINEA || quota.CUOTA_DIVISA || '';
        if (activacionInput) activacionInput.value = quota.CUOTA_ACTIVACION || '';
        showUserMessage('success', 'Cuota por categoría cargada correctamente');
    } else {
        if (amountInput) amountInput.value = '';
        if (activacionInput) activacionInput.value = '';
        showUserMessage('info', 'No se encontró cuota para esta categoría y período');
    }
}

// Guardar cuota por categoría
function saveCategoryQuota() {
    const vendor = document.getElementById('category-quota-vendor').value;
    const year = document.getElementById('category-quota-year').value;
    const month = document.getElementById('category-quota-month').value;
    const linea = document.getElementById('category-quota-linea').value;
    const amount = document.getElementById('category-quota-amount').value;
    const activacion = document.getElementById('category-quota-activacion').value;
    
    if (!vendor || !year || !month || !linea) {
        showUserMessage('warning', 'Complete todos los campos obligatorios');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_class_quota');
    formData.append('vendor', vendor);
    formData.append('year', year);
    formData.append('month', month);
    formData.append('product_class', linea);
    formData.append('amount', amount);
    formData.append('activacion', activacion);
    
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
            showUserMessage('error', 'Error al guardar la cuota por categoría: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al guardar cuota por categoría:', error);
        showUserMessage('error', 'Error al guardar la cuota por categoría');
    });
}

// Eliminar cuota por categoría
function deleteCategoryQuota() {
    const vendor = document.getElementById('category-quota-vendor').value;
    const year = document.getElementById('category-quota-year').value;
    const month = document.getElementById('category-quota-month').value;
    const linea = document.getElementById('category-quota-linea').value;
    
    if (!vendor || !year || !month || !linea) {
        showUserMessage('warning', 'Complete todos los campos obligatorios');
        return;
    }
    
    if (!confirm('¿Está seguro de que desea eliminar esta cuota por categoría?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_class_quota');
    formData.append('vendor', vendor);
    formData.append('year', year);
    formData.append('month', month);
    formData.append('product_class', linea);
    
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
            showUserMessage('error', 'Error al eliminar la cuota por categoría: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al eliminar cuota por categoría:', error);
        showUserMessage('error', 'Error al eliminar la cuota por categoría');
    });
}

// Cargar datos desde el servidor
function loadDataFromServer() {
    showLoading();
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
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success === false) {
            // Manejar caso sin datos
            if (data.message && data.message.includes('No hay datos')) {
                handleNoDataScenario();
                return;
            }
            throw new Error(data.message || 'Error en los datos recibidos');
        }
        
        updateConnectionStatus(true);
        salesData = data.data.sales || [];
        quotasData = data.data.quotas || [];
        categoryQuotasData = data.data.class_quotas || [];
        lastBillingDate = data.data.last_billing_date || null;
        filteredData = [...salesData];
        
        console.log("Datos cargados correctamente:", {
            ventas: salesData.length,
            cuotas: quotasData.length,
            cuotasClase: categoryQuotasData.length,
            ultimaFecha: lastBillingDate
        });
        
        updateDashboard();
        loadQuotasData();
        loadCategoryQuotasData();
        updateCharts();
        updateLastBillingDate();
        updateDebugInfo(`Datos cargados: ${salesData.length} registros`);
        
        if (salesData.length === 0) {
            showUserMessage('info', 'No se encontraron datos para el período seleccionado', 3000);
        }
    })
    .catch(error => {
        console.error('Error al cargar datos:', error);
        updateConnectionStatus(false);
        updateDebugInfo("Error: " + error.message);
        handleLoadError(error);
    });
}

// Manejar escenario sin datos
function handleNoDataScenario() {
    salesData = [];
    quotasData = [];
    categoryQuotasData = [];
    filteredData = [];
    lastBillingDate = "No hay datos";
    
    updateConnectionStatus(true);
    updateDashboard();
    updateCharts();
    updateLastBillingDate();
    updateDebugInfo("No hay datos para el período seleccionado");
    showUserMessage('info', 'No se encontraron datos para el período seleccionado', 3000);
}

// Manejar errores de carga
function handleLoadError(error) {
    const errorMsg = error.message.includes('Failed to fetch') 
        ? 'Error de conexión. Verifique el servidor y la red.' 
        : 'Error: ' + error.message;
        
    showUserMessage('error', errorMsg, 0); // 0 = no auto-remover
}

// Actualizar la visualización de la última fecha de facturación
function updateLastBillingDate() {
    const lastBillingElement = document.getElementById('last-billing-date');
    
    if (!lastBillingElement) return;
    
    if (lastBillingDate && lastBillingDate !== "No disponible" && lastBillingDate !== "No hay datos") {
        try {
            const dateObj = new Date(lastBillingDate);
            if (!isNaN(dateObj)) {
                const day = String(dateObj.getDate()).padStart(2, '0');
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const year = dateObj.getFullYear();
                const formattedDate = `${day}-${month}-${year}`;
                lastBillingElement.textContent = formattedDate;
                lastBillingElement.className = 'last-billing-value has-data';
            } else {
                lastBillingElement.textContent = lastBillingDate;
                lastBillingElement.className = 'last-billing-value';
            }
        } catch (e) {
            lastBillingElement.textContent = lastBillingDate;
            lastBillingElement.className = 'last-billing-value';
        }
    } else {
        lastBillingElement.textContent = lastBillingDate || 'No disponible';
        lastBillingElement.className = 'last-billing-value no-data';
    }
}

// Inicializar el dashboard
function initDashboard() {
    console.log("Inicializando dashboard...");
    
    try {
        setupTabs();
        setupEventListeners();
        updateTime();
        setInterval(updateTime, 60000);
        loadDataFromServer();
        
        // Actualizar gráficas después de un breve delay
        setTimeout(() => {
            updateCharts();
        }, 1000);
        
        console.log("Dashboard inicializado correctamente");
    } catch (error) {
        console.error("Error inicializando dashboard:", error);
        updateDebugInfo("Error inicializando: " + error.message);
        showUserMessage('error', 'Error inicializando el dashboard: ' + error.message);
    }
}

// Iniciar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}