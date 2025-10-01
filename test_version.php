<?php
// test_version.php - Archivo para probar el sistema de versionado
require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Prueba Sistema de Versionado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Prueba del Sistema de Versionado</h1>";

// Probar diferentes escenarios
echo "<div class='info'>
    <h2>Información Actual del Sistema</h2>
    <p><strong>Versión Constante:</strong> " . CURRENT_VERSION . "</p>
    <p><strong>Base Version:</strong> " . APP_VERSION . "</p>
    <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
</div>";

// Probar el VersionManager directamente
$testManager = new VersionManager(APP_VERSION);
$versionInfo = $testManager->getVersionInfo();

echo "<div class='success'>
    <h2>Información desde VersionManager</h2>
    <pre>" . json_encode($versionInfo, JSON_PRETTY_PRINT) . "</pre>
</div>";

// Simular cambios forzando nuevo build
if (isset($_GET['force_new_build'])) {
    // Eliminar archivo de build para forzar nueva versión
    if (file_exists($testManager->getBuildFile())) {
        unlink($testManager->getBuildFile());
    }
    
    $newVersion = $testManager->getBuildVersion();
    echo "<div class='warning'>
        <h2>✅ Nuevo Build Forzado</h2>
        <p><strong>Nueva Versión:</strong> $newVersion</p>
        <p>El archivo de build fue regenerado.</p>
    </div>";
}

// Mostrar contenido del archivo de build si existe
$buildFile = __DIR__ . '/.buildinfo';
if (file_exists($buildFile)) {
    $buildContent = file_get_contents($buildFile);
    echo "<div class='info'>
        <h2>📄 Contenido del Archivo .buildinfo</h2>
        <pre>" . $buildContent . "</pre>
    </div>";
} else {
    echo "<div class='warning'>
        <h2>⚠️ Archivo .buildinfo no existe</h2>
        <p>Se creará automáticamente al cargar esta página.</p>
    </div>";
}

// Probar la función sendJsonResponse
echo "<div class='info'>
    <h2>🧪 Probar Respuesta JSON</h2>
    <p><a href='test_json.php' target='_blank'>Probar respuesta JSON con versión</a></p>
</div>";

echo "<div class='info'>
    <h2>🔧 Acciones de Prueba</h2>
    <p><a href='test_version.php?force_new_build=1'>Forzar Nuevo Build</a></p>
    <p><a href='test_version.php'>Recargar Página</a></p>
    <p><a href='config.php?debug_version=1'>Debug en Config</a></p>
</div>";

echo "</body></html>";
?>