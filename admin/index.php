<?php include 'connections.php';

// Database Query Functions
function getStats() {
    global $conn;
    
    $stats = [
        'new_bookings' => 0,
        'available_rooms' => 0,
        'check_ins' => 0,
        'check_outs' => 0,
        'total_reservations' => 0,
        'average_stay' => 0,
        'occupancy_rate' => 0,
        'rooms_to_clean' => 0,
        'rooms_cleaned' => 0,
        'maintenance_required' => 0
    ];

    // Get new bookings (bookings made in the last 24 hours)
    $query = "SELECT COUNT(*) as count FROM booking WHERE BookingDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['new_bookings'] = $row['count'];
    }

    // Get available rooms
    $query = "SELECT COUNT(*) as count FROM room WHERE RoomStatus = 'Available'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['available_rooms'] = $row['count'];
    }

    // Get today's check-ins
    $query = "SELECT COUNT(*) as count FROM booking WHERE DATE(CheckInDate) = CURDATE() AND BookingStatus = 'Confirmed'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['check_ins'] = $row['count'];
    }

    // Get today's check-outs
    $query = "SELECT COUNT(*) as count FROM booking WHERE DATE(CheckOutDate) = CURDATE()";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['check_outs'] = $row['count'];
    }

    // Get total reservations
    $query = "SELECT COUNT(*) as count FROM booking WHERE BookingStatus != 'Cancelled'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_reservations'] = $row['count'];
    }

    // Calculate average stay
    $query = "SELECT AVG(TIMESTAMPDIFF(DAY, CheckInDate, CheckOutDate)) as avg_stay 
              FROM booking 
              WHERE BookingStatus != 'Cancelled' AND CheckOutDate > CheckInDate";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['average_stay'] = round($row['avg_stay'], 1);
    }

    // Calculate occupancy rate
    $query = "SELECT 
                (SELECT COUNT(*) FROM room WHERE RoomStatus = 'Occupied') as occupied,
                (SELECT COUNT(*) FROM room) as total";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['occupancy_rate'] = $row['total'] > 0 
            ? round(($row['occupied'] / $row['total']) * 100) 
            : 0;
    }

    // Get housekeeping stats
    $query = "SELECT 
                SUM(CASE WHEN RoomStatus = 'Cleaning' THEN 1 ELSE 0 END) as to_clean,
                SUM(CASE WHEN RoomStatus = 'Available' THEN 1 ELSE 0 END) as cleaned,
                SUM(CASE WHEN RoomStatus = 'Maintenance' THEN 1 ELSE 0 END) as maintenance
              FROM room";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['rooms_to_clean'] = $row['to_clean'];
        $stats['rooms_cleaned'] = $row['cleaned'];
        $stats['maintenance_required'] = $row['maintenance'];
    }

    return $stats;
}

function getBookingSchedule($year, $month) {
    global $conn;
    
    $bookings = [];
    
    $query = "SELECT 
                DAY(CheckInDate) as day,
                COUNT(*) as booking_count
              FROM booking
              WHERE YEAR(CheckInDate) = ? 
              AND MONTH(CheckInDate) = ?
              AND BookingStatus != 'Cancelled'
              GROUP BY DAY(CheckInDate)";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[$row['day']] = $row['booking_count'];
    }
    
    return $bookings;
}

function getRecentBookings($limit = 5) {
    global $conn;
    
    $bookings = [];
    
    $query = "SELECT 
                b.BookingID as id,
                CONCAT(s.FirstName, ' ', s.LastName) as guest_name,
                b.CheckInDate as check_in_date,
                b.CheckOutDate as check_out_date,
                b.RoomNumber as room_id,
                b.BookingStatus as status,
                r.RoomNumber as room_number
              FROM booking b
              LEFT JOIN account s ON b.StudentID = s.accountID
              JOIN room r ON b.RoomNumber = r.RoomNumber
              ORDER BY b.BookingDate DESC
              LIMIT ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

// Handle AJAX Calendar Updates
if (isset($_GET['ajax_calendar'])) {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    
    $bookingSchedule = getBookingSchedule($year, $month);
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDay);
    $startDay = date('N', $firstDay);
    $currentDate = date('j');
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    $calendarHtml = '';
    
    for ($i = 1; $i < $startDay; $i++) {
        $calendarHtml .= '<div class="calendar-day empty"></div>';
    }
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $classes = ['calendar-day'];
        if ($day == $currentDate && $month == $currentMonth && $year == $currentYear) {
            $classes[] = 'current-day';
        }
        if (isset($bookingSchedule[$day]) && $bookingSchedule[$day] > 0) {
            $classes[] = 'has-bookings';
        }
        
        $calendarHtml .= '<div class="' . implode(' ', $classes) . '">';
        $calendarHtml .= (int)$day;
        if (isset($bookingSchedule[$day])) {
            $calendarHtml .= '<span class="booking-count">' . $bookingSchedule[$day] . '</span>';
        }
        $calendarHtml .= '</div>';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'calendarHtml' => $calendarHtml,
        'monthDisplay' => date('F Y', $firstDay)
    ]);
    exit;
}

// Get initial data
$stats = getStats();
$currentYear = date('Y');
$currentMonth = date('n');
$bookingSchedule = getBookingSchedule($currentYear, $currentMonth);
$recentBookings = getRecentBookings(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Valore — Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --bg: #f5f6f4;
            --surface: #ffffff;
            --surface-soft: #f8faf8;
            --text: #17221c;
            --muted: #778078;
            --line: #e7ebe7;
            --primary: #214f3b;
            --primary-2: #2e6b50;
            --primary-soft: #e8f1ec;
            --gold: #b48a45;
            --danger: #c95757;
            --shadow: 0 10px 30px rgba(22, 40, 30, .06);
            --radius: 18px;
            --sidebar: 250px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "DM Sans", sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        button, input { font: inherit; }

        .app { min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar);
            background: #17382b;
            color: #fff;
            padding: 26px 18px 18px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform .25s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 10px 30px;
        }

        .brand img {
            width: 46px;
            height: 46px;
            object-fit: contain;
            border-radius: 12px;
            background: rgba(255,255,255,.08);
        }

        .brand-text strong {
            display: block;
            font-family: "Playfair Display", serif;
            font-size: 19px;
            letter-spacing: .2px;
        }

        .brand-text span {
            display: block;
            color: #afc4b8;
            font-size: 11px;
            margin-top: 2px;
        }

        .nav-label {
            color: #8fa99b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            padding: 0 12px 9px;
            margin-top: 8px;
        }

        .nav-section { margin-bottom: 20px; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #dbe7e0;
            text-decoration: none;
            padding: 11px 13px;
            border-radius: 12px;
            margin: 3px 0;
            font-size: 13px;
            transition: .2s ease;
        }

        .nav-link i {
            width: 18px;
            text-align: center;
            color: #a9c1b4;
        }

        .nav-link:hover {
            background: rgba(255,255,255,.08);
            color: #fff;
        }

        .nav-link.active {
            background: #fff;
            color: var(--primary);
            font-weight: 700;
            box-shadow: 0 7px 20px rgba(0,0,0,.10);
        }

        .nav-link.active i { color: var(--primary); }

        .sidebar-bottom { margin-top: auto; }

        .logout {
            border-top: 1px solid rgba(255,255,255,.09);
            padding-top: 14px;
        }

        /* MAIN */
        .main {
            margin-left: var(--sidebar);
            min-height: 100vh;
        }

        .topbar {
            height: 76px;
            background: rgba(255,255,255,.94);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 34px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .menu-btn {
            display: none;
            border: 0;
            background: var(--surface-soft);
            width: 40px;
            height: 40px;
            border-radius: 11px;
            cursor: pointer;
        }

        .breadcrumb {
            color: var(--muted);
            font-size: 12px;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: #fff;
            color: #536058;
            cursor: pointer;
            position: relative;
        }

        .notification-dot {
            position: absolute;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--gold);
            right: 8px;
            top: 8px;
            border: 2px solid #fff;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 5px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 13px;
        }

        .profile-text strong { display: block; font-size: 12px; }
        .profile-text span { display: block; color: var(--muted); font-size: 10px; margin-top: 1px; }

        .content {
            padding: 32px;
            max-width: 1500px;
            margin: auto;
        }

        .welcome {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin-bottom: 24px;
        }

        .eyebrow {
            color: var(--primary-2);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 7px;
        }

        h1 {
            font-family: "Playfair Display", serif;
            font-size: 32px;
            line-height: 1.15;
            letter-spacing: -.5px;
        }

        .welcome p {
            color: var(--muted);
            font-size: 13px;
            margin-top: 7px;
        }

        .date-pill {
            background: #fff;
            border: 1px solid var(--line);
            padding: 10px 14px;
            border-radius: 12px;
            color: #5e6962;
            font-size: 12px;
            box-shadow: var(--shadow);
        }

        /* STAT CARDS */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: var(--primary-soft);
            right: -35px;
            top: -35px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 15px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            margin-top: 16px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 700;
            margin-top: 3px;
        }

        .stat-meta {
            color: #8b958e;
            font-size: 10px;
            margin-top: 5px;
        }

        /* GRID */
        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(280px, .85fr);
            gap: 20px;
        }

        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .panel + .panel { margin-top: 20px; }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-title {
            font-size: 15px;
            font-weight: 700;
        }

        .panel-subtitle {
            color: var(--muted);
            font-size: 11px;
            margin-top: 4px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .month-label {
            min-width: 110px;
            text-align: center;
            font-weight: 700;
            font-size: 12px;
        }

        .nav-btn {
            width: 32px;
            height: 32px;
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 9px;
            cursor: pointer;
            color: #59645d;
        }

        .nav-btn:hover { background: var(--surface-soft); }

        /* CALENDAR */
        .calendar-header,
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 7px;
        }

        .calendar-header {
            color: #9aa39d;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .calendar-day {
            min-height: 55px;
            border: 1px solid #edf0ed;
            border-radius: 10px;
            padding: 8px;
            position: relative;
            background: #fff;
            font-size: 11px;
            color: #58635b;
        }

        .calendar-day:hover { background: #fbfcfb; }

        .calendar-day.current-day {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            font-weight: 700;
        }

        .calendar-day.has-bookings {
            border-color: #bfd5c7;
            background: #f4f9f6;
        }

        .calendar-day.current-day.has-bookings {
            background: var(--primary);
            color: #fff;
        }

        .calendar-day.empty {
            border: 0;
            background: transparent;
        }

        .booking-count {
            position: absolute;
            bottom: 7px;
            right: 7px;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: var(--gold);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
        }

        /* TABLE */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 580px;
        }

        th {
            color: #9aa39d;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .7px;
            text-align: left;
            padding: 0 12px 11px;
            border-bottom: 1px solid var(--line);
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #f0f2f0;
            font-size: 11px;
            color: #536057;
        }

        tbody tr:last-child td { border-bottom: 0; }

        .guest {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 600;
            color: var(--text);
        }

        .guest-avatar {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: var(--primary-soft);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 10px;
            font-weight: 700;
        }

        .room-badge {
            display: inline-block;
            background: #f1f4f1;
            border-radius: 7px;
            padding: 5px 8px;
            font-size: 10px;
            color: #4f5b53;
        }

        /* RIGHT CARDS */
        .metric {
            padding: 15px;
            border-radius: 13px;
            background: var(--surface-soft);
            border: 1px solid #edf0ed;
            margin-bottom: 10px;
        }

        .metric:last-child { margin-bottom: 0; }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-label {
            color: var(--muted);
            font-size: 11px;
        }

        .metric-value {
            font-size: 20px;
            font-weight: 700;
            margin-top: 3px;
        }

        .metric-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            background: #fff;
            color: var(--primary);
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
        }

        .progress {
            height: 7px;
            background: #e9eeea;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 11px;
        }

        .progress span {
            display: block;
            height: 100%;
            background: var(--primary);
            border-radius: inherit;
            width: <?php echo min(100, max(0, (int)$stats['occupancy_rate'])); ?>%;
        }

        .housekeeping-list { display: grid; gap: 10px; }

        .housekeeping-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #edf0ed;
            border-radius: 12px;
        }

        .housekeeping-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #f4f6f4;
            color: #5c6b61;
            display: grid;
            place-items: center;
        }

        .housekeeping-item strong {
            display: block;
            font-size: 12px;
        }

        .housekeeping-item span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 2px;
        }

        .empty-state {
            padding: 25px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        /* MOBILE */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            z-index: 999;
        }

        @media (max-width: 1100px) {
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            :root { --sidebar: 250px; }

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 20px 0 40px rgba(0,0,0,.12);
            }

            .sidebar.active { transform: translateX(0); }
            .overlay.active { display: block; }

            .main { margin-left: 0; }

            .topbar { padding: 0 18px; height: 68px; }
            .menu-btn { display: block; }
            .profile-text { display: none; }

            .content { padding: 22px 16px; }

            .welcome {
                align-items: flex-start;
                flex-direction: column;
                gap: 13px;
            }

            h1 { font-size: 27px; }

            .stats-cards { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card { padding: 15px; }
            .stat-value { font-size: 23px; }

            .panel { padding: 16px; border-radius: 15px; }
            .calendar-day { min-height: 43px; padding: 6px; }
        }

        @media (max-width: 480px) {
            .stats-cards { grid-template-columns: 1fr; }
            .date-pill { display: none; }
            .calendar-day { min-height: 39px; }
            .booking-count { width: 17px; min-width: 17px; height: 17px; padding: 0; right: 4px; bottom: 4px; }
        }
    </style>
</head>

<body>
<div class="app">

    <div class="overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="images/villavalorelogo.png" alt="Villa Valore">
            <div class="brand-text">
                <strong>Villa Valore</strong>
                <span>Hotel Management</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-label">Overview</div>
            <a class="nav-link active" href="index.php"><i class="fas fa-grid-2"></i><span>Dashboard</span></a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Management</div>
            <a class="nav-link" href="student.php"><i class="fas fa-users"></i><span>Guests</span></a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book-open"></i><span>Bookings</span></a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i><span>Reservations</span></a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Operations</div>
            <a class="nav-link" href="room.php"><i class="fas fa-bed"></i><span>Rooms</span></a>
            <a class="nav-link" href="inventory.php"><i class="fas fa-boxes-stacked"></i><span>Inventory</span></a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Finance</div>
            <a class="nav-link" href="payment.php"><i class="fas fa-receipt"></i><span>Invoices</span></a>
            <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
        </div>

        <div class="sidebar-bottom">
            <div class="logout">
                <a class="nav-link" href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i><span>Logout</span></a>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-btn" id="menuBtn" aria-label="Open menu"><i class="fas fa-bars"></i></button>
                <div class="breadcrumb">Dashboard / <strong>Overview</strong></div>
            </div>

            <div class="top-actions">
                <button class="icon-btn" title="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="notification-dot"></span>
                </button>

                <div class="profile">
                    <div class="avatar">VV</div>
                    <div class="profile-text">
                        <strong>Villa Valore</strong>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
        </header>

        <section class="content">

            <div class="welcome">
                <div>
                    <div class="eyebrow">Hotel Overview</div>
                    <h1>Good morning, welcome back.</h1>
                    <p>Here's what's happening at Villa Valore today.</p>
                </div>
                <div class="date-pill">
                    <i class="far fa-calendar"></i>
                    <?php echo date('F d, Y'); ?>
                </div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-calendar-plus"></i></div>
                    </div>
                    <div class="stat-label">New Bookings</div>
                    <div class="stat-value"><?php echo $stats['new_bookings']; ?></div>
                    <div class="stat-meta">Last 24 hours</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-right-to-bracket"></i></div>
                    </div>
                    <div class="stat-label">Check-ins</div>
                    <div class="stat-value"><?php echo $stats['check_ins']; ?></div>
                    <div class="stat-meta">Scheduled today</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-right-from-bracket"></i></div>
                    </div>
                    <div class="stat-label">Check-outs</div>
                    <div class="stat-value"><?php echo $stats['check_outs']; ?></div>
                    <div class="stat-meta">Scheduled today</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="fas fa-bed"></i></div>
                    </div>
                    <div class="stat-label">Available Rooms</div>
                    <div class="stat-value"><?php echo $stats['available_rooms']; ?></div>
                    <div class="stat-meta">Ready to book</div>
                </div>
            </div>

            <div class="content-grid">

                <div>
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <div class="panel-title">Booking Schedule</div>
                                <div class="panel-subtitle">Monthly reservation activity</div>
                            </div>

                            <div class="month-nav">
                                <button class="nav-btn prev-month" aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>
                                <span class="month-label current-month"><?php echo date('F Y'); ?></span>
                                <button class="nav-btn next-month" aria-label="Next month"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>

                        <div class="calendar-header">
                            <div>MON</div><div>TUE</div><div>WED</div><div>THU</div>
                            <div>FRI</div><div>SAT</div><div>SUN</div>
                        </div>

                        <div class="calendar-grid">
                            <?php
                            $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                            $daysInMonth = date('t', $firstDay);
                            $startDay = date('N', $firstDay);
                            $currentDate = date('j');

                            for ($i = 1; $i < $startDay; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }

                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $classes = ['calendar-day'];

                                if ($day == $currentDate && $currentMonth == date('n') && $currentYear == date('Y')) {
                                    $classes[] = 'current-day';
                                }

                                if (isset($bookingSchedule[$day]) && $bookingSchedule[$day] > 0) {
                                    $classes[] = 'has-bookings';
                                }

                                echo '<div class="' . implode(' ', $classes) . '">';
                                echo (int)$day;

                                if (isset($bookingSchedule[$day])) {
                                    echo '<span class="booking-count">' . (int)$bookingSchedule[$day] . '</span>';
                                }

                                echo '</div>';
                            }
                            ?>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <div class="panel-title">Recent Bookings</div>
                                <div class="panel-subtitle">Latest reservation activity</div>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Room No.</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                </tr>
                                </thead>

                                <tbody>
                                <?php if (!empty($recentBookings)): ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <?php
                                            $guestName = $booking['guest_name'] ?? 'Guest';
                                            $initials = '';
                                            foreach (preg_split('/\s+/', trim($guestName)) as $part) {
                                                if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            $initials = substr($initials ?: 'G', 0, 2);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="guest">
                                                    <div class="guest-avatar"><?php echo htmlspecialchars($initials); ?></div>
                                                    <?php echo htmlspecialchars($guestName); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="room-badge">
                                                    <?php echo htmlspecialchars($booking['room_type'] ?? 'Standard'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['room_number'] ?? '—'); ?></td>
                                            <td><?php echo !empty($booking['check_in_date']) ? date('M d, Y', strtotime($booking['check_in_date'])) : '—'; ?></td>
                                            <td><?php echo !empty($booking['check_out_date']) ? date('M d, Y', strtotime($booking['check_out_date'])) : '—'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5"><div class="empty-state">No recent bookings found.</div></td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <aside>
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <div class="panel-title">Reservation Overview</div>
                                <div class="panel-subtitle">Current property performance</div>
                            </div>
                        </div>

                        <div class="metric">
                            <div class="metric-row">
                                <div>
                                    <div class="metric-label">Total Reservations</div>
                                    <div class="metric-value"><?php echo $stats['total_reservations']; ?></div>
                                </div>
                                <div class="metric-icon"><i class="fas fa-calendar-check"></i></div>
                            </div>
                        </div>

                        <div class="metric">
                            <div class="metric-row">
                                <div>
                                    <div class="metric-label">Average Stay</div>
                                    <div class="metric-value"><?php echo $stats['average_stay']; ?> <small style="font-size:11px;color:var(--muted);">days</small></div>
                                </div>
                                <div class="metric-icon"><i class="fas fa-moon"></i></div>
                            </div>
                        </div>

                        <div class="metric">
                            <div class="metric-row">
                                <div>
                                    <div class="metric-label">Occupancy Rate</div>
                                    <div class="metric-value"><?php echo $stats['occupancy_rate']; ?>%</div>
                                </div>
                                <div class="metric-icon"><i class="fas fa-chart-pie"></i></div>
                            </div>
                            <div class="progress"><span></span></div>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <div class="panel-title">Housekeeping</div>
                                <div class="panel-subtitle">Room operations status</div>
                            </div>
                        </div>

                        <div class="housekeeping-list">
                            <div class="housekeeping-item">
                                <div class="housekeeping-icon"><i class="fas fa-broom"></i></div>
                                <div>
                                    <strong><?php echo $stats['rooms_to_clean']; ?> rooms</strong>
                                    <span>Need cleaning</span>
                                </div>
                            </div>

                            <div class="housekeeping-item">
                                <div class="housekeeping-icon"><i class="fas fa-circle-check"></i></div>
                                <div>
                                    <strong><?php echo $stats['rooms_cleaned']; ?> rooms</strong>
                                    <span>Clean and ready</span>
                                </div>
                            </div>

                            <div class="housekeeping-item">
                                <div class="housekeeping-icon"><i class="fas fa-screwdriver-wrench"></i></div>
                                <div>
                                    <strong><?php echo $stats['maintenance_required']; ?> rooms</strong>
                                    <span>Maintenance required</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>

            </div>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.getElementById('menuBtn');
    const overlay = document.getElementById('overlay');

    if (menuBtn) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }

    overlay.addEventListener('click', function () {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    const prevMonthBtn = document.querySelector('.prev-month');
    const nextMonthBtn = document.querySelector('.next-month');
    const currentMonthSpan = document.querySelector('.current-month');

    let currentDate = new Date();

    function updateCalendar(year, month) {
        fetch(`?ajax_calendar=1&year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                const calendarGrid = document.querySelector('.calendar-grid');
                if (calendarGrid) calendarGrid.innerHTML = data.calendarHtml;
                if (currentMonthSpan) currentMonthSpan.textContent = data.monthDisplay;
            })
            .catch(error => console.error('Calendar update failed:', error));
    }

    prevMonthBtn.addEventListener('click', function (e) {
        e.preventDefault();
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendar(currentDate.getFullYear(), currentDate.getMonth() + 1);
    });

    nextMonthBtn.addEventListener('click', function (e) {
        e.preventDefault();
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendar(currentDate.getFullYear(), currentDate.getMonth() + 1);
    });
});
</script>

</body>
</html>
