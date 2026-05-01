<?php
session_start();
require 'db.php'; // Kinukuha ang database connection
$msg = '';

// Kapag pinindot ang CREATE ACCOUNT button
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_btn'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check kung magkaparehas ang password
    if ($password !== $confirm_password) {
        $msg = "<div class='alert alert-danger shadow-sm'>Passwords do not match!</div>";
    } else {
        // 2. Check kung nagamit na ang email na ito
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $msg = "<div class='alert alert-warning shadow-sm'>Email is already registered! <a href='login.php' class='alert-link'>Login here</a></div>";
        } else {
            // 3. I-encrypt/Hash ang password para secured sa database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 4. I-save ang data sa database
            $insert = $conn->prepare("INSERT INTO users (firstname, lastname, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $firstname, $lastname, $email, $phone, $hashed_password);
            
            if ($insert->execute()) {
                $msg = "<div class='alert alert-success shadow-sm'>Registration successful! You can now <a href='login.php' class='alert-link'>Login</a>.</div>";
            } else {
                $msg = "<div class='alert alert-danger shadow-sm'>Something went wrong. Please try again.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAVANT - Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --savant-primary-green: #087830;
      --savant-accent-green: #12aa42;
      --savant-hover-green: #146c3a;
    }

    * {
      font-family: 'Poppins', sans-serif !important;
    }

    body, html {
      height: 100%;
      margin: 0;
      overflow-x: hidden;
    }

    .main-container {
      position: relative;
      min-height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 50px 20px;
    }

    .btn-back-circle {
      position: fixed;
      top: 30px;
      left: 30px;
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-decoration: none;
      z-index: 100;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-back-circle:hover {
      background: rgba(255, 255, 255, 0.2);
      color: var(--savant-accent-green);
      transform: scale(1.1);
    }

    .glass-card {
      background: rgba(47, 45, 45, 0.1);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 30px;
      padding: 60px;
      width: 100%;
      max-width: 1100px;
      color: white;
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }

    .form-control-glass {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      color: white !important;
      padding: 12px 15px;
      border-radius: 10px;
    }

    .btn-register {
      background-color: var(--savant-accent-green);
      border: none;
      padding: 14px;
      font-weight: 600;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .btn-register:hover {
      background-color: var(--savant-hover-green);
      transform: translateY(-2px);
    }

    .brand-logo {
      width: 300px;
      height: auto;
      margin-bottom: 20px;
    }

    @media (max-width: 768px) {
      .btn-back-circle {
        top: 20px;
        left: 20px;
        width: 40px;
        height: 40px;
      }
      .glass-card {
        padding: 40px 20px;
      }
    }
  </style>
</head>

<body>
  <!-- Na-update: index.html to index.php -->
  <a href="index.php" class="btn-back-circle" title="Go Back">
    <i class="bi bi-chevron-left fs-4"></i>
  </a>
  <div class="main-container">
    <img src="img/bg.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: -2;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50" style="z-index: -1;"></div>
    <div class="glass-card">
      <div class="row g-5 align-items-center">
        <div class="col-lg-5 text-center d-flex flex-column align-items-center justify-content-center">
          <img src="img/logo.png" class="brand-logo" style="height: 160px; width: auto;">
          <h1 class="fw-bold mb-2 display-6 w-100">Register Now</h1>
          <p class="mb-0 w-100">
            <span class="fst-italic small opacity-75">"Join Savant and unlock global opportunities"</span>
          </p>
        </div>
        <div class="col-lg-7">
          
          <!-- Dito lalabas ang Success / Error message -->
          <?php echo $msg; ?>

          <!-- Na-update: action="register.php" at method="POST" -->
          <form action="register.php" method="POST">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="small mb-1">First Name</label>
                <!-- Na-update: added name="firstname" -->
                <input type="text" name="firstname" class="form-control form-control-glass" placeholder="First name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="small mb-1">Last Name</label>
                <!-- Na-update: added name="lastname" -->
                <input type="text" name="lastname" class="form-control form-control-glass" placeholder="Last name" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="small mb-1">Email Address</label>
                <!-- Na-update: added name="email" -->
                <input type="email" name="email" class="form-control form-control-glass" placeholder="email@example.com" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="small mb-1">Phone Number</label>
                <!-- Na-update: added name="phone" -->
                <input type="tel" name="phone" class="form-control form-control-glass" placeholder="+63 9xx xxx xxxx" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="small mb-1">Password</label>
                <!-- Na-update: added name="password" -->
                <input type="password" name="password" class="form-control form-control-glass" required>
              </div>
              <div class="col-md-6 mb-4">
                <label class="small mb-1">Confirm Password</label>
                <!-- Na-update: added name="confirm_password" -->
                <input type="password" name="confirm_password" class="form-control form-control-glass" required>
              </div>
            </div>
            
            <!-- Na-update: added name="register_btn" -->
            <button type="submit" name="register_btn" class="btn btn-register w-100 text-white shadow-sm">
              CREATE ACCOUNT
            </button>
            <p class="text-center small mt-4 mb-0 text-white-50">
              <!-- Na-update: login.html to login.php -->
              Already have an account? <a href="login.php" class="text-white fw-bold text-decoration-none">Login here</a>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>