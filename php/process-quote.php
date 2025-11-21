<?php
// 1. Iniciar la respuesta que enviaremos a JavaScript (CON UTF-8)
header('Content-Type: application/json; charset=utf-8'); // <-- AÑADIDO
mb_internal_encoding('UTF-8'); // <-- AÑADIDO (Mejor práctica)
$response = [
    'success' => false, // Empezamos asumiendo que fallará
    'message' => 'Error al iniciar el proceso.',
    'errors' => []
];

// 2. Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 3. Requerir el autoloader de Composer
// Asegúrate de que la ruta a 'vendor/autoload.php' sea correcta
require '../vendor/autoload.php';

// 4. Verificar que sea un método POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

// 5. Recoger y sanear los datos
$fullname = trim(filter_input(INPUT_POST, 'fullname', FILTER_UNSAFE_RAW));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW));

// 6. Validación de datos
if (empty($fullname)) {
    $response['errors'][] = "El nombre completo es obligatorio.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors'][] = "El email no es válido o está vacío.";
}
if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
    $response['errors'][] = "El teléfono debe contener 10 dígitos numéricos.";
}


// 7. Si hay errores de validación, devolverlos y salir
if (!empty($response['errors'])) {
    $response['message'] = 'Por favor, corrige los errores.';
    echo json_encode($response);
    exit;
}

// 8. Si todo está bien, intentar enviar el correo
$mail = new PHPMailer(true);

try {
    // --- Configuración SMTP (Neubox) ---
    
    // ¡¡MUY IMPORTANTE!! DESACTIVA EL DEBUG PARA QUE EL JSON FUNCIONE
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    $mail->SMTPDebug = 0; // O coméntalo como arriba

    $mail->isSMTP();
    $mail->Host       = 'mail.seguridadprivadaenmerida.com'; // CAMBIA ESTO
    $mail->SMTPAuth   = true;
    $mail->Username   = 'no-responder@seguridadprivadaenmerida.com'; // CAMBIA ESTO
    $mail->Password   = 'acme2025*'; // CAMBIA ESTO
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // --- Remitente y Destinatarios ---
    $mail->setFrom('no-responder@seguridadprivadaenmerida.com', 'Nueva entrada en formulario web');
    $mail->addAddress('marcogarcia.gon@gmail.com'); // A quién le llega
    $mail->addReplyTo($email, $fullname);

    // --- Contenido ---
    $subject = "Nueva solicitud de cotización de: $fullname";
    $body = "Has recibido una nueva solicitud de cotización:\n\n";
    $body .= "Nombre: " . $fullname . "\n";
    $body .= "Email: " . $email . "\n";
    $body .= "Teléfono: " . $phone . "\n";
    // ... (añade el resto de campos al body) ...

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();

    // 9. Si el correo se envía, preparamos la respuesta de ÉXITO
    $response['success'] = true;
    $response['message'] = '¡Gracias! Tu solicitud ha sido enviada. Serás redirigido.';

} catch (Exception $e) {
    // 10. Si PHPMailer falla, preparamos la respuesta de ERROR
    $response['success'] = false;
    $response['message'] = "Hubo un error al enviar el mensaje. Error: {$mail->ErrorInfo}";
}

// 11. Devolver la respuesta (sea éxito o error) como JSON y salir
echo json_encode($response);
exit;
?>