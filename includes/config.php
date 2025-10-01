<?php  
   

    // DEFINICION DE RUTA PARA OCULTAR EL MENU  
            
    // SOLUCIÓN DEFINITIVA PARA OCULTAR EN CUALQUIER RUTA QUE NO SEA LA PRINCIPAL
$rutaActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);  // Obtiene solo la ruta sin parámetros
$rutasPermitidas = ['/', '/index.php', '/cuotaventas/', '/cuotaventas/index.php'];  // Añade todas las rutas principales aquí

// Verificación a prueba de errores
$esRutaCuotaVentas = in_array($rutaActual, $rutasPermitidas, true);

           
    // FIN DE DEFINICION DE RUTA


    // Definir la ruta principal
    $rutaPrincipal = '/cuotaventas';

    // Verificar si estamos en la ruta principal (con o sin barra final)
    $esRutaPrincipal = ( $rutaPrincipal .'/');
    
    // echo  $esRutaPrincipal ."<br>";
    // echo  $esRutaCuotaVentas ."<br>";
    // echo  $rutaActual ."<br>";

?>