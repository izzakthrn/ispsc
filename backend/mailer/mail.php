<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// This path assumes mail.php is in backend/mailer/. Adjust path as needed.
require __DIR__ . '/../../vendor/autoload.php';

function sendOTPEmail($email, $firstname, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hezelijahpublico@gmail.com';
        $mail->Password   = 'yxyuujromchysmiu'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('hezelijahpublico@gmail.com', 'ISPSC E-Queue');
        $mail->addAddress($email);

        $mail->isHTML(true);
        
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'ISPSC OTP Verification';
        $mail->Body    = "
            <h2>ISPSC OTP Verification</h2>
            <p>Hello $firstname,</p>
            <p>Your OTP code is:</p>
            <h1>$otp</h1>
            <p><small>This code will expire in 10 minutes. Do not share this code.</small></p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>