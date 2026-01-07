<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | Bayan ng Paluan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-[#1a0933] flex items-center justify-center min-h-screen p-4">
    <!-- Main Container -->
    <div class="flex w-full max-w-6xl min-h-[600px] rounded-2xl overflow-hidden shadow-2xl bg-white">

        <!-- LEFT SIDE - Branding Section -->
        <div class="hidden md:flex w-1/2 items-center justify-center relative bg-gradient-to-br from-purple-900/90 to-blue-800/90">
            <div class="text-center text-white z-10 px-8">
                <img src="img/paluan.png" alt="Seal of Paluan"
                    class="mx-auto w-64 h-64 object-contain mb-6 drop-shadow-2xl opacity-80" />
                <h1 class="text-4xl font-bold mb-4">Bayan ng Paluan</h1>
                <h2 class="text-xl font-semibold mb-2 opacity-90">MSWD System</h2>
                <p class="text-lg opacity-80">Senior Citizens Information Management</p>

                <!-- Feature Highlights -->
                <!-- <div class="mt-8 grid grid-cols-2 gap-4 text-left">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-shield-alt text-blue-300"></i>
                        <span class="text-sm">Secure Access</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-database text-green-300"></i>
                        <span class="text-sm">Data Management</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-chart-bar text-yellow-300"></i>
                        <span class="text-sm">Analytics</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-users text-purple-300"></i>
                        <span class="text-sm">User Management</span>
                    </div>
                </div> -->
            </div>

            <!-- Animated Background Elements -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -left-20 w-40 h-40 bg-white/5 rounded-full blur-xl"></div>
                <div class="absolute -bottom-20 -right-20 w-40 h-40 bg-purple-300/10 rounded-full blur-xl"></div>
            </div>
        </div>

        <!-- RIGHT SIDE - Login Forms -->
        <div class="flex-1 flex items-center justify-center p-8">

            <!-- User Type Selection -->
            <div id="selectUser" class="w-full max-w-sm transition-all duration-300">
                <div class="text-center mb-8">
                    <img src="img/MSWD_LOGO-removebg-preview.png" alt="MSWD Logo"
                        class="w-20 h-20 mx-auto mb-4 drop-shadow-md">
                    <h2 class="text-3xl font-bold text-purple-800 mb-2">Welcome</h2>
                    <p class="text-gray-600">Select your account type to continue</p>
                </div>

                <form class="space-y-6">
                    <div class="relative">
                        <select id="usertype"
                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white appearance-none cursor-pointer shadow-sm">
                            <option value="">- Select type of user to log-in -</option>
                            <option value="Admin">Administrator</option>
                            <option value="Staff">Staff Member</option>
                        </select>
                        <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-purple-600">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" onclick="handleUserTypeSelection()"
                            class="bg-gradient-to-r from-purple-700 to-blue-500 hover:from-purple-800 hover:to-blue-600 text-white py-3 px-8 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl flex items-center space-x-2">
                            <span>Continue</span>
                            <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select your role to access the appropriate system features
                        </p>
                    </div>
                </div>
            </div>

            <!-- Admin Login Form -->
            <div id="adminlogin" class="hidden w-full max-w-sm transition-all duration-300">
                <div class="bg-gray-100 rounded-2xl shadow-2xl p-8 w-full">
                    <div class="flex flex-col items-center mb-6">
                        <button onclick="goBackToSelection()"
                            class="self-start w-10 h-10 bg-white hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors duration-200 text-gray-600 hover:text-gray-800 shadow-sm mb-4">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <img src="img/MSWD_LOGO-removebg-preview.png" alt="MSWD Logo"
                            class="w-16 h-16 mb-3 drop-shadow-md">
                        <h2 class="text-2xl font-bold text-purple-800">Admin Login</h2>
                        <p class="text-gray-600 text-sm mt-1">System Administrator Access</p>
                    </div>

                    <form class="space-y-4" id="adminForm">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user mr-1"></i>
                                Username:
                            </label>
                            <input type="text" placeholder="Enter your username"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm"
                                autocomplete="username" />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                Password:
                            </label>
                            <div class="relative">
                                <input type="password" placeholder="Enter your password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm"
                                    autocomplete="current-password" />
                                <!-- <button type="button" onclick="togglePassword(this)"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <i class="fas fa-eye"></i>
                                </button> -->
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-600">Remember me</span>
                            </label>
                            <a href="#" class="text-sm text-purple-600 hover:text-purple-700 font-medium transition-colors duration-200">
                                Forgot Password?
                            </a>
                        </div>

                        <div class="flex flex-row gap-4 pt-2">
                            <button type="button" onclick="goBackToSelection()"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-sm">
                                Back
                            </button>
                            <button type="submit"
                                class="flex-1 bg-gradient-to-r from-purple-700 to-blue-500 hover:from-purple-800 hover:to-blue-600 text-white py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg">
                                Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Staff Login Form -->
            <div id="stafflogin" class="hidden w-full max-w-sm transition-all duration-300">
                <div class="bg-gray-100 rounded-2xl shadow-2xl p-8 w-full">
                    <div class="flex flex-col items-center mb-6">
                        <button onclick="goBackToSelection()"
                            class="self-start w-10 h-10 bg-white hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors duration-200 text-gray-600 hover:text-gray-800 shadow-sm mb-4">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <img src="img/MSWD_LOGO-removebg-preview.png" alt="MSWD Logo"
                            class="w-16 h-16 mb-3 drop-shadow-md">
                        <h2 class="text-2xl font-bold text-purple-800">Staff Login</h2>
                        <p class="text-gray-600 text-sm mt-1">Staff Member Access</p>
                    </div>

                    <form class="space-y-4" id="staffForm">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user mr-1"></i>
                                Username:
                            </label>
                            <input type="text" placeholder="Enter your username"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm"
                                autocomplete="username" />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                Password:
                            </label>
                            <div class="relative">
                                <input type="password" placeholder="Enter your password"
                                    class="w-full px-4 py-3  border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm"
                                    autocomplete="current-password" />
                                <!-- <button type="button" onclick="togglePassword(this)"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <i class="fas fa-eye"></i>
                                </button> -->
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-600">Remember me</span>
                            </label>
                            <a href="#" class="text-sm text-purple-600 hover:text-purple-700 font-medium transition-colors duration-200">
                                Forgot Password?
                            </a>
                        </div>

                        <div class="flex flex-row gap-4 pt-2">
                            <button type="button" onclick="goBackToSelection()"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-sm">
                                Back
                            </button>
                            <button type="submit"
                                class="flex-1 bg-gradient-to-r from-purple-700 to-blue-500 hover:from-purple-800 hover:to-blue-600 text-white py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg">
                                Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 flex flex-col items-center space-y-3 shadow-2xl">
            <div class="w-12 h-12 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-gray-700 font-semibold">Logging in...</span>
        </div>
    </div>

    <!-- Verification Code Modal -->
    <div id="verificationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
            <div class="flex flex-col items-center mb-6">
                <img src="img/MSWD_LOGO-removebg-preview.png" alt="MSWD Logo" class="w-16 h-16 mb-4">
                <h2 class="text-2xl font-bold text-purple-800">Enter Verification Code</h2>
                <p class="text-gray-600 text-sm mt-2 text-center">
                    We've sent a 6-digit code to your email address
                </p>
            </div>

            <form id="verificationForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Verification Code:
                    </label>
                    <input type="text" id="verificationCode" placeholder="Enter 6-digit code" maxlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm text-center text-2xl font-mono tracking-widest"
                        autocomplete="one-time-code" />
                    <p class="text-xs text-gray-500 mt-2 text-center">
                        Code expires in <span id="countdown" class="font-semibold">10:00</span>
                    </p>
                </div>

                <div class="flex flex-row gap-4 pt-2">
                    <button type="button" onclick="cancelVerification()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-sm">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 bg-gradient-to-r from-purple-700 to-blue-500 hover:from-purple-800 hover:to-blue-600 text-white py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg">
                        Verify & Login
                    </button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <button type="button" onclick="resendVerificationCode()"
                    class="text-purple-600 hover:text-purple-700 font-medium transition-colors duration-200 text-sm">
                    <i class="fas fa-redo mr-1"></i>
                    Didn't receive code? Resend
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global variables for verification
        let countdownInterval;
        let verificationExpires;

        // Enhanced existing functions with improvements
        // Enhanced user type selection with role information
        function handleUserTypeSelection() {
            const userTypeSelect = document.getElementById('usertype');
            const selectedValue = userTypeSelect.value;
            const selectUserDiv = document.getElementById('selectUser');
            const adminLoginDiv = document.getElementById('adminlogin');
            const staffLoginDiv = document.getElementById('stafflogin');

            selectUserDiv.classList.add('hidden');
            adminLoginDiv.classList.add('hidden');
            staffLoginDiv.classList.add('hidden');

            if (selectedValue === 'Admin') {
                adminLoginDiv.classList.remove('hidden');
                // Update form title based on role
                const title = adminLoginDiv.querySelector('h2');
                if (title) title.textContent = 'Administrative Login';
            } else if (selectedValue === 'Staff') {
                staffLoginDiv.classList.remove('hidden');
                const title = staffLoginDiv.querySelector('h2');
                if (title) title.textContent = 'Staff Login';
            } else {
                selectUserDiv.classList.remove('hidden');
                showMessage('Please select a user type', 'warning');
                userTypeSelect.focus();
            }
        }

        // Enhanced login function with role support
        function handleLogin(userType) {
            const form = userType === 'Admin' ?
                document.querySelector('#adminlogin form') :
                document.querySelector('#stafflogin form');

            const username = form.querySelector('input[type="text"]').value;
            const password = form.querySelector('input[type="password"]').value;

            if (!username || !password) {
                showMessage('Please fill in all fields', 'error');
                if (!username) form.querySelector('input[type="text"]').classList.add('shake-animation');
                if (!password) form.querySelector('input[type="password"]').classList.add('shake-animation');

                setTimeout(() => {
                    form.querySelectorAll('.shake-animation').forEach(el => el.classList.remove('shake-animation'));
                }, 600);
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Logging in...';
            submitBtn.disabled = true;
            showLoading(true);

            fetch('php/login/login_backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        user_type: userType
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.requires_verification) {
                            showVerificationModal();
                            showMessage(data.message, 'success');
                        } else {
                            showMessage(data.message, 'success');
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1000);
                        }
                    } else {
                        showMessage(data.message, 'error');
                        form.querySelectorAll('input').forEach(input => {
                            input.classList.add('error-input');
                            setTimeout(() => input.classList.remove('error-input'), 2000);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Login failed. Please check your connection and try again.', 'error');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    showLoading(false);
                });
        }

        function goBackToSelection() {
            const selectUserDiv = document.getElementById('selectUser');
            const adminLoginDiv = document.getElementById('adminlogin');
            const staffLoginDiv = document.getElementById('stafflogin');

            // Hide all login forms
            adminLoginDiv.classList.add('hidden');
            staffLoginDiv.classList.add('hidden');

            // Show the selection screen
            selectUserDiv.classList.remove('hidden');

            // Focus back on select
            setTimeout(() => {
                document.getElementById('usertype').focus();
            }, 100);
        }

        // New function: Toggle password visibility
        function togglePassword(button) {
            const input = button.parentNode.querySelector('input');
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // New function: Show loading overlay
        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.remove('hidden');
            } else {
                overlay.classList.add('hidden');
            }
        }

        // Enhanced form submissions
        document.addEventListener('DOMContentLoaded', function() {
            // Admin login form
            const adminForm = document.querySelector('#adminlogin form');
            adminForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleLogin('Admin');
            });

            // Staff login form
            const staffForm = document.querySelector('#stafflogin form');
            staffForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleLogin('Staff');
            });

            // Verification form
            const verificationForm = document.getElementById('verificationForm');
            if (verificationForm) {
                verificationForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const code = document.getElementById('verificationCode').value;

                    if (code.length !== 6) {
                        showMessageInModal('Please enter the 6-digit code', 'error');
                        return;
                    }

                    verifyCode(code);
                });
            }

            // Enhanced Enter key support
            const userTypeSelect = document.getElementById('usertype');
            userTypeSelect.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    handleUserTypeSelection();
                }
            });

            // Enter key support in login forms
            document.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    const activeForm = document.querySelector('#adminlogin:not(.hidden) form') ||
                        document.querySelector('#stafflogin:not(.hidden) form');
                    if (activeForm && event.target.type !== 'button' && event.target.type !== 'submit') {
                        activeForm.dispatchEvent(new Event('submit'));
                    }
                }
            });

            // Auto-format verification code input
            const verificationCodeInput = document.getElementById('verificationCode');
            if (verificationCodeInput) {
                verificationCodeInput.addEventListener('input', function(e) {
                    // Only allow numbers
                    this.value = this.value.replace(/\D/g, '');

                    // Auto-submit when 6 digits are entered
                    if (this.value.length === 6) {
                        this.blur(); // Remove focus
                        setTimeout(() => {
                            verificationForm.dispatchEvent(new Event('submit'));
                        }, 100);
                    }
                });
            }

            // Auto-focus on select when page loads
            setTimeout(() => {
                const userTypeSelect = document.getElementById('usertype');
                if (userTypeSelect) userTypeSelect.focus();
            }, 500);
        });
        // Generate a unique session context identifier
        function generateSessionContext() {
            return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        // Store session context in localStorage
        function storeSessionContext(context, userType) {
            const sessionData = {
                context: context,
                userType: userType,
                timestamp: Date.now()
            };
            localStorage.setItem('current_session_context', JSON.stringify(sessionData));
        }

        // Get current session context
        function getCurrentSessionContext() {
            const data = localStorage.getItem('current_session_context');
            return data ? JSON.parse(data) : null;
        }

        // Clear session context
        function clearSessionContext() {
            localStorage.removeItem('current_session_context');
        }
        // Enhanced handleLogin function with email verification
        function handleLogin(userType) {
            const form = userType === 'Admin' ?
                document.querySelector('#adminlogin form') :
                document.querySelector('#stafflogin form');

            const username = form.querySelector('input[type="text"]').value;
            const password = form.querySelector('input[type="password"]').value;

            if (!username || !password) {
                showMessage('Please fill in all fields', 'error');
                if (!username) form.querySelector('input[type="text"]').classList.add('shake-animation');
                if (!password) form.querySelector('input[type="password"]').classList.add('shake-animation');

                setTimeout(() => {
                    form.querySelectorAll('.shake-animation').forEach(el => el.classList.remove('shake-animation'));
                }, 600);
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Logging in...';
            submitBtn.disabled = true;
            showLoading(true);

            // Generate session context for this login attempt
            const sessionContext = generateSessionContext();
            storeSessionContext(sessionContext, userType);

            fetch('php/login/login_backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        user_type: userType,
                        session_context: sessionContext // Send context to backend
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.requires_verification) {
                            showVerificationModal();
                            showMessage(data.message, 'success');
                        } else {
                            showMessage(data.message, 'success');
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1000);
                        }
                    } else {
                        showMessage(data.message, 'error');
                        form.querySelectorAll('input').forEach(input => {
                            input.classList.add('error-input');
                            setTimeout(() => input.classList.remove('error-input'), 2000);
                        });
                        clearSessionContext(); // Clear on failure
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Login failed. Please check your connection and try again.', 'error');
                    clearSessionContext(); // Clear on error
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    showLoading(false);
                });
        }

        // New function to show verification modal
        function showVerificationModal() {
            const modal = document.getElementById('verificationModal');
            const codeInput = document.getElementById('verificationCode');

            if (!modal || !codeInput) {
                console.error('Verification modal elements not found');
                return;
            }

            modal.classList.remove('hidden');
            codeInput.value = ''; // Clear any previous input
            setTimeout(() => {
                codeInput.focus();
            }, 300);

            // Start countdown timer (10 minutes)
            startCountdown(600);
        }

        // New function to start countdown timer
        function startCountdown(duration) {
            const countdownElement = document.getElementById('countdown');
            if (!countdownElement) return;

            let timer = duration;

            clearInterval(countdownInterval);

            countdownInterval = setInterval(() => {
                const minutes = Math.floor(timer / 60);
                const seconds = timer % 60;

                countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                // Change color when less than 1 minute
                if (timer < 60) {
                    countdownElement.classList.add('text-red-600', 'font-bold');
                } else {
                    countdownElement.classList.remove('text-red-600', 'font-bold');
                }

                if (--timer < 0) {
                    clearInterval(countdownInterval);
                    cancelVerification();
                    showMessage('Verification code expired. Please login again.', 'error');
                }
            }, 1000);
        }

        // New function to verify code
        function verifyCode(code) {
            const submitBtn = document.querySelector('#verificationForm button[type="submit"]');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Verifying...';
            submitBtn.disabled = true;
            showLoading(true);

            // Get current session context
            const sessionContext = getCurrentSessionContext();

            fetch('php/login/verify_code_backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        code: code,
                        session_context: sessionContext ? sessionContext.context : null
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessageInModal(data.message, 'success');
                        clearInterval(countdownInterval);
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1000);
                    } else {
                        showMessageInModal(data.message, 'error');
                        document.getElementById('verificationCode').classList.add('shake-animation');
                        setTimeout(() => {
                            document.getElementById('verificationCode').classList.remove('shake-animation');
                        }, 600);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessageInModal('Verification failed. Please try again.', 'error');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    showLoading(false);
                });
        }

        // New function to cancel verification
        function cancelVerification() {
            const modal = document.getElementById('verificationModal');
            if (modal) {
                modal.classList.add('hidden');
            }
            clearInterval(countdownInterval);
            clearSessionContext(); // Clear context when canceling

            // Clear the form
            const codeInput = document.getElementById('verificationCode');
            if (codeInput) {
                codeInput.value = '';
            }

            // Reset countdown display
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                countdownElement.textContent = '10:00';
                countdownElement.classList.remove('text-red-600', 'font-bold');
            }

            // Reset to login form
            goBackToSelection();
        }

        // New function to resend verification code
        function resendVerificationCode() {
            showLoading(true);

            fetch('php/login/resend_verification_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessageInModal('New verification code sent!', 'success');
                        // Restart countdown
                        startCountdown(600);
                    } else {
                        showMessageInModal(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessageInModal('Failed to resend code. Please try again.', 'error');
                })
                .finally(() => {
                    showLoading(false);
                });
        }

        // Enhanced showMessage function with better styling
        function showMessage(message, type) {
            // Remove existing messages
            const existingMessage = document.querySelector('.login-message');
            if (existingMessage) {
                existingMessage.remove();
            }

            // Define styles for different message types
            const styles = {
                success: 'bg-green-100 text-green-800 border border-green-300',
                error: 'bg-red-100 text-red-800 border border-red-300',
                warning: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                info: 'bg-blue-100 text-blue-800 border border-blue-300'
            };

            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `login-message p-4 rounded-lg mb-4 text-center transition-all duration-300 transform ${styles[type] || styles.info}`;
            messageDiv.innerHTML = `
            <div class="flex items-center justify-center space-x-2">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

            // Insert message at the top of the current form
            const currentForm = document.querySelector('#adminlogin:not(.hidden) form') ||
                document.querySelector('#stafflogin:not(.hidden) form') ||
                document.querySelector('#selectUser form');

            if (currentForm) {
                currentForm.insertBefore(messageDiv, currentForm.firstChild);

                // Auto-remove message after 5 seconds
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.style.opacity = '0';
                        messageDiv.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            if (messageDiv.parentNode) {
                                messageDiv.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            } else {
                // Fallback: show as alert
                alert(message);
            }
        }

        // New function to show messages in modal
        function showMessageInModal(message, type) {
            // Remove existing messages in modal
            const existingMessage = document.querySelector('#verificationModal .modal-message');
            if (existingMessage) {
                existingMessage.remove();
            }

            const styles = {
                success: 'bg-green-100 text-green-800 border border-green-300',
                error: 'bg-red-100 text-red-800 border border-red-300',
                warning: 'bg-yellow-100 text-yellow-800 border border-yellow-300'
            };

            const messageDiv = document.createElement('div');
            messageDiv.className = `modal-message p-3 rounded-lg mb-4 text-center ${styles[type] || styles.info}`;
            messageDiv.innerHTML = `
            <div class="flex items-center justify-center space-x-2">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span class="text-sm">${message}</span>
            </div>
        `;

            const form = document.getElementById('verificationForm');
            if (form) {
                form.insertBefore(messageDiv, form.firstChild);

                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.remove();
                    }
                }, 5000);
            }
        }

        // Keyboard navigation improvements
        document.addEventListener('keydown', function(event) {
            // Escape key to cancel verification
            if (event.key === 'Escape') {
                const modal = document.getElementById('verificationModal');
                if (modal && !modal.classList.contains('hidden')) {
                    cancelVerification();
                }
            }

            // Backspace in verification code (special handling)
            if (event.key === 'Backspace') {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.id === 'verificationCode') {
                    // Allow normal backspace behavior
                    return;
                }
            }
        });
    </script>

    <style>
        /* Enhanced animations */
        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .error-input {
            border-color: #ef4444 !important;
            background-color: #fef2f2;
        }

        /* Smooth transitions for all interactive elements */
        button,
        input,
        select {
            transition: all 0.2s ease-in-out;
        }

        /* Custom scrollbar for select */
        select::-webkit-scrollbar {
            width: 6px;
        }

        select::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        select::-webkit-scrollbar-thumb {
            background: #c4b5fd;
            border-radius: 3px;
        }

        select::-webkit-scrollbar-thumb:hover {
            background: #a78bfa;
        }

        /* Loading animation */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        /* Focus states */
        input:focus,
        select:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
        }

        /* Hover effects */
        button:hover {
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        /* Verification code input styling */
        #verificationCode::placeholder {
            letter-spacing: normal;
            font-size: 1rem;
        }

        /* Countdown warning */
        .text-red-600 {
            color: #dc2626;
        }
    </style>
</body>

</html>