<?php
// pages/includes/mailer.php
require_once __DIR__ . '/../../database/database.php';

// We will resolve PHPMailer dynamically so that your local code editor
// doesn't show red squiggly lines if Composer isn't installed yet.

// We assume PHPMailer is available (either via autoload or require statements you'll configure)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

function sendOrderReceiptEmail($userEmail, $userName, $orderId, $items, $subtotal, $shipping, $total, $address) {
    $mailClass = 'PHPMailer\PHPMailer\PHPMailer';
    if (!class_exists($mailClass)) {
        // Log or handle the case where PHPMailer isn't installed.
        // For now, we will just return false so checkout doesn't fatally crash.
        error_log("PHPMailer not installed. Cannot send email receipt.");
        return false;
    }

    $envFile = __DIR__ . '/../../.env';
    $env = file_exists($envFile) ? parse_ini_file($envFile) : [];
    
    $smtpHost = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
    $smtpUser = $env['SMTP_USER'] ?? 'your-email@gmail.com';
    $smtpPass = $env['SMTP_PASS'] ?? 'your-app-password';
    $smtpPort = $env['SMTP_PORT'] ?? 587;

    $mail = new $mailClass(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = 'tls'; // 'tls' is the exact string PHPMailer::ENCRYPTION_STARTTLS maps to.
        $mail->Port       = $smtpPort;

        // Recipients
        $mail->setFrom($smtpUser, 'ShopZone Store');
        $mail->addAddress($userEmail, $userName);

        // Build elegant item list
        $itemsHtml = '';
        foreach ($items as $item) {
            $ep = number_format($item['sale_price'] ?? $item['price'], 2);
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #333;'>{$item['name']} <span style='color: #666; font-size: 0.9em;'>x{$item['quantity']}</span></td>
                    <td style='padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #000;'>$" . number_format(($item['sale_price'] ?? $item['price']) * $item['quantity'], 2) . "</td>
                </tr>
            ";
        }

        // Generate QR code link dynamically
        $orderUrl = "http://localhost/e-commerce/pages/user/orders.php?id=" . $orderId;
        $otp = strtoupper(substr(hash('sha256', "SHOPZONE_OTP_" . $orderId), 0, 6));
        $qrData = "Order: {$orderUrl}\nOTP: {$otp}";
        $qrUrl    = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);

        // Email Html Template
        $htmlBody = "
        <div style='font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; background-color: #fcfcfc; color: #000; padding: 40px 20px; text-align: center;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); text-align: left;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='font-family: \"Times New Roman\", serif; font-size: 28px; margin: 0; letter-spacing: -0.5px;'>Shop<span style='color: #666;'>Zone</span></h1>
                </div>
                
                <h2 style='font-size: 20px; text-align: center; margin-bottom: 5px;'>Thank You For Your Order!</h2>
                <p style='color: #666; text-align: center; font-size: 15px; margin-bottom: 40px;'>Order #{$orderId}</p>

                <p style='font-size: 16px; margin-bottom: 20px;'>Hi {$userName},</p>
                <p style='color: #666; font-size: 15px; margin-bottom: 30px;'>We're getting your order ready to be shipped. We will notify you when it has been sent.</p>

                <div style='text-align: center; margin-bottom: 30px; padding: 20px; border: 2px dashed #000; background: #fafafa; border-radius: 8px;'>
                    <span style='color: #666; font-size: 14px; display: block; margin-bottom: 5px; text-transform: uppercase; font-weight: bold;'>Payment OTP</span>
                    <strong style='font-size: 36px; letter-spacing: 6px; color: #000;'>{$otp}</strong>
                    <p style='font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 0;'>Provide this code if requested for your receipt.</p>
                </div>

                <h3 style='font-size: 16px; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;'>Order Summary</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style='padding: 10px 0; color: #666;'>Subtotal</td>
                            <td style='padding: 10px 0; text-align: right;'>$" . number_format($subtotal, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; color: #666;'>Shipping</td>
                            <td style='padding: 10px 0; text-align: right;'>$" . number_format($shipping, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 15px 0; font-weight: bold; font-size: 18px; border-top: 2px solid #000;'>Total</td>
                            <td style='padding: 15px 0; text-align: right; font-weight: bold; font-size: 18px; border-top: 2px solid #000;'>$" . number_format($total, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>

                <h3 style='font-size: 16px; margin-top: 40px; margin-bottom: 10px;'>Shipping Address</h3>
                <p style='color: #666; font-size: 14px; line-height: 1.5;'>
                    " . nl2br(htmlspecialchars($address)) . "
                </p>

                <div style='text-align: center; margin-top: 60px; padding-top: 30px; border-top: 1px solid #e0e0e0;'>
                    <p style='color: #666; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px;'>Your Digital Receipt</p>
                    <img src='{$qrUrl}' alt='Order QR Code' style='width: 150px; height: 150px; border: 1px solid #e0e0e0; padding: 5px; background: #fff; border-radius: 8px;'>
                    <p style='color: #666; font-size: 13px; margin-top: 15px;'>Scan this QR code with your phone's camera to view your full order details and OTP code.</p>
                </div>
            </div>
        </div>
        ";

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Your ShopZone Order Receipt (#{$orderId})";
        $mail->Body    = $htmlBody;

        if (!$mail->send()) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
        return true;
    } catch (\Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendDeliveryReceiptEmail($userEmail, $userName, $orderId, $items, $subtotal, $shipping, $total, $address) {
    $mailClass = 'PHPMailer\PHPMailer\PHPMailer';
    if (!class_exists($mailClass)) return false;

    $envFile = __DIR__ . '/../../.env';
    $env = file_exists($envFile) ? parse_ini_file($envFile) : [];
    
    $smtpHost = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
    $smtpUser = $env['SMTP_USER'] ?? 'your-email@gmail.com';
    $smtpPass = $env['SMTP_PASS'] ?? 'your-app-password';
    $smtpPort = $env['SMTP_PORT'] ?? 587;

    $mail = new $mailClass(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpUser, 'ShopZone Store');
        $mail->addAddress($userEmail, $userName);

        $itemsHtml = '';
        foreach ($items as $item) {
            $ep = number_format($item['price'], 2); // In delivery receipt, we get price right from order_items
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #333;'>{$item['name']} <span style='color: #666; font-size: 0.9em;'>x{$item['quantity']}</span></td>
                    <td style='padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #000;'>$" . number_format($item['price'] * $item['quantity'], 2) . "</td>
                </tr>
            ";
        }

        $orderUrl = "http://localhost/e-commerce/pages/user/orders.php?id=" . $orderId;
        $otp = strtoupper(substr(hash('sha256', "SHOPZONE_OTP_" . $orderId), 0, 6));
        $qrData = "Order: {$orderUrl}\nOTP: {$otp}";
        $qrUrl    = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);

        $htmlBody = "
        <div style='font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; background-color: #fcfcfc; color: #000; padding: 40px 20px; text-align: center;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); text-align: left;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='font-family: \"Times New Roman\", serif; font-size: 28px; margin: 0; letter-spacing: -0.5px;'>Shop<span style='color: #666;'>Zone</span></h1>
                </div>
                
                <h2 style='font-size: 20px; text-align: center; margin-bottom: 5px; color: #2ecc71'>Your order has been delivered!</h2>
                <p style='color: #666; text-align: center; font-size: 15px; margin-bottom: 40px;'>Order #{$orderId}</p>

                <p style='font-size: 16px; margin-bottom: 20px;'>Hi {$userName},</p>
                <p style='color: #666; font-size: 15px; margin-bottom: 30px;'>Great news! The items from your recent order have been successfully delivered to the address below. We hope you love them!</p>

                <div style='text-align: center; margin-bottom: 30px; padding: 20px; border: 2px dashed #000; background: #fafafa; border-radius: 8px;'>
                    <span style='color: #666; font-size: 14px; display: block; margin-bottom: 5px; text-transform: uppercase; font-weight: bold;'>Payment OTP</span>
                    <strong style='font-size: 36px; letter-spacing: 6px; color: #000;'>{$otp}</strong>
                    <p style='font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 0;'>Provide this code if requested for your receipt.</p>
                </div>

                <h3 style='font-size: 16px; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;'>Order Summary</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tbody>{$itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td style='padding: 10px 0; color: #666;'>Subtotal</td>
                            <td style='padding: 10px 0; text-align: right;'>$" . number_format($subtotal, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; color: #666;'>Shipping</td>
                            <td style='padding: 10px 0; text-align: right;'>$" . number_format($shipping, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 15px 0; font-weight: bold; font-size: 18px; border-top: 2px solid #000;'>Total</td>
                            <td style='padding: 15px 0; text-align: right; font-weight: bold; font-size: 18px; border-top: 2px solid #000;'>$" . number_format($total, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>

                <h3 style='font-size: 16px; margin-top: 40px; margin-bottom: 10px;'>Delivered To</h3>
                <p style='color: #666; font-size: 14px; line-height: 1.5;'>" . nl2br(htmlspecialchars($address)) . "</p>

                <div style='text-align: center; margin-top: 60px; padding-top: 30px; border-top: 1px solid #e0e0e0;'>
                    <p style='color: #666; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px;'>Your Digital Receipt</p>
                    <img src='{$qrUrl}' alt='Order QR Code' style='width: 150px; height: 150px; border: 1px solid #e0e0e0; padding: 5px; background: #fff; border-radius: 8px;'>
                    <p style='color: #666; font-size: 13px; margin-top: 15px;'>Scan this QR code with your phone's camera to view your full order details and OTP code.</p>
                </div>
            </div>
        </div>
        ";

        $mail->isHTML(true);
        $mail->Subject = "Your ShopZone Order (#{$orderId}) has been delivered!";
        $mail->Body    = $htmlBody;

        if (!$mail->send()) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
        return true;
    } catch (\Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
