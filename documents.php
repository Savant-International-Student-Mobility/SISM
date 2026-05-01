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

// KUNIN ANG USER INFO
$stmt = $conn->prepare("SELECT firstname FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ==========================================
// FILE UPLOAD LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document_file'])) {
    $upload_dir = 'uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = basename($_FILES["document_file"]["name"]);
    $unique_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
    $target_file = $upload_dir . $unique_file_name;
    
    if (move_uploaded_file($_FILES["document_file"]["tmp_name"], $target_file)) {
        $insert = $conn->prepare("INSERT INTO documents (user_id, file_name, file_path, status) VALUES (?, ?, ?, 'pending')");
        $insert->bind_param("iss", $user_id, $file_name, $target_file);
        if ($insert->execute()) {
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'>File uploaded successfully! It is now pending for review. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm'>Sorry, there was an error uploading your file. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// DELETE FILE LOGIC
// ==========================================
if (isset($_POST['delete_id'])) {
    $del_id = $_POST['delete_id'];
    
    $get_file = $conn->prepare("SELECT file_path FROM documents WHERE id = ? AND user_id = ?");
    $get_file->bind_param("ii", $del_id, $user_id);
    $get_file->execute();
    $file_result = $get_file->get_result();
    
    if ($file_result->num_rows > 0) {
        $file_data = $file_result->fetch_assoc();
        if (file_exists($file_data['file_path'])) {
            unlink($file_data['file_path']); 
        }
        
        $delete = $conn->prepare("DELETE FROM documents WHERE id = ?");
        $delete->bind_param("i", $del_id);
        $delete->execute();
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'>File deleted successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// FETCH ALL DOCUMENTS OF USER
// ==========================================
$docs = ['pending' => [], 'approved' => [], 'cancelled' => []];
$stmt_docs = $conn->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt_docs->bind_param("i", $user_id);
$stmt_docs->execute();
$result_docs = $stmt_docs->get_result();

while ($row = $result_docs->fetch_assoc()) {
    $docs[$row['status']][] = $row;
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SAVANT Documents</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="img/logoulit.png" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #087830; }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-light-gray); color: var(--savant-text-dark); overflow: hidden; }
    ::-webkit-scrollbar { width: 12px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #0C470C, #3BA43B); border-radius: 6px; } ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #023621, #2a7c2a); }
    .main-wrapper { display: flex; height: 100vh; }
    .sidebar { background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0; box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden; position: fixed; top: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031; }
    .sidebar:hover { width: 280px; }
    .sidebar .logo { font-size: 2.2rem; font-weight: 700; margin-bottom: 30px; text-align: center; letter-spacing: 1px; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; }
    .sidebar:hover .logo { opacity: 1; } .sidebar .nav { flex-grow: 1; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 30px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease, color 0.3s ease; text-decoration:none;}
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #14552b; color: #fff; }
    .sidebar .nav-link i { font-size: 1.2rem; min-width: 20px; text-align: center; transition: transform 0.3s ease; }
    .sidebar .nav-link:hover i { transform: scale(1.1); }
    .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; }
    .sidebar:hover .nav-link span { opacity: 1; }
    .sidebar .footer-text { font-size: 0.8rem; color: #bbb; text-align: center; padding: 15px 0; opacity: 0; transition: opacity 0.3s ease-in-out; margin-top: auto; }
    .sidebar:hover .footer-text { opacity: 1; }
    .content-area { flex-grow: 1; height: 100vh; overflow-y: auto; margin-left: 70px; }
    .header { background-color: var(--savant-bg-light); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; }
    .user-status { display: flex; align-items: center; gap: 10px; }
    .user-status .badge { background-color: var(--savant-button-maroon); font-size: 0.8rem; font-weight: 500; padding: 5px 15px; border-radius: 20px; color:white; }
    .power-btn i { color: #dc3545; font-size: 1.5rem; } .power-btn:hover i { color: #a71d2a; }
    .main-content { flex-grow: 1; padding: 30px; animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .main-content h1 { font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
    .document-section { background-color: var(--savant-card-bg); border-radius: 12px; box-shadow: var(--savant-shadow); padding: 20px; margin-bottom: 2rem; }
    .document-header { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1rem; gap: 20px; }
    .header-icon-container { display: flex; flex-direction: column; align-items: center; cursor: pointer; }
    .btn-icon { background: none; border: none; font-size: 1.25rem; color: var(--savant-text-light); transition: color 0.3s ease, transform 0.3s ease; }
    .btn-icon:hover { color: var(--savant-primary-green); transform: translateY(-2px); }
    .icon-label { font-size: 0.75rem; color: var(--savant-text-light); margin-top: 4px; }
    .file-preview-container { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .file-preview-item { text-align: center; width: 120px; cursor: pointer; padding: 10px; border-radius: 8px; transition: all 0.2s ease-in-out; position: relative; border: 2px solid transparent;}
    .file-preview-item:hover { background-color: var(--savant-bg-light); transform: translateY(-3px); box-shadow: var(--savant-shadow); }
    .file-preview-item.selected { background-color: #e8f5e9; border: 2px solid var(--savant-primary-green); }
    .file-preview-item i { font-size: 3rem; color: var(--savant-primary-green); }
    .file-preview-item .file-name { font-size: 0.8rem; color: var(--savant-text-dark); margin-top: 0.5rem; word-wrap: break-word; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .status-tabs .nav-link { color: var(--savant-text-light); border: none; border-bottom: 2px solid transparent; transition: all 0.3s ease; }
    .status-tabs .nav-link.active { color: var(--savant-primary-green); border-bottom-color: var(--savant-primary-green); font-weight: 600; }
    .floating-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); text-decoration: none; z-index: 100; transition: transform 0.2s; }
    .floating-btn:hover { background-color: var(--savant-dark-green); transform: scale(1.1); }
    
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

    .chat-toggle-btn { position: fixed; bottom: 30px; right: 100px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); cursor: pointer; z-index: 100; transition: transform 0.3s ease, background-color 0.3s ease; }
    
    @media (max-width: 992px) {
      body { padding-top: 60px; padding-bottom: 50px; overflow: auto; }
      .main-wrapper { flex-direction: column; height: auto; }
      .content-area { height: auto; overflow-y: visible; margin-left: 0; }
      .header { position: fixed; top: 0; left: 0; right: 0; z-index: 1030; }
      .header-title { display: none; }
      .sidebar { width: 100%; height: 50px; position: fixed; bottom: 0; left: 0; z-index: 1029; flex-direction: row; align-items: center; padding: 0; }
      .sidebar .nav { display: flex; flex-direction: row; width: 100%; justify-content: space-around; }
      .sidebar .nav-link span, .sidebar .logo, .sidebar .footer-text { display: none; }
      .sidebar .nav-link i { font-size: 1.5rem; }
      .chat-toggle-btn { bottom: 80px; right: 15px; } .chat-container { display: none !important; }
    }
  </style>
</head>
<body class="">
  <div class="main-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="logo">SAVANT</div>
      <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a>
        <a class="nav-link" href="booking.php"><i class="bi bi-calendar-check"></i><span>Book Consultation</span></a>
        <a class="nav-link" href="statement.php"><i class="bi bi-receipt"></i><span>Statement of Account</span></a>
        <a class="nav-link active" href="documents.php"><i class="bi bi-file-earmark-text"></i><span>Documents</span></a>
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
          <a href="" class="btn btn-link power-btn" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-power"></i></a>
          <span class="badge"><?php echo htmlspecialchars($user['firstname']); ?></span>
        </div>
      </div>
      
      <main class="main-content" style="color: #087830">
        <h1>Please submit your documents</h1>
        <?php echo $msg; ?>
        
        <div class="document-section">
          <div class="document-header">
            <!-- FORM PARA SA UPLOAD -->
            <form action="documents.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="header-icon-container" onclick="document.getElementById('fileUpload').click()">
                  <button type="button" class="btn-icon"><i class="bi bi-file-earmark-arrow-up"></i></button>
                  <div class="icon-label">Upload File</div>
                </div>
                <input type="file" name="document_file" id="fileUpload" style="display: none;" onchange="document.getElementById('uploadForm').submit();" required>
            </form>

            <!-- FORM PARA SA DELETE -->
            <form action="documents.php" method="POST" id="deleteForm">
                <input type="hidden" name="delete_id" id="deleteFileId" value="">
                <div class="header-icon-container" id="deleteBtnContainer" onclick="confirmDelete()">
                  <button type="button" class="btn-icon text-danger"><i class="bi bi-trash"></i></button>
                  <div class="icon-label text-danger">Delete</div>
                </div>
            </form>
          </div>
          <hr>
          
          <ul class="nav nav-tabs status-tabs" id="statusTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">Pending</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button">Approved</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button">Cancelled</button>
            </li>
          </ul>
          
          <div class="tab-content pt-4">
            <!-- PENDING TAB -->
            <div class="tab-pane fade show active" id="pending">
              <div class="file-preview-container">
                <?php if (empty($docs['pending'])): ?>
                    <p class="text-muted w-100 text-center">No pending documents. Upload files using the icon above.</p>
                <?php else: ?>
                    <?php foreach ($docs['pending'] as $doc): ?>
                        <div class="file-preview-item" onclick="selectFile(this, <?php echo $doc['id']; ?>)">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            <div class="file-name" title="<?php echo htmlspecialchars($doc['file_name']); ?>"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- APPROVED TAB -->
            <div class="tab-pane fade" id="approved">
              <div class="file-preview-container">
                <?php if (empty($docs['approved'])): ?>
                    <p class="text-muted w-100 text-center">No approved documents.</p>
                <?php else: ?>
                    <?php foreach ($docs['approved'] as $doc): ?>
                        <div class="file-preview-item" onclick="selectFile(this, <?php echo $doc['id']; ?>)">
                            <i class="bi bi-file-earmark-check-fill text-success"></i>
                            <div class="file-name" title="<?php echo htmlspecialchars($doc['file_name']); ?>"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- CANCELLED TAB -->
            <div class="tab-pane fade" id="cancelled">
              <div class="file-preview-container">
                <?php if (empty($docs['cancelled'])): ?>
                    <p class="text-muted w-100 text-center">No cancelled documents.</p>
                <?php else: ?>
                    <?php foreach ($docs['cancelled'] as $doc): ?>
                        <div class="file-preview-item" onclick="selectFile(this, <?php echo $doc['id']; ?>)">
                            <i class="bi bi-file-earmark-x-fill text-danger"></i>
                            <div class="file-name" title="<?php echo htmlspecialchars($doc['file_name']); ?>"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
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

    // Para sa Selection and Deletion ng File
    let selectedFileId = null;

    function selectFile(element, fileId) {
        document.querySelectorAll('.file-preview-item').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        selectedFileId = fileId;
        document.getElementById('deleteFileId').value = fileId;
    }

    function confirmDelete() {
        if (!selectedFileId) {
            alert("Please select a file first by clicking on it.");
            return;
        }
        
        if (confirm("Are you sure you want to delete this file? This action cannot be undone.")) {
            document.getElementById('deleteForm').submit();
        }
    }
  </script>
</body>
</html>