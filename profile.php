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

// ==========================================
// EDIT PROFILE LOGIC (KAPAG NAG-SAVE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fname = trim($_POST['firstName']);
    $lname = trim($_POST['lastName']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bday = !empty($_POST['birthday']) ? $_POST['birthday'] : NULL;
    $fb = trim($_POST['facebook']);
    $ig = trim($_POST['instagram']);

    $profile_pic_query = "";
    
    // Logic kung nag-upload ng bagong Profile Picture
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true); // Gumawa ng folder kung wala pa
        
        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES["profileImage"]["name"]));
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file)) {
            $profile_pic_query = ", profile_pic = '$target_file'";
        }
    }

    // I-Update ang database
    $sql = "UPDATE users SET firstname=?, lastname=?, address=?, email=?, phone=?, birthday=?, facebook=?, instagram=? $profile_pic_query WHERE id=?";
    $update = $conn->prepare($sql);
    $update->bind_param("ssssssssi", $fname, $lname, $address, $email, $phone, $bday, $fb, $ig, $user_id);
    
    if ($update->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'>Profile updated successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm'>Failed to update profile. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// FETCH CURRENT USER DATA
// ==========================================
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ==========================================
// FETCH RECENT NOTIFICATIONS (LIMIT 5)
// ==========================================
$stmt_notif = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$notifications = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAVANT - My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" href="img/logoulit.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;900&display=swap" rel="stylesheet">
  <style>
    :root { --savant-primary-green: #087830; --savant-dark-green: #087830; --savant-text-dark: #333333; --savant-text-light: #525252; --savant-card-bg: #FFFFFF; --savant-light-gray: #E8E8E8; --savant-bg-light: #F7F7F7; --savant-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); --savant-button-maroon: #087830; }
    ::-webkit-scrollbar { width: 12px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #0C470C, #3BA43B); border-radius: 6px; } ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #023621, #2a7c2a); }
    body { font-family: 'Poppins', sans-serif; background-color: var(--savant-bg-light); color: var(--savant-text-dark); overflow: hidden; }
    .main-wrapper { display: flex; height: 100vh; }
    .sidebar { background-color: #087830; width: 70px; flex-shrink: 0; color: white; padding: 30px 0; box-shadow: var(--savant-shadow); transition: width 0.3s ease-in-out; overflow: hidden; position: fixed; top: 0; height: 100vh; display: flex; flex-direction: column; z-index: 1031; }
    .sidebar:hover { width: 280px; }
    .sidebar .logo { font-size: 2.2rem; font-weight: 700; margin-bottom: 30px; text-align: center; letter-spacing: 1px; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease-in-out; }
    .sidebar:hover .logo { opacity: 1; } .sidebar .nav { flex-grow: 1; }
    .sidebar .nav-link { color: white; font-weight: 500; padding: 15px 30px; display: flex; align-items: center; gap: 15px; white-space: nowrap; transition: background-color 0.3s ease; text-decoration: none;}
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: #14552b; }
    .sidebar .nav-link i { font-size: 1.2rem; min-width: 20px; text-align: center; }
    .sidebar .nav-link span { opacity: 0; transition: opacity 0.1s ease-in-out 0.2s; }
    .sidebar:hover .nav-link span { opacity: 1; }
    .sidebar .footer-text { font-size: 0.8rem; color: #bbb; text-align: center; padding: 15px 0; opacity: 0; transition: opacity 0.3s ease-in-out; margin-top: auto; }
    .sidebar:hover .footer-text { opacity: 1; }
    .content-area { flex-grow: 1; height: 100vh; overflow-y: auto; margin-left: 70px; }
    .header { background-color: var(--savant-card-bg); height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 25px; box-shadow: var(--savant-shadow); position: sticky; top: 0; z-index: 1020; }
    .header-title { font-size: 1rem; font-weight: 600; color: var(--savant-text-dark); white-space: nowrap; }
    .user-status { display: flex; align-items: center; gap: 10px; }
    .user-status .badge { background-color: var(--savant-button-maroon); font-size: 0.8rem; font-weight: 500; padding: 5px 15px; border-radius: 20px; color:white; }
    .power-btn i { color: #dc3545; font-size: 1.5rem; } .power-btn:hover i { color: #a71d2a; }
    .main-content { flex-grow: 1; padding: 30px; animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Profile Specific Styles */
    .profile-header { position: relative; margin-bottom: 2rem; }
    .cover-photo { height: 250px; background-image: url('img/bg.png'); background-size: cover; background-position: center; border-radius: .5rem; }
    .profile-info { display: flex; align-items: flex-end; gap: 1.5rem; padding: 0 2rem; }
    .profile-name { padding-bottom: 1.5rem; }
    .profile-name h2 { font-weight: 700; margin-bottom: 0.25rem; }
    .profile-name p { color: #6c757d; margin-bottom: 0; }
    .edit-profile-btn { margin-left: auto; margin-bottom: 1.5rem; background-color: #087830; color: #ffffff;}
    .content-card { background-color: var(--savant-card-bg); padding: 1.5rem; border-radius: .5rem; box-shadow: var(--savant-shadow); margin-bottom: 1.5rem; }
    .info-list li { padding: 0.5rem 0; display: flex; align-items: center; gap: 1rem; }
    .info-list li i { color: #6c757d; font-size: 1.25rem; }
    .social-links { padding-top: 1rem; margin-top: 1rem; border-top: 1px solid #e9ecef; }
    .social-links a { font-size: 1.5rem; color: #087830; transition: color 0.2s; text-decoration: none;}
    .social-links a:hover { color: var(--savant-dark-green); }
    
    .profile-picture { width: 150px; height: 150px; border-radius: 50%; overflow: hidden; position: relative; background-color: #e9ecef; border: 5px solid #fff; margin-top: -75px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; }
    .profile-picture img { width: 100%; height: 100%; object-fit: cover; }
    .profile-picture .bi-person-circle { font-size: 8rem; color: #adb5bd; }

    /* Notifications Feed inside Profile */
    .notification-feed-list { max-height: 350px; overflow-y: auto; padding-right: 15px; }
    .notification-item { padding-bottom: 1rem; display: flex; gap: 15px;}
    .notification-item:not(:last-child) { margin-bottom: 1rem; border-bottom: 1px solid #e9ecef; }
    .notification-icon-container { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .notification-icon-container.approved { background-color: rgba(25, 135, 84, 0.1); color: var(--bs-success); }
    .notification-icon-container.cancelled { background-color: rgba(220, 53, 69, 0.1); color: var(--bs-danger); }
    .notification-icon-container.info { background-color: rgba(13, 110, 253, 0.1); color: var(--bs-primary); }
    
    .floating-btn { position: fixed; bottom: 30px; right: 30px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); text-decoration: none; transition: background-color 0.2s; z-index: 100; }
    
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
    
    .chat-toggle-btn { position: fixed; bottom: 30px; right: 100px; background-color: var(--savant-button-maroon); color: white; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); cursor: pointer; z-index: 100; transition: transform 0.3s ease, background-color 0.3s ease; border: none;}
    
    @media (max-width: 992px) { body { padding-top: 60px; padding-bottom: 50px; overflow: auto; } .main-wrapper { flex-direction: column; height: auto; } .content-area { height: auto; overflow-y: visible; margin-left: 0; } .header { position: fixed; top: 0; left: 0; right: 0; z-index: 1030; } .header-title { display: none; } .main-content { padding: 15px; } .sidebar { width: 100%; height: 50px; position: fixed; top: auto; bottom: 0; left: 0; z-index: 1029; flex-direction: row; align-items: center; padding: 0; } .sidebar .nav { display: flex; flex-direction: row; width: 100%; justify-content: space-around; } .sidebar .nav-link span, .sidebar .logo, .sidebar .footer-text { display: none; } .profile-info { flex-direction: column; align-items: center; text-align: center; padding: 0 1rem 1rem 1rem; } .profile-picture { width: 120px; height: 120px; margin-top: -60px; } .profile-name { padding-bottom: 0; margin-top: 0.5rem; } .edit-profile-btn { margin: 1rem auto 0; width: 80%; } .chat-toggle-btn { bottom: 80px; right: 15px; } .floating-btn { bottom: 80px; right: 85px; } .chat-container { display: none !important; } }
  </style>
</head>

<body class="">
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
        <a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i><span>Profile</span></a>
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
          <!-- DYNAMIC USER BADGE -->
          <span class="badge"><?php echo htmlspecialchars($user['firstname']); ?></span>
        </div>
      </div>

      <main class="main-content">
        
        <!-- DISPLAY MESSAGES -->
        <?php echo $msg; ?>

        <div class="profile-header">
          <div class="cover-photo"></div>
          <div class="profile-info">
            
            <!-- DYNAMIC PROFILE PICTURE -->
            <div id="profilePictureContainer" class="profile-picture bg-white">
              <?php if(!empty($user['profile_pic'])): ?>
                  <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture">
              <?php else: ?>
                  <i class="bi bi-person-circle text-secondary"></i>
              <?php endif; ?>
            </div>

            <div class="profile-name">
              <!-- DYNAMIC NAME -->
              <h2 id="profileName"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h2>
              <p id="profileTitle">Client of Savant</p>
            </div>
            
            <button class="btn btn-outline-secondary edit-profile-btn fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="bi bi-pencil-fill me-2"></i> Edit Profile
            </button>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-4">
            <div class="content-card">
              <h5>About</h5>
              <ul class="list-unstyled info-list mt-3">
                <li><i class="bi bi-briefcase-fill"></i> Client of Savant</li>
                
                <!-- DYNAMIC DATA DISPLAY -->
                <li><i class="bi bi-geo-alt-fill"></i> <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Location not set'; ?></li>
                <li><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($user['email']); ?></li>
                <li><i class="bi bi-telephone-fill"></i> <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Phone not set'; ?></li>
                <li><i class="bi bi-calendar-heart-fill"></i> <?php echo !empty($user['birthday']) ? date("F d, Y", strtotime($user['birthday'])) : 'Birthday not set'; ?></li>
              </ul>
              
              <!-- DYNAMIC SOCIAL LINKS -->
              <div class="social-links d-flex justify-content-center gap-3">
                  <?php if(!empty($user['facebook'])): ?>
                      <a href="<?php echo htmlspecialchars($user['facebook']); ?>" target="_blank" title="Facebook"><i class="bi bi-facebook"></i></a>
                  <?php endif; ?>
                  <?php if(!empty($user['instagram'])): ?>
                      <a href="<?php echo htmlspecialchars($user['instagram']); ?>" target="_blank" title="Instagram"><i class="bi bi-instagram"></i></a>
                  <?php endif; ?>
                  <?php if(empty($user['facebook']) && empty($user['instagram'])): ?>
                      <p class="text-muted small mb-0 mt-2">No social links added.</p>
                  <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="col-lg-8">
            <div class="content-card">
              <h5 class="mb-4">Recent Notifications</h5>
              <div class="notification-feed-list">
                
                <!-- RECENT NOTIFICATIONS FETCHED FROM DATABASE -->
                <?php if (empty($notifications)): ?>
                    <p class="text-muted text-center py-4">No recent notifications to display.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item">
                            <div class="notification-icon-container <?php echo htmlspecialchars($notif['type']); ?>">
                                <?php if($notif['type'] == 'approved'): ?>
                                    <i class="bi bi-check-lg"></i>
                                <?php elseif($notif['type'] == 'cancelled'): ?>
                                    <i class="bi bi-x-lg"></i>
                                <?php else: ?>
                                    <i class="bi bi-info-lg"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <small class="text-secondary"><?php echo date("M d, Y h:i A", strtotime($notif['created_at'])); ?></small>
                            </div>
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

  <!-- EDIT PROFILE MODAL WITH FORM SUBMISSION -->
  <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            
          <!-- DITO YUNG TOTOONG FORM -->
          <form id="editProfileForm" action="profile.php" method="POST" enctype="multipart/form-data">
            
            <input type="file" id="profilePictureInput" name="profileImage" style="display: none;" accept="image/*" onchange="previewImage(event)">
            <div class="mb-4 text-center">
              <div id="modalProfilePicturePreviewContainer" class="rounded-circle mx-auto mb-3"
                style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; border: 2px solid #dee2e6; background-color: #f8f9fa; font-size: 5rem; color: #6c757d; overflow: hidden;">
                <!-- DYNAMIC PREVIEW IF PICTURE ALREADY EXISTS -->
                <?php if(!empty($user['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" id="previewImg" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <img id="previewImg" style="width:100%; height:100%; object-fit:cover; display:none;">
                    <i class="bi bi-person-circle" id="previewIcon"></i>
                <?php endif; ?>
              </div>
              <div>
                <label for="profilePictureInput" class="btn text-white fw-bold btn-sm" style="background-color: var(--savant-primary-green);">Upload New Photo</label>
              </div>
            </div>
            
            <hr>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">First Name *</label>
                <input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Last Name *</label>
                <input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Address</label>
              <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address *</label>
              <!-- Required field kaya di pwede i-empty -->
              <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                  <label class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
              </div>
              <div class="col-md-6 mb-3">
                  <label class="form-label">Birthday</label>
                  <input type="date" class="form-control" name="birthday" value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>">
              </div>
            </div>
            <hr>
            <h6 class="mb-3 fw-bold">Social Links</h6>
            <div class="mb-3">
              <label class="form-label"><i class="bi bi-facebook me-2 text-primary"></i>Facebook Profile URL</label>
              <input type="url" class="form-control" name="facebook" value="<?php echo htmlspecialchars($user['facebook'] ?? ''); ?>" placeholder="https://facebook.com/username">
            </div>
            <div class="mb-3">
              <label class="form-label"><i class="bi bi-instagram me-2 text-danger"></i>Instagram URL</label>
              <input type="url" class="form-control" name="instagram" value="<?php echo htmlspecialchars($user['instagram'] ?? ''); ?>" placeholder="https://instagram.com/username">
            </div>
            
            <!-- HIDDEN FIELD TO TRIGGER PHP SUBMISSION -->
            <input type="hidden" name="update_profile" value="1">
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn text-white fw-bold shadow-sm" style="background-color: var(--savant-primary-green);" data-bs-toggle="modal" data-bs-target="#confirmSaveModal">Save Changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- CONFIRM SAVE MODAL -->
  <div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Changes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to save these changes to your profile?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <!-- ITO YUNG MAGSU-SUBMIT NG FORM SA ITAAS -->
          <button type="button" class="btn text-white" style="background-color: var(--savant-primary-green);" onclick="document.getElementById('editProfileForm').submit();">Yes, Save It</button>
        </div>
      </div>
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

    // PANG-UPDATE NG PREVIEW NG PROFILE PICTURE BAGO I-SAVE
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var img = document.getElementById('previewImg');
            var icon = document.getElementById('previewIcon');
            
            img.src = reader.result;
            img.style.display = 'block';
            if(icon) { icon.style.display = 'none'; }
        }
        reader.readAsDataURL(event.target.files[0]);
    }
  </script>
</body>
</html>