<?php
require_once __DIR__ . '/../../Config/Database.php';
use App\Config\Database;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bulk_input_marks'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $marks = $_POST['marks'] ?? [];
    if (empty($marks)) {
        echo json_encode(['success' => false, 'message' => 'No marks submitted.']);
        exit();
    }
    // Get all subjects and their IDs
    $subjects = [];
    $stmt = $db->query('SELECT id, name FROM subjects');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subjects[strtolower(trim($row['name']))] = $row['id'];
    }
    $successCount = 0;
    $failCount = 0;
    $subjectNotFound = [];
    foreach ($marks as $student_id => $subjectMarks) {
        foreach ($subjectMarks as $subject_name => $mark) {
            $subject_key = strtolower(trim($subject_name));
            $subject_id = $subjects[$subject_key] ?? null;
            if (!$subject_id || !is_numeric($mark)) {
                if (!$subject_id) $subjectNotFound[$subject_name] = true;
                $failCount++;
                continue;
            }
            // Check if mark already exists
            $check = $db->prepare('SELECT id FROM marks WHERE student_id = :student_id AND subject_id = :subject_id');
            $check->execute([':student_id' => $student_id, ':subject_id' => $subject_id]);
            if ($check->fetch()) {
                // Update
                $update = $db->prepare('UPDATE marks SET marks_obtained = :mark WHERE student_id = :student_id AND subject_id = :subject_id');
                $ok = $update->execute([':mark' => $mark, ':student_id' => $student_id, ':subject_id' => $subject_id]);
            } else {
                // Insert
                $insert = $db->prepare('INSERT INTO marks (student_id, subject_id, marks_obtained) VALUES (:student_id, :subject_id, :mark)');
                $ok = $insert->execute([':student_id' => $student_id, ':subject_id' => $subject_id, ':mark' => $mark]);
            }
            if ($ok) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
    }
    $msg = "Marks saved: $successCount. Errors: $failCount.";
    if (!empty($subjectNotFound)) {
        $msg .= " Subjects not found: " . implode(", ", array_keys($subjectNotFound));
    }
    echo json_encode(['success' => $failCount === 0, 'message' => $msg]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
