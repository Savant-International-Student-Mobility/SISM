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
$msg = '';

// KUNIN ANG DATA NG USER PARA I-AUTOFILL ANG FORM
$stmt = $conn->prepare("SELECT firstname, lastname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// KAPAG SINUBMIT ANG FLIGHT FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_flight_btn'])) {
    $dept_date = $_POST['departureDate'];
    $dept_loc = trim($_POST['departureLocation']);
    $destination = trim($_POST['destination']);

    $insert = $conn->prepare("INSERT INTO flights (user_id, first_name, last_name, email, departure_date, departure_location, destination) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("issssss", $user_id, $user['firstname'], $user['lastname'], $user['email'], $dept_date, $dept_loc, $destination);

    if ($insert->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'>
                    <i class='bi bi-check-circle-fill me-2'></i> Flight booking submitted successfully! Our team will contact you shortly.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i> Something went wrong. Please try again.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAVANT Book Flight</title>
  <link rel="icon" href="img/logoulit.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #087830; }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow: hidden; }
    .main-wrapper { display: flex; height: 100vh; }
    ::-webkit-scrollbar { width: 12px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #0C470C, #3BA43B); border-radius: 6px; } ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #023621, #2a7c2a); }
    .sidebar { background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0; box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden; position: fixed; top: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031; }
    .sidebar:hover { width: 280px; }
    .sidebar .logo { font-size: 2.2rem; font-weight: 700; margin-bottom: 30px; text-align: center; letter-spacing: 1px; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; }
    .sidebar:hover .logo { opacity: 1; }
    .sidebar .nav { flex-grow: 1; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 30px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease; text-decoration: none;}
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #14552b; }
    .sidebar .nav-link i { font-size: 1.2rem; min-width: 20px; text-align: center; }
    .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; }
    .sidebar:hover .nav-link span { opacity: 1; }
    .sidebar .footer-text { font-size: 0.8rem; color: #bbb; text-align: center; padding: 15px 0; opacity: 0; transition: opacity 0.3s ease-in-out; margin-top: auto; }
    .sidebar:hover .footer-text { opacity: 1; }
    .content-area { flex-grow: 1; height: 100vh; overflow-y: auto; margin-left: 70px; }
    .header { background-color: var(--savant-bg-light); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-brand { gap: 10px; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; }
    .user-status { display: flex; align-items: center; gap: 10px; }
    .user-status .badge { background-color: var(--savant-button-maroon); font-size: 0.8rem; font-weight: 500; padding: 5px 15px; border-radius: 20px; color: white;}
    .power-btn i { color: #dc3545; font-size: 1.5rem; } .power-btn:hover i { color: #a71d2a; }
    .main-content { flex-grow: 1; padding: 30px; }
    .main-content h1 { font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
    .content-card { background-color: var(--savant-card-bg); border-radius: 12px; box-shadow: var(--savant-shadow); padding: 30px; }
    .flight-form-card .form-label { font-weight: 600; }
    .flight-form-card .btn-primary { background-color: var(--savant-button-maroon); border: none; transition: background-color 0.2s; }
    
    /* ================= CHAT STYLES ================= */
    .chat-container { position: fixed; bottom: 100px; right: 30px; width: 350px; background-color: white; border-radius: 12px; box-shadow: var(--savant-shadow); display: none; flex-direction: column; z-index: 99; overflow: hidden;}
    .chat-container.show { display: flex; }
    .chat-header { background-color: var(--savant-primary-green); color: white; padding: 1rem; cursor: pointer; }
    .chat-body { padding: 1rem; height: 350px; overflow-y: auto; background-color: var(--savant-bg-light); display: flex; flex-direction: column; }
    .chat-footer { padding: 1rem; border-top: 1px solid var(--savant-light-gray); background: white;}
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
    
    .chat-toggle-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); cursor: pointer; z-index: 100; transition: transform 0.3s ease, background-color 0.3s ease; border: none;}
    
    @media (max-width: 992px) { body { padding-top: 60px; padding-bottom: 50px; overflow: auto; } .main-wrapper { flex-direction: column; height: auto; } .content-area { height: auto; overflow-y: visible; margin-left: 0; } .header { position: fixed; top: 0; left: 0; right: 0; z-index: 1030; } .header-title { display: none; } .main-content { padding-top: 15px; } .sidebar { width: 100%; height: 50px; position: fixed; top: auto; bottom: 0; left: 0; z-index: 1029; flex-direction: row; align-items: center; padding: 0; transition: none; overflow: hidden; } .sidebar:hover { width: 100%; } .sidebar .logo, .sidebar .footer-text { display: none; } .sidebar .nav { display: flex; flex-direction: row; align-items: center; height: 100%; width: 100%; justify-content: space-around; } .sidebar .nav-link { flex: 1; justify-content: center; align-items: center; padding: 0 5px; gap: 0; height: 100%; } .sidebar .nav-link i { font-size: 1.5rem; margin-bottom: 0; } .sidebar .nav-link span, .sidebar:hover .nav-link span { display: none; } .chat-toggle-btn { bottom: 80px; right: 15px; } .chat-container { display: none !important; } }
  </style>
</head>
<body>
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <!-- ALL LINKS UPDATED TO .PHP -->
        <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="booking.php"><i class="bi bi-calendar-check"></i><span>Book Consultation</span></a>
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
          <!-- LOGOUT TRIGGER -->
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-power"></i></a>
          <!-- DYNAMIC USER BADGE -->
          <span class="badge"><?php echo htmlspecialchars($user['firstname']); ?></span>
        </div>
      </div>
      
      <main class="main-content">
        <h1>Let us help you plan your trip!</h1>
        
        <?php echo $msg; // DITO LALABAS ANG SUCCESS/ERROR MESSAGE ?>

        <div class="row g-4 justify-content-center mt-2">
          <div class="col-12 col-lg-8">
            <div class="content-card flight-form-card">
              <h5 class="mb-4">Fill out the form to plan your flight.</h5>

              <!-- FORM NA NAKA-CONNECT SA PHP -->
              <form action="bookflight.php" method="POST">
                <div class="row g-3 mb-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['firstname']); ?>" readonly style="background-color: #f8f9fa;">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['lastname']); ?>" readonly style="background-color: #f8f9fa;">
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Email Address</label>
                  <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="mb-3">
                  <label for="departureDate" class="form-label">Departure Date *</label>
                  <input type="date" class="form-control" id="departureDate" name="departureDate" required>
                </div>
                
                <div class="mb-3">
                  <label for="departureLocation" class="form-label">Departure Location *</label>
                  <input type="text" class="form-control" id="departureLocation" name="departureLocation" placeholder="e.g. Manila, Philippines" required>
                </div>
                
                <div class="mb-3">
                  <label for="destination" class="form-label">Destination *</label>
                  <input type="text" class="form-control" id="destination" name="destination" placeholder="e.g. Toronto, Canada" required>
                </div>
                
                <div class="d-grid mt-4">
                  <button type="submit" name="book_flight_btn" class="btn btn-primary py-2 text-white fw-bold">Book Now</button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- ================= LIVE CHAT UI ================= -->
  <div class="chat-container" id="chatContainer">
    <div class="chat-header d-flex justify-content-between align-items-center" onclick="toggleChat()">
      <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Live Chat</h5>
      <i class="bi bi-x-lg text-white"></i>
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
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
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
      document.getElementById('headerDate').textContent = new Date().toLocaleDateString('en-US', {
        month: 'long', day: 'numeric', year: 'numeric'
      });
      
      // I-set ang minimum date ng kalendaryo sa "Ngayon" para hindi makapag-book in the past
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('departureDate').setAttribute('min', today);

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