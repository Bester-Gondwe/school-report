<?php
require_once __DIR__ . '/../../config/Database.php';
use App\Config\Database;

session_start();

$db = (new Database())->getConnection();
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    if ($role === 'admin' || $role === 'teacher') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :identifier AND role = :role AND status = 'active'");
        $identifier = $email;
        $password_to_check = $password;
        $stmt->bindParam(':role', $role);
    } else {
        $student_name = trim($_POST['student_name']);
        $registration_number = trim($_POST['registration_number']);
        $stmt = $db->prepare("SELECT * FROM users WHERE name = :identifier AND role = 'student' AND status = 'active'");
        $identifier = $student_name;
        $password_to_check = $registration_number;
    }
    try {
        $stmt->execute([':identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && ($role === 'admin' || $role === 'teacher') && password_verify($password_to_check, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            // Redirect based on role
            if ($role === 'admin') {
                header("Location: ../Admin/admin_dashboard.php");
            } else {
                header("Location: ../Teachers/teacher_dashboard.php");
            }
            exit();
        } elseif ($user && $role === 'student' && $password_to_check === $user['examination_number']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            header("Location: ../Students/student_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid credentials!";
        }
    } catch (PDOException $e) {
        die("Login failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Report System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-600 to-purple-600 rounded-full mb-4 shadow-lg">
                <i class="fas fa-graduation-cap text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your account to continue</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="login-form" class="space-y-6">
                <!-- Role Selection -->
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user-tag mr-2 text-blue-500"></i>Select Role
                    </label>
                    <select id="role" name="role" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <!-- Dynamic Fields -->
                <div id="identifier-field">
                    <!-- Default to student fields -->
                    <label id="identifier-label" for="student_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-blue-500"></i>Student Name
                    </label>
                    <input type="text" id="student_name" name="student_name" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                           placeholder="Enter your full name">
                </div>

                <div id="password-field">
                    <label id="password-label" for="registration_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-2 text-blue-500"></i>Registration Number
                    </label>
                    <input type="text" id="registration_number" name="registration_number" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                           placeholder="Enter your registration number">
                </div>

                <!-- Login Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">Don't have an account? 
                    <a href="register.php" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors duration-200">
                        Register as Student
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> School Report System. All rights reserved.</p>
        </div>
    </div>
    <script>
    function toggleIdentifierField() {
        var role = document.getElementById('role').value;
        var fieldDiv = document.getElementById('identifier-field');
        var passwordDiv = document.getElementById('password-field');
        
        fieldDiv.innerHTML = '';
        passwordDiv.innerHTML = '';
        
        if (role === 'admin' || role === 'teacher') {
            fieldDiv.innerHTML = `
                <label id="identifier-label" for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
                </label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                       placeholder="Enter your email address">`;
            
            passwordDiv.innerHTML = `
                <label id="password-label" for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                </label>
                <input type="password" id="password" name="password" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                       placeholder="Enter your password">`;
        } else {
            fieldDiv.innerHTML = `
                <label id="identifier-label" for="student_name" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user mr-2 text-blue-500"></i>Student Name
                </label>
                <input type="text" id="student_name" name="student_name" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                       placeholder="Enter your full name">`;
            
            passwordDiv.innerHTML = `
                <label id="password-label" for="registration_number" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-id-card mr-2 text-blue-500"></i>Registration Number
                </label>
                <input type="text" id="registration_number" name="registration_number" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                       placeholder="Enter your registration number">`;
        }
    }
    
    document.getElementById('role').addEventListener('change', toggleIdentifierField);
    // Initialize on page load
    window.onload = toggleIdentifierField;
    </script>
</body>
</html>
