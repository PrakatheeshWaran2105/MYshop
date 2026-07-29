<?php

/**
 * Send Admin Password Reset OTP Email
 *
 * @param string $email
 * @param string $name
 * @param string $otp
 * @param string|null &$errorMessage
 * @return bool
 */
function sendAdminOtpEmail(
    string $email,
    string $name,
    string $otp,
    ?string &$errorMessage = null
): bool {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $subject = 'Admin Password Reset OTP - KGF Control Room';

    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 25px; border: 1px solid #e0e0e0; border-radius: 10px; background-color: #ffffff;'>
        <h2 style='color: #111111; margin-top: 0; font-size: 22px; border-bottom: 2px solid #111111; padding-bottom: 10px;'>KGF Mens Wear</h2>
        <h3 style='color: #333333; margin-top: 20px;'>Admin Password Reset OTP</h3>
        <p style='color: #555555; font-size: 15px;'>Hello <strong>{$safeName}</strong>,</p>
        <p style='color: #555555; font-size: 15px;'>We received a request to reset your admin password. Use the OTP code below to verify your request:</p>
        <div style='background-color: #f4f4f6; border: 1px dashed #cccccc; padding: 15px; text-align: center; border-radius: 8px; margin: 25px 0;'>
            <span style='font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #111111;'>{$safeOtp}</span>
        </div>
        <p style='color: #888888; font-size: 13px;'>This OTP code will expire in <strong>10 minutes</strong>.</p>
        <p style='color: #888888; font-size: 13px;'>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
    </div>
    ";

    return sendMail($email, $subject, $htmlBody, '', '', $errorMessage);
}