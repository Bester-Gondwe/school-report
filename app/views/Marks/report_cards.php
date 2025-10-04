<?php
// Start session for authentication
session_start();

// Include the database connection from the Database class
require_once __DIR__ . '/../Config/Database.php'; 
use App\Config\Database;

// Ensure the user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch grade and term from session or default to last accessed values
$grade = $_SESSION['grade'] ?? 'Form One'; // Default to Form One if not set
$term = $_SESSION['term'] ?? 'Term One';   // Default to Term One if not set

// Fetch user information (name)
$db = (new Database())->getConnection();
$userQuery = "SELECT name FROM users WHERE id = :user_id";
$userStmt = $db->prepare($userQuery);
$userStmt->execute([':user_id' => $user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user['name'] ?? 'Unknown User';

try {
    $schoolQuery = "SELECT school_name, logo FROM school_info LIMIT 1";
    $schoolStmt = $db->prepare($schoolQuery);
    $schoolStmt->execute();
    $school = $schoolStmt->fetch(PDO::FETCH_ASSOC);

    $school_name = $school['school_name'] ?? 'Your School Name';
    $school_logo = $school['logo'] ?? 'default_logo.png';

    // Fetch the report card for the student
    $reportQuery = "SELECT * FROM report_cards WHERE student_id = :student_id AND grade = :grade AND term = :term LIMIT 1";
    $reportStmt = $db->prepare($reportQuery);
    $reportStmt->execute([':student_id' => $user_id, ':grade' => $grade, ':term' => $term]);
    $reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch student marks per subject
    $marks = [];
    if ($reports) {
        $marksQuery = "SELECT subjects.name AS subject_name, marks.marks_obtained FROM marks
                       JOIN subjects ON marks.subject_id = subjects.id
                       WHERE marks.student_id = :student_id AND marks.grade = :grade AND marks.term = :term";
        $marksStmt = $db->prepare($marksQuery);
        $marksStmt->execute([':student_id' => $user_id, ':grade' => $grade, ':term' => $term]);
        $marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <!-- School Logo -->
        <div class="flex items-center justify-between">
            <img src="<?php echo htmlspecialchars($school_logo); ?>" alt="School Logo" class="h-20 w-20">
            <h2 class="text-2xl font-bold text-gray-700"><?php echo htmlspecialchars($school_name); ?></h2>
        </div>
        <hr class="my-4">

        <h3 class="text-xl font-semibold text-gray-700">Report Card</h3>
        <p class="text-lg text-gray-600">Student: <span class="font-semibold text-blue-500"><?php echo htmlspecialchars($user_name); ?></span></p>
        <p class="text-lg text-gray-600">Grade: <span class="font-semibold text-blue-500"><?php echo htmlspecialchars($grade); ?></span>, Term: <span class="font-semibold text-blue-500"><?php echo htmlspecialchars($term); ?></span></p>

        <!-- Form for filtering grade and term -->
        <form method="GET" action="report_cards.php" class="mb-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="grade" class="block text-sm font-medium text-gray-700">Grade</label>
                    <select id="grade" name="grade" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="Form One" <?php echo ($grade == 'Form One') ? 'selected' : ''; ?>>Form One</option>
                        <option value="Form Two" <?php echo ($grade == 'Form Two') ? 'selected' : ''; ?>>Form Two</option>
                        <option value="Form Three" <?php echo ($grade == 'Form Three') ? 'selected' : ''; ?>>Form Three</option>
                        <option value="Form Four" <?php echo ($grade == 'Form Four') ? 'selected' : ''; ?>>Form Four</option>
                    </select>
                </div>
                <div>
                    <label for="term" class="block text-sm font-medium text-gray-700">Term</label>
                    <select id="term" name="term" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="Term One" <?php echo ($term == 'Term One') ? 'selected' : ''; ?>>Term One</option>
                        <option value="Term Two" <?php echo ($term == 'Term Two') ? 'selected' : ''; ?>>Term Two</option>
                        <option value="Term Three" <?php echo ($term == 'Term Three') ? 'selected' : ''; ?>>Term Three</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md mt-4">View Report Card</button>
        </form>

        <?php if ($reports): ?>
            <table class="w-full mt-4 border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-blue-200">
                        <th class="border border-gray-300 p-2">Subject</th>
                        <th class="border border-gray-300 p-2">Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($marks as $mark): ?>
                        <tr>
                            <td class="border border-gray-300 p-2 bg-gray-50"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                            <td class="border border-gray-300 p-2 bg-gray-50"><?php echo htmlspecialchars($mark['marks_obtained']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="text-lg font-semibold text-gray-700 mt-4">Total Marks: <span class="text-blue-500"><?php echo htmlspecialchars($reports[0]['total_marks'] ?? 'N/A'); ?></span></p>
            <p class="text-lg font-semibold text-gray-700">Average Marks: <span class="text-blue-500"><?php echo htmlspecialchars($reports[0]['average_marks'] ?? 'N/A'); ?></span></p>
            <p class="text-lg font-semibold text-gray-700">Rank: <span class="text-blue-500"><?php echo htmlspecialchars($reports[0]['rank'] ?? 'N/A'); ?></span></p>
        <?php else: ?>
            <p class="text-red-500 font-semibold mt-4">No report card available for this grade and term.</p>
        <?php endif; ?>
    </div>
</body>
</html>
