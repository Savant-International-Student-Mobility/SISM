<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 

// Check kung naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kunin ang data ng user para ilagay sa form automatically
$stmt = $conn->prepare("SELECT firstname, lastname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$full_name = $user['firstname'] . " " . $user['lastname'];
$email = $user['email'];

// --- ITO ANG MAGSE-SAVE NG BOOKING SA DATABASE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'book') {
    $date = $_POST['booking_date'];
    $time = $_POST['booking_time'];
    $fb_link = trim($_POST['facebook_link']);
    $notes = trim($_POST['notes']);

    $insert = $conn->prepare("INSERT INTO bookings (user_id, booking_date, booking_time, facebook_link, notes) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("issss", $user_id, $date, $time, $fb_link, $notes);

    if ($insert->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save booking.']);
    }
    exit(); 
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAVANT Book Consultation</title>
  <link rel="icon" href="img/logoulit.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #085723; }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow: hidden; }
    ::-webkit-scrollbar { width: 12px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #0C470C, #3BA43B); border-radius: 6px; } ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #023621, #2a7c2a); }
    .main-wrapper { display: flex; height: 100vh; }
    .sidebar { background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0; box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden; position: fixed; top: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031; }
    .sidebar:hover { width: 280px; } .sidebar .logo { font-size: 2.2rem; font-weight: 700; margin-bottom: 30px; text-align: center; letter-spacing: 1px; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; } .sidebar:hover .logo { opacity: 1; }
    .sidebar .nav { flex-grow: 1; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 30px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease; text-decoration: none;}
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #14552b; }
    .sidebar .nav-link i { font-size: 1.2rem; min-width: 20px; text-align: center; } .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; } .sidebar:hover .nav-link span { opacity: 1; }
    .sidebar .footer-text { font-size: 0.8rem; color: #bbb; text-align: center; padding: 15px 0; opacity: 0; transition: opacity 0.3s ease-in-out; margin-top: auto; } .sidebar:hover .footer-text { opacity: 1; }
    .content-area { flex-grow: 1; height: 100vh; overflow-y: auto; margin-left: 70px; }
    .header { background-color: var(--savant-bg-light); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; }
    .user-status { display: flex; align-items: center; gap: 10px; } .user-status .badge { background-color: var(--savant-button-maroon); font-size: 0.8rem; font-weight: 500; padding: 5px 15px; border-radius: 20px; color: white;}
    .power-btn i { color: #dc3545; font-size: 1.5rem;} .power-btn:hover i { color: #a71d2a; }
    .main-content { flex-grow: 1; padding: 30px; }
    .booking-wrapper { max-width: 1200px; margin: 0 auto; background-color: var(--savant-card-bg); border-radius: 12px; box-shadow: var(--savant-shadow); display: flex; overflow: hidden; animation: fadeIn 0.5s ease-in-out; }
    .booking-info { flex-basis: 40%; padding: 2.5rem; border-right: 1px solid var(--savant-light-gray); } .booking-info .logo { width: 150px; margin-bottom: 1.5rem; } .booking-info h2 { font-size: 1.75rem; font-weight: 600; } .booking-info .info-item { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; color: var(--savant-text-light); font-size: 1.1rem; }
    .booking-scheduler { flex-basis: 60%; padding: 2.5rem; }
    #monthYear { font-size: 1.25rem; font-weight: 600; text-align: center; }
    .calendar-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .calendar-nav-btn { background: var(--savant-primary-green); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: bold; }
    .weekdays, .days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; text-align: center; } .weekday { font-weight: 600; font-size: 0.9rem; }
    .day { cursor: pointer; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; margin: 0 auto; border: 1px solid transparent; font-size: 1.1rem; }
    .day:hover:not(.empty):not(.disabled) { background-color: var(--savant-light-gray); } .day.disabled { color: #ccc; cursor: not-allowed; } .day.today { border-color: var(--savant-primary-green); } .day.selected { background-color: var(--savant-primary-green); color: white; transform: scale(1.1); }
    #time-slots-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 0.5rem; } .time-slot-btn { border: 1px solid var(--savant-light-gray); } .time-slot-btn.active { background-color: var(--savant-primary-green); color: white; border-color: var(--savant-primary-green); }
    .btn-submit { background-color: var(--savant-button-maroon); color: white; transition: background-color 0.3s ease; } .btn-submit:hover { background-color: var(--savant-primary-green); border-color: var(--savant-primary-green); color: white; }
    .floating-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); text-decoration: none; z-index: 100; transition: 0.2s;} .floating-btn:hover { background-color: var(--savant-dark-green); }
    
    /* ================= CHAT STYLES ================= */
    .chat-container { position: fixed; bottom: 100px; right: 30px; width: 350px; background-color: white; border-radius: 12px; box-shadow: var(--savant-shadow); display: none; flex-direction: column; z-index: 99; overflow: hidden;}
    .chat-container.show { display: flex; }
    .chat-header { background-color: var(--savant-primary-green); color: white; padding: 1rem; cursor: pointer; }
    .chat-body { padding: 1rem; height: 350px; overflow-y: auto; background-color: var(--savant-bg-light); display: flex; flex-direction: column; }
    .chat-footer { padding: 1rem; border-top: 1px solid var(--savant-light-gray); background: white;}
    
    /* Toggle Button */
    .chat-toggle-btn { position: fixed; bottom: 30px; right: 100px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); cursor: pointer; z-index: 100; transition: transform 0.3s ease, background-color 0.3s ease; border: none; }
    
    /* Chat Bubbles */
    .chat-bubble { max-width: 80%; padding: 10px 15px; margin-bottom: 10px; border-radius: 15px; font-size: 0.9rem; clear: both; word-wrap: break-word;}
    .chat-bubble.user { background-color: var(--savant-primary-green); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
    .chat-bubble.admin { background-color: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; }
    
    /* DATE AND TIME BADGE STYLE SA LOOB NG CHAT */
    .chat-time-badge { font-size: 0.65rem; background-color: rgba(0, 0, 0, 0.15); padding: 3px 8px; border-radius: 12px; display: inline-block; margin-top: 5px; }
    .chat-bubble.user .chat-time-badge { color: #e6e6e6; background-color: rgba(255, 255, 255, 0.2); }
    .chat-bubble.admin .chat-time-badge { color: #666; }

    /* SIMPLE NUMBER BADGE */
    .chat-unread-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #dc3545; /* Red color */
        color: white;
        border-radius: 50%;
        font-size: 0.8rem;
        font-weight: bold;
        width: 24px;
        height: 24px;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    @media (max-width: 992px) { body { padding-top: 60px; padding-bottom: 50px; overflow: auto; } .main-wrapper { flex-direction: column; height: auto; } .content-area { height: auto; overflow-y: visible; margin-left: 0; } .header { position: fixed; top: 0; left: 0; right: 0; z-index: 1030; height: 60px; } .header-title { display: none; } .sidebar { width: 100%; height: 50px; position: fixed; bottom: 0; left: 0; z-index: 1029; flex-direction: row; align-items: center; padding: 0; } .sidebar .nav { display: flex; flex-direction: row; width: 100%; justify-content: space-around; } .sidebar .nav-link span, .sidebar .logo, .sidebar .footer-text { display: none; } .booking-wrapper { flex-direction: column; } .booking-info { border-right: none; border-bottom: 1px solid var(--savant-light-gray); } .floating-btn { bottom: 80px; right: 85px; } .chat-toggle-btn { bottom: 80px; right: 15px; } }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a>
        <a class="nav-link active" href="booking.php"><i class="bi bi-calendar-check"></i><span>Book Consultation</span></a>
        <a class="nav-link" href="statement.php"><i class="bi bi-receipt"></i><span>Statement of Account</span></a>
        <a class="nav-link" href="documents.php"><i class="bi bi-file-earmark-text"></i><span>Documents</span></a>
        <a class="nav-link" href="forms.php"><i class="bi bi-journal-text"></i><span>Forms</span></a>
        <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i><span>Notifications</span></a>
        <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i><span>Settings</span></a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i><span>Profile</span></a>
      </nav>
      <div class="mt-auto footer-text">&copy; 2026 SAVANT</div>
    </aside>

    <div class="content-area">
      <div class="header">
        <div class="header-brand d-flex align-items-center">
          <span class="header-title">Savant-International Student Mobility</span>
        </div>
        <div class="user-status d-flex align-items-center gap-2">
          <div id="headerDate" class="me-3" style="font-weight: 500;"></div>
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-power"></i></a>
          <span class="badge"><?php echo htmlspecialchars($user['firstname']); ?></span>
        </div>
      </div>

      <main class="main-content">
        <div id="booking-view">
          <div class="booking-wrapper">
            <div class="booking-info">
              <img src="img/logo.png" alt="Savant Logo" class="logo" style="width: 250px;">
              <hr>
              <h2>45 Minute Meeting</h2>
              <div class="info-item mt-3"><i class="bi bi-clock"></i><span>45 mins</span></div>
              <div class="info-item"><i class="bi bi-camera-video"></i><span>Zoom Meeting</span></div>
            </div>
            
            <div class="booking-scheduler">
              <div id="date-time-selection-view">
                <h4 class="mb-3">Select a Date & Time</h4>
                <div class="row">
                  <div class="calendar col-lg-7">
                    <div class="calendar-controls">
                      <button id="prevMonth" class="calendar-nav-btn">&lt;</button>
                      <div id="monthYear"></div>
                      <button id="nextMonth" class="calendar-nav-btn">&gt;</button>
                    </div>
                    <div class="weekdays"></div>
                    <div class="days"></div>
                  </div>
                  <div class="time-slots col-lg-5 mt-4 mt-lg-0">
                    <h6 class="text-center fw-bold" id="selected-date-display"></h6>
                    <div id="time-slots-container" class="mt-3"></div>
                  </div>
                </div>
                <button id="continue-to-details" class="btn text-white mt-4 w-100" style="background-color: var(--savant-button-maroon); display: none;">
                  BOOK YOUR FREE CONSULTATION NOW! <i class="bi bi-arrow-right-circle-fill ms-2"></i>
                </button>
              </div>

              <!-- FORM DETAILS VIEW -->
              <div id="details-entry-view" style="display: none;">
                <button id="back-to-calendar" class="btn btn-sm btn-submit mb-3">&lt; Back</button>
                <h4>Enter Your Details</h4>
                <p><strong>Selected:</strong> <span id="details-date-display"></span> at <span id="details-time-display"></span></p>
                
                <form id="booking-form">
                  <div class="mb-3">
                    <label class="form-label">Name *</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" readonly>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
                  </div>
                  <div class="mb-3">
                    <label for="facebookLink" class="form-label">Facebook Profile Link *</label>
                    <input type="url" id="facebookLink" class="form-control" placeholder="https://www.facebook.com/yourprofile" required>
                  </div>
                  <div class="mb-3">
                    <label for="notes" class="form-label">Additional Notes</label>
                    <textarea id="notes" class="form-control" rows="3" placeholder="What is this meeting about?"></textarea>
                  </div>
                  <button type="submit" id="submit-btn" class="btn btn-submit w-100">Schedule Event</button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- CONFIRMATION VIEW -->
        <div id="confirmation-view" style="display:none;" class="text-center mt-5">
          <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          <h2 class="mt-3">Consultation Booked!</h2>
          <p class="lead">Your meeting has been scheduled and saved successfully.</p>
          <div class="card mt-4 d-inline-block text-start border-success shadow-sm">
            <div class="card-body">
              <h5 class="card-title text-success fw-bold">Meeting Details</h5>
              <p><strong>With:</strong> Savant Advisory</p>
              <p><strong>Date:</strong> <span id="confirm-date"></span></p>
              <p><strong>Time:</strong> <span id="confirm-time"></span></p>
              <p class="small text-muted mb-0">The Zoom meeting link will be sent to your Messenger.</p>
            </div>
          </div>
          <br>
          <button id="book-another" class="btn mt-4 text-white" style="background-color: var(--savant-button-maroon); border-radius: 30px;">Book Another</button>
        </div>
      </main>
    </div>
  </div>

  <a href="bookflight.php" class="floating-btn text-decoration-none"><i class="bi bi-plus-lg"></i></a>

  <!-- ================= LIVE CHAT UI ================= -->
  <div class="chat-container" id="chatContainer">
    <div class="chat-header d-flex justify-content-between align-items-center" onclick="toggleChat()">
      <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Live Chat</h5>
      <i class="bi bi-x-lg"></i>
    </div>
    <div class="chat-body" id="chatBody">
      <div class="text-center text-muted small mb-3">Savant Support, how may I help you?</div>
      <!-- Messages will load here via AJAX -->
    </div>
    <div class="chat-footer">
      <div class="input-group">
        <input type="text" id="chatInput" class="form-control" placeholder="Type a message..." autocomplete="off">
        <button class="btn btn-outline-secondary" type="button" id="sendChatBtn"><i class="bi bi-send-fill"></i></button>
      </div>
    </div>
  </div>

  <button class="chat-toggle-btn" onclick="toggleChat()">
    <i class="bi bi-chat-dots"></i>
    <!-- NUMBER BADGE NOTIFICATION DITO -->
    <span class="chat-unread-badge" id="chatUnreadBadge">0</span>
  </button>

  <!-- LOGOUT MODAL -->
  <div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Are you sure you want to log out?</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.getElementById('headerDate').textContent = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

      // ==========================================
      // CALENDAR LOGIC
      // ==========================================
      let currentDate = new Date();
      let selectedDate = null;
      let selectedTime = null;

      const monthYearEl = document.getElementById('monthYear');
      const weekdaysEl = document.querySelector('.weekdays');
      const daysEl = document.querySelector('.days');
      const timeSlotsContainer = document.getElementById('time-slots-container');
      const continueBtn = document.getElementById('continue-to-details');
      const timeSlots = ["9:00 AM", "10:00 AM", "11:00 AM", "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM"];

      function renderCalendar() {
        daysEl.innerHTML = ''; weekdaysEl.innerHTML = '';
        const year = currentDate.getFullYear(); const month = currentDate.getMonth();
        monthYearEl.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
          const weekday = document.createElement('div'); weekday.className = 'weekday'; weekday.textContent = day; weekdaysEl.appendChild(weekday);
        });

        for (let i = 0; i < firstDayOfMonth; i++) {
          const emptyDay = document.createElement('div'); emptyDay.className = 'day empty'; daysEl.appendChild(emptyDay);
        }

        for (let i = 1; i <= daysInMonth; i++) {
          const dayEl = document.createElement('div'); dayEl.className = 'day'; dayEl.textContent = i;
          const today = new Date(); const date = new Date(year, month, i);

          if (date < new Date(today.getFullYear(), today.getMonth(), today.getDate())) dayEl.classList.add('disabled');
          if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) dayEl.classList.add('today');
          if (selectedDate && selectedDate.getTime() === date.getTime()) dayEl.classList.add('selected');

          dayEl.addEventListener('click', () => {
            if (dayEl.classList.contains('disabled')) return;
            selectedDate = new Date(year, month, i); selectedTime = null;
            renderCalendar(); renderTimeSlots();
            document.getElementById('selected-date-display').textContent = new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric' }).format(selectedDate);
            continueBtn.style.display = 'none';
          });
          daysEl.appendChild(dayEl);
        }
      }

      function renderTimeSlots() {
        timeSlotsContainer.innerHTML = '';
        if (!selectedDate) { timeSlotsContainer.innerHTML = '<p class="text-muted small">Select a date to see available times.</p>'; return; }
        timeSlots.forEach(time => {
          const timeBtn = document.createElement('button'); timeBtn.className = 'btn btn-outline-secondary time-slot-btn'; timeBtn.textContent = time;
          if (selectedTime === time) timeBtn.classList.add('active');
          timeBtn.addEventListener('click', () => { selectedTime = time; renderTimeSlots(); continueBtn.style.display = 'block'; });
          timeSlotsContainer.appendChild(timeBtn);
        });
      }

      document.getElementById('prevMonth').addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); });
      document.getElementById('nextMonth').addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); });

      continueBtn.addEventListener('click', () => {
        document.getElementById('date-time-selection-view').style.display = 'none';
        document.getElementById('details-entry-view').style.display = 'block';
        document.getElementById('details-date-display').textContent = new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric' }).format(selectedDate);
        document.getElementById('details-time-display').textContent = selectedTime;
      });

      document.getElementById('back-to-calendar').addEventListener('click', () => {
        document.getElementById('details-entry-view').style.display = 'none';
        document.getElementById('date-time-selection-view').style.display = 'block';
      });

      // ==========================================
      // BACKEND SAVING LOGIC
      // ==========================================
      const bookingForm = document.getElementById('booking-form');
      const submitBtn = document.getElementById('submit-btn');

      bookingForm.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const y = selectedDate.getFullYear();
        const m = String(selectedDate.getMonth() + 1).padStart(2, '0');
        const d = String(selectedDate.getDate()).padStart(2, '0');
        const sqlDate = `${y}-${m}-${d}`;

        const formData = new FormData();
        formData.append('action', 'book');
        formData.append('booking_date', sqlDate);
        formData.append('booking_time', selectedTime);
        formData.append('facebook_link', document.getElementById('facebookLink').value);
        formData.append('notes', document.getElementById('notes').value);

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
        submitBtn.disabled = true;

        fetch('booking.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('booking-view').style.display = 'none';
                document.getElementById('confirmation-view').style.display = 'block';
                document.getElementById('confirm-date').textContent = document.getElementById('details-date-display').textContent;
                document.getElementById('confirm-time').textContent = selectedTime;
            } else {
                alert("Failed to save. Try again.");
                submitBtn.innerHTML = 'Schedule Event'; submitBtn.disabled = false;
            }
        });
      });

      document.getElementById('book-another').addEventListener('click', () => { window.location.reload(); });
      renderCalendar(); renderTimeSlots(); document.getElementById('selected-date-display').textContent = 'Select a Date';

      // ==========================================
      // LIVE CHAT AJAX LOGIC WITH UNREAD BADGE
      // ==========================================
      const chatInput = document.getElementById('chatInput');
      const sendChatBtn = document.getElementById('sendChatBtn');
      const chatBody = document.getElementById('chatBody');
      let chatInterval;
      
      let previousUnreadCount = 0;
      const originalTitle = document.title; // Default Title for Browser Alert

      function loadMessages() {
          fetch('chat_handler.php?action=getMessages')
          .then(res => res.json())
          .then(data => {
              if(data.status === 'success') {
                  chatBody.innerHTML = '<div class="text-center text-muted small mb-3">Savant Support, how may I help you?</div>';
                  data.messages.forEach(msg => {
                      const bubbleClass = msg.sender === 'user' ? 'user' : 'admin';
                      const senderName = msg.sender === 'user' ? 'You' : 'Savant Support';
                      
                      // Accurate Date Parsing Logic
                      const msgDate = new Date(msg.created_at);
                      const todayDate = new Date();
                      const yesterday = new Date(todayDate);
                      yesterday.setDate(yesterday.getDate() - 1);
                      let dateLabel = '';
                      
                      if (msgDate.toDateString() === todayDate.toDateString()) { 
                          dateLabel = 'Today'; 
                      } else if (msgDate.toDateString() === yesterday.toDateString()) { 
                          dateLabel = 'Yesterday'; 
                      } else { 
                          dateLabel = msgDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); 
                      }
                      
                      const timeString = msgDate.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'});
                      
                      // Accurate Date & Time Badge Design
                      chatBody.innerHTML += `
                          <div class="chat-bubble ${bubbleClass}">
                              <b>${senderName}</b><br>
                              ${msg.message}
                              <div class="text-end w-100">
                                <span class="chat-time-badge"><i class="bi bi-clock me-1"></i>${dateLabel} at ${timeString}</span>
                              </div>
                          </div>
                      `;
                  });
                  chatBody.scrollTop = chatBody.scrollHeight;
              }
          });
      }

      function sendMessage() {
          const text = chatInput.value.trim();
          if(text === '') return;
          
          const fd = new FormData();
          fd.append('action', 'sendMessage');
          fd.append('message', text);
          chatInput.value = '';

          fetch('chat_handler.php', { method: 'POST', body: fd })
          .then(res => res.json())
          .then(data => { if(data.status === 'success') loadMessages(); });
      }

      if(sendChatBtn) sendChatBtn.addEventListener('click', sendMessage);
      if(chatInput) {
          chatInput.addEventListener('keypress', function(e) {
              if(e.key === 'Enter') { e.preventDefault(); sendMessage(); }
          });
      }

      // Check Unread Logic With Browser Alert
      function checkUnreadMessages() {
          fetch('chat_handler.php?action=getUnreadCount')
          .then(res => res.json())
          .then(data => {
              const badge = document.getElementById('chatUnreadBadge');
              if(data.status === 'success' && data.count > 0) {
                  badge.textContent = data.count;
                  badge.style.display = 'flex'; // Ipapamukha yung number kapag > 0
                  
                  // BROWSER TITLE ALERT
                  if (data.count > previousUnreadCount) {
                      document.title = `(${data.count}) New Message! - Savant`;
                  }
                  previousUnreadCount = data.count;
              } else {
                  badge.style.display = 'none'; // Tatago pag 0 na
                  document.title = originalTitle; // Ibalik sa dati yung browser title kung walang unread
                  previousUnreadCount = 0;
              }
          });
      }

      setInterval(checkUnreadMessages, 3000);
      checkUnreadMessages();

      window.toggleChat = function() {
          const container = document.getElementById('chatContainer');
          container.classList.toggle('show');
          
          if(container.classList.contains('show')) {
              const fd = new FormData();
              fd.append('action', 'markAsRead');
              fetch('chat_handler.php', { method: 'POST', body: fd })
              .then(() => { 
                  document.getElementById('chatUnreadBadge').style.display = 'none'; 
                  document.title = originalTitle; // Reset browser title 
                  previousUnreadCount = 0;
              });

              loadMessages();
              chatInterval = setInterval(loadMessages, 3000);
          } else {
              clearInterval(chatInterval);
          }
      };
    });
  </script>
</body>
</html>