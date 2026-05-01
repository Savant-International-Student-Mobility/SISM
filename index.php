<?php
require 'db.php'; // Siguraduhin na tama ang path ng db.php

// 1. Kunin ang Site Settings (Hero, About, Footer)
$settings_res = $conn->query("SELECT * FROM site_settings WHERE id=1");
$settings = $settings_res->fetch_assoc();

// 2. Kunin ang lahat ng Services
$services_res = $conn->query("SELECT * FROM services ORDER BY id ASC");

// 3. Simulan ang session para ma-detect kung may nakalog-in na user
session_start();
$isLoggedIn = isset($_SESSION['user_id']); // Magiging true ito kung naka-login
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($settings['hero_title']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --savant-primary-green: #12aa42;
      --savant-dark-green: #087830;
    }

    * {
      font-family: 'Poppins', sans-serif !important;
    }

    body {
      background-color: #000;
      overflow-x: hidden;
    }

    .hero-section {
      height: 100vh;
      position: relative;
    }

    .glass-effect {
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: 30px;
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }

    .flag-container {
      transition: all 0.4s ease;
      cursor: pointer;
    }

    .flag-container:hover {
      transform: translateY(-10px);
    }

    .flag-icon-circle {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 10px;
      transition: transform 0.3s ease;
    }

    .flag-container:hover .flag-icon-circle {
      transform: scale(1.1) rotate(5deg);
    }

    .btn-savant {
      background-color: var(--savant-primary-green) !important;
      transition: transform 0.2s ease;
    }

    .btn-savant:hover {
      transform: scale(1.05);
      filter: brightness(1.2);
    }

    .nav-shadow {
      text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5);
    }

    .section-padding {
      padding: 100px 0;
    }

    .service-card {
      background: white;
      padding: 40px;
      border-radius: 20px;
      text-align: center;
      height: 100%;
      transition: all 0.3s ease;
      border: 1px solid #eee;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
      border-color: var(--savant-primary-green);
    }

    .icon-circle {
      width: 70px;
      height: 70px;
      background: #f0fdf4;
      color: var(--savant-dark-green);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      border-radius: 50%;
      margin: 0 auto 20px;
      transition: 0.3s;
    }

    .service-card:hover .icon-circle {
      background: var(--savant-primary-green);
      color: white;
    }

    @media (max-width: 991.98px) {
      .navbar-collapse {
        position: absolute;
        right: 10px;
        top: 60px;
        width: 220px;
        z-index: 1000;
      }
      .mobile-nav-bg {
        background: rgba(255, 255, 255, 0.15) !important;
        backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        padding: 15px !important;
      }
      .nav-item .nav-link {
        font-size: 0.9rem !important;
        padding: 8px 0 !important;
        color: rgba(255, 255, 255, 0.9) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }
      .nav-item:last-child .nav-link {
        border-bottom: none;
      }
    }
  </style>
</head>

<body>
  <div class="hero-section">
    <!-- VIDEO FROM DATABASE (uploads/ folder) -->
    <video autoplay muted loop playsinline class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: -2;">
      <source src="uploads/<?php echo $settings['hero_video']; ?>" type="video/mp4">
      Your browser does not support the video tag.
    </video>
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50" style="z-index: -1;"></div>

    <div class="d-flex flex-column h-100 position-relative">

      <!-- DESKTOP HEADER -->
      <header class="d-none d-lg-flex position-absolute w-100 justify-content-center align-items-center px-5" style="top: 40px; min-height: 100px; z-index: 10;">
        <div class="position-absolute start-0 ms-5">
          <a href="index.php">
            <img src="img/logo.png" style="height: 110px; width: auto;" alt="SAVANT Logo">
          </a>
        </div>
        <div class="glass-effect rounded-pill py-3 px-5">
          <ul class="navbar-nav flex-row align-items-center">
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#about">About</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#services">Services Offered</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="blogs.html">Blogs</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#partners">Partner</a></li>
            <li class="nav-item"><a class="nav-link text-white nav-shadow mx-3 fs-6 fw-normal" href="#contacts">Contacts</a></li>
          </ul>
        </div>
        <div class="position-absolute end-0 me-5">
          <!-- PHP Logic: Change icon link based on login status -->
          <?php if($isLoggedIn): ?>
            <a href="admin_dashboard.php" class="d-flex align-items-center justify-content-center bg-white rounded-circle text-decoration-none shadow-sm" style="width: 55px; height: 55px;" title="Go to Admin Panel">
              <i class="bi bi-person-check-fill fs-4 text-success"></i>
            </a>
          <?php else: ?>
            <a href="login.php" class="d-flex align-items-center justify-content-center bg-white rounded-circle text-decoration-none shadow-sm" style="width: 55px; height: 55px;" title="Login / Register">
              <i class="bi bi-person fs-4 text-success"></i>
            </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- MOBILE HEADER -->
      <nav class="navbar navbar-dark d-lg-none position-absolute w-100 px-3" style="top: 10px; z-index: 100;">
        <div class="container-fluid">
          <a class="navbar-brand" href="index.php">
            <img src="img/flogo.png" style="height: 80px; width: auto;" alt="SAVANT Logo">
          </a>
          <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
            <i class="bi bi-list fs-1 text-white"></i>
          </button>
          <div class="collapse navbar-collapse mt-3 p-3 rounded-4 mobile-nav-bg" id="mobileMenu">
            <ul class="navbar-nav text-center">
              <li class="nav-item py-2"><a class="nav-link text-white" href="#about">About</a></li>
              <li class="nav-item py-2"><a class="nav-link text-white" href="#services">Services Offered</a></li>
              <li class="nav-item py-2"><a class="nav-link text-white" href="blogs.html">Blogs</a></li>
              <li class="nav-item py-2"><a class="nav-link text-white" href="#partners">Partner</a></li>
              <li class="nav-item py-2"><a class="nav-link text-white" href="#contacts">Contacts</a></li>
              <li class="nav-item py-2">
                <!-- PHP Logic: Change Mobile Button based on login status -->
                <?php if($isLoggedIn): ?>
                    <a href="admin_dashboard.php" class="btn btn-savant text-white w-100 rounded-pill">Admin Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-savant text-white w-100 rounded-pill">Login / Register</a>
                <?php endif; ?>
              </li>
            </ul>
          </div>
        </div>
      </nav>

      <!-- HERO CONTENT FROM DATABASE -->
      <main class="container flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center text-white pt-5">
        <h1 class="display-5 fw-bolder text-uppercase mb-0" style="text-shadow: 2px 2px 15px rgba(0,0,0,0.4); letter-spacing: 1px;">
          <?php echo htmlspecialchars($settings['hero_title']); ?>
        </h1>
        <p class="fs-5 fst-italic mt-2 mb-4 opacity-75"><?php echo htmlspecialchars($settings['hero_subtitle']); ?></p>
        <a href="#about" class="btn btn-savant text-white px-5 py-3 rounded-pill fw-semibold shadow-lg fs-5 border-0 text-decoration-none">
          Get Started
        </a>
      </main>
    </div>
  </div>

  <section id="about" class="section-padding bg-light">
    <div class="container">
      <div class="glass-card p-5 shadow-sm">
        <div class="row g-5 align-items-center">
          <div class="col-lg-6">
            <h3 class="fw-bold text-uppercase mb-2" style="letter-spacing: 2px; color: var(--savant-primary-green);">About Savant</h3>
            <p class="lead fw-normal text-dark fs-5"><?php echo nl2br(htmlspecialchars($settings['about_text'])); ?></p>
          </div>
          <div class="col-lg-6">
            <div class="p-4 bg-white rounded-4 mb-4 shadow-sm border-start border-4 border-success">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-rocket-takeoff fs-4 me-3" style="color: var(--savant-primary-green);"></i>
                <h5 class="fw-bold mb-0" style="color: var(--savant-primary-green);">Our Mission</h5>
              </div>
              <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($settings['mission_text'])); ?></p>
            </div>
            <div class="p-4 bg-white rounded-4 shadow-sm border-start border-4 border-success">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-eye fs-4 me-3" style="color: var(--savant-primary-green);"></i>
                <h5 class="fw-bold mb-0" style="color: var(--savant-primary-green);">Our Vision</h5>
              </div>
              <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($settings['vision_text'])); ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES SECTION (DYNAMIC LOOP) -->
  <section id="services" class="section-padding bg-white">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="fw-bold text-uppercase display-6" style="color: var(--savant-primary-green);">Services Offered</h2>
        <div class="mx-auto bg-success mt-3" style="height: 4px; width: 80px; border-radius: 2px;"></div>
      </div>
      <div class="row g-4">
        <?php while($srv = $services_res->fetch_assoc()): ?>
        <div class="col-md-4">
          <div class="service-card shadow-sm">
            <div class="icon-circle"><i class="bi <?php echo $srv['icon']; ?>"></i></div>
            <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($srv['title']); ?></h5>
            <p class="text-muted"><?php echo htmlspecialchars($srv['description']); ?></p>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

  <section id="partners" class="section-padding bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="fw-bold text-uppercase display-6" style="color: var(--savant-primary-green);">Our Partners</h2>
        <div class="mx-auto bg-success mt-3" style="height: 4px; width: 80px; border-radius: 2px;"></div>
        <p class="text-muted mt-4 mx-auto" style="max-width: 700px;">
          Empowering student success through our strategic alliances with world-class academic institutions and pathway providers.
        </p>
      </div>

      <div class="row g-4 justify-content-center row-cols-2 row-cols-md-3 row-cols-lg-5">
        <div class="col"><div class="glass-card flag-container p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center shadow-sm border-0"><img src="https://flagcdn.com/w160/ca.png" class="flag-icon-circle" alt="Canada"><div class="fw-bold text-dark" style="font-size: 0.95rem;">Canada</div></div></div>
        <div class="col"><div class="glass-card flag-container p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center shadow-sm border-0"><img src="https://flagcdn.com/w160/au.png" class="flag-icon-circle" alt="Australia"><div class="fw-bold text-dark" style="font-size: 0.95rem;">Australia</div></div></div>
        <div class="col"><div class="glass-card flag-container p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center shadow-sm border-0"><img src="https://flagcdn.com/w160/us.png" class="flag-icon-circle" alt="USA"><div class="fw-bold text-dark" style="font-size: 0.95rem;">USA</div></div></div>
        <div class="col"><div class="glass-card flag-container p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center shadow-sm border-0"><img src="https://flagcdn.com/w160/gb.png" class="flag-icon-circle" alt="UK"><div class="fw-bold text-dark" style="font-size: 0.95rem;">United Kingdom</div></div></div>
        <div class="col"><div class="glass-card flag-container p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center shadow-sm border-0"><img src="https://flagcdn.com/w160/eu.png" class="flag-icon-circle" alt="EU"><div class="fw-bold text-dark" style="font-size: 0.95rem;">European Union</div></div></div>
      </div>
    </div>
  </section>

  <section id="contacts" class="section-padding bg-white">
    <div class="container">
      <div class="glass-card shadow-lg overflow-hidden border-0" style="background: #f8f9fa;">
        <div class="row g-0">
          <div class="col-lg-7 p-4 p-md-5 bg-white">
            <h2 class="fw-bold mb-4" style="color: #1a1a1a;">Send us a Message</h2>
            <form>
              <div class="mb-4">
                <label class="form-label fw-semibold small text-muted text-uppercase">Full Name</label>
                <input type="text" class="form-control form-control-lg border-0 bg-light rounded-3" placeholder="Full Name" style="font-size: 0.95rem;">
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold small text-muted text-uppercase">Email Address</label>
                <input type="email" class="form-control form-control-lg border-0 bg-light rounded-3" placeholder="example@gmail.com" style="font-size: 0.95rem;">
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold small text-muted text-uppercase">Message</label>
                <textarea class="form-control border-0 bg-light rounded-3" rows="4" placeholder="How can we help you achieve your global ambitions?" style="font-size: 0.95rem;"></textarea>
              </div>
              <button type="submit" class="btn btn-savant text-white w-100 py-3 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-2">
                Send Message <i class="bi bi-send-fill"></i>
              </button>
            </form>
          </div>

          <div class="col-lg-5 p-4 p-md-5 d-flex flex-column justify-content-center" style="border-left: 1px solid #eee;">
            <h2 class="fw-bold mb-4" style="color: #1a1a1a;">Connect with Us</h2>
            <p class="text-muted mb-5">Have questions about our programs or partnerships? We are here to help you prepare for success.</p>

            <div class="d-flex align-items-center mb-4">
              <div class="icon-circle m-0 me-3 shadow-sm" style="width: 50px; height: 50px; background: #e7f5ec;">
                <i class="bi bi-telephone-fill" style="font-size: 1.2rem;"></i>
              </div>
              <div>
                <div class="small text-muted fw-bold text-uppercase">Contact Number</div>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($settings['contact_phone']); ?></div>
              </div>
            </div>

            <div class="d-flex align-items-center mb-5">
              <div class="icon-circle m-0 me-3 shadow-sm" style="width: 50px; height: 50px; background: #e7f5ec;">
                <i class="bi bi-envelope-at-fill" style="font-size: 1.2rem;"></i>
              </div>
              <div>
                <div class="small text-muted fw-bold text-uppercase">Email</div>
                <div class="fw-bold"><?php echo htmlspecialchars($settings['contact_email']); ?></div>
              </div>
            </div>

            <div class="mt-auto">
              <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 1px;">Social Media</h6>
              <div class="d-flex gap-3">
                <a href="<?php echo htmlspecialchars($settings['contact_facebook']); ?>" target="_blank" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-facebook text-success fs-5"></i></a>
                <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-envelope text-success fs-5"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="py-5 text-center" style="background-color: #085723;">
    <div class="container">
      <img src="img/logo.png" style="height: 120px; width: auto;" class="mb-4" alt="SAVANT Logo">
      <p class="text-white small mb-0"><?php echo htmlspecialchars($settings['footer_copyright']); ?></p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>