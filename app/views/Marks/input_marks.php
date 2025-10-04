<?php
require_once __DIR__ . '/../Config/Database.php';
use App\Config\Database;

session_start();

// Ensure the user is logged in as a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    // Redirect to login page or send a forbidden error
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(["success" => false, "message" => "You do not have permission to access this page."]);
    exit();
}

try {
    $db = (new Database())->getConnection();

    // Fetch the distinct grades and terms for filtering
    $grades = [1, 2, 3, 4]; // Form 1 to Form 4
    $terms = [1, 2, 3]; // Term 1, Term 2, Term 3

    // Fetch students filtered by selected grade and term for marks input
    if (isset($_POST['grade_filter']) && isset($_POST['term_filter'])) {
        $gradeFilter = $_POST['grade_filter'];
        $termFilter = $_POST['term_filter'];

        // Fetch students based on grade and term
        $stmt = $db->prepare("SELECT * FROM users WHERE role='student' AND grade = :grade AND term = :term");
        $stmt->bindParam(':grade', $gradeFilter);
        $stmt->bindParam(':term', $termFilter);
        $stmt->execute();
        $studentsFiltered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch all students if no filter is applied
        $stmt = $db->prepare("SELECT * FROM users WHERE role='student'");
        $stmt->execute();
        $studentsFiltered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle marks input for students
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get POST data and sanitize it
        $student_id = $_POST['student_id'] ?? '';
        $subject_id = $_POST['subject_id'] ?? '';
        $marks = $_POST['marks'] ?? '';
        $grade = $_POST['grade_filter'] ?? '';
        $term = $_POST['term_filter'] ?? '';

        // Validate the inputs
        if (empty($student_id) || empty($subject_id) || !is_numeric($marks)) {
            echo json_encode(["success" => false, "message" => "Invalid input. Please provide valid student ID, subject ID, and marks."]);
            exit();
        }

        // Check if the student and subject exist in the database
        $stmt = $db->prepare("SELECT id FROM users WHERE id = :student_id AND role = 'student'");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            echo json_encode(["success" => false, "message" => "Student not found."]);
            exit();
        }

        $stmt = $db->prepare("SELECT id FROM subjects WHERE id = :subject_id");
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            echo json_encode(["success" => false, "message" => "Subject not found."]);
            exit();
        }

        // Insert marks into the database
        $stmt = $db->prepare("INSERT INTO marks (student_id, subject_id, marks_obtained, grade, term) VALUES (:student_id, :subject_id, :marks, :grade, :term)");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':marks', $marks);
        $stmt->bindParam(':grade', $grade);
        $stmt->bindParam(':term', $term);
        $stmt->execute();

        // Calculate and Update Rank After Marks are Submitted
        updateStudentRank($student_id, $db, $grade, $term);

        echo json_encode(["success" => true, "message" => "Marks recorded successfully."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

// Function to update rank after marks are submitted
function updateStudentRank($student_id, $db, $grade, $term) {
    // Calculate average marks for the student
    $stmt = $db->prepare("SELECT AVG(marks_obtained) AS average FROM marks WHERE student_id = :student_id");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $average_marks = $result['average'];

    // Fetch all students from the same grade and term and their average marks
    $stmt = $db->prepare("SELECT u.id, AVG(m.marks_obtained) AS average_marks 
                          FROM users u 
                          JOIN marks m ON u.id = m.student_id 
                          WHERE u.grade = :grade AND m.term = :term 
                          GROUP BY u.id 
                          ORDER BY average_marks DESC");
    $stmt->bindParam(':grade', $grade);
    $stmt->bindParam(':term', $term);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rank the students based on average marks
    $rank = 1;
    foreach ($students as $student) {
        if ($student['id'] == $student_id) {
            // Update the rank of the student
            $updateStmt = $db->prepare("UPDATE users SET rank = :rank WHERE id = :student_id");
            $updateStmt->bindParam(':rank', $rank);
            $updateStmt->bindParam(':student_id', $student_id);
            $updateStmt->execute();
            break;
        }
        $rank++;
    }
}
