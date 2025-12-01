<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$servidor = "localhost";
$usuario_db = "root";    
$password_db = "";
$nombre_db = "remediosnaturales"; 

// CORRECCIÓN 1: Usamos la variable correcta ($password_db)
$conexion = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);

// CORRECCIÓN 2: Si falla la conexión, devolvemos JSON, no texto plano con die()
if ($conexion->connect_error) {
    echo json_encode([
        "estado" => "ERROR", 
        "mensaje" => "Fallo conexión base de datos: " . $conexion->connect_error
    ]);
    exit(); // Terminamos el script limpiamente
}

// 3. Recibir los datos de la app de Android
if (isset($_POST['correo']) && isset($_POST['contrasena'])) {
    
    $correo = $_POST['correo'];
    $contrasena_usuario = $_POST['contrasena']; // Nombre diferente para evitar confusión

    $stmt = $conexion->prepare("CALL sp_validar_login(?, ?)");
    // Usamos la contraseña que nos mandó el usuario
    $stmt->bind_param("ss", $correo, $contrasena_usuario);

    $stmt->execute();
    $resultado = $stmt->get_result();

    $respuesta = array();

    if ($resultado->num_rows > 0) {
        // Encontramos al usuario
        $respuesta['estado'] = 'EXITO';
        $respuesta['usuario'] = $resultado->fetch_assoc();
        $respuesta['mensaje'] = 'Login correcto';
    } else {
        // No existe o contraseña mal
        $respuesta['estado'] = 'ERROR';
        $respuesta['mensaje'] = 'Correo o contraseña incorrectos.';
    }
    
    echo json_encode($respuesta);
    
    $stmt->close();

} else {
    // Faltan datos
    echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Faltan correo o contraseña']);
}

$conexion->close();

?>