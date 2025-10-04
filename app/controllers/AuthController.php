<?php
namespace App\Controllers;

use App\models\User;

class AuthController {
    private $user;

    public function __construct($db) {
        $this->user = new User($db);
    }

    // Registration method
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize input
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $role = trim($_POST['role']);

            // Validate input
            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                die("All fields are required.");
            }

            // Hash password before storing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Create new user
            $this->user->name = $name;
            $this->user->email = $email;
            $this->user->password = $hashedPassword;
            $this->user->role = $role;

            if ($this->user->create()) {
                // Redirect user to login page after successful registration
                header("Location: ../Auth/login.php?success=registered");
                exit();
            } else {
                die("Registration failed. Try again.");
            }
        }
    }

    // Student login method
    public function studentLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $examination_number = trim($_POST['examination_number'] ?? '');
            $grade = trim($_POST['grade'] ?? '');
            $term = trim($_POST['term'] ?? '');
            if (empty($name) || empty($examination_number) || empty($grade) || empty($term)) {
                die("All student fields are required.");
            }
            $user = $this->user->studentAuthenticate($examination_number, $name, $grade, $term);
            if ($user && password_verify($examination_number, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                header("Location: student_dashboard.php");
                exit();
            } else {
                die("Invalid student credentials.");
            }
        }
    }

    // Update login method to handle both teacher and student
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $role = $_POST['role'] ?? '';
            if ($role === 'student') {
                $this->studentLogin();
                return;
            }
            // Teacher login logic (existing)
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            if (empty($email) || empty($password)) {
                die("Email and password are required.");
            }
            $user = $this->user->authenticate($email);
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'student') {
                    header("Location: ../Students/student_dashboard.php");
                } elseif ($user['role'] === 'teacher') {
                    header("Location: ../Teachers/teacher_dashboard.php");
                } else {
                    die("Unknown user role.");
                }
                exit();
            } else {
                die("Invalid email or password.");
            }
        }
    }
}
