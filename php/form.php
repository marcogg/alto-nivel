use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

<?php
require 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer


// Recaptcha secret key
define('RECAPTCHA_SECRET_KEY', '6Leg7borAAAAAE5WoHOawnjIUq_jKYbBqG9J4_h2');

// Validate inputs
function validate_input($name, $phone, $email) {
    if (empty($name) || empty($phone) || empty($email)) return false;
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) return false;
    if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $phone)) return false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    return true;
}

// Verify Recaptcha v3
function verify_recaptcha($token) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    return $response['success'] && $response['score'] > 0.5;
}

// Send email using PHPMailer
function send_contact_email($name, $phone, $email) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.americanet.mx'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your@email.com';   // SMTP username
        $mail->Password   = 'yourpassword';     // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('marcogarcia.gon@gmail.com', 'Web Contact');
        $mail->addAddress('no-responder@americanet.mx', 'Notificaciones');

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Nuevo contacto desde el sitio web';
        $mail->Body    = "Nombre: $name<br>Teléfono: $phone<br>Email: $email";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Main process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $recaptcha_token = $_POST['recaptcha_token'] ?? '';

    if (!validate_input($name, $phone, $email)) {
        die('Datos inválidos.');
    }

    if (!verify_recaptcha($recaptcha_token)) {
        die('Recaptcha falló.');
    }

    if (send_contact_email($name, $phone, $email)) {
        header('Location: gracias-por-contactarnos.html');
        exit;
    } else {
        die('No se pudo enviar el correo.');
    }
}
?>