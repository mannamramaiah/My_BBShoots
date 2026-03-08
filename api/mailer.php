<?php
require_once __DIR__ . '/config.php';

$phpmailerLoaded = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpmailerLoaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

function sendMail(string $to, string $toName, string $subject, string $body): bool {
    global $phpmailerLoaded;
    if ($phpmailerLoaded) {
        return smtpSend($to, $toName, $subject, $body);
    }
    $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    return @mail($to, $subject, $body, $headers);
}

function smtpSend(string $to, string $name, string $subject, string $body): bool {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = MAIL_PORT;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('BBShoots Mail Error: ' . $e->getMessage());
        return false;
    }
}

function wrap(string $content): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{margin:0;padding:20px;background:#0a0c10;font-family:Arial,sans-serif}
.w{max-width:600px;margin:0 auto;background:#111827;border:1px solid #1e2d45;border-radius:12px;overflow:hidden}
.h{background:linear-gradient(135deg,#0f1923,#1a0a0a);padding:28px;text-align:center;border-bottom:3px solid #dc2626}
.logo{font-size:30px;font-weight:900;color:#fff;letter-spacing:4px}
.logo span{color:#dc2626}
.tag{color:#64748b;font-size:12px;letter-spacing:2px;margin-top:4px}
.b{padding:32px;color:#cbd5e1;line-height:1.7}
.b h2{color:#f1f5f9;font-size:20px;margin-bottom:16px}
.box{background:#141c2b;border:1px solid #1e2d45;border-radius:10px;padding:20px;margin:16px 0}
.row{display:flex;padding:8px 0;border-bottom:1px solid #1e2d45;font-size:14px}
.row:last-child{border-bottom:none}
.lbl{color:#64748b;width:140px;flex-shrink:0}
.val{color:#f1f5f9;font-weight:600}
.pay{background:#1a1a0a;border:1px solid rgba(251,191,36,0.3);border-radius:10px;padding:20px;margin:16px 0}
.pay h3{color:#fbbf24;margin:0 0 12px;font-size:15px}
.pay p{color:#94a3b8;font-size:14px;margin:4px 0;line-height:1.7}
.pay .bank{background:#0f1318;border-radius:8px;padding:14px;margin-top:12px}
.pay .bank p{color:#cbd5e1;font-size:13px;line-height:1.9}
.f{background:#0a0c10;padding:20px;text-align:center;color:#374151;font-size:12px;border-top:1px solid #1e2d45}
.f a{color:#dc2626;text-decoration:none}
</style></head><body>
<div class="w">
<div class="h"><div class="logo">BB<span>SHOOTS</span></div><div class="tag">PROFESSIONAL PHOTOGRAPHY &amp; VIDEOGRAPHY</div></div>
<div class="b">' . $content . '</div>
<div class="f">&copy; 2025 BBShoots Productions &middot; Visakhapatnam, AP<br>
<a href="mailto:bbshoots49@gmail.com">bbshoots49@gmail.com</a> &middot; +91 98765 43210</div>
</div></body></html>';
}

function paymentBlock(): string {
    return '<div class="pay">
<h3>&#128179; Payment Information</h3>
<p>Your booking is confirmed. Please pay via <strong>bank transfer or cash at the event</strong>.<br>
Our team will contact you within 24 hours with full payment details and to confirm your slot.</p>
<div class="bank"><p>
<strong style="color:#f1f5f9">Bank Transfer Details:</strong><br>
Account Name: <strong>BBShoots Productions</strong><br>
Bank: HDFC Bank &nbsp;|&nbsp; IFSC: HDFC0001234<br>
Account No: 5020 XXXX XXXX 1234<br>
UPI ID: bbshoots@hdfc
</p></div>
<p style="margin-top:10px;color:#64748b;font-size:13px">&#9888; Please mention your booking reference in the payment description.</p>
</div>';
}

function mailBookingConfirm(array $b): bool {
    $content = '<h2>&#127916; Booking Request Received!</h2>
<p>Hi <strong>' . $b['client_name'] . '</strong>, thank you for choosing BBShoots! We have received your booking and will confirm within 24 hours.</p>
<div class="box">
<div class="row"><span class="lbl">Booking Ref</span><span class="val" style="color:#dc2626">' . $b['booking_ref'] . '</span></div>
<div class="row"><span class="lbl">Event</span><span class="val">' . $b['event_type'] . '</span></div>
<div class="row"><span class="lbl">Date</span><span class="val">' . $b['event_date'] . '</span></div>
<div class="row"><span class="lbl">Location</span><span class="val">' . $b['location'] . '</span></div>
<div class="row"><span class="lbl">Package</span><span class="val">' . $b['package'] . '</span></div>
</div>' . paymentBlock() . '
<p style="color:#64748b;font-size:14px">Questions? Call us at <strong>+91 98765 43210</strong></p>';
    return sendMail($b['email'], $b['client_name'],
        '✅ Booking Received — ' . $b['booking_ref'] . ' | BBShoots', wrap($content));
}

function mailStatusUpdate(array $b): bool {
    $status = $b['status'];
    $msgs = [
        'confirmed' => 'Great news! Your booking has been <strong>confirmed</strong>. We are excited to shoot with you!',
        'rejected'  => 'We are sorry — your booking has been <strong>rejected</strong>. Please contact us to discuss alternatives.',
        'completed' => 'Your project has been <strong>completed</strong>! Thank you for choosing BBShoots.',
        'cancelled' => 'Your booking has been <strong>cancelled</strong>. Contact us if you would like to rebook.',
    ];
    $msg       = $msgs[$status] ?? 'Your booking status has been updated to <strong>' . ucfirst($status) . '</strong>.';
    $payBlock  = ($status === 'confirmed') ? paymentBlock() : '';
    $content = '<h2>&#128203; Booking Status Update</h2>
<p>Hi <strong>' . $b['client_name'] . '</strong>, ' . $msg . '</p>
<div class="box">
<div class="row"><span class="lbl">Booking Ref</span><span class="val" style="color:#dc2626">' . $b['booking_ref'] . '</span></div>
<div class="row"><span class="lbl">Event</span><span class="val">' . $b['event_type'] . '</span></div>
<div class="row"><span class="lbl">Date</span><span class="val">' . $b['event_date'] . '</span></div>
<div class="row"><span class="lbl">New Status</span><span class="val">' . ucfirst($status) . '</span></div>
</div>' . $payBlock . '
<p style="color:#64748b;font-size:14px">Need help? Email us at bbshoots49@gmail.com</p>';
    return sendMail($b['email'], $b['client_name'],
        '📋 Booking ' . ucfirst($status) . ' — ' . $b['booking_ref'] . ' | BBShoots', wrap($content));
}

function mailVideosReady(array $b, array $urls): bool {
    $links = '';
    foreach ($urls as $i => $url) {
        $n = $i + 1;
        $links .= '<div class="row"><span class="lbl">Video ' . $n . '</span>
        <span class="val"><a href="' . $url . '" style="color:#dc2626">Download &#8594;</a></span></div>';
    }
    $content = '<h2>&#127881; Your Videos Are Ready!</h2>
<p>Hi <strong>' . $b['client_name'] . '</strong>, your BBShoots project is complete. Download your videos below.</p>
<div class="box">
<div class="row"><span class="lbl">Booking Ref</span><span class="val" style="color:#dc2626">' . $b['booking_ref'] . '</span></div>
<div class="row"><span class="lbl">Event</span><span class="val">' . $b['event_type'] . '</span></div>
<div class="row"><span class="lbl">Package</span><span class="val">' . $b['package'] . '</span></div>
' . $links . '
</div>
<p style="color:#64748b;font-size:14px">Links expire in 30 days. Save your files now. Thank you for choosing BBShoots!</p>';
    return sendMail($b['email'], $b['client_name'],
        '🎬 Your Videos Are Ready — ' . $b['booking_ref'] . ' | BBShoots', wrap($content));
}

function mailAdminNewBooking(array $b): bool {
    $content = '<h2>&#128276; New Booking Alert!</h2>
<p>A new booking has just been submitted. Login to confirm it.</p>
<div class="box">
<div class="row"><span class="lbl">Booking Ref</span><span class="val" style="color:#dc2626">' . $b['booking_ref'] . '</span></div>
<div class="row"><span class="lbl">Client</span><span class="val">' . $b['client_name'] . '</span></div>
<div class="row"><span class="lbl">Email</span><span class="val">' . $b['email'] . '</span></div>
<div class="row"><span class="lbl">Phone</span><span class="val">' . $b['phone'] . '</span></div>
<div class="row"><span class="lbl">Event</span><span class="val">' . $b['event_type'] . '</span></div>
<div class="row"><span class="lbl">Date</span><span class="val">' . $b['event_date'] . '</span></div>
<div class="row"><span class="lbl">Location</span><span class="val">' . $b['location'] . '</span></div>
<div class="row"><span class="lbl">Package</span><span class="val">' . $b['package'] . '</span></div>
<div class="row"><span class="lbl">Notes</span><span class="val">' . ($b['notes'] ?: 'None') . '</span></div>
</div>
<p><a href="http://localhost/bbshoots/admin/" style="background:#dc2626;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block">Open Admin Panel &#8594;</a></p>';
    return sendMail(ADMIN_EMAIL, 'BBShoots Admin',
        '🔔 New Booking: ' . $b['booking_ref'] . ' — ' . $b['event_type'], wrap($content));
}

function mailAdminContact(string $name, string $email, string $message): bool {
    $content = '<h2>&#128233; New Contact Message</h2>
<p>Someone sent a message via the BBShoots contact form.</p>
<div class="box">
<div class="row"><span class="lbl">From</span><span class="val">' . $name . '</span></div>
<div class="row"><span class="lbl">Email</span><span class="val"><a href="mailto:' . $email . '" style="color:#dc2626">' . $email . '</a></span></div>
<div class="row"><span class="lbl">Message</span><span class="val">' . nl2br(htmlspecialchars($message)) . '</span></div>
</div>
<p style="color:#64748b;font-size:14px">Reply directly to <strong>' . $email . '</strong></p>';
    return sendMail(ADMIN_EMAIL, 'BBShoots Admin',
        '📩 Contact Message from ' . $name . ' | BBShoots', wrap($content));
}