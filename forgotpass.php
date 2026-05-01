<?php
require 'db.php';
date_default_timezone_set('Asia/Manila');

// I-load ang PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Siguraduhing tama ang path ng PHPMailer folder na dinownload mo
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_btn'])) {
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("SELECT id, firstname FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 1. Gumawa ng random unique token
        $token = bin2hex(random_bytes(32));
        $expire = date("Y-m-d H:i:s", strtotime('+30 minutes')); // Valid for 30 mins
        
        // 2. I-save ang token sa database
        $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expire, $email);
        $update->execute();
        
        // 3. I-setup ang Email Sender (PHPMailer)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            // PALITAN ITO NG TOTOONG GMAIL MO:
            $mail->Username   = 'shamelgcamposano@gmail.com'; 
            // PALITAN ITO NG 16-LETTER APP PASSWORD (walang space):
            $mail->Password   = 'xonshsyugayytuay'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Sender at Recipient
            $mail->setFrom('shamelgcamposano@gmail.com', 'SAVANT Admin');
            $mail->addAddress($email, $user['firstname']);

            // PALITAN ANG URL DEPENDE SA PANGALAN NG FOLDER MO SA XAMPP
            $reset_link = "http://localhost/SAVANT/resetpass.php?token=" . $token;

            // Laman ng Email
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - SAVANT';
            $mail->Body    = "
                <h3>Hello {$user['firstname']},</h3>
                <p>You requested a password reset. Click the link below to change your password:</p>
                <p><a href='{$reset_link}' style='background-color: #12aa42; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Reset My Password</a></p>
                <p>If you did not request this, please ignore this email.</p>
                <br>
                <p>Thank you,<br>SAVANT Team</p>
            ";

            $mail->send();
            $msg = "<div class='alert alert-success shadow-sm'>We have sent a password reset link to your email! Please check your inbox.</div>";
            
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger shadow-sm'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'>No account found with that email address!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<!-- KUNIN ANG BUONG <head> HANGGANG </style> MULA SA PREVIOUS FORGOTPASS.PHP NATIN AT I-PASTE DITO -->
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAVANT - Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --savant-primary-green: #087830; --savant-accent-green: #12aa42; --savant-hover-green: #146c3a; }
    * { font-family: 'Poppins', sans-serif !important; }
    body, html { height: 100%; margin: 0; overflow: hidden; }
    .main-container { position: relative; height: 100vh; width: 100%; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .btn-back-circle { position: fixed; top: 30px; left: 30px; width: 50px; height: 50px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; z-index: 100; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
    .btn-back-circle:hover { background: rgba(255, 255, 255, 0.2); color: var(--savant-primary-green); transform: scale(1.1); }
    .glass-card { background: rgba(47, 45, 45, 0.1); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 30px; padding: 60px; width: 100%; max-width: 1100px; color: white; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
    .form-control-glass { background: rgba(255, 255, 255, 0.2) !important; border: none !important; color: white !important; padding: 12px 20px; border-radius: 8px; }
    .form-control-glass::placeholder { color: rgba(255, 255, 255, 0.6); }
    .btn-action { background-color: var(--savant-accent-green); border: none; padding: 12px; font-weight: 500; border-radius: 8px; transition: background 0.3s ease; }
    .btn-action:hover { background-color: var(--savant-hover-green); }
    @media (max-width: 768px) { .glass-card { padding: 40px 20px; } }
  </style>
</head>

<body>
  <a href="login.php" class="btn-back-circle"><i class="bi bi-chevron-left fs-4"></i></a>
  <div class="main-container">
    <img src="img/bg.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: -2;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50" style="z-index: -1;"></div>
    
    <div class="glass-card">
      <div class="row align-items-center">
        <div class="col-lg-6 d-flex align-items-center mb-5 mb-lg-0 justify-content-center">
          <img src="img/logo.png" alt="SAVANT Logo" style="height: 200px; width: auto;">
        </div>
        <div class="col-lg-6">
          <h3 class="fw-semibold mb-1">Forgot Password?</h3>
          <p class="small mb-4 text-white-50">No worries! Enter your email and we'll send you reset instructions.</p>
          
          <?php echo $msg; ?>

          <form action="forgotpass.php" method="POST">
            <div class="mb-4">
              <label class="form-label small text-white-50">Email Address</label>
              <input type="email" name="email" class="form-control form-control-glass" placeholder="name@gmail.com" required>
            </div>
            <button type="submit" name="reset_btn" class="btn btn-action w-100 text-white mb-4">Send Code</button>
          </form>
          
        </div>
      </div>
    </div>
  </div>
</body>
</html>