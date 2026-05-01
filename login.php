<?php
session_start();
require 'db.php'; 
$msg = '';

// Kapag may naka-login na, i-check kung admin o user
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_btn'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Kukunin natin pati ang 'role' column sa database
    $stmt = $conn->prepare("SELECT id, firstname, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // I-verify ang password
        if (password_verify($password, $user['password'])) {
            
            // I-save sa session ang details
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['role'] = $user['role']; // Importante ito!
            
            // I-REDIRECT BASE SA ROLE NIYA
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'>Incorrect password! Please try again.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'>Account not found with this email!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAVANT - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --savant-primary-green: #087830; --savant-accent-green: #12aa42; --savant-hover-green: #146c3a; }
    * { font-family: 'Poppins', sans-serif !important; }
    body, html { height: 100%; margin: 0; overflow: hidden; }
    .main-container { position: relative; height: 100vh; width: 100%; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .btn-back-circle { position: fixed; top: 30px; left: 30px; width: 50px; height: 50px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; z-index: 100; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
    .btn-back-circle:hover { background: rgba(255, 255, 255, 0.2); color: var(--savant-accent-green); transform: scale(1.1); }
    .glass-card { background: rgba(47, 45, 45, 0.1); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 30px; padding: 60px; width: 100%; max-width: 1100px; color: white; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
    .form-control-glass { background: rgba(255, 255, 255, 0.2) !important; border: none !important; color: white !important; padding: 12px 20px; border-radius: 8px; }
    .form-control-glass::placeholder { color: rgba(255, 255, 255, 0.6); }
    .btn-login { background-color: var(--savant-accent-green); border: none; padding: 12px; font-weight: 500; border-radius: 8px; transition: background 0.3s ease; }
    .btn-login:hover { background-color: var(--savant-hover-green); }
    .login-links a { color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem; }
    .login-links a:hover { color: white; }
    @media (max-width: 768px) { .btn-back-circle { top: 20px; left: 20px; width: 40px; height: 40px; } .glass-card { padding: 40px 20px; } }
  </style>
</head>
<body>
  <a href="index.php" class="btn-back-circle" title="Back to Home"><i class="bi bi-chevron-left fs-4"></i></a>
  <div class="main-container">
    <img src="img/bg.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: -2;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50" style="z-index: -1;"></div>
    <div class="glass-card">
      <div class="row align-items-center">
        <div class="col-lg-6 d-flex align-items-center mb-5 mb-lg-0 justify-content-center">
          <img src="img/flogo.png" alt="SAVANT Logo" style="height: 200px; width: auto;">
        </div>
        <div class="col-lg-6">
          <h3 class="fw-semibold mb-1">Login to your Account</h3>
          <p class="small mb-4 text-white-50">Welcome! Please enter your email address</p>
          <?php echo $msg; ?>
          <form action="login.php" method="POST">
            <div class="mb-3">
              <input type="email" name="email" class="form-control form-control-glass" placeholder="Email" required>
            </div>
            <div class="mb-3">
              <input type="password" name="password" class="form-control form-control-glass" placeholder="Password" required>
            </div>
            <button type="submit" name="login_btn" class="btn btn-login w-100 text-white mb-3">Login</button>
            <div class="d-flex justify-content-between login-links">
              <a href="register.php">Don't have an account?</a>
              <a href="forgotpass.php">Forgot Password?</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>