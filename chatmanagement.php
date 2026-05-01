<?php
session_start();

// PIGILAN ANG BROWSER NA I-SAVE ANG PAGE SA CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'db.php'; 
date_default_timezone_set('Asia/Manila');

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['firstname'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_convo'])) {
    $del_user_id = $_POST['delete_user_id'];
    $conn->query("DELETE FROM chats WHERE user_id = $del_user_id");
    header("Location: chatmanagement.php");
    exit();
}

// ==========================================
// KUNIN ANG MGA USERS NA MAY CHAT HISTORY AT ANG PROFILE NILA
// ==========================================
$stmt_users = $conn->query("
    SELECT u.id, u.firstname, u.lastname, u.profile_pic, u.last_active 
    FROM chats c 
    JOIN users u ON c.user_id = u.id 
    GROUP BY u.id 
    ORDER BY MAX(c.created_at) DESC
");
$convo_users = $stmt_users->fetch_all(MYSQLI_ASSOC);

$active_user_id = isset($_GET['user']) ? $_GET['user'] : (!empty($convo_users) ? $convo_users[0]['id'] : null);
$active_user_name = "Select a Conversation";
$active_user_status = "";

if ($active_user_id) {
    foreach ($convo_users as $u) {
        if ($u['id'] == $active_user_id) {
            $active_user_name = $u['firstname'] . ' ' . $u['lastname'];
            
            // Check kung online (Kung nag-active in the last 1 minute)
            $is_online = false;
            if (!empty($u['last_active'])) {
                $last_active = strtotime($u['last_active']);
                $now = time();
                if (($now - $last_active) <= 60) { // 60 seconds threshold
                    $is_online = true;
                }
            }
            $active_user_status = $is_online ? "<span class='text-success small fw-bold'><i class='bi bi-circle-fill' style='font-size:0.5rem;'></i> Active Now</span>" : "<span class='text-muted small'><i class='bi bi-circle-fill' style='font-size:0.5rem;'></i> Offline</span>";
            break;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" href="img/logoulit.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #085723; --savant-highlight-light-green: #98FB98; }
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
    .main-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
    
    .chat-container { background-color: var(--savant-card-bg); border-radius: 12px; display: flex; flex-grow: 1; overflow: hidden; box-shadow: var(--savant-shadow); border: 1px solid #ddd; height: calc(100vh - 180px);}
    .chat-sidebar { width: 300px; border-right: 1px solid #eee; display: flex; flex-direction: column; background: #fcfcfc; }
    .chat-search { padding: 15px; border-bottom: 1px solid #eee; }
    .conversation-list { overflow-y: auto; flex-grow: 1; }
    .convo-item { display: flex; align-items: center; padding: 15px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f9f9f9; text-decoration: none; color: inherit; }
    .convo-item:hover { background: #f0f7f2; }
    .convo-item.active { background: #e8f5ed; border-left: 4px solid var(--savant-primary-green); }
    
    .avatar-circle { width: 45px; height: 45px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 12px; color: #666; flex-shrink:0; position: relative;}
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .status-dot { position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; }
    .status-online { background-color: #12aa42; }
    .status-offline { background-color: #adb5bd; }
    
    .convo-name-container { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .convo-name { font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.95rem;}
    .convo-status-text { font-size: 0.7rem; color: #888; margin:0;}
    
    .chat-window { flex-grow: 1; display: flex; flex-direction: column; background: white; }
    .chat-window-header { padding: 15px 25px; border-bottom: 1px solid #eee; display:flex; flex-direction: column; }
    .chat-window-name { color: var(--savant-primary-green); font-weight: 700; font-size: 1.1rem; }
    
    .chat-messages { flex-grow: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; background-color: #fbfbfb; }
    .msg-bubble { max-width: 70%; padding: 12px 18px; border-radius: 15px; position: relative; }
    .msg-user { align-self: flex-start; background-color: var(--savant-primary-green); color: white; border-bottom-left-radius: 2px; }
    .msg-admin { align-self: flex-end; background-color: #e9ecef; color: var(--savant-text-dark); border-bottom-right-radius: 2px; }
    .msg-info { font-size: 0.75rem; display: block; margin-bottom: 4px; font-weight: 600; }
    .msg-time { font-size: 0.65rem; display: block; text-align: right; margin-top: 5px; opacity: 0.8; }
    .chat-input-area { padding: 20px; border-top: 1px solid #eee; }
    .chat-input-wrapper { display: flex; gap: 10px; background: #f1f3f4; padding: 5px; border-radius: 30px; }
    .chat-input-wrapper input { border: none; background: transparent; padding-left: 20px; box-shadow: none !important; width:100%;}
    .btn-send { background-color: var(--savant-primary-green); color: white; border-radius: 30px; padding: 5px 25px; border: none; transition: 0.2s;}
    .btn-send:hover { filter: brightness(1.1); }
    
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
        <a class="nav-link active" href="chatmanagement.php"><i class="bi bi-chat-dots-fill"></i><span>Chat Management</span></a>
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
        <h3 class="fw-bold mb-3" style="color: var(--savant-primary-green);">Chat Management</h3>

        <div class="chat-container">
          
          <!-- LEFT SIDE: USER LIST -->
          <div class="chat-sidebar">
            <div class="chat-search">
              <input type="text" class="form-control rounded-pill small" id="searchUser" placeholder="Search Conversations...">
            </div>
            
            <div class="conversation-list" id="convoList">
              <?php if(empty($convo_users)): ?>
                  <p class="text-muted text-center mt-4 small">No conversations found.</p>
              <?php else: ?>
                  <?php foreach($convo_users as $u): ?>
                      <?php 
                          $initials = strtoupper(substr($u['firstname'], 0, 1) . substr($u['lastname'], 0, 1)); 
                          $isActiveConvo = ($active_user_id == $u['id']) ? 'active' : '';
                          
                          // Check kung online (last active in the last 60 seconds)
                          $is_online = false;
                          if (!empty($u['last_active'])) {
                              $last_active = strtotime($u['last_active']);
                              if ((time() - $last_active) <= 60) {
                                  $is_online = true;
                              }
                          }
                          $status_class = $is_online ? 'status-online' : 'status-offline';
                          $status_text = $is_online ? 'Active Now' : 'Offline';
                      ?>
                      
                      <!-- Pindutin para ma-load ang messages -->
                      <a href="chatmanagement.php?user=<?php echo $u['id']; ?>" class="convo-item <?php echo $isActiveConvo; ?>">
                        <div class="avatar-circle">
                            <!-- IPAPAKITA ANG PICTURE KUNG MERON, INITIALS KUNG WALA -->
                            <?php if(!empty($u['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($u['profile_pic']); ?>">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                            
                            <!-- THE GREEN/GRAY STATUS DOT -->
                            <span class="status-dot <?php echo $status_class; ?>"></span>
                        </div>
                        
                        <div class="convo-name-container">
                            <p class="convo-name"><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></p>
                            <p class="convo-status-text <?php echo $is_online ? 'text-success' : ''; ?>"><?php echo $status_text; ?></p>
                        </div>
                        
                        <!-- Delete Convo Button -->
                        <form action="chatmanagement.php" method="POST" class="ms-2" onsubmit="return confirm('Delete this conversation?');">
                            <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="delete_convo" class="btn btn-sm text-danger p-0 border-0"><i class="bi bi-trash3"></i></button>
                        </form>
                      </a>
                  <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- RIGHT SIDE: CHAT WINDOW -->
          <div class="chat-window">
            <div class="chat-window-header">
              <span class="chat-window-name"><?php echo htmlspecialchars($active_user_name); ?></span>
              <!-- IDINAGDAG ANG ID DITO PARA MA-TARGET NG JAVASCRIPT ANG PAG-UPDATE -->
              <span id="headerStatusArea"><?php echo $active_user_status; ?></span>
            </div>

            <div class="chat-messages" id="chatMessages">
              <?php if(!$active_user_id): ?>
                  <div class="text-center text-muted mt-5">Please select a user to start chatting.</div>
              <?php else: ?>
                  <div class="text-center text-muted mt-5"><span class="spinner-border spinner-border-sm"></span> Loading messages...</div>
              <?php endif; ?>
            </div>

            <?php if($active_user_id): ?>
            <div class="chat-input-area">
              <div class="chat-input-wrapper">
                <input type="hidden" id="chatTargetUser" value="<?php echo $active_user_id; ?>">
                <input type="text" id="chatInput" class="form-control" placeholder="Reply to <?php echo htmlspecialchars($active_user_name); ?>..." autocomplete="off">
                <button class="btn-send" id="sendBtn">Send</button>
              </div>
            </div>
            <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', function () {
        
        // SEARCH FUNCTION PARA SA CONVERSATION LIST
        const searchInput = document.getElementById('searchUser');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = searchInput.value.toLowerCase();
                let items = document.querySelectorAll('.convo-item');
                
                items.forEach(item => {
                    let name = item.querySelector('.convo-name').innerText.toLowerCase();
                    if(name.includes(filter)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        // ====================================================
        // AJAX LOGIC PARA MAG-FETCH AT MAG-SEND NG MESSAGE 
        // ====================================================
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendBtn');
        const targetUserId = document.getElementById('chatTargetUser') ? document.getElementById('chatTargetUser').value : null;

        function loadMessages() {
            if(!targetUserId) return;

            fetch(`chat_handler.php?action=getAdminMessages&target_user=${targetUserId}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    chatMessages.innerHTML = '';
                    
                    if(data.messages.length === 0) {
                        chatMessages.innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Reply to start a conversation.</div>';
                    } else {
                        data.messages.forEach(msg => {
                            const bubbleClass = msg.sender === 'admin' ? 'msg-admin' : 'msg-user';
                            const senderName = msg.sender === 'admin' ? 'You (Admin)' : 'User';
                            const timeString = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            
                            chatMessages.innerHTML += `
                                <div class="msg-bubble ${bubbleClass}">
                                    <span class="msg-info">${senderName}</span>
                                    ${msg.message}
                                    <span class="msg-time">${timeString}</span>
                                </div>
                            `;
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll sa pinakababa
                    }
                }
            });
        }

        function sendMessage() {
            if(!targetUserId) return;
            const text = chatInput.value.trim();
            if(text === '') return;
            
            const fd = new FormData();
            fd.append('action', 'sendAdminMessage');
            fd.append('message', text);
            fd.append('target_user', targetUserId);
            
            chatInput.value = '';

            fetch('chat_handler.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => { if(data.status === 'success') loadMessages(); });
        }

        if(sendBtn) sendBtn.addEventListener('click', sendMessage);
        if(chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if(e.key === 'Enter') { e.preventDefault(); sendMessage(); }
            });
        }

        // I-load ang messages pagkabukas ng page, at i-refresh every 3 seconds
        if(targetUserId) {
            loadMessages();
            setInterval(loadMessages, 3000);
        }
        
        // I-refresh din ang buong page silently every 10 secs para mag-update ang online/offline indicator sa gilid AT sa taas
        setInterval(() => {
            fetch(window.location.href).then(res => res.text()).then(html => {
                let doc = new DOMParser().parseFromString(html, "text/html"); // Tinanggal ko ang typo error dito (parseParseFromString)
                
                // 1. I-update ang sidebar listahan
                let newSidebar = doc.getElementById('convoList');
                if (newSidebar) {
                    document.getElementById('convoList').innerHTML = newSidebar.innerHTML;
                }
                
                // 2. I-update ang Status sa Header (Ito ang nawawala kanina kaya sila hindi tugma)
                let newHeaderStatus = doc.getElementById('headerStatusArea');
                if (newHeaderStatus && document.getElementById('headerStatusArea')) {
                    document.getElementById('headerStatusArea').innerHTML = newHeaderStatus.innerHTML;
                }
            });
        }, 10000);
    });
  </script>
</body>
</html>