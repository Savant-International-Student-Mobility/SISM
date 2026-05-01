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
$admin_id = $_SESSION['user_id'];
$msg = '';

// ==========================================
// DELETE USER LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $del_id = $_POST['delete_user_id'];
    $admin_password = $_POST['admin_password'];

    $stmt_admin = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt_admin->bind_param("i", $admin_id);
    $stmt_admin->execute();
    $admin_data = $stmt_admin->get_result()->fetch_assoc();

    if (password_verify($admin_password, $admin_data['password'])) {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $del_stmt->bind_param("i", $del_id);
        
        if ($del_stmt->execute()) {
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4'>User successfully deleted! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4'>Failed to delete user. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4'>Incorrect admin password! Deletion cancelled. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
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
// FETCH ALL USERS
// ==========================================
$stmt_users = $conn->query("SELECT id, firstname, lastname, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users_list = $stmt_users->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="img/logoulit.png" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --savant-primary-green: #087830; --savant-text-dark: #333333; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #085723; }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow-x: hidden; margin: 0; }
    .main-wrapper { display: flex; min-height: 100vh; }
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
    .management-header { color: #14804a; font-weight: 700; margin-bottom: 20px; }
    .user-card { background-color: #FFFFFF; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
    .table { margin-bottom: 0; }
    .table thead th { border-bottom: 1px solid #eee; color: #333; font-weight: 600; font-size: 1.1rem; }
    .table tbody td { padding: 15px 8px; color: #555; border-bottom: 1px solid #f8f8f8; }
    
    /* NA-UPDATE ANG CSS NG BUTTONS PARA TUMUGMA SA DASHBOARD */
    .btn-edit { background-color: #5bc0de; color: white; border-radius: 6px; border: none; padding: 4px 15px; font-size: 0.9rem; transition: 0.2s; }
    .btn-delete { background-color: #dc3545; color: white; border-radius: 6px; border: none; padding: 4px 15px; font-size: 0.9rem; transition: 0.2s; }
    .btn-edit:hover { background-color: #46b8da; color: white;}
    .btn-delete:hover { background-color: #c82333; color: white;}
    
    @media (max-width: 992px) { .content-area { margin-left: 0; } .sidebar { display: none; } }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <!-- ALL LINKS POINT TO PHP -->
        <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a>
        <a class="nav-link active" href="usermanagement.php"><i class="bi bi-people-fill"></i><span>Users Management</span></a>
        <a class="nav-link" href="applicationmanagement.php"><i class="bi bi-display"></i><span>Application Management</span></a>
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
          <span class="header-title">Savant-International Student Mobility</span>
        </div>
        <div class="user-status d-flex align-items-center gap-2">
          <div class="me-3 d-none d-lg-block fw-medium"><?php echo date('F j, Y'); ?></div>
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-power"></i></a>
          <span class="badge"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
      </div>

      <main class="main-content">
        <h2 class="management-header">User Management</h2>
        
        <?php echo $msg; // SUCCESS / ERROR MESSAGE CONTAINER ?>

        <div class="user-card">
          <h4 class="fw-bold mb-4">All Users</h4>
          <div class="table-responsive">
            <table class="table align-middle table-hover">
              <thead>
                <tr class="text-muted small">
                  <th width="10%">ID</th>
                  <th width="25%">NAME</th>
                  <th width="35%">EMAIL</th>
                  <th width="15%">STATUS</th>
                  <th width="15%">ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                
                <!-- PHP LOOP PARA SA LAHAT NG USERS -->
                <?php if(empty($users_list)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No registered users yet.</td></tr>
                <?php else: ?>
                    <?php foreach($users_list as $u): ?>
                    <tr>
                      <td><?php echo $u['id']; ?></td>
                      <td class="fw-meduim"><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
                      <td><?php echo htmlspecialchars($u['email']); ?></td>
                      <!-- NA-UPDATE ANG STATUS BADGE STYLE -->
                      <td><span class="badge bg-success-subtle text-success px-3">Active</span></td>
                      <td>
                        <div class="d-flex gap-2">
                            <button class="btn-edit" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editUserModal"
                                data-id="<?php echo $u['id']; ?>"
                                data-fname="<?php echo htmlspecialchars($u['firstname']); ?>"
                                data-lname="<?php echo htmlspecialchars($u['lastname']); ?>"
                                data-email="<?php echo htmlspecialchars($u['email']); ?>">
                                Edit
                            </button>
                            
                            <button class="btn-delete" 
                                data-bs-toggle="modal" 
                                data-bs-target="#confirmDeleteModal"
                                data-id="<?php echo $u['id']; ?>"
                                data-name="<?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?>">
                                Delete
                            </button>
                        </div>
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

  <!-- EDIT USER MODAL -->
  <div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Edit User Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="usermanagement.php" method="POST">
            <div class="modal-body">
              <input type="hidden" name="edit_user_id" id="modal_edit_id">
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">First Name</label>
                  <input type="text" name="firstname" id="modal_edit_fname" class="form-control bg-light" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Last Name</label>
                  <input type="text" name="lastname" id="modal_edit_lname" class="form-control bg-light" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Email Address</label>
                  <input type="email" name="email" id="modal_edit_email" class="form-control bg-light" required>
              </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="edit_user" class="btn text-white fw-medium px-4" style="background-color: var(--savant-primary-green);">Save Changes</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <!-- DELETE USER MODAL -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Security Check</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="usermanagement.php" method="POST">
            <div class="modal-body">
              <input type="hidden" name="delete_user_id" id="modal_delete_id">
              <p>Are you sure you want to permanently delete the account of <strong id="modal_delete_name" class="text-danger"></strong>?</p>
              <p class="small text-muted mb-4">This action cannot be undone.</p>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Enter Admin Password to confirm:</label>
                  <input type="password" name="admin_password" class="form-control bg-light" placeholder="••••••••" required>
              </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="delete_user" class="btn btn-danger fw-medium px-4">Permanently Delete</button>
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
        // SCRIPT PARA SA EDIT MODAL
        const editModal = document.getElementById('editUserModal');
        if(editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('modal_edit_id').value = button.getAttribute('data-id');
                document.getElementById('modal_edit_fname').value = button.getAttribute('data-fname');
                document.getElementById('modal_edit_lname').value = button.getAttribute('data-lname');
                document.getElementById('modal_edit_email').value = button.getAttribute('data-email');
            });
        }

        // SCRIPT PARA SA DELETE MODAL
        const deleteModal = document.getElementById('confirmDeleteModal');
        if(deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('modal_delete_id').value = button.getAttribute('data-id');
                document.getElementById('modal_delete_name').textContent = button.getAttribute('data-name');
            });
        }
    });
  </script>
</body>
</html>