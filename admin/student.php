<?php
include 'connections.php';

// --- FILTER HANDLING ---
$where = [];
$params = [];

// 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
  isset($_GET['StudentID']) && isset($_GET['FirstName']) && isset($_GET['LastName']) &&
  isset($_GET['Gender']) && isset($_GET['PhoneNumber']) && isset($_GET['Address'])  &&
  isset($_GET['Email']) && isset($_GET['Nationality']) && isset($_GET['Birthdate'])
)) 
{
  if (!empty($_GET['StudentID'])) {
    $where[] = "StudentID = ?";
    $params[] = $_GET['StudentID'];
  }
  if (!empty($_GET['FirstName'])) {
    $where[] = "FirstName = ?";
    $params[] = $_GET['FirstName'];
  }
  if (!empty($_GET['LastName'])) {
    $where[] = "LastName = ?";
    $params[] = $_GET['LastName'];
  }
  if (!empty($_GET['Gender'])) {
    $where[] = "Gender = ?";
    $params[] = $_GET['Gender'];
  }
  if (!empty($_GET['PhoneNumber'])) {
    $where[] = "PhoneNumber = ?";
    $params[] = $_GET['PhoneNumber'];
  }
  if (!empty($_GET['Address'])) {
    $where[] = "Address = ?";
    $params[] = $_GET['Address'];
  }
  if (!empty($_GET['Email'])) {
    $where[] = "Email = ?";
    $params[] = $_GET['Email'];
  }
  if (!empty($_GET['Nationality'])) {
    $where[] = "Nationality = ?";
    $params[] = $_GET['Nationality'];
  }
  if (!empty($_GET['Birthdate'])) {
    $where[] = "Birthdate = ?";
    $params[] = $_GET['Birthdate'];
  }
}

// --- AJAX UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['StudentID']) && isset($_POST['FirstName']) && isset($_POST['LastName']) && !isset($_POST['addStudent']) && !isset($_POST['deleteStudent'])) {
  $studentid = $conn->real_escape_string($_POST['StudentID']);
  $originalStudentID = $conn->real_escape_string($_POST['OriginalStudentID']);
  $firstname = $conn->real_escape_string($_POST['FirstName']);
  $lastname = $conn->real_escape_string($_POST['LastName']);
  $gender = $conn->real_escape_string($_POST['Gender']);
  $phonenumber = $conn->real_escape_string($_POST['PhoneNumber']);
  $address = $conn->real_escape_string($_POST['Address']);
  $email = $conn->real_escape_string($_POST['Email']);
  $nationality = $conn->real_escape_string($_POST['Nationality']);
  $birthdate = $conn->real_escape_string($_POST['Birthdate']);

  $sql = "UPDATE student SET
    StudentID='$studentid',
    FirstName='$firstname',
    LastName='$lastname',
    Gender='$gender',
    PhoneNumber='$phonenumber',
    Address='$address',
    Email='$email',
    Nationality='$nationality',
    Birthdate='$birthdate'
    WHERE StudentID='$originalStudentID'";

  $success = $conn->query($sql);

  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- AJAX DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteStudent']) && isset($_POST['StudentID'])) {
  $studentid = intval($_POST['StudentID']);
  $sql = "DELETE FROM student WHERE StudentID=$studentid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- CREATE STUDENT (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['addStudent']) || isset($_POST['createStudent']))) {
  $studentid = $conn->real_escape_string($_POST['StudentID']);
  $firstname = $conn->real_escape_string($_POST['FirstName']);
  $lastname = $conn->real_escape_string($_POST['LastName']);
  $gender = $conn->real_escape_string($_POST['Gender']);
  $phonenumber = $conn->real_escape_string($_POST['PhoneNumber']);
  $address = $conn->real_escape_string($_POST['Address']);
  $email = $conn->real_escape_string($_POST['Email']);
  $nationality = $conn->real_escape_string($_POST['Nationality']);
  $birthdate = $conn->real_escape_string($_POST['Birthdate']);
  $sql = "INSERT INTO student (StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate) VALUES ('$studentid','$firstname', '$lastname', '$gender', '$phonenumber', '$address', '$email', '$nationality', '$birthdate')";
  $success = $conn->query($sql);
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
  } else {
    header('Location: student.php');
    exit;
  }
}

// --- FETCH STUDENTS (with filter) ---
if (count($where) > 0) {
  $sql = "SELECT * FROM student WHERE " . implode(' AND ', $where) . " ORDER BY StudentID DESC";
  $stmt = $conn->prepare($sql);
  if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $resResult = $stmt->get_result();
} else {
  $resQuery = "SELECT * FROM student ORDER BY StudentID DESC";
  $resResult = $conn->query($resQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Villa Valore — Guests</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{--bg:#f5f6f4;--surface:#fff;--soft:#f8faf8;--text:#17221c;--muted:#778078;--line:#e7ebe7;--primary:#214f3b;--primary2:#2e6b50;--primarysoft:#e8f1ec;--gold:#b48a45;--danger:#c95757;--sidebar:250px;--shadow:0 10px 30px rgba(22,40,30,.06)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"DM Sans",sans-serif;background:var(--bg);color:var(--text)}
button,input,select{font:inherit}
.sidebar{position:fixed;inset:0 auto 0 0;width:var(--sidebar);background:#17382b;color:#fff;padding:26px 18px 18px;z-index:1000;display:flex;flex-direction:column;transition:transform .25s}
.brand{display:flex;align-items:center;gap:12px;padding:4px 10px 30px}.brand img{width:46px;height:46px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,.08)}.brand strong{display:block;font-family:"Playfair Display",serif;font-size:19px}.brand span{display:block;color:#afc4b8;font-size:11px;margin-top:2px}
.nav-label{color:#8fa99b;font-size:10px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;padding:0 12px 9px}.nav-section{margin-bottom:20px}.nav-link{display:flex;align-items:center;gap:12px;color:#dbe7e0;text-decoration:none;padding:11px 13px;border-radius:12px;margin:3px 0;font-size:13px;transition:.2s}.nav-link i{width:18px;text-align:center;color:#a9c1b4}.nav-link:hover{background:rgba(255,255,255,.08);color:#fff}.nav-link.active{background:#fff;color:var(--primary);font-weight:700;box-shadow:0 7px 20px rgba(0,0,0,.1)}.nav-link.active i{color:var(--primary)}.sidebar-bottom{margin-top:auto}.logout{border-top:1px solid rgba(255,255,255,.09);padding-top:14px}
.main{margin-left:var(--sidebar);min-height:100vh}.topbar{height:76px;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 34px;position:sticky;top:0;z-index:900}.topbar-left{display:flex;align-items:center;gap:14px}.menu-btn{display:none;border:0;background:var(--soft);width:40px;height:40px;border-radius:11px;cursor:pointer}.breadcrumb{color:var(--muted);font-size:12px}.top-actions{display:flex;align-items:center;gap:14px}.icon-btn{width:40px;height:40px;border:1px solid var(--line);border-radius:11px;background:#fff;color:#536058;cursor:pointer;position:relative}.notification-dot{position:absolute;width:7px;height:7px;border-radius:50%;background:var(--gold);right:8px;top:8px;border:2px solid #fff}.profile{display:flex;align-items:center;gap:10px}.avatar{width:38px;height:38px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;font-weight:700;font-size:13px}.profile strong{display:block;font-size:12px}.profile span{display:block;color:var(--muted);font-size:10px;margin-top:1px}
.content{padding:32px;max-width:1500px;margin:auto}.page-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:22px}.eyebrow{color:var(--primary2);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:7px}h1{font-family:"Playfair Display",serif;font-size:32px;line-height:1.15;letter-spacing:-.5px}.page-heading p{color:var(--muted);font-size:13px;margin-top:7px}
.toolbar{background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px;display:flex;align-items:center;gap:10px;box-shadow:var(--shadow);margin-bottom:18px}.search-wrapper{position:relative;flex:1;max-width:520px}.search-wrapper i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#98a19b;font-size:13px}.search-input{width:100%;height:42px;border:1px solid var(--line);border-radius:11px;padding:0 15px 0 40px;outline:none;background:var(--soft);font-size:12px}.search-input:focus{border-color:#b8ccc0;background:#fff;box-shadow:0 0 0 3px rgba(46,107,80,.08)}.toolbar-spacer{flex:1}.create-btn,.filter-btn{border-radius:11px;height:42px;padding:0 16px;cursor:pointer;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:8px}.create-btn{border:0;background:var(--primary);color:#fff;box-shadow:0 6px 16px rgba(33,79,59,.18)}.create-btn:hover{background:var(--primary2)}.filter-btn{background:#fff;color:#4e5a52;border:1px solid var(--line)}.filter-btn:hover{background:var(--soft)}
.panel{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow);overflow:hidden}.panel-head{display:flex;align-items:center;justify-content:space-between;padding:20px 22px;border-bottom:1px solid var(--line)}.panel-title{font-size:15px;font-weight:700}.panel-subtitle{color:var(--muted);font-size:11px;margin-top:4px}.table-wrap{overflow-x:auto}.reservation-table{width:100%;border-collapse:collapse;min-width:800px}.reservation-table th{color:#9aa39d;font-size:9px;text-transform:uppercase;letter-spacing:.7px;text-align:left;padding:13px 16px;background:#fbfcfb;border-bottom:1px solid var(--line)}.reservation-table td{padding:13px 16px;border-bottom:1px solid #f0f2f0;font-size:11px;color:#536057;vertical-align:middle}.reservation-table tbody tr:hover{background:#fbfdfb}.reservation-table tbody tr:last-child td{border-bottom:0}.guest-cell{display:flex;align-items:center;gap:10px}.guest-avatar{width:34px;height:34px;border-radius:10px;background:var(--primarysoft);color:var(--primary);display:grid;place-items:center;font-size:10px;font-weight:700;flex:none}.guest-name strong{display:block;color:var(--text);font-size:12px}.guest-name span{display:block;color:var(--muted);font-size:9px;margin-top:2px}.id-badge{background:#f1f4f1;border-radius:7px;padding:5px 8px;font-size:10px;color:#4f5b53}.contact i{width:15px;color:#9aa39d;margin-right:3px}.action-group{display:flex;gap:5px;justify-content:center}.action-btn,.download-table-btn{width:31px;height:31px;border:1px solid var(--line);border-radius:9px;background:#fff;display:inline-grid;place-items:center;cursor:pointer;transition:.18s}.action-btn i,.download-table-btn i{font-size:11px}.edit-btn i{color:var(--primary2)}.view-btn i{color:#548c75}.delete-btn i{color:var(--danger)}.action-btn:hover,.download-table-btn:hover{background:var(--primarysoft);border-color:#c8dbcf}.delete-btn:hover{background:#fff2f2!important;border-color:#f0cccc!important}.empty-state{padding:45px;text-align:center;color:var(--muted);font-size:12px}
.modal{display:none;position:fixed;inset:0;z-index:2000;background:rgba(13,25,19,.48);backdrop-filter:blur(4px);padding:20px;overflow:auto}.modal-content{background:#fff;width:min(650px,100%);margin:5vh auto;padding:28px;border-radius:20px;position:relative;box-shadow:0 25px 70px rgba(0,0,0,.18)}.modal-content h2{font-family:"Playfair Display",serif;font-size:23px;margin-bottom:20px}.close{position:absolute;right:18px;top:16px;width:34px;height:34px;border-radius:9px;background:#f5f7f5;color:#6d776f;display:grid;place-items:center;cursor:pointer;font-size:20px}.modal-content form{display:grid;grid-template-columns:1fr 1fr;gap:14px}.modal-content form p{margin:0}.modal-content form input,.modal-content form select{width:100%;height:42px;padding:0 11px;border-radius:10px;border:1px solid var(--line);background:var(--soft);outline:none;font-size:12px}.modal-content label{display:block;font-size:10px;font-weight:700;color:#657067;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}.modal-content form button[type=submit]{grid-column:1/-1;height:44px;border:0;border-radius:11px;background:var(--primary);color:#fff;font-size:12px;font-weight:700;cursor:pointer;margin-top:4px}.modal-content form p:last-of-type{grid-column:auto}.confirm-delete{background:var(--danger);color:#fff;border:0;border-radius:10px;padding:11px 17px;font-size:12px;font-weight:700;cursor:pointer}.cancel-delete{background:#f5f7f5;color:#445047;border:1px solid var(--line);border-radius:10px;padding:11px 17px;font-size:12px;font-weight:700;cursor:pointer}#viewDetails{display:grid;grid-template-columns:1fr 1fr;gap:10px}#viewDetails p{background:var(--soft);border:1px solid var(--line);padding:12px;border-radius:11px;font-size:11px}#viewDetails label{display:block;font-size:9px;color:var(--muted);font-weight:700;margin-bottom:4px}.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:999}
@media(max-width:900px){.sidebar{transform:translateX(-100%)}.sidebar.active{transform:translateX(0)}.overlay.active{display:block}.main{margin-left:0}.menu-btn{display:block}.topbar{padding:0 18px}.content{padding:22px 16px}}
@media(max-width:650px){.profile-text{display:none}.page-heading{align-items:flex-start;flex-direction:column}h1{font-size:27px}.toolbar{flex-wrap:wrap}.search-wrapper{max-width:none;flex-basis:100%}.toolbar-spacer{display:none}.modal-content form,#viewDetails{grid-template-columns:1fr}.modal-content{padding:22px}}
</style>
</head>

<body>
<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="brand"><img src="images/villavalorelogo.png" alt="Villa Valore"><div><strong>Villa Valore</strong><span>Hotel Management</span></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a class="nav-link" href="index.php"><i class="fas fa-grid-2"></i><span>Dashboard</span></a></div>
  <div class="nav-section"><div class="nav-label">Management</div><a class="nav-link active" href="student.php"><i class="fas fa-users"></i><span>Guests</span></a><a class="nav-link" href="booking.php"><i class="fas fa-book-open"></i><span>Bookings</span></a><a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i><span>Reservations</span></a></div>
  <div class="nav-section"><div class="nav-label">Operations</div><a class="nav-link" href="room.php"><i class="fas fa-bed"></i><span>Rooms</span></a><a class="nav-link" href="inventory.php"><i class="fas fa-boxes-stacked"></i><span>Inventory</span></a></div>
  <div class="nav-section"><div class="nav-label">Finance</div><a class="nav-link" href="payment.php"><i class="fas fa-receipt"></i><span>Invoices</span></a><a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i><span>Analytics</span></a></div>
  <div class="sidebar-bottom"><div class="logout"><a class="nav-link" href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i><span>Logout</span></a></div></div>
</aside>

<main class="main">
<header class="topbar">
  <div class="topbar-left"><button class="menu-btn" id="menuBtn"><i class="fas fa-bars"></i></button><div class="breadcrumb">Management / <strong>Guests</strong></div></div>
  <div class="top-actions"><button class="icon-btn"><i class="far fa-bell"></i><span class="notification-dot"></span></button><div class="profile"><div class="avatar">VV</div><div class="profile-text"><strong>Villa Valore</strong><span>Administrator</span></div></div></div>
</header>

<section class="content">
  <div class="page-heading">
    <div><div class="eyebrow">Guest Management</div><h1>Guests</h1><p>Manage guest profiles, contact details, and records.</p></div>
    <button class="create-btn" id="createBtn"><i class="fas fa-plus"></i> Add Guest</button>
  </div>

  <div class="toolbar">
    <div class="search-wrapper"><i class="fas fa-search"></i><input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, phone, or guest ID..."></div>
    <div class="toolbar-spacer"></div>
    <button class="filter-btn" onclick="showDownloadModal(event)"><i class="fas fa-download"></i> Export</button>
  </div>

  <section class="panel">
    <div class="panel-head"><div><div class="panel-title">Guest Directory</div><div class="panel-subtitle">All registered guests</div></div><div class="panel-subtitle"><?php echo $resResult ? $resResult->num_rows : 0; ?> records</div></div>
    <div class="table-wrap">
      <table class="reservation-table">
        <thead><tr><th>Guest</th><th>Guest ID</th><th>Phone</th><th>Email</th><th style="text-align:center">Actions</th><th style="text-align:center">Export</th></tr></thead>
        <tbody>
        <?php if ($resResult && $resResult->num_rows > 0): ?>
          <?php while($row = $resResult->fetch_assoc()): ?>
          <?php $fullName=trim(($row['FirstName']??'').' '.($row['LastName']??'')); $initials=strtoupper(substr($row['FirstName']??'G',0,1).substr($row['LastName']??'',0,1)); ?>
          <tr data-id="<?php echo $row['StudentID']; ?>">
            <td><div class="guest-cell"><div class="guest-avatar"><?php echo htmlspecialchars($initials?:'G'); ?></div><div class="guest-name"><strong><?php echo htmlspecialchars($fullName); ?></strong><span><?php echo htmlspecialchars($row['Nationality']??''); ?></span></div></div></td>
            <td><span class="id-badge">#<?php echo htmlspecialchars($row['StudentID']); ?></span></td>
            <td class="contact"><i class="fas fa-phone"></i><?php echo htmlspecialchars($row['PhoneNumber']); ?></td>
            <td class="contact"><i class="fas fa-envelope"></i><?php echo htmlspecialchars($row['Email']); ?></td>
            <td><div class="action-group">
              <button type="button" class="action-btn edit-btn" data-id="<?php echo $row['StudentID']; ?>" data-firstname="<?php echo htmlspecialchars($row['FirstName']); ?>" data-lastname="<?php echo htmlspecialchars($row['LastName']); ?>" data-gender="<?php echo htmlspecialchars($row['Gender']); ?>" data-phonenumber="<?php echo htmlspecialchars($row['PhoneNumber']); ?>" data-address="<?php echo htmlspecialchars($row['Address']); ?>" data-email="<?php echo htmlspecialchars($row['Email']); ?>" data-nationality="<?php echo htmlspecialchars($row['Nationality']); ?>" data-birthdate="<?php echo htmlspecialchars($row['Birthdate']); ?>"><i class="fas fa-pen"></i></button>
              <button type="button" class="action-btn view-btn" data-id="<?php echo $row['StudentID']; ?>" data-firstname="<?php echo htmlspecialchars($row['FirstName']); ?>" data-lastname="<?php echo htmlspecialchars($row['LastName']); ?>" data-gender="<?php echo htmlspecialchars($row['Gender']); ?>" data-phonenumber="<?php echo htmlspecialchars($row['PhoneNumber']); ?>" data-address="<?php echo htmlspecialchars($row['Address']); ?>" data-email="<?php echo htmlspecialchars($row['Email']); ?>" data-nationality="<?php echo htmlspecialchars($row['Nationality']); ?>" data-birthdate="<?php echo htmlspecialchars($row['Birthdate']); ?>"><i class="fas fa-eye"></i></button>
              <button type="button" class="action-btn delete-btn" data-id="<?php echo $row['StudentID']; ?>"><i class="fas fa-trash"></i></button>
            </div></td>
            <td style="text-align:center"><button class="download-table-btn" onclick="showDownloadModal(event)" title="Export"><i class="fas fa-download"></i></button></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?><tr><td colspan="6"><div class="empty-state"><i class="fas fa-users" style="font-size:28px;opacity:.35"></i><br><br>No guests found.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>
</main>

<!-- Modals keep the same IDs expected by the existing CRUD scripts -->
<div id="editModal" class="modal"><div class="modal-content"><span class="close" id="closeEditModal">&times;</span><h2>Edit Guest</h2><form id="editForm">
<input type="hidden" name="OriginalStudentID" id="editOriginalStudentID">
<p><label>Guest ID</label><input type="text" name="StudentID" id="editStudentID" required></p><p><label>First Name</label><input type="text" name="FirstName" id="editFirstName" required></p><p><label>Last Name</label><input type="text" name="LastName" id="editLastName" required></p><p><label>Gender</label><select name="Gender" id="editGender" required><option value="Male">Male</option><option value="Female">Female</option><option value="Prefer not to say">Prefer not to say</option></select></p><p><label>Phone Number</label><input type="text" name="PhoneNumber" id="editPhoneNumber" required></p><p><label>Address</label><input type="text" name="Address" id="editAddress" required></p><p><label>Email</label><input type="email" name="Email" id="editEmail" required></p><p><label>Nationality</label><input type="text" name="Nationality" id="editNationality" required></p><p><label>Birthdate</label><input type="date" name="Birthdate" id="editBirthdate" required></p><button type="submit">Save Changes</button>
</form></div></div>

<div id="viewModal" class="modal"><div class="modal-content"><span class="close" id="closeViewModal">&times;</span><h2>Guest Information</h2><div id="viewDetails"></div></div></div>

<div id="downloadModal" class="modal"><div class="modal-content" style="width:350px"><span class="close" id="closeDownloadModal">&times;</span><h2>Export Guests</h2><div style="display:flex;flex-direction:column;gap:9px;margin-top:12px">
<button class="filter-btn" id="copyTableBtn"><i class="fas fa-copy"></i> Copy</button><button class="filter-btn" id="csvTableBtn"><i class="fas fa-file-csv"></i> CSV File</button><button class="filter-btn" id="excelTableBtn"><i class="fas fa-file-excel"></i> Excel File</button><button class="filter-btn" id="pdfTableBtn"><i class="fas fa-file-pdf"></i> PDF File</button><button class="filter-btn" id="printTableBtn"><i class="fas fa-print"></i> Print</button>
</div></div></div>

<div id="createModal" class="modal"><div class="modal-content"><span class="close" id="closeCreateModal">&times;</span><h2>Add Guest</h2><form id="createForm">
<input type="hidden" name="createStudent" value="1"><p><label>Guest ID</label><input type="text" name="StudentID" required></p><p><label>First Name</label><input type="text" name="FirstName" required></p><p><label>Last Name</label><input type="text" name="LastName" required></p><p><label>Gender</label><select name="Gender" required><option value="Male">Male</option><option value="Female">Female</option><option value="Prefer not to say">Prefer not to say</option></select></p><p><label>Phone Number</label><input type="text" name="PhoneNumber" required></p><p><label>Address</label><input type="text" name="Address" required></p><p><label>Email</label><input type="email" name="Email" required></p><p><label>Nationality</label><input type="text" name="Nationality" required></p><p><label>Birthdate</label><input type="date" name="Birthdate" required></p><button type="submit">Create Guest</button>
</form></div></div>

<div id="deleteModal" class="modal"><div class="modal-content" style="width:420px"><span class="close" id="closeDeleteModal">&times;</span><h2>Delete Guest</h2><p style="color:var(--muted);font-size:12px;line-height:1.6">Are you sure you want to permanently delete this guest record?</p><div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end"><button class="cancel-delete">Cancel</button><button class="confirm-delete">Delete Guest</button></div></div></div>

<script>
document.addEventListener('DOMContentLoaded',function(){
 const sidebar=document.getElementById('sidebar'), menu=document.getElementById('menuBtn'), overlay=document.getElementById('overlay');
 if(menu) menu.onclick=()=>{sidebar.classList.toggle('active');overlay.classList.toggle('active')};
 overlay.onclick=()=>{sidebar.classList.remove('active');overlay.classList.remove('active')};
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    // Download Modal logic
    const downloadModal = document.getElementById('downloadModal');
    const closeDownloadModal = document.getElementById('closeDownloadModal');

    // Show modal from table cell download icon
    function showDownloadModal(e) {
      e.preventDefault();
      downloadModal.style.display = 'block';
    }

    closeDownloadModal.onclick = function() {
      downloadModal.style.display = 'none';
    };
    window.addEventListener('click', function(e) {
      if (e.target == downloadModal) downloadModal.style.display = 'none';
    });

    // Helper: get table data as array (optionally exclude actions/download columns)
    function getTableData(excludeActions = false) {
      const rows = Array.from(document.querySelectorAll('.reservation-table tbody tr'))
        .filter(row => row.style.display !== 'none');
      let headers = Array.from(document.querySelectorAll('.reservation-table thead th'));
      let colCount = headers.length;
      if (excludeActions) {
        // Remove last two columns: Actions and Download
        headers = headers.slice(0, -2);
        colCount = headers.length;
      } else {
        // Remove only Download column
        headers = headers.slice(0, -1);
        colCount = headers.length;
      }
      headers = headers.map(th => th.innerText.trim());
      const data = rows.map(row =>
        Array.from(row.querySelectorAll('td')).slice(0, colCount).map(td => td.innerText.trim())
      );
      return { headers, data };
    }

    // Copy Table
    document.getElementById('copyTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const text = [headers.join('\t'), ...data.map(row => row.join('\t'))].join('\n');
      navigator.clipboard.writeText(text).then(() => {
        alert('Table copied to clipboard!');
        downloadModal.style.display = 'none';
      });
    };

    // Download CSV
    document.getElementById('csvTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const csv = [headers.join(','), ...data.map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(','))].join('\r\n');
      const blob = new Blob([csv], {type: 'text/csv'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'students.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      downloadModal.style.display = 'none';
    };

    // Download Excel
    document.getElementById('excelTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Students");
      XLSX.writeFile(wb, "students.xlsx");
      downloadModal.style.display = 'none';
    };

    // Download PDF
    document.getElementById('pdfTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.autoTable({
        head: [headers],
        body: data,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [0,128,0] }
      });
      doc.save('students.pdf');
      downloadModal.style.display = 'none';
    };

    // Print Table (exclude actions/download columns)
    document.getElementById('printTableBtn').onclick = function() {
      const { headers, data } = getTableData(true);
      let html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
      html += '<thead><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';
      html += '<tbody>' + data.map(row => '<tr>' + row.map(cell => `<td>${cell}</td>`).join('') + '</tr>').join('') + '</tbody></table>';
      const win = window.open('', '', 'width=900,height=700');
      win.document.write('<html><head><title>Print Students</title></head><body>' + html + '</body></html>');
      win.document.close();
      win.print();
      downloadModal.style.display = 'none';
    };
  </script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
  // --- Modal Logic ---
  const editModal = document.getElementById('editModal');
  const closeEditModal = document.getElementById('closeEditModal');
  const viewModal = document.getElementById('viewModal');
  const closeViewModal = document.getElementById('closeViewModal');
  const createModal = document.getElementById('createModal');
  const createBtn = document.getElementById('createBtn');
  const closeCreateModal = document.getElementById('closeCreateModal');
  const deleteModal = document.getElementById('deleteModal');
  const closeDeleteModal = document.getElementById('closeDeleteModal');
  let deleteStudentId = null;

  // Edit Modal
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function() {
      editModal.style.display = 'block';
      document.getElementById('editOriginalStudentID').value = this.dataset.id;
      document.getElementById('editStudentID').value = this.dataset.id;
      document.getElementById('editFirstName').value = this.dataset.firstname;
      document.getElementById('editLastName').value = this.dataset.lastname;
      document.getElementById('editGender').value = this.dataset.gender;
      document.getElementById('editPhoneNumber').value = this.dataset.phonenumber;
      document.getElementById('editAddress').value = this.dataset.address;
      document.getElementById('editEmail').value = this.dataset.email;
      document.getElementById('editNationality').value = this.dataset.nationality;
      document.getElementById('editBirthdate').value = this.dataset.birthdate;
    }
  });
  closeEditModal.onclick = function() { editModal.style.display = 'none'; }

  // Save Edit
  document.getElementById('editForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('student.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        editModal.style.display = 'none';
        location.reload();
      } else {
        alert('Update failed.');
      }
    });
  }

  // View Modal
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.onclick = function() {
      viewModal.style.display = 'block';
      document.getElementById('viewDetails').innerHTML = `
        <p><label>Student ID:</label> <span>${this.dataset.id}</span></p>
        <p><label>First Name:</label> <span>${this.dataset.firstname}</span></p>
        <p><label>Last Name:</label> <span>${this.dataset.lastname}</span></p>
        <p><label>Gender:</label> <span>${this.dataset.gender}</span></p>
        <p><label>Phone Number:</label> <span>${this.dataset.phonenumber}</span></p>
        <p><label>Address:</label> <span>${this.dataset.address}</span></p>
        <p><label>Email:</label> <span>${this.dataset.email}</span></p>
        <p><label>Nationality:</label> <span>${this.dataset.nationality}</span></p>
        <p><label>Birthdate:</label> <span>${this.dataset.birthdate}</span></p>
      `;
    }
  });
  closeViewModal.onclick = function() { viewModal.style.display = 'none'; }

  // Create Modal
  createBtn.onclick = function() { createModal.style.display = 'block'; }
  closeCreateModal.onclick = function() { createModal.style.display = 'none'; }

  // AJAX Create Student
  document.getElementById('createForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('student.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        createModal.style.display = 'none';
        location.reload();
      } else {
        alert('Create failed.');
      }
    });
  };

  // Delete Modal
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.onclick = function() {
      deleteStudentId = this.dataset.id;
      deleteModal.style.display = 'block';
    }
  });
  closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
  document.querySelector('#deleteModal .cancel-delete').onclick = function() {
    deleteModal.style.display = 'none';
    deleteStudentId = null;
  }
  document.querySelector('#deleteModal .confirm-delete').onclick = function() {
    if (!deleteStudentId) return;
    const formData = new FormData();
    formData.append('deleteStudent', 1);
    formData.append('StudentID', deleteStudentId);
    fetch('student.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        deleteModal.style.display = 'none';
        location.reload();
      } else {
        alert('Delete failed.');
      }
    });
  }

  // Modal close on outside click
  window.onclick = function(event) {
    if (event.target == editModal) editModal.style.display = 'none';
    if (event.target == viewModal) viewModal.style.display = 'none';
    if (event.target == createModal) createModal.style.display = 'none';
    if (event.target == deleteModal) deleteModal.style.display = 'none';
  }

  // Search logic
  const searchInput = document.getElementById('searchInput');
  const tableRows = document.querySelectorAll('.reservation-table tbody tr');
  searchInput.oninput = function() {
    const val = searchInput.value.toLowerCase();
    tableRows.forEach(row => {
      let match = false;
      row.querySelectorAll('td').forEach(cell => {
        if (cell.innerText.toLowerCase().includes(val)) match = true;
      });
      row.style.display = match ? '' : 'none';
    });
  }
  </script>
</body>
</html>
