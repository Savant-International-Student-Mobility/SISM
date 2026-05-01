<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 
date_default_timezone_set('Asia/Manila');

// SECURITY CHECK (Dapat Admin lang ang makakapasok)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['firstname'];

// KUNIN LAHAT NG BOOKINGS MULA SA DATABASE (Joined with Users table para makuha ang pangalan at email)
// I-a-assume natin na galing ito sa 'bookings' table mo mula sa booking.php
$sql = "SELECT b.id AS book_id, u.firstname, u.lastname, u.email, b.booking_date, b.booking_time, b.status 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        ORDER BY b.booking_date DESC, b.booking_time DESC";

$result = $conn->query($sql);
$bookings = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="img/logoulit.png" />
  
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

    /* --- SIDEBAR --- */
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

    /* --- CONTENT AREA --- */
    .content-area {
      flex-grow: 1;
      margin-left: 70px;
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

    .user-remarks .badge {
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

    /* --- BOOKING MANAGEMENT STYLES --- */
    .booking-header {
      color: #14804a;
      font-weight: 700;
      margin-bottom: 25px;
    }

    .booking-card {
      background-color: var(--savant-card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--savant-shadow);
    }

    .search-input-group {
      max-width: 400px;
    }

    .search-input {
      border-radius: 8px 0 0 8px !important;
      border: 1px solid #ced4da;
    }

    .search-btn {
      border-radius: 0 8px 8px 0 !important;
      border: 1px solid #ced4da;
      border-left: none;
      background-color: #fff;
    }

    .table thead th {
      border-bottom: 2px solid #f0f0f0;
      color: var(--savant-text-dark);
      font-weight: 600;
      padding: 15px;
    }

    .table tbody td {
      padding: 15px;
      vertical-align: middle;
      border-bottom: 1px solid #f8f8f8;
      font-size: 0.9rem;
    }

    /* Status Badges */
    .remarks-confirmed { background-color: #087830; color: white; padding: 5px 12px; border-radius: 4px; font-weight: 500; font-size: 0.8rem; }
    .remarks-pending { background-color: #f1c40f; color: #333; padding: 5px 12px; border-radius: 4px; font-weight: 500; font-size: 0.8rem; }
    .remarks-completed { background-color: #3498db; color: white; padding: 5px 12px; border-radius: 4px; font-weight: 500; font-size: 0.8rem; }
    .remarks-cancelled { background-color: #e74c3c; color: white; padding: 5px 12px; border-radius: 4px; font-weight: 500; font-size: 0.8rem; }

    @media (max-width: 992px) {
      .content-area { margin-left: 0; }
      .sidebar { display: none; }
    }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <!-- FIXED ADMIN NAV LINKS -->
        <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="usermanagement.php"><i class="bi bi-people-fill"></i><span>Users Management</span></a>
        <a class="nav-link" href="applicationmanagement.php"><i class="bi bi-display"></i><span>Application Management</span></a>
        <a class="nav-link" href="chatmanagement.php"><i class="bi bi-chat-dots-fill"></i><span>Chat Management</span></a>
        <a class="nav-link active" href="bookingmanagement.php"><i class="bi bi-file-earmark-text"></i><span>Booking Management</span></a>
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

        <div class="user-remarks d-flex align-items-center gap-2">
          <!-- DYNAMIC DATE -->
          <div class="me-3 d-none d-lg-block fw-medium"><?php echo date('F j, Y'); ?></div>
          
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
              <i class="bi bi-power"></i>
          </a>
          <!-- DYNAMIC ADMIN NAME -->
          <span class="badge"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
      </div>

      <main class="main-content">
        <h2 class="booking-header">Booking Management</h2>

        <div class="booking-card">
          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold mb-0">Submitted Bookings</h4>

            <div class="input-group search-input-group">
              <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by Name or Email...">
              <button class="btn search-btn"><i class="bi bi-search"></i></button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle" id="bookingsTable">
              <thead>
                <tr>
                  <th>Book ID</th>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Email</th>
                  <th>Service</th>
                  <th>Date & Time</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No bookings found in the database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $row): ?>
                        <tr>
                          <!-- Padded ID (e.g. BK-001) -->
                          <td class="fw-bold text-secondary">BK-<?php echo str_pad($row['book_id'], 3, '0', STR_PAD_LEFT); ?></td>
                          <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                          <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                          <td class="text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
                          
                          <!-- Naka-default muna sa Consultation dahil ito yung galing sa booking.php -->
                          <td>Consultation</td> 
                          
                          <td><?php echo date("Y-m-d", strtotime($row['booking_date'])) . ' <br> <span class="text-muted small">' . htmlspecialchars($row['booking_time']) . '</span>'; ?></td>
                          
                          <td>
                            <?php 
                                $remarks = strtolower($row['remarks'] ?? 'pending');
                                if ($remarks == 'confirmed') {
                                    echo '<span class="remarks-confirmed">Confirmed</span>';
                                } elseif ($remarks == 'completed') {
                                    echo '<span class="remarks-completed">Completed</span>';
                                } elseif ($remarks == 'cancelled') {
                                    echo '<span class="remarks-cancelled">Cancelled</span>';
                                } else {
                                    echo '<span class="remarks-pending">Pending</span>';
                                }
                            ?>
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
    // REAL-TIME JAVASCRIPT SEARCH FUNCTION
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#bookingsTable tbody tr');

        rows.forEach(row => {
            // Huwag isama yung "No bookings found" text
            if(row.cells.length > 1) {
                // Kunin yung First Name, Last Name, at Email columns (index 1, 2, 3)
                let textContent = row.cells[1].textContent.toLowerCase() + " " + 
                                  row.cells[2].textContent.toLowerCase() + " " + 
                                  row.cells[3].textContent.toLowerCase();
                                  
                if (textContent.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
  </script>
</body>
</html>