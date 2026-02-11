<?php
session_start();
require_once 'config.php';
require_once '../mailer/mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        handleRegister($conn);
    } elseif ($action === 'login') {
        handleLogin($conn);
    } elseif ($action === 'verify_otp') {
        handleVerifyOTP($conn);
    } elseif ($action === 'resend_otp') {
        handleResendOTP($conn);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleRegister($conn) {
    // Sanitize and validate input
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstname) || empty($lastname) || empty($student_id) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        return;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        return;
    }
    
    // Check if email already exists
$stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $existingUser = $result->fetch_assoc();
    if ($existingUser['is_verified'] == 0) {
        // User exists but isn't verified. Generate new OTP and send it.
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $updateStmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $otp, $otp_expiry, $existingUser['id']);

        if ($updateStmt->execute()) {
            $emailSent = sendOTPEmail($email, $firstname, $otp);
            if ($emailSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'A new OTP has been sent to your email. Please enter the OTP.',
                    'email' => $email,
                    'show_otp_form' => true
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'A new OTP has been generated. However, there was an issue sending the email. Please contact support.',
                    'email' => $email,
                    'show_otp_form' => true
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate new OTP. Please try again.']);
        }
        $updateStmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Email already registered and verified.']);
    }
    $stmt->close();
    return;
}
$stmt->close();
    
    // Check if student ID already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID already registered']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set OTP expiry to 10 minutes from now
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, student_id, email, password, is_verified, otp, otp_expiry) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
    $stmt->bind_param("sssssss", $firstname, $lastname, $student_id, $email, $hashed_password, $otp, $otp_expiry);
    
    if ($stmt->execute()) {
        // Send OTP email
        $emailSent = sendOTPEmail($email, $firstname, $otp);
        
        if ($emailSent) {
            echo json_encode([
                'success' => true, 
                'message' => 'Registration successful! Please check your email for the OTP code.',
                'email' => $email,
                'show_otp_form' => true
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'Registration successful! However, there was an issue sending the OTP email. Please contact support.',
                'email' => $email,
                'show_otp_form' => true
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    
    $stmt->close();
}

function handleLogin($conn) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, firstname, lastname, student_id, email, password, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        return;
    }
    
    // Check if email is verified
    if ($user['is_verified'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Please verify your email address before logging in. Check your inbox for the OTP code.']);
        return;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['lastname'] = $user['lastname'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_verified'] = $user['is_verified'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful! Redirecting...'
    ]);
}

function handleVerifyOTP($conn) {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    
    // Validation
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        return;
    }
    
    if (!preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
        return;
    }
    
    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, otp, otp_expiry, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if already verified
    if ($user['is_verified'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Email already verified. Please login.']);
        return;
    }
    
    // Check if OTP has expired
    $current_time = date('Y-m-d H:i:s');
    if ($current_time > $user['otp_expiry']) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        return;
    }
    
    // Verify OTP
    if ($otp !== $user['otp']) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP code']);
        return;
    }
    
    // Update user as verified
    $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Email verified successfully! You can now login.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
    }
    
    $updateStmt->close();
}

function handleResendOTP($conn) {
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, firstname, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if already verified
    if ($user['is_verified'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Email already verified. Please login.']);
        return;
    }
    
    // Generate new OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set new OTP expiry to 10 minutes from now
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update OTP in database
    $updateStmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $otp, $otp_expiry, $user['id']);
    
    if ($updateStmt->execute()) {
        // Send new OTP email
        $emailSent = sendOTPEmail($email, $user['firstname'], $otp);
        
        if ($emailSent) {
            echo json_encode([
                'success' => true, 
                'message' => 'New OTP has been sent to your email.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to send OTP email. Please try again.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate new OTP. Please try again.']);
    }
    
    $updateStmt->close();
}
?>