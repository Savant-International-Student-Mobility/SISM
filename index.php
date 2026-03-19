<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAVANT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

  <style>
    * {
      font-family: 'Poppins', sans-serif !important;
    }

    .glass-effect {
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      background: rgba(183, 176, 176, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-dark-green {
      background-color: #164a13 !important;
      transition: transform 0.2s ease;
    }

    .btn-dark-green:hover {
      transform: scale(1.05);
      filter: brightness(1.2);
    }

    .nav-shadow {
      text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5);
    }
  </style>
</head>

<body class="vh-100 overflow-hidden bg-black">
  <div class="position-relative vh-100 w-100">
    <img src="img/bg.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
      style="z-index: -2;">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50" style="z-index: -1;"></div>
    <div class="d-flex flex-column h-100 position-relative">
      <header class="d-none d-lg-flex position-absolute w-100 justify-content-center align-items-center px-5"
        style="top: 40px; min-height: 100px; z-index: 10;">
        <div class="position-absolute start-0 ms-5">
          <a href="index.html">
            <img src="img/logo.png" style="height: 100px; width: auto;">
          </a>
        </div>
        <div class="glass-effect rounded-pill py-3 px-5">
          <ul class="navbar-nav flex-row align-items-center">
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#about">About</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#services">Services Offered</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#blogs">Blogs</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#partners">Partner</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#contacts">Contacts</a></li>
          </ul>
        </div>
        <div class="position-absolute end-0 me-5">
          <a href="register.php"
            class="d-flex align-items-center justify-content-center bg-white rounded-circle text-decoration-none shadow-sm"
            style="width: 55px; height: 55px;">
            <i class="bi bi-person fs-4 text-success"></i>
          </a>
        </div>
      </header>
      <main
        class="container flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center text-white pt-5">
        <h1 class="display-5 fw-bolder text-uppercase mb-0"
          style="text-shadow: 2px 2px 15px rgba(0,0,0,0.4); letter-spacing: 1px;">
          Savant-International Student Mobility
        </h1>
        <p class="fs-5 fst-italic mt-2 mb-4 opacity-75">"Where Ambition Meets Opportunity"</p>
        <button class="btn btn-dark-green text-white px-5 py-3 rounded-pill fw-semibold shadow-lg fs-5 border-0">
          Get Started
        </button>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>