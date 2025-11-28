<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <div class="text-red-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Access Denied</h1>
        <p class="text-gray-600 mb-6">You don't have permission to access this page.</p>

        <div class="space-y-3">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <a href="<?php echo isAdmin() ? '/MSWDPALUAN_SYSTEM-MAIN/html/index.php' : '/MSWDPALUAN_SYSTEM-MAIN/html/staff/index.php'; ?>"
                    class="block w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded transition duration-200">
                    Go to Your Dashboard
                </a>
            <?php endif; ?>

            <a href="/MSWDPALUAN_SYSTEM-MAIN/html/login.php"
                class="block w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded transition duration-200">
                Back to Login
            </a>
        </div>
    </div>
</body>

</html>