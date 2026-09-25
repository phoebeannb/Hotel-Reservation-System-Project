    <?php include 'connections.php';
    session_start();

    // ✅ Save all booking parameters from URL to session if they exist
    if (isset($_GET['room'])) {
        $room = strtolower(trim($_GET['room']));
        $valid_rooms = ['standard', 'deluxe', 'suite'];
        if (in_array($room, $valid_rooms)) {
            $_SESSION['selected_room_type'] = $room;
        }
    }

    // Save check-in and check-out dates
    if (isset($_GET['checkin'])) {
        $_SESSION['checkin_date'] = $_GET['checkin'];
    }
    if (isset($_GET['checkout'])) {
        $_SESSION['checkout_date'] = $_GET['checkout'];
    }

    // Save check-in and check-out times
    if (isset($_GET['checkin_time'])) {
        $_SESSION['checkin_time'] = $_GET['checkin_time'];
    }
    if (isset($_GET['checkout_time'])) {
        $_SESSION['checkout_time'] = $_GET['checkout_time'];
    }

    // Save price and reservation fee
    if (isset($_GET['price'])) {
        $_SESSION['total_price'] = $_GET['price'];
    }
    if (isset($_GET['rf'])) {
        $_SESSION['reservation_fee'] = $_GET['rf'];
    }

    // Save duration
    if (isset($_GET['duration'])) {
        $_SESSION['duration'] = $_GET['duration'];
    }

    // Save guest counts
    if (isset($_GET['adults'])) {
        $_SESSION['adults'] = $_GET['adults'];
    }
    if (isset($_GET['children'])) {
        $_SESSION['children'] = $_GET['children'];
    }

    // ✅ Optional: Save next page (for redirect after login)
    if (isset($_GET['next'])) {
        $_SESSION['next_page'] = $_GET['next'];
    }
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM student WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['Password'])) {
                $_SESSION['email'] = $email;
                $_SESSION['student_id'] = $row['StudentID'];
                $_SESSION['first_name'] = $row['FirstName'];
                $_SESSION['last_name'] = $row['LastName'];
                
                // After successful login
                if (isset($_SESSION['next_page'])) {
                    $next = $_SESSION['next_page'];
                    $room = $_SESSION['selected_room_type'] ?? '';
                    unset($_SESSION['next_page']);
                    
                    // Build query string with all saved parameters
                    $query_params = [];
                    if ($room) $query_params['room'] = $room;
                    if (isset($_SESSION['checkin_date'])) $query_params['checkin'] = $_SESSION['checkin_date'];
                    if (isset($_SESSION['checkout_date'])) $query_params['checkout'] = $_SESSION['checkout_date'];
                    if (isset($_SESSION['checkin_time'])) $query_params['checkin_time'] = $_SESSION['checkin_time'];
                    if (isset($_SESSION['checkout_time'])) $query_params['checkout_time'] = $_SESSION['checkout_time'];
                    if (isset($_SESSION['total_price'])) $query_params['price'] = $_SESSION['total_price'];
                    if (isset($_SESSION['reservation_fee'])) $query_params['rf'] = $_SESSION['reservation_fee'];
                    if (isset($_SESSION['duration'])) $query_params['duration'] = $_SESSION['duration'];
                    if (isset($_SESSION['adults'])) $query_params['adults'] = $_SESSION['adults'];
                    if (isset($_SESSION['children'])) $query_params['children'] = $_SESSION['children'];
                    
                    $query_string = http_build_query($query_params);
                    $redirect_url = $query_string ? "$next?$query_string" : $next;
                    
                    header("Location: $redirect_url");
                    exit();
                } else {
                    // Check for booking or payment history
                    $student_id = $_SESSION['student_id'];
                    $has_booking = false;
                    $has_payment = false;
                    $booking_res = $conn->query("SELECT 1 FROM booking WHERE StudentID = '" . $conn->real_escape_string($student_id) . "' LIMIT 1");
                    if ($booking_res && $booking_res->num_rows > 0) $has_booking = true;
                    $payment_res = $conn->query("SELECT 1 FROM payment WHERE BookingID IN (SELECT BookingID FROM booking WHERE StudentID = '" . $conn->real_escape_string($student_id) . "') LIMIT 1");
                    if ($payment_res && $payment_res->num_rows > 0) $has_payment = true;
                    $booking_res && $booking_res->close();
                    $payment_res && $payment_res->close();
                    if ($has_booking || $has_payment) {
                        header("Location: mybookings.php");
                        exit();
                    } else {
                        header("Location: booknow.php");
                        exit();
                    }
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }

            ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Hotel Login | Villa Valore Hotel</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            :root {
                --bg: #f3efe8;
                --panel: rgba(255,255,255,0.88);
                --olive: #2c4c3a;
                --olive-deep: #1f352d;
                --gold: #d6b26e;
                --text: #1d2a24;
                --muted: #6d726f;
                --line: rgba(38, 52, 46, 0.12);
                --shadow: 0 24px 60px rgba(17, 26, 22, 0.18);
            }

            * { box-sizing: border-box; }

            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle at top left, rgba(214,178,110,0.18), transparent 30%), linear-gradient(135deg, #f7f1e8 0%, #ece5d8 45%, #f4efe9 100%);
                color: var(--text);
                overflow: hidden;
            }

            .main-wrapper {
                display: flex;
                align-items: stretch;
                justify-content: center;
                width: min(1200px, 94vw);
                min-height: 680px;
                border-radius: 28px;
                overflow: hidden;
                background: rgba(255,255,255,0.4);
                backdrop-filter: blur(6px);
                box-shadow: var(--shadow);
                border: 1px solid rgba(255,255,255,0.5);
            }

            .left-portrait {
                position: relative;
                flex: 1.1;
                min-width: 420px;
                background: url('images/samplebedroom.png') center center/cover no-repeat;
                display: flex;
                align-items: flex-end;
                justify-content: flex-start;
            }

            .left-portrait::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(135deg, rgba(19,31,28,0.72), rgba(19,31,28,0.38), rgba(19,31,28,0.18));
            }

            .left-content {
                position: relative;
                z-index: 1;
                width: 100%;
                padding: 48px 42px 38px;
            }

            .logo {
                width: 96px;
                height: 96px;
                object-fit: contain;
                border-radius: 24px;
                background: rgba(255,255,255,0.12);
                backdrop-filter: blur(8px);
                padding: 10px;
                border: 1px solid rgba(255,255,255,0.24);
                box-shadow: 0 16px 28px rgba(13,18,16,0.18);
                margin-bottom: 22px;
            }

            .hotel-name {
                color: #fff;
                font-size: clamp(2rem, 2.3vw, 2.9rem);
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .tagline {
                margin-top: 18px;
                max-width: 360px;
                color: rgba(255,255,255,0.88);
                font-size: 1.15rem;
                line-height: 1.6;
                letter-spacing: 0.02em;
            }

            .login-container {
                flex: 0.9;
                background: rgba(255,255,255,0.76);
                backdrop-filter: blur(8px);
                padding: 38px 42px 28px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                border-left: 1px solid var(--line);
            }

            .login-container h1 {
                display: none;
            }

            .tab-nav {
                display: flex;
                border-radius: 14px;
                background: rgba(44,76,58,0.06);
                padding: 5px;
                gap: 8px;
                margin-bottom: 28px;
            }

            .tab-btn {
                flex: 1;
                border: none;
                background: transparent;
                color: var(--muted);
                font-size: 0.98rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                padding: 14px 12px;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .tab-btn.active,
            .tab-btn:focus {
                background: var(--olive);
                color: #fff;
                box-shadow: 0 10px 22px rgba(44,76,58,0.24);
                outline: none;
            }

            .tab-btn:not(.active):hover {
                background: rgba(44,76,58,0.08);
                color: var(--olive);
            }

            form {
                width: 100%;
            }

            .form-group {
                position: relative;
                margin-bottom: 22px;
            }

            .form-group label {
                display: block;
                font-size: 0.86rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                color: var(--olive-deep);
                margin-bottom: 10px;
                text-transform: uppercase;
            }

            .form-group .input-icon {
                position: absolute;
                left: 18px;
                top: 47px;
                width: 20px;
                height: 20px;
                fill: #8a8a8a;
                transition: fill 0.2s ease;
                z-index: 1;
                pointer-events: none;
            }

            .form-group input[type='email'],
            .form-group input[type='password'] {
                width: 100%;
                background: rgba(255,255,255,0.82);
                border: 1.5px solid rgba(42,58,52,0.14);
                border-radius: 14px;
                padding: 15px 16px 15px 48px;
                font-size: 1rem;
                color: var(--text);
                transition: all 0.2s ease;
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.4);
            }

            .form-group input[type='email']::placeholder,
            .form-group input[type='password']::placeholder {
                color: rgba(29,42,36,0.48);
            }

            .form-group input:focus {
                border-color: rgba(44,76,58,0.6);
                box-shadow: 0 0 0 4px rgba(214,178,110,0.2);
                outline: none;
            }

            .form-group input:focus ~ .input-icon {
                fill: var(--olive);
            }

            .form-group:nth-child(2) {
                margin-bottom: 18px;
            }

            .form-group input[type='checkbox'] {
                width: 16px;
                height: 16px;
                accent-color: var(--olive);
                margin-right: 8px;
                transform: translateY(1px);
            }

            .form-group label[for='agreePolicy'] {
                display: inline-flex;
                align-items: flex-start;
                font-size: 0.9rem;
                letter-spacing: 0.02em;
                text-transform: none;
                line-height: 1.5;
                color: var(--muted);
                font-weight: 500;
                margin-bottom: 0;
                cursor: pointer;
            }

            .form-group label[for='agreePolicy'] a {
                color: var(--olive);
                text-decoration: underline;
                text-underline-offset: 2px;
                font-weight: 600;
            }

            .login-btn {
                width: 100%;
                border: none;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--olive) 0%, #3d5d4c 100%);
                color: #fff;
                font-size: 1rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                padding: 16px 22px;
                cursor: pointer;
                box-shadow: 0 18px 30px rgba(44,76,58,0.22);
                transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            }

            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 22px 34px rgba(44,76,58,0.28);
                filter: brightness(1.02);
            }

            .login-btn:active {
                transform: translateY(0);
            }

            .error {
                margin-top: 18px;
                background: rgba(181, 32, 32, 0.08);
                border: 1px solid rgba(181,32,32,0.18);
                color: #9d1f1f;
                border-radius: 12px;
                padding: 12px 14px;
                font-size: 0.96rem;
                line-height: 1.5;
            }

            .links {
                margin-top: 22px;
                display: flex;
                justify-content: center;
            }

            .links a {
                color: var(--olive);
                text-decoration: none;
                font-size: 0.9rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                transition: color 0.18s ease;
            }

            .links a:hover {
                color: var(--gold);
                text-decoration: underline;
            }

            #policyModal.modal {
                position: fixed;
                inset: 0;
                background: rgba(12, 18, 16, 0.52);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }

            #policyModal .modal-content {
                background: #fffdfb;
                border-radius: 20px;
                max-width: 720px;
                width: min(90vw, 720px);
                max-height: 88vh;
                overflow-y: auto;
                padding: 2.2rem 2.4rem 1.8rem;
                box-shadow: 0 20px 40px rgba(11,18,15,0.18);
                position: relative;
            }

            #policyModal .close-btn {
                position: absolute;
                top: 12px;
                right: 16px;
                border: none;
                background: transparent;
                font-size: 2rem;
                color: var(--muted);
                cursor: pointer;
            }

            #policyModal .close-btn:hover {
                color: var(--olive);
            }

            #policyModal h2,
            #policyModal h3 {
                color: var(--olive-deep);
            }

            #policyModal ul {
                padding-left: 1.2rem;
                line-height: 1.7;
                color: var(--text);
            }

            @media (max-width: 960px) {
                body {
                    overflow: auto;
                    padding: 24px 0;
                }

                .main-wrapper {
                    flex-direction: column;
                    min-height: unset;
                }

                .left-portrait {
                    min-width: unset;
                    min-height: 240px;
                }

                .login-container {
                    border-left: none;
                    border-top: 1px solid var(--line);
                }
            }

            @media (max-width: 560px) {
                .left-content,
                .login-container {
                    padding-left: 20px;
                    padding-right: 20px;
                }

                .hotel-name {
                    font-size: 1.6rem;
                }

                .tagline {
                    font-size: 1rem;
                }

                .tab-btn {
                    letter-spacing: 0.05em;
                    font-size: 0.8rem;
                }

                .form-group label[for='agreePolicy'] {
                    font-size: 0.8rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="main-wrapper">
            <div class="left-portrait">
                <div class="left-content">
                    <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="logo">
                    <div class="hotel-name">Villa Valore Hotel</div>
                    <div class="tagline">Where Every Stay Feels Like Coming Home.</div>
                </div>
            </div>
            <div class="login-container">
                <div class="tab-nav">
                    <button class="tab-btn active" id="signInTab" type="button">Sign In</button>
                    <button class="tab-btn" id="signUpTab" type="button" onclick="window.location.href='register.php'">Sign Up</button>
                </div>
                <h1>Villa Valore Hotel</h1>
                <form method="POST" action="login.php" autocomplete="off" id="signInForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required autofocus>
                        <svg class="input-icon" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <svg class="input-icon" viewBox="0 0 24 24">
                            <path d="M12 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm6-7V7a6 6 0 0 0-12 0v3a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2zm-8-3a4 4 0 0 1 8 0v3H6V7z"/>
                        </svg>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="agreePolicy" required>
                        <label for="agreePolicy">
                            I agree to the <a href="#" onclick="openPolicyModal();return false;">Terms, Privacy, and Hotel Policy</a>.
                        </label>
                    </div>
                    <button type="submit" name="login" class="login-btn">SIGN IN</button>
                    <?php if (!empty($error)): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                </form>
                <div class="links">
                    <a href="forgot_password.php" class="forgot-link">FORGOT PASSWORD</a>
                </div>
            </div>
        </div>
        <script>
            // Tab navigation logic
            document.getElementById('signInTab').addEventListener('click', function() {
                this.classList.add('active');
                document.getElementById('signUpTab').classList.remove('active');
                document.getElementById('signInForm').style.display = 'block';
            });
            document.getElementById('signUpTab').addEventListener('click', function() {
                window.location.href = 'register.php';
            });

        </script>
        <!-- Combined Policy Modal -->
        <div id="policyModal" class="modal">
            <div class="modal-content">
                <button class="close-btn" onclick="closePolicyModal()">&times;</button>
                <h2>Terms, Privacy, and Hotel Policy</h2>
                <h3>Terms and Conditions</h3>
                <ul>
                    <li>No cancellation of the Reservation and Booking.</li>
                    <li>By using this site, you agree to abide by all hotel rules and policies.</li>
                </ul>
                <h3>Privacy Policy</h3>
                <ul>
                    <li>Your information is kept confidential and used only for reservation and communication purposes.</li>
                    <li>We do not share your data with third parties except as required by law.</li>
                </ul>
                <h3>Hotel Policy</h3>
                <ul>
                    <li>Check-in and check-out times must be followed.</li>
                    <li>Guests must present valid identification upon check-in.</li>
                    <li>Respect hotel property and staff at all times.</li>
                    <li>No cancellation of the Reservation and Booking.</li>
                </ul>
            </div>
        </div>
        <script>
        function openPolicyModal() {
            document.getElementById('policyModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closePolicyModal() {
            document.getElementById('policyModal').style.display = 'none';
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePolicyModal();
        });
        document.getElementById('policyModal').addEventListener('click', function(e) {
            if (e.target === this) closePolicyModal();
        });
        </script>
    </body>
    </html>