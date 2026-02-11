<?php
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['is_verified']) && $_SESSION['is_verified'] == 1) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISPSC Queue - Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-container {
            max-width: 450px;
            margin: 2rem auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        .nav-pills .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
        }
        .nav-pills .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 10px;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .password-toggle {
            cursor: pointer;
            background-color: #f8f9fa;
        }

        /* Block phones */
        @media (max-width: 767px) {
            body {
                background: #333;
                color: white;
            }
            .container {
                display: none;
            }
            .phone-message {
                display: block;
                font-size: 1.5rem;
                padding: 2rem;
                text-align: center;
            }
        }
        .phone-message {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-pills nav-justified mb-0" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register" type="button" role="tab">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="authTabsContent">
                        <!-- Login Form -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <h4 class="mb-4 text-center">Welcome Back!</h4>
                            <div id="loginMessage"></div>
                            <form id="loginForm" method="POST">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="loginEmail" name="email" required placeholder="Enter your email">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="loginPassword" name="password" required placeholder="Enter your password">
                                        <span class="input-group-text password-toggle" onclick="togglePassword('loginPassword')">
                                            <i class="fas fa-eye" id="loginPasswordIcon"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </form>
                        </div>

                        <!-- Register Form -->
                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <h4 class="mb-4 text-center">Create Account</h4>
                            <div id="registerMessage"></div>
                            <form id="registerForm" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="firstname" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" required placeholder="John">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lastname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" required placeholder="Doe">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="studentId" class="form-label">Student ID</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" id="studentId" name="student_id" required placeholder="e.g., 2024-00001">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="registerEmail" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="registerEmail" name="email" required placeholder="student@example.com">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="registerPassword" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="registerPassword" name="password" required placeholder="Min. 8 characters">
                                        <span class="input-group-text password-toggle" onclick="togglePassword('registerPassword')">
                                            <i class="fas fa-eye" id="registerPasswordIcon"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required placeholder="Re-enter password">
                                        <span class="input-group-text password-toggle" onclick="togglePassword('confirmPassword')">
                                            <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the Terms and Conditions
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>Verify Your Email
                    </h5>
                </div>
                <div class="modal-body p-4">
                    <div id="otpMessage"></div>
                    
                    <p class="text-center mb-3">
                        We've sent a 6-digit OTP code to<br>
                        <strong id="otpEmailDisplay"></strong>
                    </p>
                    
                    <form id="otpForm">
                        <input type="hidden" id="otpEmail" name="email">
                        <div class="mb-3">
                            <label for="otpCode" class="form-label text-center w-100">Enter OTP Code</label>
                            <div class="d-flex justify-content-center gap-2" id="otpInputs">
                                <input type="text" class="form-control text-center otp-input" maxlength="1" data-index="0" style="width: 50px; height: 50px; font-size: 24px; font-weight: bold;">
                                <input type="text" class="form-control text-center otp-input" maxlength="1" data-index="1" style="width: 50px; height: 50px; font-size: 24px; font-weight: bold;">
                                <input type="text" class="form-control text-center otp-input" maxlength="1" data-index="2" style="width: 50px; height: 50px; font-size: 24px; font-weight: bold;">
                                <input type="text" class="form-control text-center otp-input" maxlength="1" data-index="3" style="width: 50px; height: 50px; font-size: 24px; font-weight: bold;">
                                <input type="text" class="form-control text-center otp-input" maxlength="1" data-index="4" style="width: 50px; height: 50px; font-size: 24px; font-weight: bold;">
                                <input type="text" class="form-control text-center otp-input" maxlength="1" data-index="5" style="width: 50px; height: 50px; font-size: 24px; font-weight: bold;">
                            </div>
                        </div>
                        
                        <div class="text-center mb-3">
                            <div class="timer-container p-3 bg-light rounded">
                                <i class="fas fa-clock me-2"></i>
                                <span id="otpTimer" class="fw-bold text-danger">10:00</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="verifyOtpBtn">
                            <i class="fas fa-check-circle me-2"></i>Verify OTP
                        </button>
                        
                        <button type="button" class="btn btn-outline-secondary w-100" id="resendOtpBtn" disabled>
                            <i class="fas fa-redo me-2"></i>Resend OTP <span id="resendTimer"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let otpTimer;
        let resendTimer;
        let timeLeft = 600; // 10 minutes in seconds
        let resendTimeLeft = 60; // 1 minute before resend is enabled
        let otpModal;

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + 'Icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // OTP Input Auto-focus and Navigation
        document.addEventListener('DOMContentLoaded', function() {
            otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
            
            const otpInputs = document.querySelectorAll('.otp-input');
            
            otpInputs.forEach((input, index) => {
                // Only allow numbers
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    if (this.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                });
                
                // Handle backspace
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
                
                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                    
                    for (let i = 0; i < pastedData.length && index + i < otpInputs.length; i++) {
                        otpInputs[index + i].value = pastedData[i];
                    }
                    
                    if (index + pastedData.length < otpInputs.length) {
                        otpInputs[index + pastedData.length].focus();
                    }
                });
            });
        });

        // Start OTP Timer
        function startOTPTimer() {
            timeLeft = 600; // Reset to 10 minutes
            resendTimeLeft = 60; // Reset to 1 minute
            
            document.getElementById('resendOtpBtn').disabled = true;
            
            // Clear any existing timers
            if (otpTimer) clearInterval(otpTimer);
            if (resendTimer) clearInterval(resendTimer);
            
            // OTP expiry timer
            otpTimer = setInterval(() => {
                timeLeft--;
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('otpTimer').textContent = 
                    `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(otpTimer);
                    document.getElementById('otpTimer').textContent = 'Expired';
                    document.getElementById('verifyOtpBtn').disabled = true;
                    showOTPMessage('OTP has expired. Please request a new one.', 'danger');
                }
            }, 1000);
            
            // Resend button timer
            resendTimer = setInterval(() => {
                resendTimeLeft--;
                
                if (resendTimeLeft > 0) {
                    document.getElementById('resendTimer').textContent = `(${resendTimeLeft}s)`;
                } else {
                    clearInterval(resendTimer);
                    document.getElementById('resendOtpBtn').disabled = false;
                    document.getElementById('resendTimer').textContent = '';
                }
            }, 1000);
        }

        // Show OTP Message
        function showOTPMessage(message, type) {
            const messageDiv = document.getElementById('otpMessage');
            messageDiv.innerHTML = `<div class="alert alert-${type}"><i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}</div>`;
        }

        // Clear OTP Inputs
        function clearOTPInputs() {
            document.querySelectorAll('.otp-input').forEach(input => {
                input.value = '';
            });
            document.querySelector('.otp-input').focus();
        }

        // Login Form Handler
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'login');
            
            fetch('../../backend/configure/auth_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('loginMessage');
                
                if (data.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>${data.message}</div>`;
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1500);
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loginMessage').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.</div>';
            });
        });

        // Register Form Handler
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                document.getElementById('registerMessage').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Passwords do not match!</div>';
                return;
            }
            
            if (password.length < 8) {
                document.getElementById('registerMessage').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Password must be at least 8 characters long!</div>';
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'register');
            
            fetch('../../backend/configure/auth_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('registerMessage');
                
                if (data.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>${data.message}</div>`;
                    
                    if (data.show_otp_form) {
                        // Show OTP modal
                        document.getElementById('otpEmail').value = data.email;
                        document.getElementById('otpEmailDisplay').textContent = data.email;
                        clearOTPInputs();
                        document.getElementById('otpMessage').innerHTML = '';
                        document.getElementById('verifyOtpBtn').disabled = false;
                        otpModal.show();
                        startOTPTimer();
                    }
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('registerMessage').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.</div>';
            });
        });

        // OTP Verification Form Handler
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect OTP from individual inputs
            const otpInputs = document.querySelectorAll('.otp-input');
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            
            if (otp.length !== 6) {
                showOTPMessage('Please enter the complete 6-digit OTP code.', 'danger');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('email', document.getElementById('otpEmail').value);
            formData.append('otp', otp);
            
            fetch('../../backend/configure/auth_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showOTPMessage(data.message, 'success');
                    clearInterval(otpTimer);
                    clearInterval(resendTimer);
                    
                    setTimeout(() => {
                        otpModal.hide();
                        document.getElementById('login-tab').click();
                        document.getElementById('loginMessage').innerHTML = 
                            '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Your email has been verified! Please login.</div>';
                    }, 2000);
                } else {
                    showOTPMessage(data.message, 'danger');
                    clearOTPInputs();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showOTPMessage('An error occurred. Please try again.', 'danger');
            });
        });

        // Resend OTP Handler
        document.getElementById('resendOtpBtn').addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'resend_otp');
            formData.append('email', document.getElementById('otpEmail').value);
            
            this.disabled = true;
            
            fetch('../../backend/configure/auth_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showOTPMessage(data.message, 'success');
                    clearOTPInputs();
                    startOTPTimer();
                    document.getElementById('verifyOtpBtn').disabled = false;
                } else {
                    showOTPMessage(data.message, 'danger');
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showOTPMessage('An error occurred. Please try again.', 'danger');
                this.disabled = false;
            });
        });
    </script>
</body>
</html>