<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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

// 3. Preparar la llamada a sp_obtener_recetas_recientes
$stmt = $conexion->prepare("CALL sp_obtener_recetas_recientes()");

// 4. Ejecutar la llamada
$stmt->execute();
$resultado = $stmt->get_result();

$recetas = array(); 

// 5. Recorrer los resultados y guardarlos en el array
if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        $recetas[] = $fila; // Añade cada fila (receta) al array
    }
}

// 6. Enviar la respuesta (JSON) de vuelta a Android
echo json_encode(['estado' => 'EXITO', 'recetas' => $recetas]);

// 7. Cerrar conexión
$stmt->close();
$conexion->close();

?>