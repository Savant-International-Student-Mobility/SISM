<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 

// SECURITY CHECK: Dapat naka-login at 'admin' ang role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['firstname'];
$msg = '';

// ==========================================
// DELETE APPLICATION LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_app'])) {
    $del_id = $_POST['delete_id'];
    
    $del_stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $del_stmt->bind_param("i", $del_id);
    
    if ($del_stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4'>Application deleted successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4'>Failed to delete application. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// UPDATE APPLICATION STATUS & SEND NOTIFICATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $app_id = $_POST['app_id'];
    $new_status = $_POST['status'];
    
    // 1. I-update ang Status sa Database
    $upd_stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $upd_stmt->bind_param("si", $new_status, $app_id);
    
    if ($upd_stmt->execute()) {
        
        // 2. Kunin ang user_id ng nag-apply para mapadalhan natin ng notification
        $get_app = $conn->prepare("SELECT user_id, target_destination FROM applications WHERE id = ?");
        $get_app->bind_param("i", $app_id);
        $get_app->execute();
        $app_data = $get_app->get_result()->fetch_assoc();
        
        if ($app_data) {
            $app_user_id = $app_data['user_id'];
            $destination = $app_data['target_destination'];
            
            // I-setup ang laman ng Notification depende sa bagong status
            $notif_type = 'info';
            $notif_title = 'Application Update';
            $notif_message = "Your application to study in $destination has been updated to: $new_status.";
            
            if ($new_status == 'Approved') {
                $notif_type = 'approved';
                $notif_title = 'Application Approved! 🎉';
                $notif_message = "Great news! Your application to study in $destination has been approved by the Savant Team.";
            } elseif ($new_status == 'Rejected') {
                $notif_type = 'cancelled';
                $notif_title = 'Application Status';
                $notif_message = "We're sorry, but your application for $destination was rejected after review.";
            } elseif ($new_status == 'Under Review') {
                $notif_type = 'info';
                $notif_title = 'Application Under Review';
                $notif_message = "Your application for $destination is now under review. We will update you shortly.";
            }
            
            // 3. I-insert ang Notification sa Database
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $notif_stmt->bind_param("isss", $app_user_id, $notif_title, $notif_message, $notif_type);
            $notif_stmt->execute();
        }
        
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4'>Status updated to $new_status! The user has been notified. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4'>Failed to update status. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// FETCH ALL APPLICATIONS
// ==========================================
$stmt_apps = $conn->query("SELECT * FROM applications ORDER BY created_at DESC");
$applications = $stmt_apps->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" href="img/logoulit.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    /* [STAY AS IS: KINOPYA KO ANG CSS MO] */
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #085723; --savant-highlight-light-green: #98FB98; }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow-x: hidden; margin: 0; }
    .main-wrapper { display: flex; min-height: 100vh; }
    ::-webkit-scrollbar { width: 12px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #0C470C, #3BA43B); border-radius: 6px; } ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #023621, #2a7c2a); }
    .sidebar { background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0; box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden; position: fixed; top: 0; left: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031; }
    .sidebar:hover { width: 280px; }
    .sidebar .logo { font-size: 1.8rem; font-weight: 700; margin-bottom: 30px; text-align: center; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; }
    .sidebar:hover .logo { opacity: 1; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 25px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease; text-decoration: none; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #14552b; }
    .sidebar .nav-link i { font-size: 1.4rem; min-width: 20px; text-align: center; }
    .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; }
    .sidebar:hover .nav-link span { opacity: 1; }
    .content-area { flex-grow: 1; margin-left: 70px; min-height: 100vh; display: flex; flex-direction: column; }
    .header { background-color: var(--savant-bg-light); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; }
    .user-status .badge { background-color: var(--savant-button-maroon); padding: 5px 15px; border-radius: 20px; color: white; }
    .power-btn i { color: #dc3545; font-size: 1.5rem; }
    .main-content { padding: 30px; }
    .page-title { color: var(--savant-primary-green); font-weight: 700; margin-bottom: 25px; }
    .app-card { background-color: #FFFFFF; border-radius: 12px; padding: 25px; box-shadow: var(--savant-shadow); }
    .search-input { border-radius: 8px 0 0 8px; border: 1px solid #ced4da; }
    .search-btn { border-radius: 0 8px 8px 0; border: 1px solid #ced4da; border-left: none; background: #fff; color: var(--savant-text-light); }
    .table thead th { border-top: none; background-color: #fcfcfc; font-weight: 600; color: #333; padding: 15px; }
    .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
    .status-Pending { background-color: #ffeb3b; color: #333; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 500; }
    .status-Approved { background-color: #087830; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 500; }
    .status-Rejected, .status-Cancelled { background-color: #e74c3c; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 500; }
    .status-Under.Review { background-color: #0dcaf0; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 500; }
    .btn-action { border: none; border-radius: 4px; padding: 5px 8px; color: white; margin-right: 2px; transition: 0.2s;}
    .btn-download { background-color: #3498db; } .btn-download:hover{ background-color: #2980b9;}
    .btn-x { background-color: #e74c3c; } .btn-x:hover{background-color: #c0392b;}
    @media (max-width: 992px) { .content-area { margin-left: 0; } .sidebar { display: none; } }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="usermanagement.php"><i class="bi bi-people-fill"></i><span>Users Management</span></a>
        <a class="nav-link active" href="applicationmanagement.php"><i class="bi bi-display"></i><span>Application Management</span></a>
        <a class="nav-link" href="chatmanagement.php"><i class="bi bi-chat-dots-fill"></i><span>Chat Management</span></a>
        <a class="nav-link" href="admin_booking.php"><i class="bi bi-file-earmark-text"></i><span>Booking Management</span></a>
        <a class="nav-link" href="profilemanagement.php"><i class="bi bi-calendar-event"></i><span>Profile Management</span></a>
        <a class="nav-link" href="contentmanagement.php"><i class="bi bi-pencil-square"></i><span>Content Management</span></a>
      </nav>
      <div class="mt-auto text-center pb-4" style="opacity: 0.5; font-size: 0.7rem;">&copy; 2026 SAVANT</div>
    </aside>

    <div class="content-area">
      <div class="header">
        <div class="header-brand d-flex align-items-center">
          <span class="header-title">Savant-International Student Mobility | Admin</span>
        </div>
        <div class="user-status d-flex align-items-center gap-2">
          <div class="me-3 d-none d-lg-block fw-medium"><?php echo date('F j, Y'); ?></div>
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-power"></i></a>
          <span class="badge"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
      </div>

      <main class="main-content">
        <h2 class="page-title">Application Management</h2>
        
        <?php echo $msg; // PHP ALERTS ?>

        <div class="app-card">
          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold mb-0">Submitted Applications</h4>
            <div class="input-group" style="max-width: 400px;">
              <input type="text" class="form-control search-input" id="searchInput" placeholder="Search by ID, Name or Type...">
              <button class="btn search-btn"><i class="bi bi-search"></i></button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle" id="appTable">
              <thead>
                <tr class="text-secondary small">
                  <th>App ID</th>
                  <th>Applicant Name</th>
                  <th>Destination</th>
                  <th>Date Submitted</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                  
                <!-- PHP LOOP PARA SA DATABASE DATA -->
                <?php if(empty($applications)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No applications submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach($applications as $app): ?>
                        <tr>
                          <td class="fw-bold text-muted">APP-<?php echo str_pad($app['id'], 3, '0', STR_PAD_LEFT); ?></td>
                          <td class="fw-bold"><?php echo htmlspecialchars($app['full_name']); ?></td>
                          <td><?php echo htmlspecialchars($app['target_destination']); ?></td>
                          <td><?php echo date('Y-m-d', strtotime($app['created_at'])); ?></td>
                          
                          <!-- DYNAMIC STATUS BADGE -->
                          <td><span class="status-<?php echo str_replace(' ', '.', $app['status']); ?>"><?php echo $app['status']; ?></span></td>
                          
                          <td>
                            <!-- VIEW/EDIT BUTTON -->
                            <button class="btn-action btn-download" title="View Details"
                                data-bs-toggle="modal" 
                                data-bs-target="#manageAppModal"
                                data-id="<?php echo $app['id']; ?>"
                                data-name="<?php echo htmlspecialchars($app['full_name']); ?>"
                                data-email="<?php echo htmlspecialchars($app['email']); ?>"
                                data-phone="<?php echo htmlspecialchars($contact_number ?? $app['contact_number']); ?>"
                                data-educ="<?php echo htmlspecialchars($app['education_background']); ?>"
                                data-dest="<?php echo htmlspecialchars($app['target_destination']); ?>"
                                data-status="<?php echo $app['status']; ?>">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            
                            <!-- DELETE BUTTON -->
                            <form action="applicationmanagement.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this application?');">
                                <input type="hidden" name="delete_id" value="<?php echo $app['id']; ?>">
                                <button type="submit" name="delete_app" class="btn-action btn-x" title="Delete"><i class="bi bi-x-lg"></i></button>
                            </form>
                          </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- MANAGE APPLICATION MODAL -->
  <div class="modal fade" id="manageAppModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold" style="color: var(--savant-primary-green);">Manage Application</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="applicationmanagement.php" method="POST">
            <div class="modal-body">
              <input type="hidden" name="app_id" id="modal_app_id">
              
              <!-- Applicant Details (Readonly) -->
              <div class="mb-2"><strong>Name:</strong> <span id="modal_app_name" class="text-muted"></span></div>
              <div class="mb-2"><strong>Email:</strong> <span id="modal_app_email" class="text-muted"></span></div>
              <div class="mb-2"><strong>Contact:</strong> <span id="modal_app_phone" class="text-muted"></span></div>
              <div class="mb-2"><strong>Education:</strong> <span id="modal_app_educ" class="text-muted"></span></div>
              <div class="mb-4"><strong>Target Destination:</strong> <span id="modal_app_dest" class="text-muted"></span></div>
              
              <hr>
              
              <!-- Status Update Dropdown -->
              <div class="mb-3">
                  <label class="form-label fw-bold">Update Status:</label>
                  <select name="status" id="modal_app_status" class="form-select bg-light" required>
                      <option value="Pending">Pending</option>
                      <option value="Under Review">Under Review</option>
                      <option value="Approved">Approved</option>
                      <option value="Rejected">Rejected</option>
                  </select>
              </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="update_status" class="btn text-white fw-medium px-4" style="background-color: var(--savant-primary-green);">Save Status</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <!-- LOGOUT MODAL -->
  <div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-danger">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body pt-4 pb-4">Are you sure you want to log out from the Admin Panel?</div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Cancel</button>
          <a href="logout.php" class="btn btn-danger px-4">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        // SCRIPT PARA SA MANAGE MODAL
        const manageModal = document.getElementById('manageAppModal');
        if(manageModal) {
            manageModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                
                document.getElementById('modal_app_id').value = button.getAttribute('data-id');
                document.getElementById('modal_app_name').textContent = button.getAttribute('data-name');
                document.getElementById('modal_app_email').textContent = button.getAttribute('data-email');
                document.getElementById('modal_app_phone').textContent = button.getAttribute('data-phone');
                document.getElementById('modal_app_educ').textContent = button.getAttribute('data-educ');
                document.getElementById('modal_app_dest').textContent = button.getAttribute('data-dest');
                document.getElementById('modal_app_status').value = button.getAttribute('data-status');
            });
        }

        // SIMPLE SEARCH FILTER SCRIPT
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keyup', function() {
            let filter = searchInput.value.toLowerCase();
            let rows = document.querySelectorAll('#appTable tbody tr');
            
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                if(text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
  </script>
</body>
</html>