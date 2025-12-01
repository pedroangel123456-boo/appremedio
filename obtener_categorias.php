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

// 3. Preparar la llamada a sp_obtener_categorias
$stmt = $conexion->prepare("CALL sp_obtener_categorias()");

// 4. Ejecutar la llamada
$stmt->execute();
$resultado = $stmt->get_result();

$categorias = array(); 

// 5. Recorrer los resultados
if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        $categorias[] = $fila; 
    }
}

// 6. Enviar la respuesta (JSON) de vuelta a Android
echo json_encode(['estado' => 'EXITO', 'categorias' => $categorias]);

// 7. Cerrar conexión
$stmt->close();
$conexion->close();

?>