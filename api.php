
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API - Gestión de Ventas</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            text-align: center;
            border-radius: 0 0 10px 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .endpoint {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid var(--secondary);
        }
        
        .method {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 1rem;
            min-width: 80px;
            text-align: center;
        }
        
        .get { background-color: #e1f5fe; color: #0288d1; }
        .post { background-color: #e8f5e9; color: #388e3c; }
        .delete { background-color: #ffebee; color: #d32f2f; }
        
        .url {
            font-family: monospace;
            font-size: 1.1rem;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            overflow-x: auto;
            font-family: 'Fira Code', monospace;
        }
        
        .tab-container {
            margin-top: 1rem;
        }
        
        .tabs {
            display: flex;
            list-style: none;
            border-bottom: 2px solid #ddd;
            margin-bottom: 1rem;
        }
        
        .tabs li {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tabs li.active {
            border-bottom: 3px solid var(--secondary);
            color: var(--secondary);
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .response-field {
            background: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        button {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: var(--primary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        footer {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem 0;
            color: #777;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .card {
                padding: 1rem;
            }
            
            .endpoint {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .method {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>API de Gestión de Ventas</h1>
            <p class="subtitle">Documentación y interfaz para la aplicación Android</p>
        </div>
    </header>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Endpoints de la API</h2>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="url">/api/sales</span>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="url">/api/quotas</span>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="url">/api/category-quotas</span>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="url">/api/quotas</span>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="url">/api/category-quotas</span>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="url">/api/quotas/delete</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Probador de API</h2>
            </div>
            
            <div class="tab-container">
                <ul class="tabs">
                    <li class="active" data-tab="sales">Ventas</li>
                    <li data-tab="quotas">Cuotas</li>
                    <li data-tab="category-quotas">Cuotas por Categoría</li>
                </ul>
                
                <div class="tab-content active" id="sales">
                    <h3>Obtener datos de ventas</h3>
                    <div class="form-group">
                        <label for="year">Año:</label>
                        <input type="number" id="year" value="2023" min="2020" max="2030">
                    </div>
                    <div class="form-group">
                        <label for="month">Mes:</label>
                        <input type="number" id="month" value="10" min="1" max="12">
                    </div>
                    <button onclick="getSales()">Obtener Ventas</button>
                    
                    <div class="response-field">
                        <pre id="sales-response">La respuesta aparecerá aquí...</pre>
                    </div>
                </div>
                
                <div class="tab-content" id="quotas">
                    <h3>Gestión de Cuotas</h3>
                    <div class="form-group">
                        <label for="vendor-code">Código de Vendedor:</label>
                        <input type="text" id="vendor-code" value="VEND01">
                    </div>
                    <div class="form-group">
                        <label for="quota-year">Año:</label>
                        <input type="number" id="quota-year" value="2023" min="2020" max="2030">
                    </div>
                    <div class="form-group">
                        <label for="quota-month">Mes:</label>
                        <input type="number" id="quota-month" value="10" min="1" max="12">
                    </div>
                    <div class="form-group">
                        <label for="currency-quota">Cuota Divisa:</label>
                        <input type="number" id="currency-quota" value="1000.00" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="boxes-quota">Cuota Cajas:</label>
                        <input type="number" id="boxes-quota" value="100" step="1">
                    </div>
                    <div class="form-group">
                        <label for="kilos-quota">Cuota Kilos:</label>
                        <input type="number" id="kilos-quota" value="500.00" step="0.01">
                    </div>
                    
                    <button onclick="getQuotas()">Obtener Cuotas</button>
                    <button onclick="saveQuota()" style="background-color: var(--success)">Guardar Cuota</button>
                    <button onclick="deleteQuota()" style="background-color: var(--danger)">Eliminar Cuota</button>
                    
                    <div class="response-field">
                        <pre id="quotas-response">La respuesta aparecerá aquí...</pre>
                    </div>
                </div>
                
                <div class="tab-content" id="category-quotas">
                    <h3>Gestión de Cuotas por Categoría</h3>
                    <div class="form-group">
                        <label for="category-vendor">Código de Vendedor:</label>
                        <input type="text" id="category-vendor" value="VEND01">
                    </div>
                    <div class="form-group">
                        <label for="category-line">Línea/Categoría:</label>
                        <input type="text" id="category-line" value="CAT001">
                    </div>
                    <div class="form-group">
                        <label for="category-year">Año:</label>
                        <input type="number" id="category-year" value="2023" min="2020" max="2030">
                    </div>
                    <div class="form-group">
                        <label for="category-month">Mes:</label>
                        <input type="number" id="category-month" value="10" min="1" max="12">
                    </div>
                    <div class="form-group">
                        <label for="line-quota">Cuota Línea:</label>
                        <input type="number" id="line-quota" value="5000.00" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="activation-quota">Cuota Activación:</label>
                        <input type="number" id="activation-quota" value="1000.00" step="0.01">
                    </div>
                    
                    <button onclick="getCategoryQuotas()">Obtener Cuotas por Categoría</button>
                    <button onclick="saveCategoryQuota()" style="background-color: var(--success)">Guardar Cuota por Categoría</button>
                    
                    <div class="response-field">
                        <pre id="category-quotas-response">La respuesta aparecerá aquí...</pre>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ejemplo de Código Android (Kotlin)</h2>
            </div>
            
            <div class="code-block">
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
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Estructura de Respuesta JSON</h2>
            </div>
            
            <div class="code-block">
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
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>API para Aplicación Android de Gestión de Ventas</p>
        </div>
    </footer>

    <script>
        // Manejo de pestañas
        document.querySelectorAll('.tabs li').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remover clase active de todas las pestañas y contenidos
                document.querySelectorAll('.tabs li').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Agregar clase active a la pestaña clickeada
                tab.classList.add('active');
                
                // Mostrar el contenido correspondiente
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Funciones para interactuar con la API (simuladas)
        function getSales() {
            const year = document.getElementById('year').value;
            const month = document.getElementById('month').value;
            
            // Simular respuesta de la API
            const response = {
                success: true,
                sales: [
                    {
                        doc: "FAC 12345",
                        date: `2023-10-15`,
                        vendor: "VEND01",
                        product: "PROD001",
                        category: "CAT001",
                        unitPrice: "10.50",
                        quantity: "5",
                        kg: "25.00",
                        total: "52.50"
                    },
                    {
                        doc: "FAC 12346",
                        date: `2023-10-16`,
                        vendor: "VEND02",
                        product: "PROD002",
                        category: "CAT002",
                        unitPrice: "15.75",
                        quantity: "3",
                        kg: "18.50",
                        total: "47.25"
                    }
                ],
                vendors: ["VEND01", "VEND02"],
                categories: ["CAT001", "CAT002"]
            };
            
            document.getElementById('sales-response').textContent = JSON.stringify(response, null, 2);
        }
        
        function getQuotas() {
            // Simular respuesta de la API
            const response = {
                success: true,
                quotas: [
                    {
                        CODIGO_VENDEDOR: "VEND01",
                        ANNO: "2023",
                        MES: "10",
                        CUOTA_DIVISA: "1000.00",
                        CUOTA_CAJAS: "100",
                        CUOTA_KILOS: "500.00"
                    }
                ]
            };
            
            document.getElementById('quotas-response').textContent = JSON.stringify(response, null, 2);
        }
        
        function saveQuota() {
            // Simular respuesta de la API
            const response = {
                success: true,
                message: "Cuota guardada correctamente"
            };
            
            document.getElementById('quotas-response').textContent = JSON.stringify(response, null, 2);
        }
        
        function deleteQuota() {
            // Simular respuesta de la API
            const response = {
                success: true,
                message: "Cuota eliminada correctamente"
            };
            
            document.getElementById('quotas-response').textContent = JSON.stringify(response, null, 2);
        }
        
        function getCategoryQuotas() {
            // Simular respuesta de la API
            const response = {
                success: true,
                category_quotas: [
                    {
                        VENDEDOR: "VEND01",
                        LINEA: "CAT001",
                        ANNO: "2023",
                        MES: "10",
                        CUOTA_LINEA: "5000.00",
                        CUOTA_ACTIVACION: "1000.00"
                    }
                ]
            };
            
            document.getElementById('category-quotas-response').textContent = JSON.stringify(response, null, 2);
        }
        
        function saveCategoryQuota() {
            // Simular respuesta de la API
            const response = {
                success: true,
                message: "Cuota por categoría guardada correctamente"
            };
            
            document.getElementById('category-quotas-response').textContent = JSON.stringify(response, null, 2);
        }
    </script>
</body>
</html>