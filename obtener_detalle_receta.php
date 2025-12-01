<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


$servidor = "localhost"; 
$puerto = 3306;
$usuario_db = "root";    
$contrasena_db = "";     
$nombre_db = "remediosnaturales"; 


$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

if ($conexion->connect_error) {
    die(json_encode(['estado' => 'ERROR', 'mensaje' => 'Conexión fallida: ' . $conexion->connect_error]));
}

// 3. Recibir el ID de la receta desde Android
if (isset($_POST['idReceta'])) {
    
    $idReceta = $_POST['idReceta'];

    // 4. Preparar la llamada a sp_obtener_detalle_receta
    $stmt = $conexion->prepare("CALL sp_obtener_detalle_receta(?)");
    $stmt->bind_param("i", $idReceta); // "i" = integer

    // 5. Ejecutar la llamada
    $stmt->execute();
    $resultado = $stmt->get_result();

    // 6. Verificar si se encontró la receta
    if ($resultado->num_rows > 0) {
        // ¡Receta encontrada! Devuelve sus datos
        $receta_detalle = $resultado->fetch_assoc();
        echo json_encode(['estado' => 'EXITO', 'receta' => $receta_detalle]);
    } else {
        // No se encontró una receta con ese ID
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Receta no encontrada.']);
    }
    
    $stmt->close();

} else {
    // Si no se envió el 'idReceta'
    echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Falta el ID de la receta.']);
}

// 7. Cerrar conexión
$conexion->close();

?>