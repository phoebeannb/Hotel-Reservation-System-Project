<?php
include 'connections.php';

// --- FILTER HANDLING ---
$where = [];
$params = [];

// 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
  isset($_GET['BookingID']) && isset($_GET['StudentID']) && isset($_GET['RoomNumber']) &&
  isset($_GET['RoomType']) && isset($_GET['BookingStatus']) && isset($_GET['RoomStatus'])  &&
  isset($_GET['Notes']) && isset($_GET['CheckInDate']) && isset($_GET['CheckOutDate']) && isset($_GET['BookingDate']) &&  isset($_GET['Price'])
)) 
  if (!empty($_GET['BookingID'])) {
    $where[] = "BookingID = ?";
    $params[] = $_GET['BookingID'];
  }
  if (!empty($_GET['StudentID'])) {
    $where[] = "StudentID = ?";
    $params[] = $_GET['StudentID'];
  }
  if (!empty($_GET['CheckInDate'])) {
    $where[] = "CheckInDate = ?";
    $params[] = $_GET['CheckInDate'];
  }
  if (!empty($_GET['CheckOutDate'])) {
    $where[] = "CheckOutDate = ?";
    $params[] = $_GET['CheckOutDate'];
  }
  if (!empty($_GET['RoomNumber'])) {
    $where[] = "RoomNumber = ?";
    $params[] = $_GET['RoomNumber'];
  }
  if (!empty($_GET['RoomType'])) {
    $where[] = "RoomType = ?";
    $params[] = $_GET['RoomType'];
  }
  if (!empty($_GET['Status'])) {
    $where[] = "Status = ?";
    $params[] = $_GET['Status'];
  }
  if (!empty($_GET['RoomStatus'])) {
    $where[] = "RoomStatus = ?";
    $params[] = $_GET['RoomStatus'];
  }
  if (!empty($_GET['Price'])) {
    $where[] = "Price = ?";
    $params[] = $_GET['Price'];
  }
  if (!empty($_GET['BookingDate'])) {
    $where[] = "BookingDate = ?";
    $params[] = $_GET['BookingDate'];
  }
  if (!empty($_GET['Notes'])) {
    $where[] = "Notes = ?";
    $params[] = $_GET['Notes'];
  }


// --- AJAX UPDATE ---
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['BookingID']) && isset($_POST['StudentID']) && isset($_POST['RoomNumber']) &&
  isset($_POST['RoomType']) && isset($_POST['BookingStatus']) && isset($_POST['RoomStatus']) &&
  isset($_POST['Notes']) && isset($_POST['CheckInDate']) && isset($_POST['CheckOutDate']) && isset($_POST['BookingDate']) && isset($_POST['Price'])
) {
  $bookingid = intval($_POST['BookingID']);
  $studentid = $conn->real_escape_string($_POST['StudentID']);
  $roomnumber = $conn->real_escape_string($_POST['RoomNumber']);
  $roomtype = $conn->real_escape_string($_POST['RoomType']);
  $bookingstatus = $conn->real_escape_string($_POST['BookingStatus']);
  $roomstatus = $conn->real_escape_string($_POST['RoomStatus']);
  $notes = $conn->real_escape_string($_POST['Notes']);
  $checkin = $conn->real_escape_string($_POST['CheckInDate']);
  $checkout = $conn->real_escape_string($_POST['CheckOutDate']);
  $bookingdate = $conn->real_escape_string($_POST['BookingDate']);
  $price = $conn->real_escape_string($_POST['Price']);

  $sql = "UPDATE booking SET
    StudentID='$studentid',
    RoomNumber='$roomnumber',
    RoomType='$roomtype',
    BookingStatus='$bookingstatus',
    RoomStatus='$roomstatus',
    Notes='$notes',
    CheckInDate='$checkin',
    CheckOutDate='$checkout',
    BookingDate='$bookingdate',
    Price='$price'
    WHERE BookingID=$bookingid";

  $success = $conn->query($sql);

  header('Content-Type: application/json');
  echo json_encode(['success' => $success, 'error' => $conn->error, 'sql' => $sql]);
  exit;
}

// --- AJAX DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteBooking']) && isset($_POST['BookingID'])) {
  $bookingid = intval($_POST['BookingID']);
  $sql = "DELETE FROM booking WHERE BookingID=$bookingid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- CREATE STUDENT (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['addBooking']) || isset($_POST['createBooking']))) {
  $bookingid = $conn->real_escape_string($_POST['BookingID']);
  $studentid = $conn->real_escape_string($_POST['StudentID']);
  $roomnumber = $conn->real_escape_string($_POST['RoomNumber']);
  $roomtype = $conn->real_escape_string($_POST['RoomType']);
  $bookingstatus = $conn->real_escape_string($_POST['BookingStatus']);
  $roomstatus = $conn->real_escape_string($_POST['RoomStatus']);
  $notes = $conn->real_escape_string($_POST['Notes']);
  $checkin = $conn->real_escape_string($_POST['CheckInDate']);
  $checkout = $conn->real_escape_string($_POST['CheckOutDate']);
  $bookingdate = $conn->real_escape_string($_POST['BookingDate']);
  $price = $conn->real_escape_string($_POST['Price']);
  $sql = "INSERT INTO booking (BookingID, StudentID, RoomNumber, RoomType, BookingStatus, RoomStatus, Notes, CheckInDate, CheckOutDate, BookingDate, Price) VALUES ('$bookingid','$studentid', '$roomnumber', '$roomtype', '$bookingstatus', '$roomstatus', '$notes', '$checkin', '$checkout', '$bookingdate', '$price')";
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
  $sql = "SELECT * FROM booking WHERE " . implode(' AND ', $where) . " ORDER BY BookingID DESC";
  $stmt = $conn->prepare($sql); 
  if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $resResult = $stmt->get_result();
} else {
  $resQuery = "SELECT * FROM booking ORDER BY BookingID DESC";
  $resResult = $conn->query($resQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Management | Villa Valore</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--green:#166534;--green2:#15803d;--soft:#ecfdf3;--ink:#17211b;--muted:#6b7280;--line:#e5e7eb;--bg:#f6f8f7;--danger:#dc2626;--blue:#2563eb}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--ink);min-height:100vh}
.sidebar{position:fixed;inset:0 auto 0 0;width:232px;background:linear-gradient(180deg,#14532d,#166534);color:#fff;padding:22px 14px;z-index:1000}
.brand{display:flex;align-items:center;gap:11px;padding:4px 10px 26px}.brand img{width:50px;height:50px;object-fit:contain}.brand strong{font-family:'Playfair Display',serif;font-size:19px}
.nav-label{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#bbf7d0;padding:12px 12px 7px}
.nav-link{display:flex;align-items:center;gap:12px;color:#ecfdf5;text-decoration:none;padding:11px 12px;border-radius:10px;margin:2px 0;font-size:14px;transition:.2s}.nav-link i{width:20px;text-align:center}.nav-link:hover{background:rgba(255,255,255,.1)}.nav-link.active{background:#fff;color:var(--green);font-weight:700}
.main{margin-left:232px;min-height:100vh}.topbar{height:72px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 34px;position:sticky;top:0;z-index:900}.breadcrumb{font-size:13px;color:var(--muted)}.breadcrumb strong{color:var(--ink)}.user{width:38px;height:38px;border-radius:50%;background:var(--soft);color:var(--green);display:grid;place-items:center;font-weight:700}
.content{padding:32px;max-width:1500px;margin:auto}.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:24px}.eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:1.5px;color:var(--green2);font-weight:700;margin-bottom:5px}h1{font-family:'Playfair Display',serif;font-size:34px}.subtitle{color:var(--muted);margin-top:7px;font-size:14px}.head-actions{display:flex;gap:10px}
.btn{border:0;border-radius:10px;padding:11px 16px;font:600 14px 'DM Sans';cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px}.btn-primary{background:var(--green);color:#fff}.btn-primary:hover{background:#14532d}.btn-secondary{background:#fff;color:var(--ink);border:1px solid var(--line)}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 5px 20px rgba(16,24,40,.04)}.toolbar{padding:16px;display:flex;gap:12px;justify-content:space-between;align-items:center;border-bottom:1px solid var(--line)}.search{position:relative;flex:1;max-width:390px}.search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af}.search input{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px 13px 11px 38px;outline:0;font:14px 'DM Sans';background:#fafafa}.search input:focus{border-color:#86efac;background:#fff;box-shadow:0 0 0 3px #dcfce7}
.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:1050px}th{text-align:left;padding:13px 16px;background:#fafafa;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.65px;border-bottom:1px solid var(--line);white-space:nowrap}td{padding:15px 16px;border-bottom:1px solid #f0f2f1;font-size:13px;vertical-align:middle;white-space:nowrap}tr:hover td{background:#fbfefc}.booking-id{font-weight:700;color:var(--green)}
.status{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:700}.status-confirmed{background:#dcfce7;color:#166534}.status-pending{background:#fef3c7;color:#92400e}.status-cancelled{background:#fee2e2;color:#991b1b}.status-completed{background:#dbeafe;color:#1e40af}
.actions{display:flex;gap:5px}.icon-btn{width:34px;height:34px;border:0;border-radius:9px;background:#f5f7f6;cursor:pointer;display:grid;place-items:center}.icon-btn.edit{color:var(--green)}.icon-btn.view{color:var(--blue)}.icon-btn.delete{color:var(--danger)}.icon-btn:hover{background:#eaf5ee}.download-btn{border:0;background:#effaf2;color:var(--green);width:34px;height:34px;border-radius:9px;cursor:pointer}.empty{text-align:center;padding:48px;color:var(--muted)}
.modal{display:none;position:fixed;inset:0;background:rgba(15,23,18,.48);z-index:2000;padding:30px 15px;overflow:auto}.modal-content{background:#fff;width:min(620px,100%);margin:30px auto;border-radius:18px;padding:26px;position:relative;box-shadow:0 25px 70px rgba(0,0,0,.2)}.close{position:absolute;right:18px;top:15px;width:34px;height:34px;border-radius:9px;background:#f3f4f6;display:grid;place-items:center;color:#6b7280;cursor:pointer;font-size:22px}.modal h2{font-family:'Playfair Display',serif;font-size:24px;margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:12px;font-weight:700;color:#4b5563}.field input,.field select{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:9px;outline:0;font:14px 'DM Sans'}.field input:focus,.field select:focus{border-color:#86efac;box-shadow:0 0 0 3px #dcfce7}.form-submit{margin-top:18px;width:100%;padding:12px;border:0;border-radius:10px;background:var(--green);color:#fff;font:700 14px 'DM Sans';cursor:pointer}
.details{display:grid;grid-template-columns:1fr 1fr;gap:12px}.detail{background:#f8faf9;border-radius:10px;padding:11px 13px}.detail label{display:block;color:var(--muted);font-size:11px;margin-bottom:3px}.detail span{font-weight:600;font-size:13px}.danger{background:var(--danger);color:#fff;border:0;padding:10px 16px;border-radius:9px;font-weight:700;cursor:pointer}.cancel{background:#f3f4f6;border:1px solid var(--line);padding:10px 16px;border-radius:9px;font-weight:600;cursor:pointer}
@media(max-width:900px){.sidebar{display:none}.main{margin-left:0}.content{padding:22px 16px}.topbar{padding:0 16px}.page-head{align-items:flex-start;flex-direction:column}.head-actions{width:100%}}
@media(max-width:600px){h1{font-size:28px}.toolbar{flex-direction:column;align-items:stretch}.search{max-width:none}.form-grid,.details{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style>
</head>
<body>
<aside class="sidebar">
<div class="brand"><img src="images/villavalorelogo.png" alt="Villa Valore Logo"><strong>Villa Valore</strong></div>
<div class="nav-label">Overview</div><a class="nav-link" href="index.php"><i class="fas fa-chart-pie"></i>Dashboard</a>
<div class="nav-label">Management</div><a class="nav-link" href="student.php"><i class="fas fa-user-group"></i>Guests</a><a class="nav-link active" href="booking.php"><i class="fas fa-book"></i>Bookings</a><a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservations</a>
<div class="nav-label">Resources</div><a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Rooms</a><a class="nav-link" href="inventory.php"><i class="fas fa-box"></i>Inventory</a>
<div class="nav-label">Administration</div><a class="nav-link" href="account.php"><i class="fas fa-user"></i>Account</a><a class="nav-link" href="payment.php"><i class="fas fa-receipt"></i>Invoices</a><a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i>Statistics</a>
</aside>
<div class="main"><header class="topbar"><div class="breadcrumb">Management / <strong>Bookings</strong></div><div class="user">VV</div></header>
<main class="content">
<div class="page-head"><div><div class="eyebrow">Management</div><h1>Bookings</h1><p class="subtitle">Manage guest bookings, room assignments, dates and booking status.</p></div><div class="head-actions"><button id="createBtn" class="btn btn-primary"><i class="fas fa-plus"></i>Add Booking</button></div></div>
<section class="card"><div class="toolbar"><div class="search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Search bookings..."></div><button class="btn btn-secondary" onclick="showDownloadModal(event)"><i class="fas fa-download"></i>Export</button></div>
<div class="table-wrap"><table class="reservation-table"><thead><tr><th>Booking ID</th><th>Student ID</th><th>Room</th><th>Booking Status</th><th>Room Status</th><th>Check In</th><th>Check Out</th><th>Booking Date</th><th>Actions</th><th>Export</th></tr></thead><tbody>
<?php if ($resResult && $resResult->num_rows > 0): ?><?php while($row = $resResult->fetch_assoc()): ?>
<tr data-id="<?php echo $row['BookingID']; ?>">
<td><span class="booking-id">#<?php echo $row['BookingID']; ?></span></td><td><b><?php echo htmlspecialchars($row['StudentID']); ?></b></td>
<td><?php echo htmlspecialchars($row['RoomType']); ?> <span style="color:#9ca3af">•</span> <?php echo htmlspecialchars($row['RoomNumber']); ?></td>
<td><span class="status status-<?php echo strtolower($row['BookingStatus']); ?>"><?php echo htmlspecialchars($row['BookingStatus']); ?></span></td><td><?php echo htmlspecialchars($row['RoomStatus']); ?></td>
<td><?php echo htmlspecialchars($row['CheckInDate']); ?></td><td><?php echo htmlspecialchars($row['CheckOutDate']); ?></td><td><?php echo htmlspecialchars($row['BookingDate']); ?></td>
<td><div class="actions">
<button type="button" class="icon-btn edit edit-btn" title="Edit" data-id="<?php echo $row['BookingID']; ?>" data-studentid="<?php echo htmlspecialchars($row['StudentID']); ?>" data-roomnumber="<?php echo htmlspecialchars($row['RoomNumber']); ?>" data-roomtype="<?php echo htmlspecialchars($row['RoomType']); ?>" data-bookingstatus="<?php echo htmlspecialchars($row['BookingStatus']); ?>" data-roomstatus="<?php echo htmlspecialchars($row['RoomStatus']); ?>" data-notes="<?php echo htmlspecialchars($row['Notes']); ?>" data-checkin="<?php echo htmlspecialchars($row['CheckInDate']); ?>" data-checkout="<?php echo htmlspecialchars($row['CheckOutDate']); ?>" data-bookingdate="<?php echo htmlspecialchars($row['BookingDate']); ?>" data-price="<?php echo htmlspecialchars($row['Price']); ?>"><i class="fas fa-pen"></i></button>
<button type="button" class="icon-btn view view-btn" title="View" data-id="<?php echo $row['BookingID']; ?>" data-studentid="<?php echo htmlspecialchars($row['StudentID']); ?>" data-roomnumber="<?php echo htmlspecialchars($row['RoomNumber']); ?>" data-roomtype="<?php echo htmlspecialchars($row['RoomType']); ?>" data-bookingstatus="<?php echo htmlspecialchars($row['BookingStatus']); ?>" data-roomstatus="<?php echo htmlspecialchars($row['RoomStatus']); ?>" data-notes="<?php echo htmlspecialchars($row['Notes']); ?>" data-checkin="<?php echo htmlspecialchars($row['CheckInDate']); ?>" data-checkout="<?php echo htmlspecialchars($row['CheckOutDate']); ?>" data-bookingdate="<?php echo htmlspecialchars($row['BookingDate']); ?>" data-price="<?php echo htmlspecialchars($row['Price']); ?>"><i class="fas fa-eye"></i></button>
<button type="button" class="icon-btn delete delete-btn" title="Delete" data-id="<?php echo $row['BookingID']; ?>"><i class="fas fa-trash"></i></button>
</div></td><td><button class="download-btn" title="Export" onclick="showDownloadModal(event)"><i class="fas fa-download"></i></button></td>
</tr>
<?php endwhile; ?><?php else: ?><tr><td colspan="10"><div class="empty"><i class="fas fa-calendar-xmark" style="font-size:28px;margin-bottom:10px"></i><div>No bookings found.</div></div></td></tr><?php endif; ?>
</tbody></table></div></section></main></div>

<div id="editModal" class="modal"><div class="modal-content"><span class="close" id="closeEditModal">&times;</span><h2>Edit Booking</h2><form id="editForm"><div class="form-grid">
<div class="field"><label>Booking ID</label><input type="text" name="BookingID" id="editBookingID" required></div><div class="field"><label>Student ID</label><input type="text" name="StudentID" id="editStudentID" required></div><div class="field"><label>Room Number</label><input type="text" name="RoomNumber" id="editRoomNumber" required></div><div class="field"><label>Room Type</label><select name="RoomType" id="editRoomType" required><option>Standard</option><option>Deluxe</option><option>Suite</option></select></div>
<div class="field"><label>Booking Status</label><select name="BookingStatus" id="editBookingStatus" required><option>Pending</option><option>Confirmed</option><option>Cancelled</option><option>Completed</option></select></div><div class="field"><label>Room Status</label><input type="text" name="RoomStatus" id="editRoomStatus" required></div><div class="field full"><label>Notes</label><input type="text" name="Notes" id="editNotes" required></div>
<div class="field"><label>Check In</label><input type="date" name="CheckInDate" id="editCheckInDate" required></div><div class="field"><label>Check Out</label><input type="date" name="CheckOutDate" id="editCheckOutDate" required></div><div class="field"><label>Booking Date</label><input type="date" name="BookingDate" id="editBookingDate" required></div><div class="field"><label>Price</label><input type="number" name="Price" id="editPrice" required></div>
</div><button class="form-submit" type="submit">Save Changes</button></form></div></div>

<div id="viewModal" class="modal"><div class="modal-content"><span class="close" id="closeViewModal">&times;</span><h2>Booking Details</h2><div id="viewDetails" class="details"></div></div></div>
<div id="downloadModal" class="modal"><div class="modal-content" style="max-width:400px"><span class="close" id="closeDownloadModal">&times;</span><h2>Export Bookings</h2><div style="display:grid;gap:10px;margin-top:12px">
<button class="btn btn-secondary" id="copyTableBtn"><i class="fas fa-copy"></i>Copy to Clipboard</button><button class="btn btn-secondary" id="csvTableBtn"><i class="fas fa-file-csv"></i>CSV File</button><button class="btn btn-secondary" id="excelTableBtn"><i class="fas fa-file-excel"></i>Excel File</button><button class="btn btn-secondary" id="pdfTableBtn"><i class="fas fa-file-pdf"></i>PDF File</button><button class="btn btn-secondary" id="printTableBtn"><i class="fas fa-print"></i>Print</button></div></div></div>

<div id="createModal" class="modal"><div class="modal-content"><span class="close" id="closeCreateModal">&times;</span><h2>Add Booking</h2><form id="createForm"><input type="hidden" name="createBooking" value="1"><div class="form-grid">
<div class="field"><label>Booking ID</label><input type="text" name="BookingID" required></div><div class="field"><label>Student ID</label><input type="text" name="StudentID" required></div><div class="field"><label>Room Number</label><input type="text" name="RoomNumber" required></div><div class="field"><label>Room Type</label><select name="RoomType" required><option>Standard</option><option>Deluxe</option><option>Suite</option></select></div>
<div class="field"><label>Booking Status</label><select name="BookingStatus" required><option>Pending</option><option>Confirmed</option><option>Cancelled</option><option>Completed</option></select></div><div class="field"><label>Room Status</label><input type="text" name="RoomStatus" required></div><div class="field full"><label>Notes</label><input type="text" name="Notes" required></div>
<div class="field"><label>Check In</label><input type="date" name="CheckInDate" required></div><div class="field"><label>Check Out</label><input type="date" name="CheckOutDate" required></div><div class="field"><label>Booking Date</label><input type="date" name="BookingDate" required></div><div class="field"><label>Price</label><input type="number" name="Price" required></div>
</div><button class="form-submit" type="submit">Create Booking</button></form></div></div>

<div id="deleteModal" class="modal"><div class="modal-content" style="max-width:420px"><span class="close" id="closeDeleteModal">&times;</span><h2>Delete Booking</h2><p style="color:#6b7280;line-height:1.6">Are you sure you want to delete this booking? This action cannot be undone.</p><div style="display:flex;justify-content:flex-end;gap:9px;margin-top:22px"><button class="cancel-delete cancel">Cancel</button><button class="confirm-delete danger">Delete Booking</button></div></div></div>
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
      a.download = 'bookings.csv';
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
      XLSX.utils.book_append_sheet(wb, ws, "Bookings");
      XLSX.writeFile(wb, "bookings.xlsx");
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
      doc.save('bookings.pdf');
      downloadModal.style.display = 'none';
    };

    // Print Table (exclude actions/download columns)
    document.getElementById('printTableBtn').onclick = function() {
      const { headers, data } = getTableData(true);
      let html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
      html += '<thead><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';
      html += '<tbody>' + data.map(row => '<tr>' + row.map(cell => `<td>${cell}</td>`).join('') + '</tr>').join('') + '</tbody></table>';
      const win = window.open('', '', 'width=900,height=700');
      win.document.write('<html><head><title>Print Bookings</title></head><body>' + html + '</body></html>');
      win.document.close();
      win.print();
      downloadModal.style.display = 'none';
    };
  </script>
  <!-- jsPDF autotable plugin -->
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
  let deleteBookingId = null;

  // Edit Modal
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function() {
      editModal.style.display = 'block';
      document.getElementById('editBookingID').value = this.dataset.id;
      document.getElementById('editStudentID').value = this.dataset.studentid;
      document.getElementById('editRoomNumber').value = this.dataset.roomnumber;
      document.getElementById('editRoomType').value = this.dataset.roomtype;
      document.getElementById('editBookingStatus').value = this.dataset.bookingstatus;
      document.getElementById('editRoomStatus').value = this.dataset.roomstatus;
      document.getElementById('editNotes').value = this.dataset.notes;
      document.getElementById('editCheckInDate').value = this.dataset.checkin;
      document.getElementById('editCheckOutDate').value = this.dataset.checkout;
      document.getElementById('editBookingDate').value = this.dataset.bookingdate;
      document.getElementById('editPrice').value = this.dataset.price;
    }
  });
  closeEditModal.onclick = function() { editModal.style.display = 'none'; }

  // Save Edit
  document.getElementById('editForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('booking.php', {
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
        <p><label>Booking ID:</label> <span>${this.dataset.id}</span></p>
        <p><label>Student ID:</label> <span>${this.dataset.studentid}</span></p>
        <p><label>Room Number:</label> <span>${this.dataset.roomnumber}</span></p>
        <p><label>Room Type:</label> <span>${this.dataset.roomtype}</span></p>
        <p><label>Booking Status:</label> <span>${this.dataset.bookingstatus}</span></p>
        <p><label>Room Status:</label> <span>${this.dataset.roomstatus}</span></p>
        <p><label>Notes:</label> <span>${this.dataset.notes}</span></p>
        <p><label>Check In Date:</label> <span>${this.dataset.checkin}</span></p>
        <p><label>Check Out Date:</label> <span>${this.dataset.checkout}</span></p>
        <p><label>Booking Date:</label> <span>${this.dataset.bookingdate}</span></p>
        <p><label>Price:</label> <span>${this.dataset.price}</span></p>
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
    fetch('booking.php', {
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
      deleteBookingId = this.dataset.id;
      deleteModal.style.display = 'block';
    }
  });
  closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
  document.querySelector('#deleteModal .cancel-delete').onclick = function() {
    deleteModal.style.display = 'none';
    deleteBookingId = null;
  }
  document.querySelector('#deleteModal .confirm-delete').onclick = function() {
    if (!deleteBookingId) return;
    const formData = new FormData();
    formData.append('deleteBooking', 1);
    formData.append('BookingID', deleteBookingId);
    fetch('booking.php', {
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