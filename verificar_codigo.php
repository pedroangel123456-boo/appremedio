<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- 1. Configuración de la base de datos ---
$servidor = "localhost";
$puerto = 3306;
$usuario_db = "root";
$contrasena_db = "";
$nombre_db = "remediosnaturales";

// 2. Crear la conexión
$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

// Verificar conexión
if ($conexion->connect_error) {
    // Si la conexión falla, morimos enviando un JSON
    die(json_encode(['estado' => 'ERROR', 'mensaje' => 'Conexión fallida: ' . $conexion->connect_error]));
}

// 3. Recibir los datos de la app de Android
if (isset($_POST['idUsuario']) && isset($_POST['codigo'])) {
    
    // Aseguramos que el ID sea entero
    $idUsuario = (int)$_POST['idUsuario']; 
    $codigo = $_POST['codigo'];

    // Llamar al Stored Procedure
    $stmt = $conexion->prepare("CALL sp_verificar_codigo(?, ?)");
    $stmt->bind_param("is", $idUsuario, $codigo); // i=integer, s=string

    $stmt->execute();
    $resultado = $stmt->get_result();

    $respuesta = array();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        // El SP debe retornar una fila con el estado de la operación (EXITO o ERROR)
        if (isset($fila['estado']) && $fila['estado'] === 'EXITO') {
            $respuesta['estado'] = 'EXITO';
            $respuesta['mensaje'] = 'Código verificado con éxito.';
        } else {
            // El SP retornó ERROR (código incorrecto o expirado)
            $respuesta['estado'] = 'ERROR';
            $respuesta['mensaje'] = $fila['mensaje'] ?? 'Código de verificación incorrecto o expirado.';
        }
    } else {
        // Fallo inesperado o SP no devolvió nada
        $respuesta['estado'] = 'ERROR';
        $respuesta['mensaje'] = 'Fallo en la ejecución de la verificación.';
    }
    
    echo json_encode($respuesta);
    
    $stmt->close();
} else {
    // Si no se enviaron los datos correctos
    echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Faltan datos de ID o código.']);
}

$conexion->close();

?>