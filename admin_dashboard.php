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
$msg = '';

// ==========================================
// DELETE USER LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $del_id = $_POST['delete_user_id'];
    
    // Binubura natin base sa ID, at sinisigurado nating role='user' lang ang mabubura (bawal burahin ang kapwa admin)
    $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
    $del_stmt->bind_param("i", $del_id);
    
    if ($del_stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4'>User successfully deleted! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4'>Failed to delete user. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// EDIT USER LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $edit_id = $_POST['edit_user_id'];
    $fname = trim($_POST['firstname']);
    $lname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    
    $upd_stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE id = ? AND role = 'user'");
    $upd_stmt->bind_param("sssi", $fname, $lname, $email, $edit_id);
    
    if ($upd_stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4'>User details updated successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4'>Failed to update user. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// KUNIN ANG MGA DATOS SA DATABASE
// ==========================================

// 1. Bilangin ang Total Active Users
$stmt_users_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
$total_users = $stmt_users_count->fetch_assoc()['total'];

// 2. Kunin ang latest na nag-register na Users para sa Table
$stmt_users = $conn->query("SELECT id, firstname, lastname, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users_list = $stmt_users->fetch_all(MYSQLI_ASSOC);

// 3. Kunin ang latest Chat Messages ng mga Users
$stmt_chats = $conn->query("SELECT c.message, u.firstname, u.lastname, c.created_at FROM chats c JOIN users u ON c.user_id = u.id WHERE c.sender = 'user' ORDER BY c.created_at DESC LIMIT 5");
$latest_chats = $stmt_chats->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Savant - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
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
      --savant-highlight-light-green: #98FB98;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--savant-light-gray);
      color: var(--savant-text-dark);
      overflow-x: hidden;
      margin: 0;
    }

    .main-wrapper {
      display: flex;
      min-height: 100vh;
    }

    /* --- SIDEBAR FIXES --- */
    .sidebar {
      background-color: #087830;
      width: 70px;
      flex-shrink: 0;
      color: white;
      padding: 30px 0;
      box-shadow: var(--savant-shadow);
      transition: width 0.3s ease-in-out;
      overflow: hidden;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      display: flex;
      flex-direction: column;
      z-index: 1031;
    }

    .sidebar:hover {
      width: 280px;
    }

    .sidebar .logo {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 30px;
      text-align: center;
      white-space: nowrap;
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
    }

    .sidebar:hover .logo {
      opacity: 1;
    }

    .sidebar .nav-link {
      color: white;
      font-weight: 500;
      padding: 15px 25px;
      display: flex;
      align-items: center;
      gap: 15px;
      white-space: nowrap;
      transition: background-color 0.3s ease;
      text-decoration: none;
    }

    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background-color: #14552b;
    }

    .sidebar .nav-link i {
      font-size: 1.4rem;
      min-width: 20px;
      text-align: center;
    }

    .sidebar .nav-link span {
      opacity: 0;
      transition: opacity 0.1s ease-in-out 0.2s;
    }

    .sidebar:hover .nav-link span {
      opacity: 1;
    }

    /* --- CONTENT AREA FIXES --- */
    .content-area {
      flex-grow: 1;
      margin-left: 70px;
      /* Offset for fixed sidebar */
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .header {
      background-color: var(--savant-bg-light);
      height: 60px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 25px;
      box-shadow: var(--savant-shadow);
      position: sticky;
      top: 0;
      z-index: 1020;
    }

    .header-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--savant-text-dark);
      white-space: nowrap;
    }

    .user-status .badge {
      background-color: var(--savant-button-maroon);
      padding: 5px 15px;
      border-radius: 20px;
      color: white;
    }

    .power-btn i {
      color: #dc3545;
      font-size: 1.5rem;
    }

    .main-content {
      padding: 30px;
    }

    .admin-card {
      background-color: var(--savant-card-bg);
      border: none;
      border-radius: 12px;
      box-shadow: var(--savant-shadow);
      padding: 25px;
    }

    .stats-val {
      font-size: 2.5rem;
      font-weight: 700;
    }

    .status-active {
      color: #198754;
    }

    .status-inactive {
      color: #f0ad4e;
    }

    .chat-msg {
      background: #fdfdfd;
      border-left: 4px solid var(--savant-primary-green);
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 10px;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.02);
    }

    .btn-edit {
      background-color: #5bc0de;
      color: white;
      border-radius: 6px;
      border: none;
    }

    .btn-delete {
      background-color: #dc3545;
      color: white;
      border-radius: 6px;
      border: none;
    }

    @media (max-width: 992px) {
      .content-area {
        margin-left: 0;
      }

      .sidebar {
        display: none;
      }
    }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <!-- IN-UPDATE ANG MGA LINKS TO PHP -->
        <a class="nav-link active" href="admin_dashboard.php"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="usermanagement.php"><i class="bi bi-people-fill"></i><span>Users Management</span></a>
        <a class="nav-link" href="applicationmanagement.php"><i class="bi bi-display"></i><span>Application Management</span></a>
        <a class="nav-link" href="chatmanagement.php"><i class="bi bi-chat-dots-fill"></i><span>Chat Management</span></a>
        <a class="nav-link" href="admin_booking.php"><i class="bi bi-file-earmark-text"></i><span>Booking Management</span></a>
        <a class="nav-link" href="profilemanagement.php"><i class="bi bi-calendar-event"></i><span>Profile Management</span></a>
        <a class="nav-link" href="contentmanagement.php"><i class="bi bi-pencil-square"></i><span>Content Management</span></a>
      </nav>
      <div class="mt-auto text-center pb-4" style="opacity: 0.5; font-size: 0.7rem;">
        &copy; 2026 SAVANT
      </div>
    </aside>

    <div class="content-area">
      <div class="header">
        <div class="header-brand d-flex align-items-center">
          <span class="header-title">Savant-International Student Mobility</span>
        </div>

        <div class="user-status d-flex align-items-center gap-2">
          <!-- DYNAMIC DATE (PHP) -->
          <div class="me-3 d-none d-lg-block fw-medium"><?php echo date('F j, Y'); ?></div>
          
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
              <i class="bi bi-power"></i>
          </a>
          <!-- DYNAMIC ADMIN BADGE -->
          <span class="badge"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
      </div>

      <main class="main-content">
        <h3 class="fw-bold mb-4" style="color: var(--savant-primary-green);">Admin Dashboard</h3>

        <?php echo $msg; // DITO LALABAS ANG SUCCESS / ERROR MESSAGE PAGKATAPOS MAG-EDIT O DELETE ?>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="admin-card text-center">
              <h6 class="text-secondary text-uppercase fw-bold small">Active Users</h6>
              <div class="stats-val status-active"><?php echo $total_users; ?></div>
              <p class="text-muted small mb-0">Active in the system.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-card text-center">
              <h6 class="text-secondary text-uppercase fw-bold small">Inactive Users</h6>
              <div class="stats-val status-inactive">0</div>
              <p class="text-muted small mb-0">Inactive for over 30 days.</p>
            </div>
          </div>
        </div>

        <div class="admin-card mb-4">
          <h5 class="fw-bold mb-3">Client Users</h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr class="text-muted small">
                  <th>ID</th>
                  <th>NAME</th>
                  <th>EMAIL</th>
                  <th>STATUS</th>
                  <th>ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($users_list)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No registered users yet.</td></tr>
                <?php else: ?>
                    <?php foreach($users_list as $u): ?>
                    <tr>
                      <td><?php echo $u['id']; ?></td>
                      <td class="fw-meduim"><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
                      <td><?php echo htmlspecialchars($u['email']); ?></td>
                      <td><span class="badge bg-success-subtle text-success px-3">Active</span></td>
                      <td>
                        <div class="d-flex gap-2">
                            <!-- EDIT BUTTON NA MAG-OOPPEN NG MODAL KASAMA ANG DATA -->
                            <button class="btn btn-sm btn-edit px-3" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editUserModal"
                                data-id="<?php echo $u['id']; ?>"
                                data-fname="<?php echo htmlspecialchars($u['firstname']); ?>"
                                data-lname="<?php echo htmlspecialchars($u['lastname']); ?>"
                                data-email="<?php echo htmlspecialchars($u['email']); ?>">
                                Edit
                            </button>

                            <!-- DELETE BUTTON NA MAY CONFIRMATION FORM -->
                            <form action="admin_dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');" style="margin:0;">
                                <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-delete px-3">Delete</button>
                            </form>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <a href="#" class="text-decoration-none small fw-bold mt-2 d-inline-block"
            style="color: var(--savant-primary-green);">Manage All Users →</a>
        </div>

        <div class="admin-card">
          <h5 class="fw-bold mb-3">Live Chat Monitoring</h5>
          <?php if(empty($latest_chats)): ?>
                <p class="text-muted text-center py-3">No chat messages from users yet.</p>
          <?php else: ?>
                <?php foreach($latest_chats as $chat): ?>
                    <div class="chat-msg">
                        <strong style="color: var(--savant-primary-green);">
                            <?php echo htmlspecialchars($chat['firstname'] . ' ' . $chat['lastname']); ?>:
                        </strong>
                        <span class="ms-2"><?php echo htmlspecialchars($chat['message']); ?></span>
                    </div>
                <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <!-- EDIT USER MODAL -->
  <div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit User Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="admin_dashboard.php" method="POST">
            <div class="modal-body">
              <input type="hidden" name="edit_user_id" id="modal_edit_id">
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">First Name</label>
                  <input type="text" name="firstname" id="modal_edit_fname" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Last Name</label>
                  <input type="text" name="lastname" id="modal_edit_lname" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Email Address</label>
                  <input type="email" name="email" id="modal_edit_email" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="edit_user" class="btn text-white" style="background-color: var(--savant-primary-green);">Save Changes</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <!-- LOGOUT MODAL -->
  <div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to log out from the Admin Panel?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="logout.php" class="btn btn-danger px-4">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // JAVASCRIPT PARA ILIPAT ANG DATA GALING SA TABLE PAPUNTA SA EDIT MODAL
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editUserModal');
        if(editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                // Kunin yung button na na-click
                const button = event.relatedTarget;
                
                // Kunin ang data attributes mula sa button na kinlick
                const id = button.getAttribute('data-id');
                const fname = button.getAttribute('data-fname');
                const lname = button.getAttribute('data-lname');
                const email = button.getAttribute('data-email');
                
                // I-pasok yung mga kinuhang data sa loob ng modal inputs
                document.getElementById('modal_edit_id').value = id;
                document.getElementById('modal_edit_fname').value = fname;
                document.getElementById('modal_edit_lname').value = lname;
                document.getElementById('modal_edit_email').value = email;
            });
        }
    });
  </script>
</body>

</html>