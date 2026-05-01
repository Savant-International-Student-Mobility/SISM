<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 

// I-check kung may naka-login ba talaga. Kapag wala, ibalik sa login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// KUNIN ANG DATA NG USER SA DATABASE
$stmt = $conn->prepare("SELECT firstname, lastname, phone, email, created_at, profile_pic, birthday, facebook, instagram FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    session_destroy();
    header("Location: login.php");
    exit();
}

// LOGIC PARA SA PROGRESS STEPS
$step1 = true;
$step2 = !empty($user['profile_pic']); 
$step3 = !empty($user['birthday']);    
$step4 = (!empty($user['facebook']) || !empty($user['instagram'])); 
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Savant Dashboard</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="img/logoulit.png" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">

  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-progress-bg: #D9D9D9; --savant-progress-active: #087830; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #085723; --savant-highlight-light-green: #98FB98; }
    ::-webkit-scrollbar { width: 12px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #0C470C, #3BA43B); border-radius: 6px; } ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #023621, #2a7c2a); }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow: hidden; }
    .main-wrapper { display: flex; height: 100vh; }
    .sidebar { background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0; box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden; position: fixed; top: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031; }
    .sidebar:hover { width: 280px; } .sidebar .logo { font-size: 2.2rem; font-weight: 700; margin-bottom: 30px; text-align: center; letter-spacing: 1px; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; } .sidebar:hover .logo { opacity: 1; }
    .sidebar .nav { flex-grow: 1; overflow-y: auto; overflow-x: hidden; } .sidebar .nav::-webkit-scrollbar { width: 8px; } .sidebar .nav::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); } .sidebar .nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 4px; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 30px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease; text-decoration: none; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #14552b; } .sidebar .nav-link i { font-size: 1.2rem; min-width: 20px; text-align: center; transition: transform 0.2s ease-in-out; } .sidebar .nav-link:hover i { transform: scale(1.1); }
    .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; } .sidebar:hover .nav-link span { opacity: 1; }
    .sidebar .footer-text { font-size: 0.8rem; color: #bbb; text-align: center; padding: 15px 0; opacity: 0; transition: opacity 0.3s ease-in-out; } .sidebar:hover .footer-text { opacity: 1; }
    .content-area { flex-grow: 1; height: 100vh; overflow-y: auto; margin-left: 70px; }
    .header { background-color: var(--savant-bg-light); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; } .header-date-mobile { font-weight: 500; color: var(--savant-text-dark); }
    .user-status .badge { background-color: var(--savant-button-maroon); font-size: 0.8rem; font-weight: 500; padding: 5px 15px; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; }
    .power-btn i { color: #dc3545; transition: color 0.2s ease-in-out, transform 0.2s ease-in-out; font-size: 1.5rem; } .power-btn:hover i { color: #a71d2a; transform: scale(1.1); }
    .tour-help-btn { background: none; border: 2px solid var(--savant-highlight-light-green); border-radius: 50%; color: var(--savant-text-dark); font-size: 1.5rem; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; margin-right: 10px; transition: background-color 0.2s, color 0.2s; }
    .main-content { padding: 30px; animation: fadeIn 0.5s ease-in-out; } @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .user-info-card { background-color: var(--savant-card-bg); padding: 30px; border-radius: 12px; box-shadow: var(--savant-shadow); }
    .profile-display-area { width: 96px; height: 96px; display: flex; justify-content: center; align-items: center; font-size: 6rem; color: var(--savant-primary-green); }
    .profile-name { font-size: 1.5rem; font-weight: 600; text-transform: uppercase; }
    .quick-actions-card { display: flex; align-items: center; justify-content: center; } .logo-only-img { max-width: 200px; margin: auto; }
    
    .progress-steps-container { background-color: var(--savant-card-bg); border-radius: 12px; box-shadow: var(--savant-shadow); padding: 30px 40px; margin-bottom: 1.5rem; }
    .progress-steps { display: flex; justify-content: space-between; align-items: flex-start; position: relative; padding: 0 20px; }
    .progress-steps::before { content: ''; position: absolute; top: 30px; left: 50px; right: 50px; height: 4px; background-color: var(--savant-progress-bg); z-index: 1; }
    .step-item { text-align: center; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; width: 100px; }
    .step-circle { width: 60px; height: 60px; line-height: 60px; border-radius: 50%; background-color: var(--savant-progress-bg); color: #777; font-weight: 700; font-size: 1.2rem; margin-bottom: 10px; transition: 0.4s ease; border: 4px solid var(--savant-card-bg); box-shadow: 0 0 0 2px var(--savant-progress-bg); }
    .step-item.active .step-circle { background-color: var(--savant-primary-green); color: white; box-shadow: 0 0 0 2px var(--savant-primary-green); }
    .step-label { font-size: 0.85rem; color: var(--savant-text-light); font-weight: 500; }
    .step-item.active .step-label { color: var(--savant-primary-green); font-weight: 600; }

    .contents-grid .card { border: none; box-shadow: var(--savant-shadow); border-radius: 12px; overflow: hidden; transition: transform 0.2s; }
    .contents-grid .card:hover { transform: translateY(-5px); } .contents-grid .card img { height: 180px; object-fit: cover; }
    .floating-btn { position: fixed; bottom: 30px; right: 30px; background-color: #085723; text-decoration: none; font-size: 2rem; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); z-index: 1030; color: white;}
    
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
    .chat-time-badge { 
        font-size: 0.65rem; 
        background-color: rgba(0, 0, 0, 0.15); 
        padding: 3px 8px; 
        border-radius: 12px; 
        display: inline-block; 
        margin-top: 5px; 
    }
    .chat-bubble.user .chat-time-badge {
        color: #e6e6e6;
        background-color: rgba(255, 255, 255, 0.2);
    }
    .chat-bubble.admin .chat-time-badge {
        color: #666;
    }
    
    .chat-toggle-btn { position: fixed; bottom: 30px; right: 100px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); cursor: pointer; z-index: 100; transition: transform 0.3s ease, background-color 0.3s ease; border: none; }
    
    /* SIMPLE NUMBER BADGE (No Animation) */
    .chat-unread-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #dc3545; /* Red color */
        color: white;
        border-radius: 50%;
        font-size: 0.8rem; /* Inayos ang size ng text */
        font-weight: bold;
        width: 24px;  /* Sakto para maging perfect circle */
        height: 24px;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid white; /* Border design */
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    @media (max-width: 992px) { body { overflow: auto; padding-bottom: 60px; } .content-area { margin-left: 0; } .sidebar { width: 100%; height: 50px; position: fixed; top: auto; bottom: 0; left: 0; z-index: 1029; flex-direction: row; align-items: center; padding: 0; transition: none; overflow-x: auto; overflow-y: hidden; } .sidebar .logo, .sidebar .footer-text { display: none; } .sidebar .nav { flex-direction: row; justify-content: space-around; width: 100%; } .sidebar .nav-link span { display: none; } .header-title { display: none; } .floating-btn { bottom: 80px; right: 15px; } .chat-toggle-btn { bottom: 80px; right: 85px; } 
    .progress-steps::before { display: none; }
    .progress-steps { flex-wrap: wrap; justify-content: center; gap: 15px;}
    }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <aside class="sidebar" data-intro="Navigate through your account using this sidebar." data-step="1">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <a class="nav-link active" href="#"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="booking.php"><i class="bi bi-calendar-check"></i><span>Book Consultation</span></a>
        <a class="nav-link" href="statement.php"><i class="bi bi-receipt"></i><span>Statement of Account</span></a>
        <a class="nav-link" href="documents.php"><i class="bi bi-file-earmark-text"></i><span>Documents</span></a>
        <a class="nav-link" href="forms.php"><i class="bi bi-journal-text"></i><span>Forms</span></a>
        <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i><span>Notifications</span></a>
        <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i><span>Settings</span></a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i><span>Profile</span></a>
      </nav>
      <div class="footer-text mt-auto">&copy; 2026 SAVANT</div>
    </aside>

    <div class="content-area">
      <div class="header">
        <div class="header-brand d-flex align-items-center">
          <span class="header-title">Savant-International Student Mobility</span>
        </div>

        <div class="header-date-mobile d-lg-none" id="mobileDate"></div>

        <div class="user-status d-flex align-items-center gap-2">
          <div class="me-3 d-none d-lg-block" style="font-weight: 500;" id="desktopDate"></div>
          
          <button class="tour-help-btn" id="tourToggleButton" data-intro="Need help? Click this button anytime to replay the tour." data-step="5" onclick="startTour()">
              <i class="bi bi-question-circle"></i>
          </button>
          
          <a href="#" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="bi bi-power"></i>
          </a>
          <span class="badge"><?php echo htmlspecialchars($user['firstname']); ?></span>
        </div>
      </div>

      <main class="main-content">
        <div class="row g-4 mb-4">
          <div class="col-12 col-lg-7" data-intro="Here you can view your profile summary and contact details." data-step="2">
            <div class="user-info-card h-100">
              <div class="profile-info d-flex align-items-center gap-3">
                <div class="profile-display-area">
                    <?php if(!empty($user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
                <span class="profile-name">
                  <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                </span>
              </div>
              <div class="contact-info mt-3">
                <div><i class="bi bi-telephone-fill text-muted me-2"></i> <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Phone not set'; ?></div>
                <div><i class="bi bi-envelope-fill text-muted me-2"></i> <?php echo htmlspecialchars($user['email']); ?></div>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-5">
            <div class="quick-actions-card h-100">
              <img src="img/logo.png" alt="SAVANT Logo" class="logo-only-img img-fluid">
            </div>
          </div>
        </div>

        <div class="progress-steps-container" data-intro="This bar tracks your profile completion. Connect your socials and add your picture in the Profile settings!" data-step="3">
            <div class="progress-steps">
              <div class="step-item <?php echo $step1 ? 'active' : ''; ?>">
                <div class="step-circle"><i class="bi <?php echo $step1 ? 'bi-check-lg' : 'bi-1-circle'; ?>"></i></div>
                <div class="step-label">Account Created</div>
              </div>
              <div class="step-item <?php echo $step2 ? 'active' : ''; ?>">
                <div class="step-circle"><i class="bi <?php echo $step2 ? 'bi-check-lg' : 'bi-2-circle'; ?>"></i></div>
                <div class="step-label">Profile Picture</div>
              </div>
              <div class="step-item <?php echo $step3 ? 'active' : ''; ?>">
                <div class="step-circle"><i class="bi <?php echo $step3 ? 'bi-check-lg' : 'bi-3-circle'; ?>"></i></div>
                <div class="step-label">Birthday Added</div>
              </div>
              <div class="step-item <?php echo $step4 ? 'active' : ''; ?>">
                <div class="step-circle"><i class="bi <?php echo $step4 ? 'bi-check-lg' : 'bi-4-circle'; ?>"></i></div>
                <div class="step-label">Socials Linked</div>
              </div>
            </div>
        </div>
      </main>

  <div class="chat-container" id="chatContainer">
    <div class="chat-header d-flex justify-content-between align-items-center" onclick="toggleChat()">
      <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Live Chat</h5>
      <i class="bi bi-x-lg"></i>
    </div>
    <div class="chat-body p-3" id="chatBody">
      <div class="text-center text-muted small mb-3">Savant Support, how may I help you?</div>
    </div>
    <div class="chat-footer p-3 border-top">
      <div class="input-group">
        <input type="text" id="chatInput" class="form-control" placeholder="Type a message..." autocomplete="off">
        <button class="btn btn-outline-secondary" type="button" id="sendChatBtn"><i class="bi bi-send-fill"></i></button>
      </div>
    </div>
  </div>

  <a href="bookflight.php" class="floating-btn" data-intro="Click here to quickly book a flight or consultation!" data-step="4"><i class="bi bi-plus-lg"></i></a>
  
  <button class="chat-toggle-btn" onclick="toggleChat()">
      <i class="bi bi-chat-dots"></i>
      <!-- NUMBER BADGE NOTIFICATION -->
      <span class="chat-unread-badge" id="chatUnreadBadge">0</span>
  </button>

  <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to log out?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const options = { month: 'long', day: 'numeric', year: 'numeric' };
      const today = new Date().toLocaleDateString('en-US', options);

      if (document.getElementById('desktopDate')) document.getElementById('desktopDate').textContent = today;
      if (document.getElementById('mobileDate')) document.getElementById('mobileDate').textContent = today;
      
      if(!localStorage.getItem('tourCompleted')) {
          startTour();
      }

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

    function startTour() {
      introJs().setOptions({
        showProgress: true,
        showBullets: false,
        tooltipClass: 'customTooltip'
      }).oncomplete(function() {
        localStorage.setItem('tourCompleted', 'true');
      }).onexit(function() {
        localStorage.setItem('tourCompleted', 'true');
      }).start();
    }
  </script>
</body>
</html>