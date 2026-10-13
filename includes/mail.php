<?php
function emailConfigured(): bool { return env('SMTP_HOST') !== '' && filter_var(env('MAIL_FROM'), FILTER_VALIDATE_EMAIL) !== false; }
function sendAccountEmail(string $email, string $token, string $type): void {
    if (!emailConfigured()) throw new RuntimeException('Email delivery is not configured. Contact the platform administrator.');
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP(); $mail->Host=env('SMTP_HOST'); $mail->Port=(int)env('SMTP_PORT','587');
    $mail->SMTPAuth=env('SMTP_USER') !== ''; $mail->Username=env('SMTP_USER'); $mail->Password=env('SMTP_PASSWORD');
    $mail->SMTPSecure=env('SMTP_ENCRYPTION','tls'); $mail->Timeout=10; $mail->CharSet='UTF-8';
    if (APP_ENV === 'production' && !in_array($mail->SMTPSecure,['tls','ssl'],true)) throw new RuntimeException('Production email requires TLS.');
    $mail->setFrom(env('MAIL_FROM'),'KrishiDisha'); $mail->addAddress($email);
    $path=$type === 'password_reset' ? 'reset_password' : 'verify_email';
    $mail->Subject=$type === 'password_reset' ? 'KrishiDisha password reset' : 'Verify your KrishiDisha email';
    $mail->Body="Use this single-use link: " . baseUrl() . '/KrishiDisha/auth/' . $path . '.php?token=' . $token . "\nIf you did not request this, ignore this message.";
    $mail->send();
}
