<?php
require_once __DIR__ . '/../../Config/Database.php';
use App\Config\Database;

session_start();
$error_message = "";
$success_message = "";

// Check if registration is allowed (students only)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = (new Database())->getConnection();
    $name = trim($_POST['name']);
    $examination_number = trim($_POST['examination_number']);
    $grade = trim($_POST['grade']);
    $term = trim($_POST['term']);
    $gender = trim($_POST['gender']);
    $password = $examination_number; // Password is the examination number
    
    try {
        // Check if student already exists
        $checkStudent = $db->prepare("SELECT id FROM users WHERE examination_number = :examination_number AND role = 'student'");
        $checkStudent->execute([':examination_number' => $examination_number]);
        if ($checkStudent->rowCount() > 0) {
            $error_message = "Student with this examination number already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, examination_number, password, role, grade, term, gender, status) VALUES (:name, :examination_number, :password, 'student', :grade, :term, :gender, 'active')");
            $stmt->execute([
                ':name' => $name,
                ':examination_number' => $examination_number,
                ':password' => $hashed_password,
                ':grade' => $grade,
                ':term' => $term,
                ':gender' => $gender
            ]);
            $success_message = "Registration successful! You can now login with your examination number.";
            // Redirect to login after successful registration
            header('Location: login.php?registered=1&success=1');
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - School Report System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-indigo-50 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-600 to-blue-600 rounded-full mb-4 shadow-lg">
                <i class="fas fa-user-plus text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Student Registration</h1>
            <p class="text-gray-600">Create your student account to access your academic records</p>
        </div>

        <!-- Registration Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-green-500"></i>Full Name
                    </label>
                    <input type="text" id="name" name="name" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                           placeholder="Enter your full name">
                </div>

                <!-- Examination Number -->
                <div>
                    <label for="examination_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-2 text-green-500"></i>Examination Number
                    </label>
                    <input type="text" id="examination_number" name="examination_number" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                           placeholder="Enter your examination number">
                    <p class="text-xs text-gray-500 mt-1">This will be used as your login password</p>
                </div>

                <!-- Grade and Term -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="grade" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-graduation-cap mr-2 text-green-500"></i>Grade
                        </label>
                        <select id="grade" name="grade" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                            <option value="">Select Grade</option>
                            <option value="1">Form One</option>
                            <option value="2">Form Two</option>
                            <option value="3">Form Three</option>
                            <option value="4">Form Four</option>
                        </select>
                    </div>
                    <div>
                        <label for="term" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-2 text-green-500"></i>Term
                        </label>
                        <select id="term" name="term" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                            <option value="">Select Term</option>
                            <option value="1">Term One</option>
                            <option value="2">Term Two</option>
                            <option value="3">Term Three</option>
                        </select>
                    </div>
                </div>

                <!-- Gender -->
                <div>
                    <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-venus-mars mr-2 text-green-500"></i>Gender
                    </label>
                    <select id="gender" name="gender" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <!-- Register Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                    <i class="fas fa-user-plus mr-2"></i>Register as Student
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">Already have an account? 
                    <a href="login.php" class="text-green-600 hover:text-green-800 font-semibold transition-colors duration-200">
                        Sign In Here
                    </a>
                </p>
            </div>

            <!-- Notice -->
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">Registration Notice:</p>
                        <p>Only students can register here. Teachers and administrators are preloaded in the system.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> School Report System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
