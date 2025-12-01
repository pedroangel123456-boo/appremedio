<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- 1. Configuración de la Base de Datos (TUS DATOS) ---
$servidor = "localhost"; 
$puerto = 3306;
$usuario_db = "root";    
$contrasena_db = "";     
$nombre_db = "remediosnaturales"; 

// 2. Crear la conexión
$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

if ($conexion->connect_error) {
    die(json_encode(['estado' => 'ERROR', 'mensaje' => 'Conexión fallida: ' . $conexion->connect_error]));
}

// 3. Recibir los datos de la app de Android
// ¡Este archivo espera muchos datos!
if (
    isset($_POST['idUsuario']) &&
    isset($_POST['idCategoria']) &&
    isset($_POST['titulo']) &&
    isset($_POST['inspiracion']) &&
    isset($_POST['tiempo']) &&
    isset($_POST['ingredientes']) &&
    isset($_POST['instrucciones']) &&
    isset($_POST['imagen'])
) {
    
    // 4. Capturar todas las variables
    $idUsuario = $_POST['idUsuario'];
    $idCategoria = $_POST['idCategoria'];
    $titulo = $_POST['titulo'];
    $inspiracion = $_POST['inspiracion'];
    $tiempo = $_POST['tiempo'];
    $ingredientes = $_POST['ingredientes'];
    $instrucciones = $_POST['instrucciones'];
    // Nota: 'imagen' aquí es solo un texto (la URL). La subida de la foto
    // se maneja de forma diferente (ej. subiendo a Firebase Storage primero).
    $imagen = $_POST['imagen']; 

    // 5. Preparar la llamada a sp_crear_receta
    $stmt = $conexion->prepare("CALL sp_crear_receta(?, ?, ?, ?, ?, ?, ?, ?)");
    // i = integer, s = string. Son 2 integers y 6 strings
    $stmt->bind_param("iissssss", 
        $idUsuario, 
        $idCategoria, 
        $titulo, 
        $inspiracion, 
        $tiempo, 
        $ingredientes, 
        $instrucciones, 
        $imagen
    );

    // 6. Ejecutar y obtener respuesta
    if ($stmt->execute()) {
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $nuevoIdReceta = $fila['newRecetaId'];

        echo json_encode([
            'estado' => 'EXITO', 
            'mensaje' => 'Receta publicada exitosamente',
            'idNuevaReceta' => $nuevoIdReceta
        ]);
    } else {
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'No se pudo publicar la receta.']);
    }

    $stmt->close();

} else {
    // Si no se enviaron todos los datos correctos
    echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Faltan datos para crear la receta.']);
}

// 8. Cerrar conexión
$conexion->close();

?>