<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAVANT - Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

  <style>
    * {
      font-family: 'Poppins', sans-serif !important;
    }

    body,
    html {
      height: 100%;
      margin: 0;
      overflow: hidden;
    }

    .main-container {
      position: relative;
      height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    /* Back Button */
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
      color: #087830;
      transform: scale(1.1);
    }

    /* Glass Card */
    .glass-card {
      background: rgba(47, 45, 45, 0.1);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 30px;
      padding: 60px;
      width: 100%;
      max-width: 1100px;
      color: white;
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }

    .form-control-glass {
      background: rgba(255, 255, 255, 0.2) !important;
      border: none !important;
      color: white !important;
      padding: 12px 20px;
      border-radius: 8px;
    }

    .form-control-glass::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    .btn-action {
      background-color: #087830;
      border: none;
      padding: 12px;
      font-weight: 500;
      border-radius: 8px;
      transition: background 0.3s ease;
    }

    .btn-action:hover {
      background-color: #146c3a;
    }

    .footer-links a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-size: 0.9rem;
    }

    .footer-links a:hover {
      color: white;
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
  <a href="login.html" class="btn-back-circle" title="Back to Login">
    <i class="bi bi-chevron-left fs-4"></i>
  </a>

  <div class="main-container">
    <img src="img/bg.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: -2;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50" style="z-index: -1;"></div>

    <div class="glass-card">
      <div class="row align-items-center">
        <div class="col-lg-6 d-flex align-items-center mb-5 mb-lg-0 justify-content-center">
          <img src="img/logo.png" alt="Logo" style="width: 400px; height: auto;">
        </div>

        <div class="col-lg-6">
          <h3 class="fw-semibold mb-1">Forgot Password?</h3>
          <p class="small mb-4 text-white-50">No worries! Enter your email and we'll send you reset instructions.</p>

          <form action="#">
            <div class="mb-4">
              <label class="form-label small text-white-50">Email Address</label>
              <input type="email" class="form-control form-control-glass" placeholder="name@gmail.com" required>
            </div>

            <button type="submit" class="btn btn-action w-100 text-white mb-4">Send Code</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>