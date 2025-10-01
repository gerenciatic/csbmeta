 
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
        let lastBillingDate = null; // Nueva variable para almacenar la última fecha de facturación

        // Función para formatear números con separadores de miles
        function formatNumber(number, decimals = 2, isCurrency = false) {
            if (isNaN(number) || number === null) return isCurrency ? '$0.00' : '0.00';
            
            const fixedNum = Number(number).toFixed(decimals);
            const parts = fixedNum.toString().split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            return isCurrency ? '$' + parts.join('.') : parts.join('.');
        }

        // Actualizar la hora en tiempo real
        function updateTime() {
            const now = new Date();
            const timeStr = now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
            document.getElementById('current-time').textContent = timeStr;
        }
        
        setInterval(updateTime, 60000);
        updateTime();
        
       // Calcular métricas basadas en los datos (VERSIÓN CORREGIDA)
function calculateMetrics(data) {
    if (data.length === 0) {
        return {
            totalSales: 0,
            avgKg: 0,
            achievement: 0,
            transactions: 0,
            salesTarget: 0,
            kgTarget: 0
        };
    }
    
    const totalSales = data.reduce((sum, item) => sum + Math.abs(parseFloat(item.total) || 0), 0);
    const totalKg = data.reduce((sum, item) => sum + Math.abs(parseFloat(item.kg) || 0), 0);
    const transactions = data.length;
    
    const vendor = document.getElementById('vendor-select').value;
    let salesTarget = 0;
    
    if (vendor !== 'all') {
        const vendorQuota = quotasData.find(q => 
            q.CODIGO_VENDEDOR === vendor && 
            parseInt(q.ANNO) === currentYear && 
            parseInt(q.MES) === currentMonth
        );
        if (vendorQuota) {
            salesTarget = Math.abs(parseFloat(vendorQuota.CUOTA_DIVISA) || 0);
        }
    } else {
        const allQuotas = quotasData.filter(q => 
            parseInt(q.ANNO) === currentYear && 
            parseInt(q.MES) === currentMonth
        );
        salesTarget = allQuotas.reduce((sum, q) => sum + Math.abs(parseFloat(q.CUOTA_DIVISA) || 0), 0);
    }
    
    // --- CORRECCIÓN APLICADA AQUÍ ---
    // Calculamos el cumplimiento de ventas y lo usamos directamente.
    const salesAchievement = salesTarget > 0 ? (totalSales / salesTarget) * 100 : 0;
    
    return {
        totalSales,
        avgKg: transactions > 0 ? totalKg / transactions : 0,
        achievement: salesAchievement, // Usamos el valor correcto
        transactions,
        salesTarget,
        kgTarget: 0 // kgTarget se puede mantener o eliminar si no se usa
    };
}
        
        // Crear o actualizar la gráfica de media torta
        function updateHalfDoughnutChart(percentage, totalSales, salesTarget) {
            const ctx = document.getElementById('halfDoughnutChart').getContext('2d');
            
            // Si ya existe un gráfico, destruirlo primero
            if (halfDoughnutChart) {
                halfDoughnutChart.destroy();
            }
            
            // Calcular valores para el gráfico
            const achieved = percentage;
            const remaining = Math.max(0, 100 - percentage);
            
            halfDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Alcanzado', 'Restante'],
                    datasets: [{
                        data: [achieved, remaining],
                        backgroundColor: [
                            achieved >= 100 ? '#2ecc71' : '#3498db', // Verde si se alcanzó o superó la meta, azul si no
                            '#e3e6f0'  // Color para el resto
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
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
            document.getElementById('achievement-percentage').textContent = formatNumber(percentage, 1) + '%';
            document.getElementById('budget-compliance').textContent = formatNumber(percentage, 1) + '%';
            
            // Actualizar información adicional debajo del gráfico
            document.getElementById('accumulated-sales').textContent = formatNumber(totalSales, 2, true);
            document.getElementById('sales-target').textContent = formatNumber(salesTarget, 2, true);
            document.getElementById('remaining-amount').textContent = formatNumber(Math.max(0, salesTarget - totalSales), 2, true);
        }
        
   
        
        // Actualizar estado de conexión
        function updateConnectionStatus(connected) {
            const statusElement = document.getElementById('connection-status');
            if (connected) {
                statusElement.textContent = 'Conectado a la base de datos';
                statusElement.className = 'connection-status status-connected';
            } else {
                statusElement.textContent = 'Desconectado de la base de datos';
                statusElement.className = 'connection-status status-disconnected';
            }
        }
        
        // Actualizar información de depuración
        function updateDebugInfo(message) {
            const debugElement = document.getElementById('debug-info');
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            debugElement.textContent = `[${timeStr}] ${message}`;
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
            if (categoryQuotasData.length === 0) {
                categoryQuotasBody.innerHTML = '<tr><td colspan="7">No hay cuotas por categoría definidas</td></tr>';
                return;
            }
            
            categoryQuotasBody.innerHTML = '';
            categoryQuotasData.forEach(quota => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${quota.VENDEDOR}</td>
                    <td>${quota.LINEA}</td>
                    <td>${quota.ANNO}</td>
                    <td>${quota.MES}</td>
                    <td>${formatNumber(parseFloat(quota.CUOTA_LINEA || 0), 2, true)}</td>
                    <td>${formatNumber(parseFloat(quota.CUOTA_ACTIVACION || 0), 2, true)}</td>
                    <td>${quota.FEC_REG ? new Date(quota.FEC_REG).toLocaleDateString() : 'N/A'}</td>
                `;
                categoryQuotasBody.appendChild(row);
            });
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
                
                // Asegurar valores positivos
                grouped[item.vendor].totalSales += Math.abs(parseFloat(item.total) || 0);
                grouped[item.vendor].totalKg += Math.abs(parseFloat(item.kg) || 0);
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
                    parseInt(q.ANNO) === currentYear && 
                    parseInt(q.MES) === currentMonth
                );
                
                const salesTarget = vendorQuota ? Math.abs(parseFloat(vendorQuota.CUOTA_DIVISA) || 0) : 0;
                const achievement = salesTarget > 0 ? (item.totalSales / salesTarget) * 100 : 0;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.vendor}</td>
                    <td>${formatNumber(item.totalSales, 2, true)}</td>
                    <td>${formatNumber(salesTarget, 2, true)}</td>
                    <td>${formatNumber(achievement, 1)}%</td>
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
                row.innerHTML = '<td colspan="9" style="text-align: center;">No hay datos para mostrar</td>';
                tableBody.appendChild(row);
            } else {
                paginatedData.forEach(item => {
                    // Mostrar valores absolutos para evitar números negativos
                    const total = Math.abs(parseFloat(item.total) || 0);
                    const kg = Math.abs(parseFloat(item.kg) || 0);
                    const unitPrice = Math.abs(parseFloat(item.unitPrice) || 0);
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.doc}</td>
                        <td>${item.date}</td>
                        <td>${item.vendor}</td>
                        <td>${item.product}</td>
                        <td>${item.category || 'N/A'}</td>
                        <td>${formatNumber(unitPrice, 4, true)}</td>
                        <td>${item.quantity}</td>
                        <td>${formatNumber(kg, 2)}</td>
                        <td>${formatNumber(total, 4, true)}</td>
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
            const metrics = calculateMetrics(filteredData);
            updateHalfDoughnutChart(metrics.achievement, metrics.totalSales, metrics.salesTarget);
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
        
      
        

        // Gráfica de distribución por Vendedor (MODIFICADA PARA % DE CUMPLIMIENTO DE META)
function updateVendorDistributionChart() {
    const ctx = document.getElementById('vendor-distribution-chart').getContext('2d');

    // Usaremos los datos filtrados para que el gráfico reaccione a los filtros
    const vendorSalesData = groupDataByVendor(filteredData);
    
    if (vendorDistributionChart) {
        vendorDistributionChart.destroy();
    }

    if (vendorSalesData.length === 0) {
        return; // No hacer nada si no hay datos
    }

    // 1. Para cada vendedor, encontrar su cuota y calcular su % de cumplimiento
    const vendorAchievementData = vendorSalesData.map(vendor => {
        // Encontrar la cuota correspondiente en los datos globales de cuotas
        const quotaInfo = quotasData.find(q => 
            q.CODIGO_VENDEDOR === vendor.vendor &&
            parseInt(q.ANNO) === currentYear &&
            parseInt(q.MES) === currentMonth
        );

        const salesTarget = quotaInfo ? parseFloat(quotaInfo.CUOTA_DIVISA) || 0 : 0;
        
        // Calcular el porcentaje de cumplimiento
        const achievement = salesTarget > 0 ? (vendor.totalSales / salesTarget) * 100 : 0;

        return {
            vendor: vendor.vendor,
            achievement: achievement
        };
    });

    // 2. Ordenar los vendedores por su % de cumplimiento (de mayor a menor)
    const sortedVendors = vendorAchievementData.sort((a, b) => b.achievement - a.achievement);
    
    vendorDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sortedVendors.map(v => v.vendor),
            datasets: [{
                label: '% Cumplimiento de Meta',
                data: sortedVendors.map(v => v.achievement), // Usar el % de cumplimiento
                backgroundColor: '#3498db',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                // 3. Ajustar el tooltip para mostrar el % de cumplimiento
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(1) + '%';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    // 4. Ajustar el eje Y para que tenga sentido para porcentajes (ej. hasta 110%)
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
            const ctx = document.getElementById('monthly-sales-chart').getContext('2d');
            
            if (monthlySalesChart) {
                monthlySalesChart.destroy();
            }
            
            // Obtener datos mensuales (en una implementación real, esto vendría del servidor)
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const monthlySales = Array(12).fill(0);
            
            // Procesar datos para obtener ventas por mes
            salesData.forEach(item => {
                try {
                    const saleDate = new Date(item.date);
                    const monthIndex = saleDate.getMonth();
                    monthlySales[monthIndex] += Math.abs(parseFloat(item.total) || 0);
                } catch (e) {
                    console.error('Error procesando fecha:', e);
                }
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
                productSales[item.product] += Math.abs(parseFloat(item.total) || 0);
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
            document.getElementById('sales-data-body').innerHTML = '<tr><td colspan="9" class="loading">Cargando datos...</td></tr>';
        }
        
        // Actualizar todo el dashboard
        function updateDashboard() {
            const metrics = calculateMetrics(filteredData);
            
            // Actualizar métricas
            document.getElementById('total-sales').textContent = formatNumber(metrics.totalSales, 2, true);
            document.getElementById('avg-kg').textContent = formatNumber(metrics.avgKg, 2);
            document.getElementById('transactions').textContent = formatNumber(metrics.transactions, 0);
            
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
        
        // Actualizar selectores
        function updateSelectors() {
            const vendors = [...new Set(salesData.map(item => item.vendor))];
            const categories = [...new Set(salesData.map(item => item.category).filter(c => c))];
            const vendorSelect = document.getElementById('vendor-select');
            const quotaVendorSelect = document.getElementById('quota-vendor');
            const categoryQuotaVendorSelect = document.getElementById('category-quota-vendor');
            
            // Guardar selección actual
            const currentSelection = vendorSelect.value;
            
            // Limpiar y agregar opciones
            vendorSelect.innerHTML = '<option value="all">Todos los vendedores</option>';
            quotaVendorSelect.innerHTML = '<option value="">Seleccionar vendedor</option>';
            categoryQuotaVendorSelect.innerHTML = '<option value="">Seleccionar vendedor</option>';
            
            vendors.forEach(vendor => {
                const option = document.createElement('option');
                option.value = vendor;
                option.textContent = vendor;
                vendorSelect.appendChild(option);
                
                const quotaOption = option.cloneNode(true);
                quotaVendorSelect.appendChild(quotaOption);
                
                const categoryQuotaOption = option.cloneNode(true);
                categoryQuotaVendorSelect.appendChild(categoryQuotaOption);
            });
            
            // Restaurar selección si existe
            if (vendors.includes(currentSelection)) {
                vendorSelect.value = currentSelection;
            }
            
            // Actualizar años
            const yearSelect = document.getElementById('year-select');
            const quotaYearSelect = document.getElementById('quota-year');
            const categoryQuotaYearSelect = document.getElementById('category-quota-year');
            const currentYear = new Date().getFullYear();
            
            yearSelect.innerHTML = '';
            quotaYearSelect.innerHTML = '';
            categoryQuotaYearSelect.innerHTML = '';
            
            for (let year = currentYear - 2; year <= currentYear + 1; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                option.selected = year === currentYear;
                yearSelect.appendChild(option);
                
                const quotaOption = option.cloneNode(true);
                quotaYearSelect.appendChild(quotaOption);
                
                const categoryQuotaOption = option.cloneNode(true);
                categoryQuotaYearSelect.appendChild(categoryQuotaOption);
            }
            
            // Actualizar meses
            const monthSelect = document.getElementById('month-select');
            const quotaMonthSelect = document.getElementById('quota-month');
            const categoryQuotaMonthSelect = document.getElementById('category-quota-month');
            const currentMonth = new Date().getMonth() + 1;
            const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            monthSelect.innerHTML = '';
            quotaMonthSelect.innerHTML = '';
            categoryQuotaMonthSelect.innerHTML = '';
            
            for (let month = 1; month <= 12; month++) {
                const option = document.createElement('option');
                option.value = month;
                option.textContent = monthNames[month - 1];
                option.selected = month === currentMonth;
                monthSelect.appendChild(option);
                
                const quotaOption = option.cloneNode(true);
                quotaMonthSelect.appendChild(quotaOption);
                
                const categoryQuotaOption = option.cloneNode(true);
                categoryQuotaMonthSelect.appendChild(categoryQuotaOption);
            }
            
            // Actualizar líneas/categorías
            const categoryQuotaLineaSelect = document.getElementById('category-quota-linea');
            categoryQuotaLineaSelect.innerHTML = '<option value="">Seleccionar línea/categoría</option>';
            
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categoryQuotaLineaSelect.appendChild(option);
            });
            
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
            
            // Actualizar botones de categorías
            const categorySelector = document.getElementById('category-selector');
            categorySelector.innerHTML = '';
            
            // Botón para todas las categorías
            const allCategoryBtn = document.createElement('button');
            allCategoryBtn.className = 'category-btn active';
            allCategoryBtn.textContent = 'Todas las Categorías';
            allCategoryBtn.dataset.category = 'all';
            allCategoryBtn.addEventListener('click', categoryButtonHandler);
            categorySelector.appendChild(allCategoryBtn);
            
            // Botones para cada categoría
            categories.forEach(category => {
                const btn = document.createElement('button');
                btn.className = 'category-btn';
                btn.textContent = category;
                btn.dataset.category = category;
                btn.addEventListener('click', categoryButtonHandler);
                categorySelector.appendChild(btn);
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
        
        // Manejador para botones de categorías
        function categoryButtonHandler() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            currentCategory = this.dataset.category;
            document.getElementById('selected-category').textContent = this.textContent;
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
                    updateDebugInfo(`Empresa cambiada a: ${company}`);
                    loadDataFromServer();
                } else {
                    alert('Error al cambiar de empresa: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al cambiar empresa:', error);
                updateDebugInfo("Error al cambiar empresa: " + error.message);
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
            
            // Configurar botones de cuotas por categoría
            document.getElementById('load-category-quota').addEventListener('click', loadCategoryQuota);
            document.getElementById('save-category-quota').addEventListener('click', saveCategoryQuota);
            document.getElementById('delete-category-quota').addEventListener('click', deleteCategoryQuota);
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
                parseInt(q.ANNO) === parseInt(year) && 
                parseInt(q.MES) === parseInt(month)
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
        
        // Cargar cuota por categoría
        function loadCategoryQuota() {
            const vendor = document.getElementById('category-quota-vendor').value;
            const year = document.getElementById('category-quota-year').value;
            const month = document.getElementById('category-quota-month').value;
            const linea = document.getElementById('category-quota-linea').value;
            
            if (!vendor || !linea) {
                alert('Seleccione un vendedor y una línea/categoría');
                return;
            }
            
            const quota = categoryQuotasData.find(q => 
                q.VENDEDOR === vendor && 
                q.LINEA === linea && 
                parseInt(q.ANNO) === parseInt(year) && 
                parseInt(q.MES) === parseInt(month)
            );
            
            if (quota) {
                document.getElementById('category-quota-amount').value = quota.CUOTA_LINEA;
                document.getElementById('category-quota-activacion').value = quota.CUOTA_ACTIVACION;
            } else {
                document.getElementById('category-quota-amount').value = '';
                document.getElementById('category-quota-activacion').value = '';
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
                alert('Complete todos los campos obligatorios');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_category_quota');
            formData.append('vendor', vendor);
            formData.append('year', year);
            formData.append('month', month);
            formData.append('linea', linea);
            formData.append('amount', amount);
            formData.append('activacion', activacion);
            
            fetch('sales_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cuota por categoría guardada correctamente');
                    loadDataFromServer();
                } else {
                    alert('Error al guardar la cuota por categoría: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al guardar cuota por categoría:', error);
                alert('Error al guardar la cuota por categoría');
            });
        }


              

        // Cargar datos desde el servidor
        function loadDataFromServer() {
            showLoading();
            updateDebugInfo("Solicitando datos al servidor...");
            
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
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success === false) {
                    throw new Error(data.message || 'Error en los datos recibidos');
                }
                
                updateConnectionStatus(true);
                salesData = data.sales || [];
                quotasData = data.quotas || [];
                categoryQuotasData = data.category_quotas || [];
                lastBillingDate = data.last_billing_date || null; // Almacenar la última fecha de facturación
                filteredData = [...salesData];
                updateDashboard();
                loadQuotasData();
                loadCategoryQuotasData();
                updateCharts();
                updateLastBillingDate(); // Actualizar la visualización de la última fecha
                updateDebugInfo("Datos cargados correctamente. " + salesData.length + " registros de ventas, " + quotasData.length + " registros de cuotas.");
            })
            .catch(error => {
                console.error('Error al cargar datos:', error);
                updateConnectionStatus(false);
                updateDebugInfo("Error: " + error.message);
                alert('Error al cargar datos. Verifica la conexión y la configuración del servidor.');
            });
        }

    // Actualizar la visualización de la última fecha de facturación
function updateLastBillingDate() {
    const lastBillingElement = document.getElementById('last-billing-date');
    
    if (lastBillingDate) {
        // Crear objeto Date y ajustar por zona horaria
        const dateObj = new Date(lastBillingDate + 'T00:00:00');
        
        // Formatear manualmente para evitar problemas de zona horaria
        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = dateObj.getFullYear();
        
        const formattedDate = `${day}-${month}-${year}`;
        
        lastBillingElement.textContent = formattedDate;
        updateDebugInfo("Última factura: " + formattedDate);
    } else {
        lastBillingElement.textContent = 'No disponible';
        updateDebugInfo("Última factura: No disponible");
    }
}

        
        
        // Eliminar cuota por categoría
        function deleteCategoryQuota() {
            const vendor = document.getElementById('category-quota-vendor').value;
            const year = document.getElementById('category-quota-year').value;
            const month = document.getElementById('category-quota-month').value;
            const linea = document.getElementById('category-quota-linea').value;
            
            if (!vendor || !year || !month || !linea) {
                alert('Complete todos los campos obligatorios');
                return;
            }
            
            if (!confirm('¿Está seguro de que desea eliminar esta cuota por categoría?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_category_quota');
            formData.append('vendor', vendor);
            formData.append('year', year);
            formData.append('month', month);
            formData.append('linea', linea);
            
            fetch('sales_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cuota por categoría eliminada correctamente');
                    loadDataFromServer();
                } else {
                    alert('Error al eliminar la cuota por categoría: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al eliminar cuota por categoría:', error);
                alert('Error al eliminar la cuota por categoría');
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
  