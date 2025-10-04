<?php
require_once __DIR__ . '/../../config/Database.php';
use App\Config\Database;

session_start();

$db = (new Database())->getConnection();
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    if ($role === 'teacher') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :identifier AND role = 'teacher'");
        $identifier = $email;
        $password_to_check = $password;
    } else {
        $student_name = trim($_POST['student_name']);
        $registration_number = trim($_POST['registration_number']);
        $stmt = $db->prepare("SELECT * FROM users WHERE name = :identifier AND role = 'student'");
        $identifier = $student_name;
        $password_to_check = $registration_number;
    }
    try {
        $stmt->execute([':identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $role === 'teacher' && password_verify($password_to_check, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            header("Location: ../Teachers/teacher_dashboard.php");
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
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold text-center text-gray-700">Login</h2>
        <?php if (!empty($error_message)): ?>
            <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form action="" method="POST" id="login-form">
            <div class="mb-4">
                <label for="role" class="block text-gray-700 font-semibold">Role</label>
                <select id="role" name="role" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleIdentifierField()">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            <div class="mb-4" id="identifier-field">
                <!-- Default to student fields -->
                <label id="identifier-label" for="student_name" class="block text-gray-700 font-semibold">Student Name</label>
                <input type="text" id="student_name" name="student_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4" id="password-field">
                <label id="password-label" for="registration_number" class="block text-gray-700 font-semibold">Registration Number</label>
                <input type="text" id="registration_number" name="registration_number" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-green-400 via-green-500 to-green-600 hover:from-green-500 hover:to-green-700 text-white font-bold py-2 rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-opacity-50">Login</button>
            <p class="text-center text-gray-600 mt-4">Don't have an account?
                <a href="register.php" class="text-blue-500 hover:underline">Register here</a>
            </p>
        </form>
    </div>
    <script>
    function toggleIdentifierField() {
        var role = document.getElementById('role').value;
        var fieldDiv = document.getElementById('identifier-field');
        var passwordDiv = document.getElementById('password-field');
        fieldDiv.innerHTML = '';
        passwordDiv.innerHTML = '';
        if (role === 'teacher') {
            fieldDiv.innerHTML = '<label id="identifier-label" for="email" class="block text-gray-700 font-semibold">Email</label>' +
                '<input type="email" id="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">';
            passwordDiv.innerHTML = '<label id="password-label" for="password" class="block text-gray-700 font-semibold">Password</label>' +
                '<input type="password" id="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">';
        } else {
            fieldDiv.innerHTML = '<label id="identifier-label" for="student_name" class="block text-gray-700 font-semibold">Student Name</label>' +
                '<input type="text" id="student_name" name="student_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">';
            passwordDiv.innerHTML = '<label id="password-label" for="registration_number" class="block text-gray-700 font-semibold">Registration Number</label>' +
                '<input type="text" id="registration_number" name="registration_number" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">';
        }
    }
    document.getElementById('role').addEventListener('change', toggleIdentifierField);
    // Initialize on page load
    window.onload = toggleIdentifierField;
    </script>
</body>
</html>
