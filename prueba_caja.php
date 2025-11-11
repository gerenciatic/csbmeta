<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Resumen de Cajas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .filters { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .filter-item { margin-bottom: 10px; }
        label { display: inline-block; width: 100px; font-weight: bold; }
        select, input { padding: 5px; width: 200px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .results { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .fulfilled { background-color: #d4edda; }
        .warning { background-color: #fff3cd; }
        .not-fulfilled { background-color: #f8d7da; }
        .debug { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prueba Resumen de Cajas vs Metas</h1>
        
        <div class="filters">
            <div class="filter-item">
                <label for="empresa">Empresa:</label>
                <select id="empresa">
                    <option value="A">CSB</option>
                    <option value="B">MAXI</option>
                    <option value="C">MERIDA</option>
                </select>
            </div>
            
            <div class="filter-item">
                <label for="year">Año:</label>
                <select id="year"></select>
            </div>
            
            <div class="filter-item">
                <label for="month">Mes:</label>
                <select id="month"></select>
            </div>
            
            <div class="filter-item">
                <label for="vendor">Vendedor:</label>
                <select id="vendor">
                    <option value="all">Todos los vendedores</option>
                </select>
            </div>
            
            <button onclick="loadBoxesSummary()">Cargar Resumen de Cajas</button>
        </div>
        
        <div class="results">
            <h2>Resumen de Cajas vs Metas</h2>
            <div id="summary-table"></div>
        </div>
        
        <div class="debug">
            <h3>Información de Depuración</h3>
            <pre id="debug-info">Haga clic en "Cargar Resumen de Cajas" para comenzar...</pre>
        </div>
    </div>

    <script>
        // Inicializar selectores de año y mes
        function initializeSelectors() {
            const yearSelect = document.getElementById('year');
            const monthSelect = document.getElementById('month');
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1;
            
            // Llenar años (2 años atrás, año actual, 1 año adelante)
            for (let year = currentYear - 2; year <= currentYear + 1; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                option.selected = year === currentYear;
                yearSelect.appendChild(option);
            }
            
            // Llenar meses
            const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            for (let month = 1; month <= 12; month++) {
                const option = document.createElement('option');
                option.value = month;
                option.textContent = monthNames[month - 1];
                option.selected = month === currentMonth;
                monthSelect.appendChild(option);
            }
        }
        
        // Cargar resumen de cajas
        function loadBoxesSummary() {
            const empresa = document.getElementById('empresa').value;
            const year = document.getElementById('year').value;
            const month = document.getElementById('month').value;
            const vendor = document.getElementById('vendor').value;
            
            document.getElementById('debug-info').textContent = 'Cargando datos...';
            document.getElementById('summary-table').innerHTML = '<p>Cargando...</p>';
            
            const formData = new FormData();
            formData.append('empresa', empresa);
            formData.append('year', year);
            formData.append('month', month);
            formData.append('vendor', vendor);
            
            fetch('test_cajas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
    // En la función loadBoxesSummary, actualiza esta parte:
.then(data => {
    if (data.success) {
        displaySummary(data);
        // Mostrar el debug log en el área de depuración
        document.getElementById('debug-info').textContent = data.debug_log || 'No hay información de depuración';
    } else {
        document.getElementById('summary-table').innerHTML = '<p style="color: red;">Error: ' + data.message + '</p>';
        document.getElementById('debug-info').textContent = data.message || 'Error desconocido';
    }
})
            .catch(error => {
                document.getElementById('summary-table').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                document.getElementById('debug-info').textContent = 'Error de conexión: ' + error.message;
            });
        }
        
        // Mostrar resumen en tabla
        function displaySummary(data) {
            const summary = data.summary;
            let html = '';
            
            if (Object.keys(summary).length === 0) {
                html = '<p>No hay datos disponibles para los filtros seleccionados.</p>';
            } else {
                html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Meta Cajas</th>
                                <th>Cajas Real</th>
                                <th>Faltante</th>
                                <th>% Cumplimiento</th>
                                <th>Clases</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                Object.keys(summary).forEach(vendor => {
                    const item = summary[vendor];
                    const achievement = item.achievement_percentage;
                    let statusClass = 'not-fulfilled';
                    let statusText = 'No cumplido';
                    
                    if (achievement >= 100) {
                        statusClass = 'fulfilled';
                        statusText = '✅ Cumplido';
                    } else if (achievement >= 80) {
                        statusClass = 'warning';
                        statusText = '⚠️ Cerca';
                    }
                    
                    html += `
                        <tr class="${statusClass}">
                            <td>${vendor}</td>
                            <td>${item.quota_boxes.toFixed(2)}</td>
                            <td>${item.actual_boxes.toFixed(2)}</td>
                            <td>${item.remaining_boxes.toFixed(2)}</td>
                            <td>${achievement.toFixed(2)}%</td>
                            <td>${item.classes.join(', ')}</td>
                            <td>${statusText}</td>
                        </tr>
                    `;
                });
                
                // Total general
                html += `
                        <tr style="font-weight: bold; background-color: #e9ecef;">
                            <td>TOTAL</td>
                            <td>${data.total_quota_boxes.toFixed(2)}</td>
                            <td>${data.total_actual_boxes.toFixed(2)}</td>
                            <td>${data.total_remaining_boxes.toFixed(2)}</td>
                            <td>${data.total_achievement_percentage.toFixed(2)}%</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                    </table>
                `;
            }
            
            document.getElementById('summary-table').innerHTML = html;
        }
        
        // Inicializar cuando cargue la página
        document.addEventListener('DOMContentLoaded', initializeSelectors);
    </script>
</body>
</html>