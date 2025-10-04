<?php
// Include the Database class
require_once __DIR__ . '/../Config/Database.php'; 
use App\Config\Database;

try {
    // Initialize the database connection
    $db = (new Database())->getConnection();

    // Fetch all students
    $studentsQuery = "SELECT * FROM users WHERE role = 'student'";
    $studentsResult = $db->query($studentsQuery);
    $students = $studentsResult->fetchAll(PDO::FETCH_ASSOC);

    // Fetch subjects
    $subjectsQuery = "SELECT * FROM subjects";
    $subjectsResult = $db->query($subjectsQuery);
    $subjects = $subjectsResult->fetchAll(PDO::FETCH_ASSOC);

    // Initialize an array for storing rankings
    $rankings = [];

    // Calculate total marks and average marks for each student
    foreach ($students as $student) {
        $totalMarks = 0;
        foreach ($subjects as $subject) {
            // Fetch marks for each student and subject
            $marksQuery = "SELECT marks_obtained FROM marks WHERE student_id = :student_id AND subject_id = :subject_id";
            $stmt = $db->prepare($marksQuery);
            $stmt->execute([':student_id' => $student['id'], ':subject_id' => $subject['id']]);
            $marks = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalMarks += $marks ? $marks['marks_obtained'] : 0;
        }

        // Add student details and total marks to rankings
        $rankings[] = [
            'student_id' => $student['id'],
            'student_name' => $student['name'],
            'total_marks' => $totalMarks
        ];
    }

    // Sort rankings by total marks in descending order
    usort($rankings, function($a, $b) {
        return $b['total_marks'] - $a['total_marks'];
    });

    // Update rankings and report cards
    foreach ($rankings as $index => $ranking) {
        $rank = $index + 1;

        // Update rank in the users table
        $updateRankQuery = "UPDATE users SET rank = :rank WHERE id = :student_id";
        $stmt = $db->prepare($updateRankQuery);
        $stmt->execute([':rank' => $rank, ':student_id' => $ranking['student_id']]);

        // Calculate grade based on total marks
        $grade = calculateGrade($ranking['total_marks']);
        $averageMarks = $ranking['total_marks'] / count($subjects); // Calculate average marks

        // Update or insert into the report cards table
        $updateReportCardQuery = "INSERT INTO report_cards (student_id, total_marks, average_marks, rank, grade)
                                  VALUES (:student_id, :total_marks, :average_marks, :rank, :grade)
                                  ON DUPLICATE KEY UPDATE total_marks = :total_marks, average_marks = :average_marks, rank = :rank, grade = :grade";
        $stmt = $db->prepare($updateReportCardQuery);
        $stmt->execute([
            ':student_id' => $ranking['student_id'],
            ':total_marks' => $ranking['total_marks'],
            ':average_marks' => $averageMarks,
            ':rank' => $rank,
            ':grade' => $grade
        ]);
    }

    echo "Rankings and report cards have been successfully updated!";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to calculate grade based on total marks
function calculateGrade($totalMarks) {
    if ($totalMarks >= 80) {
        return 'A';
    } elseif ($totalMarks >= 70) {
        return 'B';
    } elseif ($totalMarks >= 60) {
        return 'C';
    } elseif ($totalMarks >= 50) {
        return 'D';
    } else {
        return 'F';
    }
}
?>
