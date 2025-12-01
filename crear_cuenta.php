<?php
// --- INICIO DEL BLINDAJE DE ERRORES ---
// Esto captura cualquier error fatal de PHP y lo convierte en JSON
ini_set('display_errors', 0); // No mostrar errores como HTML
error_reporting(E_ALL);

set_exception_handler(function($exception) {
    // Asegurarnos de que la respuesta sea JSON
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'estado' => 'ERROR_PHP',
        'mensaje' => 'Excepción: ' . $exception->getMessage(),
        'archivo' => $exception->getFile(),
        'linea' => $exception->getLine()
    ]);
    exit();
});

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
// --- FIN DEL BLINDAJE DE ERRORES ---


// --- TU CÓDIGO ORIGINAL EMPIEZA AQUÍ ---

// Usamos las clases de PHPMailer
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// ⚠️ IMPORTANTE: Si no tienes instalada la librería PHPMailer, 
// esta línea causará el Error 500.
// Si te falla, comenta esta línea y la parte del envío de correo más abajo.
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    // Si no existe, definimos una bandera para no intentar enviar correo
    $sin_mailer = true;
}

// Cabeceras
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- CORREGIDO: Sin caracteres invisibles ---
$servidor = "localhost";
$puerto = 3306;
$usuario_db = "root";
$contrasena_db = ""; // Esta línea está re-escrita
$nombre_db = "remediosnaturales";

// --- 2. Configuración de tu Gmail para enviar correos ---
define('MI_CORREO', 'angelgontri@gmail.com'); // ¡LISTO! Tu correo
define('MI_CONTRASENA_GMAIL', 'hsedtaqqahfldjdi'); // ¡LISTO! Tu contraseña de app (sin espacios)

// -----------------------------------------------------------------

// 3. Crear la conexión (Con tus datos)
$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

// CORRECCIÓN: Si la conexión falla, morimos enviando un JSON (re-escrito sin caracteres raros)
if ($conexion->connect_error) {
    die(json_encode(['estado' => 'ERROR', 'mensaje' => 'Conexión fallida: ' . $conexion->connect_error]));
}

// 4. Recibir los datos de la app de Android
if (isset($_POST['usuario']) && isset($_POST['nombre']) && isset($_POST['correo']) && isset($_POST['contrasena'])) {
    
    $usuario = $_POST['usuario'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    // --- PRIMERA PARTE: Crear el usuario ---
    $stmt_crear = $conexion->prepare("CALL sp_crear_cuenta(?, ?, ?, ?)");
    $stmt_crear->bind_param("ssss", $usuario, $nombre, $correo, $contrasena);

    if ($stmt_crear->execute()) {
        $resultado_crear = $stmt_crear->get_result();
        
        // --- POSIBLE ERROR AQUÍ: ¿Qué pasa si el usuario ya existe? ---
        // $resultado_crear podría no tener filas.
        
        $fila_usuario = $resultado_crear->fetch_assoc();
        
        // Si el usuario no se pudo crear (ej. correo duplicado y el SP no devolvió nada)
        if (!$fila_usuario || !isset($fila_usuario['newUserId'])) {
             echo json_encode([
                'estado' => 'ERROR',
                'mensaje' => 'Correo o usuario ya están registrados (o error en SP).'
            ]);
             $stmt_crear->close();
             $conexion->close();
             exit(); // Salimos para no continuar
        }
        
        $nuevo_id_usuario = $fila_usuario['newUserId'];
        $stmt_crear->close();
        
        // --- SEGUNDA PARTE: Generar el código ---
        while($conexion->more_results() && $conexion->next_result()) {} // Limpiador
        
        $stmt_codigo = $conexion->prepare("CALL sp_generar_codigo_verificacion(?)");
        $stmt_codigo->bind_param("i", $nuevo_id_usuario);
        $stmt_codigo->execute();
        $resultado_codigo = $stmt_codigo->get_result();
        $fila_codigo = $resultado_codigo->fetch_assoc();
        $codigo_generado = $fila_codigo['codigo'];
        $stmt_codigo->close();

        // --- TERCERA PARTE: Enviar el correo con PHPMailer ---
        
        // Verificamos si tenemos la librería antes de intentar usarla
        if (isset($sin_mailer) && $sin_mailer) {
             echo json_encode([
                'estado' => 'EXITO',
                'mensaje' => 'Usuario creado (Correo no enviado: falta librería vendor).',
                'idUsuario' => $nuevo_id_usuario
            ]);
            exit();
        }

        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor (SMTP)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = MI_CORREO; // Tu correo de Gmail
            $mail->Password = MI_CONTRASENA_GMAIL; // Tu contraseña de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Destinatarios
            $mail->setFrom(MI_CORREO, 'App Remedios Naturales'); // Quién envía
            $mail->addAddress($correo, $nombre); // A quién se envía (el usuario)

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Tu codigo de verificacion - App Remedios';
            $mail->Body = 'Hola ' . $nombre . ',<br>Tu codigo de verificacion es: <b>' . $codigo_generado . '</b>';
            $mail->AltBody = 'Tu codigo de verificacion es: ' . $codigo_generado;

            $mail->send(); // ¡Enviar el correo!

            // 5. ¡ÉXITO! Enviar la respuesta a Android
            echo json_encode([
                'estado' => 'EXITO',
                'mensaje' => 'Usuario creado. Te hemos enviado un correo.',
                'idUsuario' => $nuevo_id_usuario
            ]);

        } catch (Exception $e) {
            // Si PHPMailer falla
            echo json_encode([
                'estado' => 'ERROR',
                'mensaje' => 'Usuario creado, pero falló el envío de correo. Mailer Error: ' . $mail->ErrorInfo
            ]);
        }

    } else {
        // Esto captura si el $stmt_crear->execute() falla
        echo json_encode([
            'estado' => 'ERROR',
            'mensaje' => 'Error al ejecutar el Stored Procedure de creación.'
        ]);
    }
} else {
    echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Faltan datos']);
}
$conexion->close();

?>