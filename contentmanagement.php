<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 

// SECURITY CHECK: Dapat naka-login at dapat 'admin' ang role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['firstname'];
$status_msg = "";

// --- 1. HANDLING FORM SUBMISSIONS (MySQLi logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // UPDATE HERO SECTION
    if (isset($_POST['update_hero'])) {
        $title = $_POST['hero_title'];
        $subtitle = $_POST['hero_subtitle'];
        if (!empty($_FILES['hero_video']['name'])) {
            $video_name = time() . '_' . $_FILES['hero_video']['name'];
            move_uploaded_file($_FILES['hero_video']['tmp_name'], "uploads/" . $video_name);
            $stmt = $conn->prepare("UPDATE site_settings SET hero_title=?, hero_subtitle=?, hero_video=? WHERE id=1");
            $stmt->bind_param("sss", $title, $subtitle, $video_name);
        } else {
            $stmt = $conn->prepare("UPDATE site_settings SET hero_title=?, hero_subtitle=? WHERE id=1");
            $stmt->bind_param("ss", $title, $subtitle);
        }
        $stmt->execute();
        $status_msg = "Hero Section updated successfully!";
    }

    // UPDATE ABOUT / MISSION / VISION
    if (isset($_POST['update_about'])) {
        $stmt = $conn->prepare("UPDATE site_settings SET about_text=?, mission_text=?, vision_text=? WHERE id=1");
        $stmt->bind_param("sss", $_POST['about_text'], $_POST['mission_text'], $_POST['vision_text']);
        $stmt->execute();
        $status_msg = "About section updated!";
    }

    // UPDATE FOOTER / CONTACT
    if (isset($_POST['update_footer'])) {
        $stmt = $conn->prepare("UPDATE site_settings SET contact_phone=?, contact_email=?, contact_facebook=?, footer_copyright=? WHERE id=1");
        $stmt->bind_param("ssss", $_POST['contact_phone'], $_POST['contact_email'], $_POST['contact_facebook'], $_POST['footer_copyright']);
        $stmt->execute();
        $status_msg = "Contact details updated!";
    }

    // SERVICES: ADD NEW
    if (isset($_POST['add_service'])) {
        $conn->query("INSERT INTO services (icon, title, description) VALUES ('bi-gear', 'New Service', 'Description here')");
        $status_msg = "New service slot added!";
    }

    // SERVICES: UPDATE ALL
    if (isset($_POST['save_services'])) {
        foreach ($_POST['srv'] as $id => $data) {
            $stmt = $conn->prepare("UPDATE services SET icon=?, title=?, description=? WHERE id=?");
            $stmt->bind_param("sssi", $data['icon'], $data['title'], $data['description'], $id);
            $stmt->execute();
        }
        $status_msg = "Services updated!";
    }

    // SERVICES: DELETE
    if (isset($_POST['delete_service'])) {
        $id = $_POST['service_id'];
        $conn->query("DELETE FROM services WHERE id=$id");
        $status_msg = "Service removed!";
    }
}

// --- 2. FETCH DATA FROM DATABASE ---
$settings_res = $conn->query("SELECT * FROM site_settings WHERE id=1");
$settings = $settings_res->fetch_assoc();
$services_res = $conn->query("SELECT * FROM services ORDER BY id ASC");
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Savant - Content Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    /* --- ORIGINAL CSS FROM YOUR DASHBOARD --- */
    :root {
      --savant-primary-green: #087830;
      --savant-dark-green: #087830;
      --savant-text-dark: #333333;
      --savant-text-light: #525252;
      --savant-card-bg: #FFFFFF;
      --savant-light-gray: #E8E8E8;
      --savant-bg-light: #F7F7F7;
      --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      --savant-button-maroon: #085723;
    }

    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow-x: hidden; margin: 0; }
    .main-wrapper { display: flex; min-height: 100vh; }

    /* SIDEBAR (EXACT COPY) */
    .sidebar {
      background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0;
      box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden;
      position: fixed; top: 0; left: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031;
    }
    .sidebar:hover { width: 280px; }
    .sidebar .logo { font-size: 1.8rem; font-weight: 700; margin-bottom: 30px; text-align: center; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; }
    .sidebar:hover .logo { opacity: 1; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 25px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease; text-decoration: none; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #14552b; }
    .sidebar .nav-link i { font-size: 1.4rem; min-width: 20px; text-align: center; }
    .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; }
    .sidebar:hover .nav-link span { opacity: 1; }

    /* CONTENT AREA & HEADER (EXACT COPY) */
    .content-area { flex-grow: 1; margin-left: 70px; min-height: 100vh; display: flex; flex-direction: column; }
    .header { background-color: var(--savant-bg-light); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; }
    .user-status .badge { background-color: var(--savant-button-maroon); padding: 5px 15px; border-radius: 20px; color: white; }
    .power-btn i { color: #dc3545; font-size: 1.5rem; }
    .main-content { padding: 30px; }
    .admin-card { background-color: var(--savant-card-bg); border: none; border-radius: 12px; box-shadow: var(--savant-shadow); padding: 25px; margin-bottom: 25px; }

    /* CMS TABS STYLE */
    .nav-pills .nav-link { color: var(--savant-text-light); font-weight: 500; border-radius: 8px; padding: 12px 20px; margin-bottom: 5px; text-align: left;}
    .nav-pills .nav-link.active { background-color: var(--savant-primary-green) !important; color: white !important; }
    .btn-savant-save { background-color: var(--savant-primary-green); color: white; border: none; padding: 10px 30px; font-weight: 600; border-radius: 8px; }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <!-- SIDEBAR (GINAYA SA DASHBOARD) -->
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="usermanagement.php"><i class="bi bi-people-fill"></i><span>Users Management</span></a>
        <a class="nav-link" href="applicationmanagement.php"><i class="bi bi-display"></i><span>Application Management</span></a>
        <a class="nav-link" href="chatmanagement.php"><i class="bi bi-chat-dots-fill"></i><span>Chat Management</span></a>
        <a class="nav-link" href="admin_booking.php"><i class="bi bi-file-earmark-text"></i><span>Booking Management</span></a>
        <a class="nav-link" href="profilemanagement.php"><i class="bi bi-calendar-event"></i><span>Profile Management</span></a>
        <a class="nav-link active" href="contentmanagement.php"><i class="bi bi-pencil-square"></i><span>Content Management</span></a>
      </nav>
      <div class="mt-auto text-center pb-4" style="opacity: 0.5; font-size: 0.7rem;">&copy; 2026 SAVANT</div>
    </aside>

    <div class="content-area">
      <!-- HEADER (GINAYA SA DASHBOARD) -->
      <div class="header">
        <div class="header-brand d-flex align-items-center">
          <span class="header-title">Savant-International Student Mobility</span>
        </div>
        <div class="user-status d-flex align-items-center gap-2">
          <div class="me-3 d-none d-lg-block fw-medium"><?php echo date('F j, Y'); ?></div>
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
              <i class="bi bi-power"></i>
          </a>
          <span class="badge"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
      </div>

      <main class="main-content">
        <!-- STATUS MESSAGE -->
        <?php if($status_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $status_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0" style="color: var(--savant-primary-green);">Content Management</h3>
            <a href="index.php" target="_blank" class="btn btn-sm btn-outline-dark text-white fw-bold px-3" style="background-color: #087830">
                <i class="bi bi-eye me-1"></i> View Website
            </a>
        </div>

        <div class="row">
            <!-- Navigation Tabs -->
            <div class="col-lg-3">
                <div class="admin-card p-3">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#hero" type="button"><i class="bi bi-camera-reels me-2"></i> Hero Section</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#about" type="button"><i class="bi bi-info-circle me-2"></i> About & Mission</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#services" type="button"><i class="bi bi-briefcase me-2"></i> Services</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer" type="button"><i class="bi bi-telephone me-2"></i> Footer & Contact</button>
                    </div>
                </div>
            </div>

            <!-- Forms -->
            <div class="col-lg-9">
                <div class="tab-content">
                    
                    <!-- HERO SECTION -->
                    <div class="tab-pane fade show active" id="hero">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4">Hero Section Settings</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Headline Title</label>
                                    <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($settings['hero_title'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sub-Headline Quote</label>
                                    <input type="text" name="hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Background Video</label>
                                    <input type="file" name="hero_video" class="form-control">
                                    <small class="text-muted">Current: <?php echo $settings['hero_video'] ?? 'vid.mp4'; ?></small>
                                </div>
                                <button type="submit" name="update_hero" class="btn btn-savant-save">Update Hero</button>
                            </form>
                        </div>
                    </div>

                    <!-- ABOUT SECTION -->
                    <div class="tab-pane fade" id="about">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4">About, Mission & Vision</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">About Savant Description</label>
                                    <textarea name="about_text" class="form-control" rows="4"><?php echo htmlspecialchars($settings['about_text'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mission Statement</label>
                                    <textarea name="mission_text" class="form-control" rows="3"><?php echo htmlspecialchars($settings['mission_text'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Vision Statement</label>
                                    <textarea name="vision_text" class="form-control" rows="3"><?php echo htmlspecialchars($settings['vision_text'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="update_about" class="btn btn-savant-save">Update About Section</button>
                            </form>
                        </div>
                    </div>

                    <!-- SERVICES SECTION -->
                    <div class="tab-pane fade" id="services">
                        <div class="admin-card">
                            <div class="d-flex justify-content-between mb-4">
                                <h5 class="fw-bold">Services Offered</h5>
                                <form method="POST"><button type="submit" name="add_service" class="btn btn-sm btn-success">+ Add Service</button></form>
                            </div>
                            <form method="POST">
                                <?php while($srv = $services_res->fetch_assoc()): ?>
                                <div class="p-3 border rounded mb-3 bg-light position-relative">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label fw-bold small">Icon (Bootstrap Class)</label>
                                            <input type="text" name="srv[<?php echo $srv['id']; ?>][icon]" class="form-control" value="<?php echo $srv['icon']; ?>">
                                        </div>
                                        <div class="col-md-8 mb-2">
                                            <label class="form-label fw-bold small">Service Title</label>
                                            <input type="text" name="srv[<?php echo $srv['id']; ?>][title]" class="form-control" value="<?php echo htmlspecialchars($srv['title']); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-bold small">Description</label>
                                            <textarea name="srv[<?php echo $srv['id']; ?>][description]" class="form-control" rows="2"><?php echo htmlspecialchars($srv['description']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-2">
                                        <input type="hidden" name="service_id" value="<?php echo $srv['id']; ?>">
                                        <button type="submit" name="delete_service" class="btn btn-sm btn-danger" onclick="return confirm('Delete this service?')">Remove</button>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <button type="submit" name="save_services" class="btn btn-savant-save">Save All Services</button>
                            </form>
                        </div>
                    </div>

                    <!-- FOOTER SECTION -->
                    <div class="tab-pane fade" id="footer">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4">Contact Details & Footer</h5>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Phone Number</label>
                                        <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-bold">Facebook Page Link</label>
                                        <input type="text" name="contact_facebook" class="form-control" value="<?php echo htmlspecialchars($settings['contact_facebook'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-bold">Copyright Text</label>
                                        <input type="text" name="footer_copyright" class="form-control" value="<?php echo htmlspecialchars($settings['footer_copyright'] ?? ''); ?>">
                                    </div>
                                </div>
                                <button type="submit" name="update_footer" class="btn btn-savant-save">Update Footer</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
      </main>
    </div>
  </div>

  <!-- LOGOUT MODAL (EXACT COPY) -->
  <div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Are you sure you want to log out from the Admin Panel?</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="logout.php" class="btn btn-danger px-4">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>