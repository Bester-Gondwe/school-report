<?php
require_once __DIR__ . '/../../Config/Database.php'; // Fixed path
use App\Config\Database;
require_once __DIR__ . '/../../vendor/autoload.php'; // Include TCPDF autoloader

// Start session for authentication
session_start();

// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

function getGrade($marks) {
    if ($marks >= 90) {
        return 'A+';
    } elseif ($marks >= 80) {
        return 'A';
    } elseif ($marks >= 70) {
        return 'B';
    } elseif ($marks >= 60) {
        return 'C';
    } elseif ($marks >= 50) {
        return 'D';
    } else {
        return 'F';
    }
}

// Redirect to login if user is not logged in or is not a student
if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher' && isset($_GET['student_id'])) {
    // Teacher view: get student by ID
    $student_id = (int)$_GET['student_id'];
    $grade = $_GET['grade'] ?? null;
    $term = $_GET['term'] ?? null;
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT name FROM users WHERE id = :id');
    $stmt->execute([':id' => $student_id]);
    $student_name = $stmt->fetchColumn() ?: 'Unknown Student';
} else {
    // Student view (default)
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        header("Location: ../Auth/login.php");
        exit();
    }
    $student_id = $_SESSION['user_id'];
    $student_name = $_SESSION['name'] ?? 'Unknown Student';
    $grade = $_SESSION['grade'] ?? null;
    $term = $_SESSION['term'] ?? null;
}

try {
    // Initialize the database connection
    $db = (new Database())->getConnection();

    // Fetch student info and normalize grade/term
    $stmt = $db->prepare('SELECT name, grade, term FROM users WHERE id = :id');
    $stmt->execute([':id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_name = $student['name'] ?? 'Student';
    $student_grade = $student['grade'] ?? '';
    $student_term = $student['term'] ?? '';

    // Normalize grade and term for query (handle both numeric and string)
    $grade_map = [
        '1' => 'Form One', 'Form One' => 'Form One',
        '2' => 'Form Two', 'Form Two' => 'Form Two',
        '3' => 'Form Three', 'Form Three' => 'Form Three',
        '4' => 'Form Four', 'Form Four' => 'Form Four',
    ];
    $term_map = [
        '1' => 'Term One', 'Term One' => 'Term One',
        '2' => 'Term Two', 'Term Two' => 'Term Two',
        '3' => 'Term Three', 'Term Three' => 'Term Three',
    ];
    $normalized_grade = $grade_map[$student_grade] ?? $student_grade;
    $normalized_term = $term_map[$student_term] ?? $student_term;

    // Fetch marks for the logged-in student, including subject name (no grade from DB)
    $marksQuery = "SELECT s.name AS subject_name, MAX(m.marks_obtained) AS marks_obtained
                   FROM marks m
                   JOIN subjects s ON m.subject_id = s.id
                   WHERE m.student_id = :student_id AND (m.grade = :grade OR m.grade = :grade_num) AND (m.term = :term OR m.term = :term_num)
                   GROUP BY m.subject_id";
    $marksStmt = $db->prepare($marksQuery);
    $marksStmt->execute([
        ':student_id' => $student_id,
        ':grade' => $normalized_grade,
        ':grade_num' => array_search($normalized_grade, $grade_map),
        ':term' => $normalized_term,
        ':term_num' => array_search($normalized_term, $term_map),
    ]);
    $marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate grades based on marks (A: 90+, B: 80-89, C: 70-79, D: 60-69, F: <60)
    foreach ($marks as &$mark) {
        $score = $mark['marks_obtained'];
        if ($score >= 90) {
            $grade = 'A';
        } elseif ($score >= 80) {
            $grade = 'B';
        } elseif ($score >= 70) {
            $grade = 'C';
        } elseif ($score >= 60) {
            $grade = 'D';
        } else {
            $grade = 'F';
        }
        $mark['grade'] = $grade;
    }
    unset($mark); // break reference

    // Calculate average marks
    $average_marks = 'N/A';
    if ($marks) {
        $total = 0;
        $count = 0;
        foreach ($marks as $mark) {
            $total += $mark['marks_obtained'];
            $count++;
        }
        if ($count > 0) {
            $average_marks = round($total / $count, 2);
        }
    }

    // Calculate rank and total students (filtered by grade and term, robust to both string and numeric)
    $rankQuery = "SELECT m.student_id, SUM(m.marks_obtained) AS total_marks,
                  RANK() OVER (ORDER BY SUM(m.marks_obtained) DESC) AS rank
                  FROM marks m
                  WHERE (m.grade = :grade OR m.grade = :grade_num) AND (m.term = :term OR m.term = :term_num)
                  GROUP BY m.student_id";
    $rankStmt = $db->prepare($rankQuery);
    $rankStmt->execute([
        ':grade' => $normalized_grade,
        ':grade_num' => array_search($normalized_grade, $grade_map),
        ':term' => $normalized_term,
        ':term_num' => array_search($normalized_term, $term_map),
    ]);
    $ranking = $rankStmt->fetchAll(PDO::FETCH_ASSOC);

    $student_rank = 'N/A';
    $total_students = count($ranking);
    foreach ($ranking as $row) {
        if ($row['student_id'] == $student_id) {
            $student_rank = $row['rank'];
            break;
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Always generate the PDF (no HTML output)
try {
    // Create new PDF instance
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Header with logo and school name
    $logoPath = __DIR__ . '/../../uploads/default_logo.png';
    $addLogo = false;
    if (file_exists($logoPath) && filesize($logoPath) > 0) {
        $imageInfo = @getimagesize($logoPath);
        if ($imageInfo && $imageInfo[2] === IMAGETYPE_PNG) {
            $addLogo = true;
        }
    }
    if ($addLogo) {
        $pdf->Image($logoPath, 15, 10, 25, 25, 'PNG');
    }
    $pdf->SetXY(45, 10);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 12, 'REPORT CARD', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetX(45);
    $pdf->Cell(0, 8, 'Bester Gondwe Secondary School', 0, 1, 'L');
    $pdf->Ln(5);

    // Student info
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(30, 8, 'Student :', 0, 0, 'L');
    $pdf->Cell(60, 8, $student_name, 0, 0, 'L');
    $pdf->Cell(20, 8, 'Level :', 0, 0, 'L');
    $pdf->Cell(30, 8, $normalized_grade, 0, 0, 'L');
    $pdf->Cell(20, 8, 'Class :', 0, 0, 'L');
    $pdf->Cell(30, 8, $normalized_grade, 0, 1, 'L');
    $pdf->Ln(2);

    // Table header
    $pdf->SetFillColor(180, 200, 230);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(60, 10, 'Subject', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Marks', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Grade', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Rank', 1, 1, 'C', 1);
    $pdf->SetFont('helvetica', '', 11);
    foreach ($marks as $mark) {
        $pdf->Cell(60, 10, $mark['subject_name'], 1, 0, 'C');
        $pdf->Cell(40, 10, $mark['marks_obtained'], 1, 0, 'C');
        $pdf->Cell(40, 10, $mark['grade'], 1, 0, 'C');
        $pdf->Cell(40, 10, ($mark['rank'] ?? '-') . ' / ' . $total_students, 1, 1, 'C');
    }

    // Grading scale
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'GRADING SCALE :   A = 90% - 100%   B = 80% - 89%   C = 60% - 79%   D = 0% - 59%', 0, 1, 'L');

    // Comment box
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Comment :', 0, 1, 'L');
    $pdf->SetFillColor(240, 240, 255);
    $pdf->MultiCell(0, 20, '', 1, 'L', 1);

    // Overall ranking and average
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Overall Rank: ' . $student_rank . ' out of ' . $total_students . ' students', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Average Marks: ' . $average_marks, 0, 1, 'L');

    $pdf->Output('report_card.pdf', 'I');
    exit;

} catch (Exception $e) {
    error_log("Error generating PDF: " . $e->getMessage());
    echo "Error generating PDF.";
    exit;
}
