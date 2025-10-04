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

    // Check if grade and term are set
    $missing_grade_or_term = false;
    if (empty($student_grade) || empty($student_term)) {
        $missing_grade_or_term = true;
    }

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
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dynamic greeting based on time
        function getGreeting() {
            const hour = new Date().getHours();
            if (hour < 12) return {text: "Good morning", icon: "fa-sun"};
            if (hour < 18) return {text: "Good afternoon", icon: "fa-cloud-sun"};
            return {text: "Good evening", icon: "fa-moon"};
        }
        document.addEventListener("DOMContentLoaded", function() {
            const greetingObj = getGreeting();
            document.getElementById("greeting").textContent = greetingObj.text;
            document.getElementById("greeting-icon").classList.add("fas", greetingObj.icon);
        });
    </script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-200 min-h-screen">

    <!-- Top Navbar -->
    <nav class="fixed top-0 left-0 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-3">
            <img src="/app/uploads/default_logo.png" alt="Logo" class="h-10 w-10 rounded-full border border-gray-300 bg-gray-200">
            <span class="text-xl font-bold text-gray-700">Student Dashboard</span>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-gray-600 font-semibold hidden md:inline">Welcome, <span class="text-blue-500"><?= htmlspecialchars($student_name) ?></span></span>
            <a href="../Auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow flex items-center gap-2 transition">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Fixed Sidebar -->
    <aside class="fixed top-0 left-0 h-screen w-72 bg-gradient-to-b from-green-700 to-green-500 text-white p-6 shadow-2xl flex flex-col justify-between z-40 rounded-r-3xl">
        <div>
            <div class="flex items-center gap-3 mb-8">
                <span class="bg-white rounded-full p-2 shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <span class="text-2xl font-extrabold tracking-wide">Student Panel</span>
            </div>
            <nav class="space-y-3">
                <a href="#" data-target="dashboard_overview" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-chart-pie"></i>
                    Dashboard
                </a>
                <a href="#" data-target="academic_performance" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-chart-line"></i>
                    Academic Performance
                </a>
                <a href="school_report_card.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-file-pdf"></i>
                    Report Card
                </a>
                <a href="#" data-target="settings" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-gear"></i>
                    Settings
                </a>
            </nav>
        </div>
        <div class="mt-8">
            <a href="../Auth/logout.php" class="w-full flex items-center gap-2 justify-center bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg shadow transition font-semibold">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 p-8 ml-72">
        <!-- Welcome Header -->
        <header class="bg-white/90 shadow-lg rounded-xl mb-8 flex items-center gap-4 px-6 py-5 border border-green-200">
            <span class="text-3xl">
                <i class="fas fa-sun text-yellow-400"></i>
            </span>
            <div>
                <h2 class="text-2xl font-bold text-green-800">
                    <span id="greeting">Good morning</span>, <span class="text-green-600"><?= htmlspecialchars($student_name) ?></span>!
                </h2>
                <p class="text-gray-500 text-sm mt-1">Student Dashboard - Track your academic progress</p>
            </div>
        </header>

        <!-- Dashboard Overview -->
        <section id="dashboard_overview" class="content-section">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">Dashboard Overview</h3>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Current Grade</p>
                            <p class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($normalized_grade) ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Current Term</p>
                            <p class="text-3xl font-bold text-green-600"><?= htmlspecialchars($normalized_term) ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-calendar text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Average Marks</p>
                            <p class="text-3xl font-bold text-purple-600"><?= htmlspecialchars($average_marks) ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Current Rank</p>
                            <p class="text-3xl font-bold text-orange-600"><?= htmlspecialchars($student_rank) ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-trophy text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Quick Actions</h4>
                    <div class="space-y-3">
                        <button onclick="showSection('academic_performance')" class="w-full text-left p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                            <i class="fas fa-chart-line text-blue-600 mr-3"></i>View Academic Performance
                        </button>
                        <a href="school_report_card.php" target="_blank" class="w-full text-left p-3 bg-green-50 hover:bg-green-100 rounded-lg transition block">
                            <i class="fas fa-file-pdf text-green-600 mr-3"></i>Download Report Card
                        </a>
                        <button onclick="showSection('settings')" class="w-full text-left p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                            <i class="fas fa-cog text-purple-600 mr-3"></i>Account Settings
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Academic Summary</h4>
                    <div class="space-y-3">
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <i class="fas fa-book text-blue-500 mr-3"></i>
                            <span class="text-sm"><?= count($marks) ?> subjects completed</span>
                        </div>
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <i class="fas fa-users text-green-500 mr-3"></i>
                            <span class="text-sm">Rank <?= $student_rank ?> of <?= $total_students ?></span>
                        </div>
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <i class="fas fa-star text-purple-500 mr-3"></i>
                            <span class="text-sm">Average: <?= $average_marks ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Academic Performance -->
        <section id="academic_performance" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">Academic Performance</h3>
            <!-- Academic Graph Card -->
            <div class="mt-8">
                <div class="bg-gradient-to-r from-purple-200 to-blue-200 rounded-lg p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-gray-700 flex items-center gap-2"><i class="fas fa-chart-bar text-purple-500"></i> Academic Graph</h3>
                        <span class="bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow ml-2">Grade: <?= htmlspecialchars($normalized_grade) ?> | Term: <?= htmlspecialchars($normalized_term) ?></span>
                    </div>
                    <canvas id="marksChart" height="120"></canvas>

                    <!-- Marks Table and Ranking Info (inside the card) -->
                    <div class="mt-8">
                        <?php if (!empty($marks)) : ?>
                            <h3 class="text-lg font-bold text-gray-700 mb-2 flex items-center gap-2"><i class="fas fa-table text-blue-500"></i> Marks Table</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white rounded shadow">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 border-b text-left">Subject</th>
                                            <th class="px-4 py-2 border-b text-left">Marks Obtained</th>
                                            <th class="px-4 py-2 border-b text-left">Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($marks as $mark) : ?>
                                            <tr>
                                                <td class="px-4 py-2 border-b"><?= htmlspecialchars($mark['subject_name']) ?></td>
                                                <td class="px-4 py-2 border-b"><?= htmlspecialchars($mark['marks_obtained']) ?></td>
                                                <td class="px-4 py-2 border-b font-bold">
                                                    <?php
                                                        $gradeColor = match($mark['grade']) {
                                                            'A' => 'text-green-600',
                                                            'B' => 'text-blue-600',
                                                            'C' => 'text-yellow-600',
                                                            'D' => 'text-orange-600',
                                                            default => 'text-red-600',
                                                        };
                                                    ?>
                                                    <span class="<?= $gradeColor ?>"><?= $mark['grade'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-6">
                                <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded shadow">
                                    <strong>Average Marks:</strong> <?= htmlspecialchars($average_marks) ?>
                                </div>
                                <div class="bg-purple-100 text-purple-800 px-4 py-2 rounded shadow">
                                    <strong>Rank:</strong> <?= htmlspecialchars($student_rank) ?> / <?= htmlspecialchars($total_students) ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="text-center text-gray-500 py-8">
                                No marks data available for this grade and term.<br>
                                <?php if ($missing_grade_or_term): ?>
                                    <span class="text-red-500">Your grade or term information is missing. Please contact your teacher or admin.</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Settings Section -->
        <section id="settings" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">Account Settings</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Profile Information</h4>
                    <form class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Full Name</label>
                            <input type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" 
                                   value="<?= htmlspecialchars($student_name) ?>" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Examination Number</label>
                            <input type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" 
                                   value="<?= htmlspecialchars($student['examination_number'] ?? '') ?>" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Current Grade</label>
                            <input type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" 
                                   value="<?= htmlspecialchars($normalized_grade) ?>" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Current Term</label>
                            <input type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" 
                                   value="<?= htmlspecialchars($normalized_term) ?>" readonly>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Security Settings</h4>
                    <form class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Current Password</label>
                            <input type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" 
                                   placeholder="Your examination number" readonly>
                            <p class="text-xs text-gray-500 mt-1">Your password is your examination number</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Contact Information</label>
                            <p class="text-sm text-gray-600">To update your contact information, please contact your teacher or administrator.</p>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">Account Information:</p>
                        <p>Your student account information is managed by your school administrators. If you need to update any information, please contact your teacher or school office.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <script>
        // Sidebar navigation
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const target = link.getAttribute('data-target');
                showSection(target);
            });
        });

        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.add('hidden');
            });
            document.getElementById(sectionId)?.classList.remove('hidden');
        }

        // Show first section by default
        window.addEventListener('DOMContentLoaded', () => {
            const firstSection = document.querySelector('.content-section');
            if (firstSection) firstSection.classList.remove('hidden');
        });
    </script>
    <!-- JS for rank toggle -->
    <script>
        // Chart.js Academic Graph
        document.addEventListener('DOMContentLoaded', function() {
            // PHP to JS: subject names and marks
            const subjects = <?php echo json_encode(array_column($marks, 'subject_name')); ?>;
            const marks = <?php echo json_encode(array_map(function($m) { return (float)$m['marks_obtained']; }, $marks)); ?>;
            const bgColors = [
                'rgba(99, 102, 241, 0.7)', // indigo
                'rgba(139, 92, 246, 0.7)', // purple
                'rgba(16, 185, 129, 0.7)', // emerald
                'rgba(251, 191, 36, 0.7)', // yellow
                'rgba(239, 68, 68, 0.7)', // red
                'rgba(59, 130, 246, 0.7)', // blue
                'rgba(236, 72, 153, 0.7)', // pink
                'rgba(34, 197, 94, 0.7)', // green
            ];
            if (subjects.length > 0) {
                const ctx = document.getElementById('marksChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: subjects,
                        datasets: [{
                            label: 'Marks Obtained',
                            data: marks,
                            backgroundColor: subjects.map((_, i) => bgColors[i % bgColors.length]),
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            title: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Marks: ${context.parsed.y}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Marks (%)',
                                    color: '#6366f1',
                                    font: { weight: 'bold', size: 14 }
                                },
                                grid: { color: '#e0e7ff' },
                                ticks: { color: '#6366f1', font: { weight: 'bold' } }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Subjects',
                                    color: '#6366f1',
                                    font: { weight: 'bold', size: 14 }
                                },
                                grid: { display: false },
                                ticks: { color: '#6366f1', font: { weight: 'bold' } }
                            }
                        },
                        animation: {
                            duration: 1200,
                            easing: 'easeOutBounce'
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>