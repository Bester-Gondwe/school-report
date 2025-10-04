<?php
require_once __DIR__ . '/../../Config/Database.php';
use App\Config\Database;

session_start();
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $db = (new Database())->getConnection();
    $name = trim($_POST['name']);
    if ($role === 'teacher') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        try {
            $checkTeacher = $db->prepare("SELECT id FROM users WHERE email = :email AND role = 'teacher'");
            $checkTeacher->execute([':email' => $email]);
            if ($checkTeacher->rowCount() > 0) {
                $error_message = "Teacher with this email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'teacher')");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed_password
                ]);
                header('Location: login.php?registered=1');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Registration failed: " . $e->getMessage();
        }
    } else {
        $examination_number = trim($_POST['examination_number']);
        $grade = trim($_POST['grade']);
        $term = trim($_POST['term']);
        $password = $examination_number; // Password is the examination number
        try {
            $checkStudent = $db->prepare("SELECT id FROM users WHERE examination_number = :examination_number AND role = 'student'");
            $checkStudent->execute([':examination_number' => $examination_number]);
            if ($checkStudent->rowCount() > 0) {
                $error_message = "Student with this examination number already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, examination_number, password, role, grade, term) VALUES (:name, :examination_number, :password, 'student', :grade, :term)");
                $stmt->execute([
                    ':name' => $name,
                    ':examination_number' => $examination_number,
                    ':password' => $hashed_password,
                    ':grade' => $grade,
                    ':term' => $term
                ]);
                header('Location: login.php?registered=1');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-100 to-green-100">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-lg">
        <h2 class="text-3xl font-extrabold text-center text-blue-700 mb-6">Register</h2>
        <?php if (!empty($error_message)): ?>
            <p class="text-red-600 text-center mb-4 font-semibold"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-4" id="registerForm">
            <div>
                <label for="role" class="block text-gray-700 font-semibold mb-1">Role</label>
                <select id="role" name="role" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" onchange="toggleRoleFields()">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            <div>
                <label for="name" class="block text-gray-700 font-semibold mb-1">Full Name</label>
                <input type="text" id="name" name="name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" placeholder="Enter your full name">
            </div>
            <div id="studentFields">
                <div>
                    <label for="examination_number" class="block text-gray-700 font-semibold mb-1">Examination Number</label>
                    <input type="text" id="examination_number" name="examination_number" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" placeholder="Enter your examination number">
                </div>
                <div class="flex gap-2">
                    <div class="w-1/2">
                        <label for="grade" class="block text-gray-700 font-semibold mb-1">Grade</label>
                        <select id="grade" name="grade" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="Form One">Form One</option>
                            <option value="Form Two">Form Two</option>
                            <option value="Form Three">Form Three</option>
                            <option value="Form Four">Form Four</option>
                            <option value="Form Five">Form Five</option>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label for="term" class="block text-gray-700 font-semibold mb-1">Term</label>
                        <select id="term" name="term" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="Term One">Term One</option>
                            <option value="Term Two">Term Two</option>
                            <option value="Term Three">Term Three</option>
                        </select>
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-1">Your password will be your examination number.</div>
            </div>
            <div id="teacherFields" style="display:none;">
                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-1">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter your email">
                </div>
                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-1">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" placeholder="Enter your password">
                </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-green-400 via-blue-400 to-green-600 hover:from-green-500 hover:to-blue-700 text-white font-bold py-2 rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-opacity-50">Register</button>
            <p class="text-center text-gray-600 mt-4">Already have an account? <a href="login.php" class="text-blue-500 hover:underline">Login here</a></p>
        </form>
    </div>
    <script>
    function toggleRoleFields() {
        var role = document.getElementById('role').value;
        var studentFields = document.getElementById('studentFields');
        var teacherFields = document.getElementById('teacherFields');
        if (role === 'teacher') {
            studentFields.style.display = 'none';
            teacherFields.style.display = 'block';
            document.getElementById('examination_number').required = false;
            document.getElementById('grade').required = false;
            document.getElementById('term').required = false;
            document.getElementById('email').required = true;
            document.getElementById('password').required = true;
        } else {
            studentFields.style.display = 'block';
            teacherFields.style.display = 'none';
            document.getElementById('examination_number').required = true;
            document.getElementById('grade').required = true;
            document.getElementById('term').required = true;
            document.getElementById('email').required = false;
            document.getElementById('password').required = false;
        }
    }
    document.getElementById('role').addEventListener('change', toggleRoleFields);
    window.onload = toggleRoleFields;
    </script>
</body>
</html>
