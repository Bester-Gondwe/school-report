<?php
require_once __DIR__ . '/../../Config/Database.php';
use App\Config\Database;

header('Content-Type: application/json');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$role = $_POST['role'] ?? '';
$db = (new Database())->getConnection();

if ($role === 'student') {
    $name = trim($_POST['name'] ?? '');
    $examination_number = trim($_POST['examination_number'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $term = trim($_POST['term'] ?? '');
    if (empty($name) || empty($examination_number) || empty($grade) || empty($term)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }
    // Check if student exists
    $stmt = $db->prepare('SELECT id FROM users WHERE examination_number = :exam AND role = "student"');
    $stmt->bindParam(':exam', $examination_number);
    $stmt->execute();
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Student with this examination number already exists.']);
        exit();
    }
    $hashed_password = password_hash($examination_number, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, examination_number, password, grade, term, role) VALUES (:name, :exam, :password, :grade, :term, "student")');
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':exam', $examination_number);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':grade', $grade);
    $stmt->bindParam(':term', $term);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student registered successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to register student.']);
    }
    exit();
} elseif ($role === 'teacher') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }
    // Check if teacher exists
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND role = "teacher"');
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Teacher with this email already exists.']);
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, "teacher")');
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Teacher registered successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to register teacher.']);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid role.']);
    exit();
} 