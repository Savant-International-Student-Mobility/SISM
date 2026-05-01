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
$stmt = $conn->prepare("SELECT firstname, lastname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$full_name = $user['firstname'] . ' ' . $user['lastname'];

// KAPAG SINUBMIT ANG FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_application'])) {
    $educ_bg = $_POST['education'];
    $destination = $_POST['destination'];
    // Kunin ang phone number mula sa form, in case in-edit ng user bago i-submit
    $contact = trim($_POST['contact_number']); 

    $insert = $conn->prepare("INSERT INTO applications (user_id, full_name, email, contact_number, education_background, target_destination) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("isssss", $user_id, $full_name, $user['email'], $contact, $educ_bg, $destination);

    if ($insert->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show text-center' style='margin: 0; border-radius: 0;'>
                    <i class='bi bi-check-circle-fill me-2'></i> Application Sent Successfully! Our team will contact you soon.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show text-center' style='margin: 0; border-radius: 0;'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i> Something went wrong. Please try again.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Form - SAVANT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --savant-green: #087830;
      --savant-light-green: #12aa42;
      --savant-bg: #f4f7f6;
    }

    * {
      font-family: 'Poppins', sans-serif !important;
    }

    body {
      background: linear-gradient(135deg, #fdfdfd 0%, #e9f2ee 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .btn-back-circle {
      position: fixed;
      top: 30px;
      left: 30px;
      width: 45px;
      height: 45px;
      background: #087830;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      text-decoration: none;
      z-index: 100;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .btn-back-circle:hover {
      background: var(--savant-green);
      color: white;
      transform: translateX(-5px);
    }

    .form-card {
      background-color: #ffffff;
      width: 100%;
      max-width: 550px;
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
      margin: 40px 20px;
      border: none;
    }

    .form-header {
      background-color: var(--savant-green);
      color: white;
      padding: 40px 20px;
      text-align: center;
      position: relative;
    }

    .form-header::after {
      content: '';
      position: absolute;
      bottom: -20px;
      left: 0;
      right: 0;
      height: 40px;
      background: white;
      border-radius: 50% 50% 0 0;
    }

    .form-header h2 {
      font-weight: 700;
      margin: 10px 0 0;
      font-size: 1.4rem;
      letter-spacing: 1px;
    }

    .form-body {
      padding: 40px 50px;
    }

    .input-group-text {
      background-color: transparent;
      border-right: none;
      color: var(--savant-green);
      border-radius: 12px 0 0 12px;
    }

    .form-control,
    .form-select {
      border: 1px solid #e1e1e1;
      padding: 12px 15px;
      border-radius: 12px;
      font-size: 0.95rem;
      transition: all 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--savant-green);
      box-shadow: 0 0 0 4px rgba(8, 120, 48, 0.1);
      background-color: #fff;
    }

    /* Wrap input for icons */
    .field-wrapper {
      margin-bottom: 25px;
    }

    .form-label {
      font-weight: 500;
      color: #444;
      margin-bottom: 8px;
      font-size: 0.85rem;
      display: block;
    }

    .btn-submit {
      background: linear-gradient(to right, var(--savant-green), var(--savant-light-green));
      color: white;
      border: none;
      padding: 16px;
      width: 100%;
      border-radius: 15px;
      font-weight: 600;
      font-size: 1rem;
      box-shadow: 0 10px 20px rgba(8, 120, 48, 0.2);
      transition: all 0.3s;
      margin-top: 10px;
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 25px rgba(8, 120, 48, 0.3);
      filter: brightness(1.1);
    }

    .required {
      color: #e74c3c;
    }

    @media (max-width: 576px) {
      .form-body {
        padding: 30px 25px;
      }
      .btn-back-circle {
        top: 15px;
        left: 15px;
      }
    }
  </style>
</head>

<body>

  <!-- In-update ang link pabalik sa forms.php -->
  <a href="forms.php" class="btn-back-circle" title="Back to Forms">
    <i class="bi bi-arrow-left fs-5"></i>
  </a>

  <!-- Message Box Container (Lilitaw sa taas kapag nag-submit) -->
  <div class="w-100" style="position: absolute; top:0; z-index: 1000;">
      <?php echo $msg; ?>
  </div>

  <div class="form-card">
    <div class="form-header">
      <!-- In-update ang image path -->
      <img src="img/logo.png" style="height: 80px; filter: brightness(0) invert(1);">
      <h2>APPLICATION FORM</h2>
    </div>

    <div class="form-body">
      <!-- Naka-connect na sa PHP -->
      <form action="applicationform.php" method="POST" id="bookingForm">

        <div class="field-wrapper">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <!-- Naka-readonly para secured -->
            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($full_name); ?>" readonly>
          </div>
        </div>

        <div class="field-wrapper">
          <label class="form-label">Email Address <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <!-- Naka-readonly para secured -->
            <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
          </div>
        </div>

        <div class="field-wrapper">
          <label class="form-label">Contact Number <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <!-- Editable 'to incase gusto nilang palitan ang number for this specific application -->
            <input type="tel" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
          </div>
        </div>

        <div class="field-wrapper">
          <label class="form-label">Education Background <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-book"></i></span>
            <select name="education" class="form-select" required>
              <option value="" selected disabled>Select Education Level</option>
              <option value="Senior High School Graduate">Senior High School Graduate</option>
              <option value="College Level">College Level</option>
              <option value="College Graduate">College Graduate</option>
              <option value="Master's Degree / Others">Master's Degree / Others</option>
            </select>
          </div>
        </div>

        <div class="field-wrapper">
          <label class="form-label">Target Destination <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <select name="destination" class="form-select" required>
              <option value="" selected disabled>Select Country</option>
              <option value="Canada">Canada</option>
              <option value="United Kingdom">United Kingdom</option>
              <option value="Australia">Australia</option>
              <option value="USA">USA</option>
            </select>
          </div>
        </div>

        <button type="submit" name="submit_application" class="btn-submit" onclick="this.innerHTML='Sending...'">Submit Application</button>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>