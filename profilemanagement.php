<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 
date_default_timezone_set('Asia/Manila');

// SECURITY CHECK (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$admin_name = $_SESSION['firstname'];
$msg = '';

// ==========================================
// ADD NEW ADMIN LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $fname = trim($_POST['firstname']);
    $lname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'admin';

    // Check kung may kaparehong email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm'>Email is already registered!</div>";
    } else {
        $insert = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("sssss", $fname, $lname, $email, $password, $role);
        if ($insert->execute()) {
            $msg = "<div class='alert alert-success shadow-sm'>New Admin added successfully!</div>";
        }
    }
}

// ==========================================
// EDIT ADMIN LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_admin'])) {
    $edit_id = $_POST['edit_id'];
    $fname = trim($_POST['firstname']);
    $lname = trim($_POST['lastname']);
    $email = trim($_POST['email']);

    $update = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=? WHERE id=?");
    $update->bind_param("sssi", $fname, $lname, $email, $edit_id);
    if ($update->execute()) {
        $msg = "<div class='alert alert-success shadow-sm'>Admin details updated successfully!</div>";
    }
}

// ==========================================
// DELETE ADMIN LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_admin'])) {
    $del_id = $_POST['delete_id'];
    
    // Safety Check: Wag payagang burahin ni admin ang sarili niya
    if ($del_id == $user_id) {
        $msg = "<div class='alert alert-danger shadow-sm'>You cannot delete your own account!</div>";
    } else {
        $delete = $conn->prepare("DELETE FROM users WHERE id=?");
        $delete->bind_param("i", $del_id);
        if ($delete->execute()) {
            $msg = "<div class='alert alert-success shadow-sm'>Admin account deleted successfully!</div>";
        }
    }
}

// ==========================================
// FETCH ALL ADMINS
// ==========================================
$stmt = $conn->prepare("SELECT id, firstname, lastname, email, profile_pic, last_active FROM users WHERE role = 'admin' ORDER BY id ASC");
$stmt->execute();
$admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Profile Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="img/logoulit.png" />
  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #085723; }
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
    .profile-header { color: #14804a; font-weight: 700; margin-bottom: 25px; }
    .profile-card { background-color: var(--savant-card-bg); border-radius: 12px; padding: 25px; box-shadow: var(--savant-shadow); }
    .table thead th { border-bottom: 2px solid #f0f0f0; color: #333; font-weight: 600; padding: 15px; }
    .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f8f8f8; font-size: 0.9rem; }
    
    .avatar-placeholder { width: 35px; height: 35px; background-color: #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #fff; overflow: hidden;}
    .avatar-placeholder img { width: 100%; height: 100%; object-fit: cover; }
    
    .status-active { background-color: #087830; color: white; padding: 4px 10px; border-radius: 4px; font-weight: 500; font-size: 0.8rem;}
    .status-inactive { background-color: #f1c40f; color: #333; padding: 4px 10px; border-radius: 4px; font-weight: 500; font-size: 0.8rem;}
    
    .btn-password { background: transparent; border: 1px solid #666; color: #333; border-radius: 4px; padding: 2px 12px; font-size: 0.85rem; }
    .btn-edit { background-color: #5bc0de; color: white; border: none; border-radius: 4px; padding: 5px 15px; font-size: 0.85rem; margin-right: 5px; }
    .btn-delete { background-color: #ff4d4d; color: white; border: none; border-radius: 4px; padding: 5px 15px; font-size: 0.85rem; }
    .btn-add { background-color: #087830; color: white; border: none; border-radius: 5px; padding: 6px 15px; font-size: 0.9rem; margin-top: 20px; transition: 0.2s;}
    .btn-add:hover { background-color: #065e25; }

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
        <a class="nav-link" href="applicationmanagement.php"><i class="bi bi-display"></i><span>Application Management</span></a>
        <a class="nav-link" href="chatmanagement.php"><i class="bi bi-chat-dots-fill"></i><span>Chat Management</span></a>
        <a class="nav-link" href="admin_booking.php"><i class="bi bi-file-earmark-text"></i><span>Booking Management</span></a>
        <a class="nav-link active" href="profilemanagement.php"><i class="bi bi-calendar-event"></i><span>Profile Management</span></a>
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
        <h2 class="profile-header">Admin Profile Management</h2>

        <?php echo $msg; ?>

        <div class="profile-card">
          <h4 class="fw-bold mb-4">Existing Admin Accounts</h4>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr class="text-secondary small">
                  <th></th>
                  <th>ID</th>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Email</th>
                  <th>Admin Type</th>
                  <th>Status</th>
                  <th>Password</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($admins as $ad): ?>
                    <?php 
                        // Initials for avatar
                        $initials = strtoupper(substr($ad['firstname'], 0, 1) . substr($ad['lastname'], 0, 1));
                        
                        // Status logic (Kung online in the last 24 hours = Active)
                        $is_active = false;
                        if (!empty($ad['last_active'])) {
                            $last_active = strtotime($ad['last_active']);
                            if ((time() - $last_active) <= 86400) { // 86400 = 24 hours
                                $is_active = true;
                            }
                        }
                    ?>
                    <tr>
                      <td>
                        <div class="avatar-placeholder bg-success">
                            <?php if(!empty($ad['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['profile_pic']); ?>">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                      </td>
                      <td><?php echo $ad['id']; ?></td>
                      <td><?php echo htmlspecialchars($ad['firstname']); ?></td>
                      <td><?php echo htmlspecialchars($ad['lastname']); ?></td>
                      <td class="text-muted"><?php echo htmlspecialchars($ad['email']); ?></td>
                      <td>Admin</td>
                      <td>
                          <?php if($is_active): ?>
                              <span class="status-active">Active</span>
                          <?php else: ?>
                              <span class="status-inactive">Inactive</span>
                          <?php endif; ?>
                      </td>
                      <td><button class="btn-password" onclick="alert('For security reasons, passwords are encrypted and cannot be viewed.')">Show</button></td>
                      <td>
                        <button class="btn-edit" onclick="openEditModal(<?php echo $ad['id']; ?>, '<?php echo addslashes($ad['firstname']); ?>', '<?php echo addslashes($ad['lastname']); ?>', '<?php echo addslashes($ad['email']); ?>')">Edit</button>
                        <button class="btn-delete" onclick="openDeleteModal(<?php echo $ad['id']; ?>)">Delete</button>
                      </td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addAdminModal">Add New Admin</button>
        </div>
      </main>
    </div>
  </div>

  <!-- ADD ADMIN MODAL -->
  <div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add New Admin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="profilemanagement.php" method="POST">
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="firstname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="lastname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_admin" class="btn text-white" style="background-color: var(--savant-primary-green);">Create Admin</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <!-- EDIT ADMIN MODAL -->
  <div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Admin Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="profilemanagement.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="firstname" id="edit_fname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="lastname" id="edit_lname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_admin" class="btn btn-info text-white">Save Changes</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <!-- DELETE ADMIN MODAL -->
  <div class="modal fade" id="deleteAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger">Delete Admin Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="profilemanagement.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="delete_id" id="delete_id">
                <p>Are you sure you want to delete this admin account? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="delete_admin" class="btn btn-danger">Yes, Delete</button>
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
      // Function para i-pasa ang data papunta sa Edit Modal
      function openEditModal(id, fname, lname, email) {
          document.getElementById('edit_id').value = id;
          document.getElementById('edit_fname').value = fname;
          document.getElementById('edit_lname').value = lname;
          document.getElementById('edit_email').value = email;
          var myModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
          myModal.show();
      }

      // Function para i-pasa ang ID papunta sa Delete Modal
      function openDeleteModal(id) {
          document.getElementById('delete_id').value = id;
          var myModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
          myModal.show();
      }
  </script>
</body>
</html>