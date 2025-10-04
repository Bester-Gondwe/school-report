<?php
// Debug: Session status and contents
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log('Session not active!');
}
if (isset(
    $_SESSION)) {
    error_log('Current session: ' . print_r($_SESSION, true));
}

session_start();

// Redirect to login if user is not logged in or not a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    error_log('Redirecting to: ../Teachers/teacher_dashboard.php');
    header('Location: ../Auth/login.php');
    exit();
}

require_once __DIR__ . '/../../Config/Database.php';

use App\Config\Database;

try {
    $db = (new Database())->getConnection();

    $teacher_id = $_SESSION['user_id'];
    $name = $_SESSION['name'] ?? 'Unknown Teacher';

    // Dynamic greeting based on time
    $hour = (int)date('H');
    if ($hour >= 5 && $hour < 12) {
        $greeting = 'Good morning';
        $greeting_icon = '<svg class="inline h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-8.66l-.71.71M4.05 19.07l-.71-.71M21 12h-1M4 12H3m16.95 7.07l-.71-.71M7.05 4.93l-.71.71M12 5a7 7 0 100 14 7 7 0 000-14z" /></svg>';
    } elseif ($hour >= 12 && $hour < 18) {
        $greeting = 'Good afternoon';
        $greeting_icon = '<svg class="inline h-6 w-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-8.66l-.71.71M4.05 19.07l-.71-.71M21 12h-1M4 12H3m16.95 7.07l-.71-.71M7.05 4.93l-.71.71M12 5a7 7 0 100 14 7 7 0 000-14z" /></svg>';
    } else {
        $greeting = 'Good evening';
        $greeting_icon = '<svg class="inline h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" /></svg>';
    }

    // Get all subjects
    $stmt = $db->prepare('SELECT * FROM subjects');
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ranking summary (Total marks + RANK)
    $rankingQuery = "SELECT student_id, SUM(marks_obtained) AS total_marks,
                    RANK() OVER (ORDER BY SUM(marks_obtained) DESC) AS rank
                    FROM marks GROUP BY student_id";
    $rankingStmt = $db->query($rankingQuery);
    $rankingSummary = $rankingStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get students
    $stmt = $db->prepare("SELECT * FROM users WHERE role='student'");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group students by grade and term
    $studentsGrouped = [];
    foreach ($students as $student) {
        $studentsGrouped[$student['grade']][$student['term']][] = $student;
    }

    // Group students by grade, term, and gender for summary
    $genderSummary = [];
    foreach ($students as $student) {
        $grade = $student['grade'];
        $term = $student['term'];
        $gender = strtolower($student['gender'] ?? 'unknown');
        if (!isset($genderSummary[$grade])) $genderSummary[$grade] = [];
        if (!isset($genderSummary[$grade][$term])) $genderSummary[$grade][$term] = ['male' => 0, 'female' => 0, 'unknown' => 0];
        if ($gender === 'male' || $gender === 'm') {
            $genderSummary[$grade][$term]['male']++;
        } elseif ($gender === 'female' || $gender === 'f') {
            $genderSummary[$grade][$term]['female']++;
        } else {
            $genderSummary[$grade][$term]['unknown']++;
        }
    }

    // Register student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_student'])) {
        $name = trim($_POST['name']);
        $examination_number = trim($_POST['examination_number']);
        $grade = trim($_POST['grade']);
        $term = trim($_POST['term']);

        if (!empty($name) && !empty($examination_number) && !empty($grade) && !empty($term)) {
            $stmt = $db->prepare('SELECT id FROM users WHERE examination_number = :exam');
            $stmt->bindParam(':exam', $examination_number);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $db->prepare('UPDATE users SET grade=:grade, term=:term WHERE id=:id');
                $stmt->bindParam(':grade', $grade);
                $stmt->bindParam(':term', $term);
                $stmt->bindParam(':id', $existing['id']);
                $stmt->execute();
            } else {
                $stmt = $db->prepare("INSERT INTO users (name, examination_number, grade, term, role)
                                      VALUES (:name, :exam, :grade, :term, 'student')");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':exam', $examination_number);
                $stmt->bindParam(':grade', $grade);
                $stmt->bindParam(':term', $term);
                $stmt->execute();
            }

            // Only set session for student if not already logged in as teacher
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['role'] = 'student';
                $_SESSION['name'] = $name;
            }

            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Input marks
    $marks_input_message = '';
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['input_marks'])
    ) {
        $student_id = $_POST['student_id'];
        $marks = $_POST['marks'];
        // Fetch the student's current grade and term
        $stmt = $db->prepare('SELECT grade, term FROM users WHERE id = :id');
        $stmt->bindParam(':id', $student_id);
        $stmt->execute();
        $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $grade = $student_info['grade'];
        $term = $student_info['term'];
        foreach ($marks as $subject_id => $mark) {
            // Check if a mark already exists for this student, subject, grade, and term
            $checkStmt = $db->prepare("SELECT id FROM marks WHERE student_id = :student_id AND subject_id = :subject_id AND grade = :grade AND term = :term");
            $checkStmt->bindParam(':student_id', $student_id);
            $checkStmt->bindParam(':subject_id', $subject_id);
            $checkStmt->bindParam(':grade', $grade);
            $checkStmt->bindParam(':term', $term);
            $checkStmt->execute();
            $existingMark = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingMark) {
                // Update the existing mark
                $updateStmt = $db->prepare("UPDATE marks SET marks_obtained = :mark WHERE id = :id");
                $updateStmt->bindParam(':mark', $mark);
                $updateStmt->bindParam(':id', $existingMark['id']);
                $updateStmt->execute();
            } else {
                // Insert new mark
                $insertStmt = $db->prepare("INSERT INTO marks (student_id, subject_id, marks_obtained, grade, term) VALUES (:student_id, :subject_id, :mark, :grade, :term)");
                $insertStmt->bindParam(':student_id', $student_id);
                $insertStmt->bindParam(':subject_id', $subject_id);
                $insertStmt->bindParam(':mark', $mark);
                $insertStmt->bindParam(':grade', $grade);
                $insertStmt->bindParam(':term', $term);
                $insertStmt->execute();
            }
        }
        // Show success message after reload
        $_SESSION['marks_input_message'] = '<span class="text-green-600">Marks submitted successfully!</span>';
        header('Location: ' . $_SERVER['PHP_SELF'] . '#input_marks');
        exit();
    }
    if (!empty($_SESSION['marks_input_message'])) {
        $marks_input_message = $_SESSION['marks_input_message'];
        unset($_SESSION['marks_input_message']);
    }

    // Change password
    $password_change_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_id = $_SESSION['user_id'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_change_message = '<span class="text-red-600">All fields are required.</span>';
        } elseif ($new_password !== $confirm_password) {
            $password_change_message = '<span class="text-red-600">New passwords do not match.</span>';
        } else {
            // Fetch current password hash
            $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || !password_verify($current_password, $user['password'])) {
                $password_change_message = '<span class="text-red-600">Current password is incorrect.</span>';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE users SET password = :new_hash WHERE id = :id');
                $stmt->bindParam(':new_hash', $new_hash);
                $stmt->bindParam(':id', $user_id);
                if ($stmt->execute()) {
                    $password_change_message = '<span class="text-green-600">Password changed successfully!</span>';
                } else {
                    $password_change_message = '<span class="text-red-600">Failed to update password. Please try again.</span>';
                }
            }
        }
    }

    // Profile settings update
    $profile_update_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $profile_name = trim($_POST['profile_name'] ?? '');
        $profile_email = trim($_POST['profile_email'] ?? '');
        $user_id = $_SESSION['user_id'];
        if (empty($profile_name) && empty($profile_email)) {
            $profile_update_message = '<span class="text-red-600">Please provide a name or email to update.</span>';
        } else {
            $fields = [];
            $params = [':id' => $user_id];
            if (!empty($profile_name)) {
                $fields[] = 'name = :name';
                $params[':name'] = $profile_name;
            }
            if (!empty($profile_email)) {
                $fields[] = 'email = :email';
                $params[':email'] = $profile_email;
            }
            if ($fields) {
                $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
                $stmt = $db->prepare($sql);
                if ($stmt->execute($params)) {
                    if (!empty($profile_name)) $_SESSION['name'] = $profile_name;
                    $profile_update_message = '<span class="text-green-600">Profile updated successfully!</span>';
                } else {
                    $profile_update_message = '<span class="text-red-600">Failed to update profile. Please try again.</span>';
                }
            }
        }
    }

    // Notification preferences update (stubbed, use session as fallback)
    $notification_prefs_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notification_prefs'])) {
        $email_notif = isset($_POST['notif_email']) ? 1 : 0;
        $inapp_notif = isset($_POST['notif_inapp']) ? 1 : 0;
        // Try to save in DB, fallback to session
        try {
            $stmt = $db->prepare('UPDATE users SET notif_email = :email, notif_inapp = :inapp WHERE id = :id');
            $stmt->bindParam(':email', $email_notif);
            $stmt->bindParam(':inapp', $inapp_notif);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            if ($stmt->execute()) {
                $_SESSION['notif_email'] = $email_notif;
                $_SESSION['notif_inapp'] = $inapp_notif;
                $notification_prefs_message = '<span class="text-green-600">Preferences saved!</span>';
            } else {
                $notification_prefs_message = '<span class="text-red-600">Failed to save preferences.</span>';
            }
        } catch (Exception $e) {
            // Fallback to session only
            $_SESSION['notif_email'] = $email_notif;
            $_SESSION['notif_inapp'] = $inapp_notif;
            $notification_prefs_message = '<span class="text-green-600">Preferences saved (session only).</span>';
        }
    }
    $notif_email_checked = !empty($_SESSION['notif_email']) ? 'checked' : '';
    $notif_inapp_checked = !empty($_SESSION['notif_inapp']) ? 'checked' : '';

    // Bulk promote students
    $student_management_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_promote'])) {
        // Promote all students: increment grade if < 4, reset to 1 if 4 (or keep at 4)
        try {
            $stmt = $db->prepare("UPDATE users SET grade = CASE WHEN grade < 4 THEN grade + 1 ELSE 4 END WHERE role = 'student'");
            if ($stmt->execute()) {
                $student_management_message = '<span class="text-green-600">All students promoted to the next grade!</span>';
            } else {
                $student_management_message = '<span class="text-red-600">Failed to promote students.</span>';
            }
        } catch (Exception $e) {
            $student_management_message = '<span class="text-red-600">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
    }
    // Archive graduated students (Form 4)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_graduated'])) {
        try {
            // If users table has a status column, set status=archived; else, delete or stub
            $stmt = $db->prepare("UPDATE users SET status = 'archived' WHERE role = 'student' AND grade = 4");
            if ($stmt->execute()) {
                $student_management_message = '<span class="text-green-600">Graduated students archived!</span>';
            } else {
                $student_management_message = '<span class="text-red-600">Failed to archive graduated students.</span>';
            }
        } catch (Exception $e) {
            $student_management_message = '<span class="text-red-600">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
    }

    // System preferences update (save default grade/term for user)
    $system_prefs_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_system_prefs'])) {
        $default_grade = $_POST['default_grade'] ?? '';
        $default_term = $_POST['default_term'] ?? '';
        try {
            $stmt = $db->prepare('UPDATE users SET default_grade = :grade, default_term = :term WHERE id = :id');
            $stmt->bindParam(':grade', $default_grade);
            $stmt->bindParam(':term', $default_term);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            if ($stmt->execute()) {
                $_SESSION['default_grade'] = $default_grade;
                $_SESSION['default_term'] = $default_term;
                $system_prefs_message = '<span class="text-green-600">System preferences saved!</span>';
            } else {
                $system_prefs_message = '<span class="text-red-600">Failed to save preferences.</span>';
            }
        } catch (Exception $e) {
            // Fallback to session only
            $_SESSION['default_grade'] = $default_grade;
            $_SESSION['default_term'] = $default_term;
            $system_prefs_message = '<span class="text-green-600">Preferences saved (session only).</span>';
        }
    }
    $selected_grade = $_SESSION['default_grade'] ?? '';
    $selected_term = $_SESSION['default_term'] ?? '';

    // Bulk Marks Input (CSV Upload)
    $bulk_marks_message = '';
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bulk_marks']) && isset($_FILES['bulk_marks_csv'])
    ) {
        $grade = $_POST['bulk_grade'] ?? '';
        $term = $_POST['bulk_term'] ?? '';
        $file = $_FILES['bulk_marks_csv'];
        if ($file['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'csv') {
            $csv = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($csv); // e.g. [Examination Number, Math, English, Science]
            // Map subject names to IDs
            $subjectNameToId = [];
            foreach ($subjects as $subj) {
                $subjectNameToId[strtolower($subj['name'])] = $subj['id'];
            }
            $rowCount = 0;
            $errorCount = 0;
            while (($row = fgetcsv($csv)) !== false) {
                $exam_no = trim($row[0] ?? '');
                if (!$exam_no) { $errorCount++; continue; }
                // Find student by exam number and check grade/term
                $stmt = $db->prepare('SELECT id FROM users WHERE examination_number = :exam AND grade = :grade AND term = :term');
                $stmt->bindParam(':exam', $exam_no);
                $stmt->bindParam(':grade', $grade);
                $stmt->bindParam(':term', $term);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$student) { $errorCount++; continue; }
                $student_id = $student['id'];
                // For each subject column
                for ($i = 1; $i < count($header); $i++) {
                    $subject_name = strtolower(trim($header[$i]));
                    $subject_id = $subjectNameToId[$subject_name] ?? null;
                    $marks = trim($row[$i] ?? '');
                    if ($subject_id && is_numeric($marks)) {
                        // Check if mark exists
                        $checkStmt = $db->prepare('SELECT id FROM marks WHERE student_id = :student_id AND subject_id = :subject_id AND grade = :grade AND term = :term');
                        $checkStmt->bindParam(':student_id', $student_id);
                        $checkStmt->bindParam(':subject_id', $subject_id);
                        $checkStmt->bindParam(':grade', $grade);
                        $checkStmt->bindParam(':term', $term);
                        $checkStmt->execute();
                        $existingMark = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        if ($existingMark) {
                            $updateStmt = $db->prepare('UPDATE marks SET marks_obtained = :mark WHERE id = :id');
                            $updateStmt->bindParam(':mark', $marks);
                            $updateStmt->bindParam(':id', $existingMark['id']);
                            $updateStmt->execute();
                        } else {
                            $insertStmt = $db->prepare('INSERT INTO marks (student_id, subject_id, marks_obtained, grade, term) VALUES (:student_id, :subject_id, :mark, :grade, :term)');
                            $insertStmt->bindParam(':student_id', $student_id);
                            $insertStmt->bindParam(':subject_id', $subject_id);
                            $insertStmt->bindParam(':mark', $marks);
                            $insertStmt->bindParam(':grade', $grade);
                            $insertStmt->bindParam(':term', $term);
                            $insertStmt->execute();
                        }
                    }
                }
                $rowCount++;
            }
            fclose($csv);
            $bulk_marks_message = '<span class="text-green-700">Bulk marks upload complete. Students processed: ' . $rowCount . ', Errors: ' . $errorCount . '.</span>';
        } else {
            $bulk_marks_message = '<span class="text-red-600">Invalid file. Please upload a valid CSV file.</span>';
        }
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo "<p style='color:red;'>A database error occurred. Please try again later.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/heroicons@2.0.16/dist/heroicons.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-to-br from-blue-50 to-green-100 min-h-screen">
    <!-- Fixed Sidebar -->
    <aside class="fixed top-0 left-0 h-screen w-72 bg-gradient-to-b from-green-700 to-green-500 text-white p-6 shadow-2xl flex flex-col justify-between z-40 rounded-r-3xl">
        <div>
            <div class="flex items-center gap-3 mb-8">
                <span class="bg-white rounded-full p-2 shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6.75m0-6.75l6.16-3.422A12.042 12.042 0 0112 12.75a12.042 12.042 0 01-6.16-2.172L12 14z" />
                    </svg>
                </span>
                <span class="text-2xl font-extrabold tracking-wide">AcademiaPro</span>
            </div>
            <nav class="space-y-3">
                <a href="#" data-target="register_student" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-user-plus"></i>
                    Register Student
                </a>
                <a href="#" data-target="input_marks" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Input Marks
                </a>
                <a href="#" data-target="view_students" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-green-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-users"></i>
                    View Students
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

    <!-- Main Content (with left margin for sidebar) -->
    <div class="flex-1 p-8 ml-72">
        <!-- Welcome Header with Dynamic Greeting -->
        <header class="bg-white/90 shadow-lg rounded-xl mb-8 flex items-center gap-4 px-6 py-5 border border-green-200">
            <span class="text-3xl">
                <?= $greeting_icon ?>
            </span>
            <div>
                <h2 class="text-2xl font-bold text-green-800">
                    <?= $greeting ?>, <span class="text-green-600"><?= htmlspecialchars($name) ?></span>!
                </h2>
                <p class="text-gray-500 text-sm mt-1">Empowering academic excellence</p>
            </div>
        </header>
        <!-- Register Student -->
        <section id="register_student" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-4">Register New Student</h3>
            <form method="POST" class="space-y-4">
                <input type="text" name="name" placeholder="Full Name" class="w-full p-2 border rounded" required>
                <input type="text" name="examination_number" placeholder="Examination Number" class="w-full p-2 border rounded" required>
                <select name="grade" class="w-full p-2 border rounded" required>
                    <option value="">Select Grade</option>
                    <option value="1">Form One</option>
                    <option value="2">Form Two</option>
                    <option value="3">Form Three</option>
                    <option value="4">Form Four</option>
                </select>
                <select name="term" class="w-full p-2 border rounded" required>
                    <option value="">Select Term</option>
                    <option value="1">Term One</option>
                    <option value="2">Term Two</option>
                    <option value="3">Term Three</option>
                </select>
                <button type="submit" name="register_student" class="bg-blue-500 text-white px-4 py-2 rounded">Register</button>
            </form>
        </section>
        <!-- Input Marks -->
        <section id="input_marks" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-4">Input Marks</h3>
            <?php if (!empty($marks_input_message)) echo '<div class="mb-4">' . $marks_input_message . '</div>'; ?>
            <form method="POST" class="space-y-4 max-w-xl mx-auto bg-white p-6 rounded shadow" id="marks_input_form">
                <!-- Select Form (Grade) -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Select Form</label>
                    <select id="form_selector" class="w-full p-2 border rounded" required>
                        <option value="">-- Select Form --</option>
                        <?php foreach ($studentsGrouped as $grade => $terms): ?>
                            <option value="form_<?= $grade ?>">Form <?= $grade ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Student Dropdown (Filtered by Form) -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Select Student</label>
                    <select name="student_id" id="student_selector" class="w-full p-2 border rounded" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($studentsGrouped as $grade => $terms): ?>
                            <?php foreach ($terms as $term => $list): ?>
                                <?php foreach ($list as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-form="form_<?= $grade ?>">
                                        <?= htmlspecialchars($s['name']) ?> - Form <?= $grade ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Input for Marks -->
                <h3 class="text-xl font-semibold mb-2">Enter The Grades</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($subjects as $sub): ?>
                        <div>
                            <label class="block text-gray-700 font-medium"><?= htmlspecialchars($sub['name']) ?></label>
                            <input type="number" name="marks[<?= $sub['id'] ?>]" class="w-full p-2 border rounded mark-input" required data-subject="<?= htmlspecialchars($sub['name']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Submit Button -->
                <button type="submit" name="input_marks" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded w-full">
                    Submit Marks
                </button>
            </form>
        </section>
        <!-- View Students -->
        <section id="view_students" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-4">View Students</h3>
            <!-- Filters and Search -->
            <div class="flex flex-col md:flex-row md:items-end gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Filter by Grade</label>
                    <select id="filter_grade" class="w-full p-2 border rounded">
                        <option value="">All Grades</option>
                        <option value="1">Form One</option>
                        <option value="2">Form Two</option>
                        <option value="3">Form Three</option>
                        <option value="4">Form Four</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Filter by Term</label>
                    <select id="filter_term" class="w-full p-2 border rounded">
                        <option value="">All Terms</option>
                        <option value="1">Term One</option>
                        <option value="2">Term Two</option>
                        <option value="3">Term Three</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-gray-700 font-semibold mb-1">Search</label>
                    <input type="text" id="student_search" class="w-full p-2 border rounded" placeholder="Search by name or exam number...">
                </div>
            </div>
            <!-- Bulk Print Button -->
            <div class="mb-4">
                <form method="GET" action="../ExportPDF/export_bulk_report_cards.php" target="_blank" class="inline">
                    <input type="hidden" name="grade" id="bulk_print_grade" value="">
                    <input type="hidden" name="term" id="bulk_print_term" value="">
                    <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded shadow hover:bg-blue-800"><i class="fa-solid fa-print"></i> Bulk Print Report Cards</button>
                </form>
            </div>
            <div id="students_table_container">
                <table class="min-w-full border text-sm" id="students_table">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-2 border text-left">#</th>
                            <th class="px-4 py-2 border text-left">Full Name</th>
                            <th class="px-4 py-2 border text-left">Examination Number</th>
                            <th class="px-4 py-2 border text-left">Grade</th>
                            <th class="px-4 py-2 border text-left">Term</th>
                            <th class="px-4 py-2 border text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="students_table_body">
                        <?php 
                        $rowNum = 1; 
                        $gradeLabels = [1 => 'Form One', 2 => 'Form Two', 3 => 'Form Three', 4 => 'Form Four'];
                        $termLabels = [1 => 'Term One', 2 => 'Term Two', 3 => 'Term Three'];
                        foreach ($students as $stu): ?>
                            <?php
                            $isInvalidGrade = !isset($gradeLabels[(int)$stu['grade']]);
                            $isInvalidTerm = !isset($termLabels[(int)$stu['term']]);
                            $invalidClass = ($isInvalidGrade || $isInvalidTerm) ? 'bg-red-100' : '';
                            ?>
                            <tr class="hover:bg-gray-100 <?= $invalidClass ?>" data-grade="<?= (int)$stu['grade'] ?>" data-term="<?= (int)$stu['term'] ?>">
                                <td class="px-4 py-2 border"> <?= $rowNum++ ?> </td>
                                <td class="px-4 py-2 border"> <?= htmlspecialchars($stu['name']) ?> </td>
                                <td class="px-4 py-2 border"> <?= htmlspecialchars($stu['examination_number']) ?> </td>
                                <td class="px-4 py-2 border"> <?= $gradeLabels[(int)$stu['grade']] ?? $gradeLabels[1] ?> </td>
                                <td class="px-4 py-2 border"> <?= $termLabels[(int)$stu['term']] ?? $termLabels[1] ?> </td>
                                <td class="px-4 py-2 border">
                                    <form method="GET" action="../ExportPDF/export_report_card.php" target="_blank" class="inline">
                                        <input type="hidden" name="student_id" value="<?= $stu['id'] ?>">
                                        <input type="hidden" name="grade" value="<?= $stu['grade'] ?>">
                                        <input type="hidden" name="term" value="<?= $stu['term'] ?>">
                                        <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded shadow hover:bg-green-700"><i class="fa-solid fa-print"></i> Print</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <!-- Settings Section -->
        <section id="settings" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-4">Settings</h3>
            <div class="space-y-6">
                <!-- Change Password -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-key"></i> Change Password</h4>
                    <form method="POST" action="#">
                        <input type="password" name="current_password" placeholder="Current Password" class="w-full p-2 border rounded mb-2" required>
                        <input type="password" name="new_password" placeholder="New Password" class="w-full p-2 border rounded mb-2" required>
                        <input type="password" name="confirm_password" placeholder="Confirm New Password" class="w-full p-2 border rounded mb-2" required>
                        <button type="submit" name="change_password" class="bg-blue-500 text-white px-4 py-2 rounded">Change Password</button>
                    </form>
                    <?php if (!empty($password_change_message)) echo '<div class="mt-2">' . $password_change_message . '</div>'; ?>
                </div>
                <!-- Profile Settings -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-user"></i> Profile Settings</h4>
                    <form method="POST" action="#">
                        <input type="text" name="profile_name" placeholder="Full Name" class="w-full p-2 border rounded mb-2">
                        <input type="email" name="profile_email" placeholder="Email Address" class="w-full p-2 border rounded mb-2">
                        <button type="submit" name="update_profile" class="bg-green-600 text-white px-4 py-2 rounded">Update Profile</button>
                    </form>
                    <?php if (!empty($profile_update_message)) echo '<div class="mt-2">' . $profile_update_message . '</div>'; ?>
                </div>
                <!-- Theme Toggle -->
                <div class="bg-gray-100 p-4 rounded-xl shadow flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-moon"></i>
                        <h4 class="font-bold">Dark Mode</h4>
                    </div>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="themeToggle" class="sr-only">
                        <span class="w-10 h-6 bg-gray-300 rounded-full shadow-inner flex items-center transition-all duration-300">
                            <span class="dot w-5 h-5 bg-white rounded-full shadow transform transition-all duration-300"></span>
                        </span>
                    </label>
                </div>
                <!-- Notification Preferences -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-bell"></i> Notification Preferences</h4>
                    <form method="POST" action="#">
                        <label class="flex items-center gap-2 mb-2">
                            <input type="checkbox" class="accent-green-600" name="notif_email" <?= $notif_email_checked ?>>
                            Email notifications
                        </label>
                        <label class="flex items-center gap-2 mb-2">
                            <input type="checkbox" class="accent-green-600" name="notif_inapp" <?= $notif_inapp_checked ?>>
                            In-app notifications
                        </label>
                        <button type="submit" name="save_notification_prefs" class="bg-blue-500 text-white px-4 py-2 rounded">Save Preferences</button>
                    </form>
                    <?php if (!empty($notification_prefs_message)) echo '<div class="mt-2">' . $notification_prefs_message . '</div>'; ?>
                </div>
                <!-- Bulk Marks Input -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-file-upload"></i> Bulk Marks Input</h4>
                    <p class="mb-2 text-sm text-gray-700">Upload a CSV file to enter marks for multiple students at once. Each row should have the examination number and marks for all subjects. <a href="#" class="text-blue-600 underline" onclick="downloadSampleCSV(event)">Download sample template</a>.</p>
                    <?php if (!empty($bulk_marks_message)) echo '<div class="mb-2">' . $bulk_marks_message . '</div>'; ?>
                    <form method="POST" action="#" enctype="multipart/form-data" class="space-y-2">
                        <div class="flex gap-2">
                            <select name="bulk_grade" class="p-2 border rounded" required>
                                <option value="">Select Grade</option>
                                <option value="1">Form One</option>
                                <option value="2">Form Two</option>
                                <option value="3">Form Three</option>
                                <option value="4">Form Four</option>
                            </select>
                            <select name="bulk_term" class="p-2 border rounded" required>
                                <option value="">Select Term</option>
                                <option value="1">Term One</option>
                                <option value="2">Term Two</option>
                                <option value="3">Term Three</option>
                            </select>
                        </div>
                        <input type="file" name="bulk_marks_csv" accept=".csv" class="w-full p-2 border rounded mb-2" required>
                        <button type="submit" name="upload_bulk_marks" class="bg-green-600 text-white px-4 py-2 rounded">Upload CSV</button>
                    </form>
                </div>
                <!-- System Preferences -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-sliders"></i> System Preferences</h4>
                    <?php if (!empty($system_prefs_message)) echo '<div class="mb-2">' . $system_prefs_message . '</div>'; ?>
                    <form method="POST" action="#">
                        <label class="block mb-2">Default Grade for Mark Entry
                            <select class="w-full p-2 border rounded mt-1" name="default_grade">
                                <option value="1" <?= $selected_grade == '1' ? 'selected' : '' ?>>Form One</option>
                                <option value="2" <?= $selected_grade == '2' ? 'selected' : '' ?>>Form Two</option>
                                <option value="3" <?= $selected_grade == '3' ? 'selected' : '' ?>>Form Three</option>
                                <option value="4" <?= $selected_grade == '4' ? 'selected' : '' ?>>Form Four</option>
                            </select>
                        </label>
                        <label class="block mb-2">Default Term
                            <select class="w-full p-2 border rounded mt-1" name="default_term">
                                <option value="1" <?= $selected_term == '1' ? 'selected' : '' ?>>Term One</option>
                                <option value="2" <?= $selected_term == '2' ? 'selected' : '' ?>>Term Two</option>
                                <option value="3" <?= $selected_term == '3' ? 'selected' : '' ?>>Term Three</option>
                            </select>
                        </label>
                        <button type="submit" name="save_system_prefs" class="bg-blue-500 text-white px-4 py-2 rounded">Save Preferences</button>
                    </form>
                </div>
                <!-- Export Data -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-file-export"></i> Export Data</h4>
                    <?php if (!empty(
                        $_SESSION['export_message'])) { echo '<div class="mb-2">' . $_SESSION['export_message'] . '</div>'; unset($_SESSION['export_message']); } ?>
                    <div class="flex gap-3">
                        <form method="POST" action="export_students_csv.php" target="_blank" class="flex-1">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                        </form>
                        <form method="POST" action="export_students_pdf.php" target="_blank" class="flex-1">
                            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded w-full"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
                        </form>
                    </div>
                </div>
                <!-- Help & Support -->
                <div class="bg-gray-100 p-4 rounded-xl shadow">
                    <h4 class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-circle-question"></i> Help & Support</h4>
                    <ul class="list-disc ml-6 text-blue-700">
                        <li><a href="#" class="hover:underline">User Guide</a></li>
                        <li><a href="#" class="hover:underline">FAQs</a></li>
                        <li><a href="#" class="hover:underline">Contact Support</a></li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
    <script>
        // Sidebar link behavior
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const target = link.getAttribute('data-target');
                document.querySelectorAll('.content-section').forEach(section => section.classList.add('hidden'));
                document.getElementById(target)?.classList.remove('hidden');
                // If bulk input marks section is shown, trigger fetch
                if (target === 'bulk_input_marks') {
                    document.getElementById('bulk_marks_table_container').innerHTML = 'Please select a grade and term to load students for bulk marks entry.';
                    document.getElementById('bulk_input_marks_form').classList.add('hidden');
                }
            });
        });
        // Show first section by default
        window.addEventListener('DOMContentLoaded', () => {
            const firstSection = document.querySelector('.content-section');
            if (firstSection) firstSection.classList.remove('hidden');

           // View Students: Show first form by default
           const formLists = document.querySelectorAll('.form-list');
           if (formLists.length > 0) {
               formLists[0].style.display = '';
               const formTabs = document.querySelectorAll('.form-tab');
               if (formTabs.length > 0) formTabs[0].classList.add('bg-blue-600', 'text-white');
           }

           // THEME: Apply saved theme on load
           const savedTheme = localStorage.getItem('theme');
           if (savedTheme === 'dark') {
               document.body.classList.add('bg-gray-900', 'text-white');
               document.body.classList.remove('bg-gradient-to-br', 'from-blue-50', 'to-green-100');
               document.getElementById('themeToggle').checked = true;
           } else {
               document.body.classList.remove('bg-gray-900', 'text-white');
               document.body.classList.add('bg-gradient-to-br', 'from-blue-50', 'to-green-100');
               document.getElementById('themeToggle').checked = false;
           }
        });

        // Theme toggle
        document.getElementById('themeToggle')?.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('bg-gray-900', 'text-white');
                document.body.classList.remove('bg-gradient-to-br', 'from-blue-50', 'to-green-100');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('bg-gray-900', 'text-white');
                document.body.classList.add('bg-gradient-to-br', 'from-blue-50', 'to-green-100');
                localStorage.setItem('theme', 'light');
            }
        });
    </script>
    <script>
        // Filter students by selected form
        document.getElementById('form_selector').addEventListener('change', function() {
            const selectedForm = this.value;
            const studentOptions = document.querySelectorAll('#student_selector option');
            studentOptions.forEach(option => {
                if (!option.value) {
                    option.hidden = false; // Keep the default option visible
                    return;
                }
                if (option.getAttribute('data-form') === selectedForm) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                }
            });
            // Reset student selector to default
            document.getElementById('student_selector').value = '';
        });
        // Student table filter and search
        function filterStudentsTable() {
            const grade = document.getElementById('filter_grade').value;
            const term = document.getElementById('filter_term').value;
            const search = document.getElementById('student_search').value.toLowerCase();
            const rows = document.querySelectorAll('#students_table_body tr');
            let visibleCount = 0;
            rows.forEach((row, idx) => {
                const rowGrade = row.getAttribute('data-grade');
                const rowTerm = row.getAttribute('data-term');
                const name = row.children[1].textContent.toLowerCase();
                const exam = row.children[2].textContent.toLowerCase();
                let show = true;
                if (grade && rowGrade !== grade) show = false;
                if (term && rowTerm !== term) show = false;
                if (search && !(name.includes(search) || exam.includes(search))) show = false;
                row.style.display = show ? '' : 'none';
                if (show) {
                    row.children[0].textContent = ++visibleCount;
                }
            });
        }
        document.getElementById('filter_grade').addEventListener('change', filterStudentsTable);
        document.getElementById('filter_term').addEventListener('change', filterStudentsTable);
        document.getElementById('student_search').addEventListener('input', filterStudentsTable);
        // Ensure table is visible on load
        window.addEventListener('DOMContentLoaded', filterStudentsTable);
    </script>
    <script>
        // Remove JS for marks/grades preview
    </script>
    <script>
        // Download sample CSV template for bulk marks input
        function downloadSampleCSV(e) {
            e.preventDefault();
            // Dynamically build header from PHP $subjects
            const header = ['Examination Number'<?php foreach ($subjects as $sub): ?>, '<?= addslashes($sub['name']) ?>'<?php endforeach; ?>];
            const sampleRow = ['12345'<?php foreach ($subjects as $sub): ?>, '85'<?php endforeach; ?>];
            const csvContent = header.join(',') + '\n' + sampleRow.join(',');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'bulk_marks_sample.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
    <script>
        // Sync bulk print hidden fields with filter dropdowns
        function syncBulkPrintFields() {
            document.getElementById('bulk_print_grade').value = document.getElementById('filter_grade').value;
            document.getElementById('bulk_print_term').value = document.getElementById('filter_term').value;
        }
        document.getElementById('filter_grade').addEventListener('change', syncBulkPrintFields);
        document.getElementById('filter_term').addEventListener('change', syncBulkPrintFields);
        window.addEventListener('DOMContentLoaded', syncBulkPrintFields);
    </script>
</body>