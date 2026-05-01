<?php
require 'db.php';

// I-SET ANG TIMEZONE SA PILIPINAS PARA TUGMA ANG ORAS
date_default_timezone_set('Asia/Manila');

$msg = '';

// Check kung may token sa URL
if (!isset($_GET['token'])) {
    die("Invalid request. Wala kang token.");
}

$token = $_GET['token'];
$current_time = date("Y-m-d H:i:s"); // Kunin ang tamang oras ngayon

// Check kung existing at hindi pa expired ang token gamit ang tamang oras
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expire > ?");
$stmt->bind_param("ss", $token, $current_time);
$stmt->execute();
$result = $stmt->get_result();

// Kung walang nakita, ibig sabihin mali o expired na
if ($result->num_rows == 0) {
    die("<div style='text-align: center; margin-top: 50px; font-family: sans-serif;'>
            <h2 style='color: red;'>Link Expired or Invalid</h2>
            <p>This password reset link is invalid or has already expired.</p>
            <a href='forgotpass.php'>Click here to request a new one</a>
         </div>");
}

// Kapag pinindot ang Update Password button
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_btn'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $msg = "<div class='alert alert-danger'>Passwords do not match!</div>";
    } else {
        // I-hash ang bagong password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // I-update sa database at linisin ang token para hindi na magamit ulit
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expire = NULL WHERE reset_token = ?");
        $update->bind_param("ss", $hashed_password, $token);
        
        if ($update->execute()) {
            $msg = "<div class='alert alert-success'>Password updated successfully! <br><br> <a href='login.php' class='btn btn-success btn-sm'>Click here to Login</a></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Something went wrong.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
      body { background: #000; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;}
      .glass-card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; color: white; border: 1px solid rgba(255,255,255,0.2); width: 100%; max-width: 500px; z-index: 10;}
      .form-control-glass { background: rgba(255, 255, 255, 0.2); border: none; color: white; }
      .form-control-glass:focus { background: rgba(255, 255, 255, 0.3); color: white; box-shadow: none;}
  </style>
</head>
<body>
    <img src="img/bg.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: 1;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-75" style="z-index: 2;"></div>
    
    <div class="glass-card">
        <h3 class="mb-4 text-center fw-bold" style="color: #12aa42;">Create New Password</h3>
        
        <?php echo $msg; ?>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label class="small mb-1">New Password</label>
                <input type="password" name="new_password" class="form-control form-control-glass py-2" required>
            </div>
            <div class="mb-4">
                <label class="small mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control form-control-glass py-2" required>
            </div>
            <button type="submit" name="update_btn" class="btn w-100 text-white fw-bold py-2" style="background-color: #12aa42;">Update Password</button>
        </form>
    </div>
</body>
</html>