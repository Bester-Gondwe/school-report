<?php
require_once __DIR__ . '/../../Config/Database.php';
use App\Config\Database;
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT name, grade, term FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_name = $student['name'] ?? 'Student';
    $student_grade = $student['grade'] ?? '';
    $student_term = $student['term'] ?? '';

    // Set session grade and term for use in other pages
    $_SESSION['grade'] = $student_grade;
    $_SESSION['term'] = $student_term;

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
    $marks = [];
    $average_marks = 'N/A';
    $student_rank = 'N/A';
    $total_students = 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body

class="bg-gradient-to-br from-blue-50 to-green-100 min-h-screen">  <a href="student_dashboard.php" class="mb-4 inline-flex items-center gap-2 bg-white border border-gray-300 text-blue-600 font-medium px-5 py-2 rounded-full shadow hover:bg-blue-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Back to Dashboard
        </a>
    <div class="flex flex-col items-center py-10">
        <img src="/app/uploads/default_logo.png" alt="Logo" class="h-20 w-20 rounded-full border border-gray-300 bg-gray-200 mb-2">
        <h1 class="text-3xl font-bold text-green-800 mb-1">Bester Gondwe Secondary School</h1>
        <h2 class="text-xl text-gray-600 font-semibold mb-4">End of Term Report Card</h2>
      
        <div class="bg-white rounded-lg shadow p-8 w-full max-w-2xl">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-1">Hello, <span class="text-blue-600"><?= htmlspecialchars($student_name) ?></span></h3>
                    <p class="text-lg text-gray-700">Form: <span class="text-blue-500 font-semibold"><?= htmlspecialchars($student_grade) ?></span></p>
                    <p class="text-lg text-gray-700">Term: <span class="text-blue-500 font-semibold"><?= htmlspecialchars($student_term) ?></span></p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="../ExportPDF/export_report_card.php" target="_blank" class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow transition"><i class="fas fa-file-pdf"></i> Export as PDF</a>
                </div>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-2"><i class="fas fa-book text-green-500"></i> Your Marks & Grades</h3>
            <div class="overflow-x-auto">
                <table class="w-full border mt-2 text-left border-collapse rounded-lg overflow-hidden shadow">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border p-2">Subject</th>
                            <th class="border p-2">Marks</th>
                            <th class="border p-2">Grade</th>
                            <th class="border p-2">Rank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($marks)): ?>
                            <?php foreach ($marks as $mark): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-2"><?= htmlspecialchars($mark['subject_name']) ?></td>
                                    <td class="border p-2 text-center font-semibold"><?= htmlspecialchars($mark['marks_obtained']) ?></td>
                                    <td class="border p-2 text-center font-semibold"><?= htmlspecialchars($mark['grade']) ?></td>
                                    <td class="border p-2 text-center font-bold text-blue-500">
                                        <span class="rank-column hidden"><?= isset($mark['subject_rank']) ? htmlspecialchars($mark['subject_rank']) : 'N/A' ?></span>
                                        <button class="text-blue-500 px-2 py-1 toggle-rank" title="Toggle Rank">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="border p-2 text-center text-gray-500">No marks available for your current grade and term.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mt-6 flex items-center gap-2"><i class="fas fa-trophy text-yellow-500"></i> Overall Ranking</h3>
            <div class="flex flex-col md:flex-row gap-6 mt-2">
                <p class="text-lg font-semibold text-gray-700 bg-blue-50 rounded p-4 flex-1 shadow">
                    Overall Rank: <span class="text-blue-500 text-xl font-bold"><?= htmlspecialchars($student_rank) ?></span> 
                    <span class="text-gray-500">out of <?= htmlspecialchars($total_students) ?> students</span>
                </p>
                <p class="text-lg font-semibold text-gray-700 bg-green-50 rounded p-4 flex-1 shadow">
                    Average Marks: <span class="text-blue-500 text-xl font-bold"><?= htmlspecialchars($average_marks) ?></span>
                </p>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-rank').forEach(function(button) {
                button.addEventListener('click', function() {
                    // Find the closest tr, then the .rank-column inside it
                    const tr = button.closest('tr');
                    const rankSpan = tr.querySelector('.rank-column');
                    const icon = button.querySelector('i');
                    rankSpan.classList.toggle('hidden');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            });
        });
    </script>
</body>
</html>
