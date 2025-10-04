<?php
session_start();

// Redirect to login if user is not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../Auth/login.php');
    exit();
}

require_once __DIR__ . '/../../Config/Database.php';
use App\Config\Database;

try {
    $db = (new Database())->getConnection();
    $admin_id = $_SESSION['user_id'];
    $name = $_SESSION['name'] ?? 'Administrator';

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

    // Get system statistics
    $stats = [];
    
    // Total students
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'");
    $stmt->execute();
    $stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total teachers
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'");
    $stmt->execute();
    $stats['total_teachers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total subjects
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM subjects");
    $stmt->execute();
    $stats['total_subjects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total marks entered
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM marks");
    $stmt->execute();
    $stats['total_marks'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get all users for management
    $stmt = $db->prepare("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all subjects
    $stmt = $db->prepare("SELECT * FROM subjects ORDER BY name");
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle user management actions
    $user_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_user'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = trim($_POST['password']);
            
            if (!empty($name) && !empty($email) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, 'active')");
                if ($stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':role' => $role
                ])) {
                    $user_message = '<span class="text-green-600">User added successfully!</span>';
                } else {
                    $user_message = '<span class="text-red-600">Failed to add user.</span>';
                }
            }
        } elseif (isset($_POST['update_user_role'])) {
            $user_id = $_POST['user_id'];
            $new_role = $_POST['new_role'];
            $stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
            if ($stmt->execute([':role' => $new_role, ':id' => $user_id])) {
                $user_message = '<span class="text-green-600">User role updated successfully!</span>';
            } else {
                $user_message = '<span class="text-red-600">Failed to update user role.</span>';
            }
        } elseif (isset($_POST['toggle_user_status'])) {
            $user_id = $_POST['user_id'];
            $current_status = $_POST['current_status'];
            $new_status = $current_status === 'active' ? 'inactive' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
            if ($stmt->execute([':status' => $new_status, ':id' => $user_id])) {
                $user_message = '<span class="text-green-600">User status updated successfully!</span>';
            } else {
                $user_message = '<span class="text-red-600">Failed to update user status.</span>';
            }
        }
        
        // Refresh users list
        $stmt = $db->prepare("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle subject management
    $subject_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_subject'])) {
            $subject_name = trim($_POST['subject_name']);
            if (!empty($subject_name)) {
                $stmt = $db->prepare("INSERT INTO subjects (name) VALUES (:name)");
                if ($stmt->execute([':name' => $subject_name])) {
                    $subject_message = '<span class="text-green-600">Subject added successfully!</span>';
                } else {
                    $subject_message = '<span class="text-red-600">Failed to add subject.</span>';
                }
            }
        } elseif (isset($_POST['delete_subject'])) {
            $subject_id = $_POST['subject_id'];
            $stmt = $db->prepare("DELETE FROM subjects WHERE id = :id");
            if ($stmt->execute([':id' => $subject_id])) {
                $subject_message = '<span class="text-green-600">Subject deleted successfully!</span>';
            } else {
                $subject_message = '<span class="text-red-600">Failed to delete subject.</span>';
            }
        }
        
        // Refresh subjects list
        $stmt = $db->prepare("SELECT * FROM subjects ORDER BY name");
        $stmt->execute();
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-br from-purple-50 to-indigo-100 min-h-screen">
    <!-- Fixed Sidebar -->
    <aside class="fixed top-0 left-0 h-screen w-72 bg-gradient-to-b from-purple-700 to-purple-500 text-white p-6 shadow-2xl flex flex-col justify-between z-40 rounded-r-3xl">
        <div>
            <div class="flex items-center gap-3 mb-8">
                <span class="bg-white rounded-full p-2 shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
                <span class="text-2xl font-extrabold tracking-wide">Admin Panel</span>
            </div>
            <nav class="space-y-3">
                <a href="#" data-target="dashboard_overview" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-purple-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-chart-pie"></i>
                    Dashboard Overview
                </a>
                <a href="#" data-target="user_management" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-purple-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-users-cog"></i>
                    User Management
                </a>
                <a href="switch_role.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-purple-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-user-tag"></i>
                    Role Management
                </a>
                <a href="#" data-target="subject_management" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-purple-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-book"></i>
                    Subject Management
                </a>
                <a href="#" data-target="system_settings" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-purple-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-cogs"></i>
                    System Settings
                </a>
                <a href="#" data-target="reports" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-purple-800 hover:text-yellow-300 transition text-lg font-medium">
                    <i class="fa-solid fa-chart-bar"></i>
                    Reports & Analytics
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
        <header class="bg-white/90 shadow-lg rounded-xl mb-8 flex items-center gap-4 px-6 py-5 border border-purple-200">
            <span class="text-3xl">
                <?= $greeting_icon ?>
            </span>
            <div>
                <h2 class="text-2xl font-bold text-purple-800">
                    <?= $greeting ?>, <span class="text-purple-600"><?= htmlspecialchars($name) ?></span>!
                </h2>
                <p class="text-gray-500 text-sm mt-1">System Administrator Dashboard</p>
            </div>
        </header>

        <!-- Dashboard Overview -->
        <section id="dashboard_overview" class="content-section">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">System Overview</h3>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-3xl font-bold text-blue-600"><?= $stats['total_students'] ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Teachers</p>
                            <p class="text-3xl font-bold text-green-600"><?= $stats['total_teachers'] ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Subjects</p>
                            <p class="text-3xl font-bold text-purple-600"><?= $stats['total_subjects'] ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-book text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Marks</p>
                            <p class="text-3xl font-bold text-orange-600"><?= $stats['total_marks'] ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Quick Actions</h4>
                    <div class="space-y-3">
                        <button onclick="showSection('user_management')" class="w-full text-left p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                            <i class="fas fa-user-plus text-blue-600 mr-3"></i>Add New User
                        </button>
                        <button onclick="showSection('subject_management')" class="w-full text-left p-3 bg-green-50 hover:bg-green-100 rounded-lg transition">
                            <i class="fas fa-book-medical text-green-600 mr-3"></i>Add New Subject
                        </button>
                        <button onclick="showSection('reports')" class="w-full text-left p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                            <i class="fas fa-chart-bar text-purple-600 mr-3"></i>View Reports
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Recent Activity</h4>
                    <div class="space-y-3">
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                            <span class="text-sm">System running normally</span>
                        </div>
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <i class="fas fa-users text-green-500 mr-3"></i>
                            <span class="text-sm"><?= $stats['total_students'] ?> active students</span>
                        </div>
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <i class="fas fa-chalkboard-teacher text-purple-500 mr-3"></i>
                            <span class="text-sm"><?= $stats['total_teachers'] ?> active teachers</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- User Management -->
        <section id="user_management" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">User Management</h3>
            
            <?php if (!empty($user_message)): ?>
                <div class="mb-4 p-3 rounded-lg"><?= $user_message ?></div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h4 class="text-lg font-semibold mb-4 text-gray-800">Add New User</h4>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="text" name="name" placeholder="Full Name" class="p-3 border rounded-lg focus:ring-2 focus:ring-purple-500" required>
                    <input type="email" name="email" placeholder="Email Address" class="p-3 border rounded-lg focus:ring-2 focus:ring-purple-500" required>
                    <select name="role" class="p-3 border rounded-lg focus:ring-2 focus:ring-purple-500" required>
                        <option value="">Select Role</option>
                        <option value="admin">Administrator</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                    <input type="password" name="password" placeholder="Password" class="p-3 border rounded-lg focus:ring-2 focus:ring-purple-500" required>
                    <button type="submit" name="add_user" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-user-plus mr-2"></i>Add User
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b">
                    <h4 class="text-lg font-semibold text-gray-800">All Users</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                               ($user['role'] === 'teacher' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <!-- Role Change -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="new_role" onchange="this.form.submit()" class="text-xs border rounded px-2 py-1">
                                                <option value="">Change Role</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                            </select>
                                            <input type="hidden" name="update_user_role" value="1">
                                        </form>
                                        
                                        <!-- Status Toggle -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $user['status'] ?>">
                                            <button type="submit" name="toggle_user_status" class="text-xs px-2 py-1 rounded 
                                                <?= $user['status'] === 'active' ? 'bg-red-100 text-red-800 hover:bg-red-200' : 'bg-green-100 text-green-800 hover:bg-green-200' ?>">
                                                <?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Subject Management -->
        <section id="subject_management" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">Subject Management</h3>
            
            <?php if (!empty($subject_message)): ?>
                <div class="mb-4 p-3 rounded-lg"><?= $subject_message ?></div>
            <?php endif; ?>

            <!-- Add Subject Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h4 class="text-lg font-semibold mb-4 text-gray-800">Add New Subject</h4>
                <form method="POST" class="flex gap-4">
                    <input type="text" name="subject_name" placeholder="Subject Name" class="flex-1 p-3 border rounded-lg focus:ring-2 focus:ring-purple-500" required>
                    <button type="submit" name="add_subject" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Add Subject
                    </button>
                </form>
            </div>

            <!-- Subjects Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b">
                    <h4 class="text-lg font-semibold text-gray-800">All Subjects</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $subject['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($subject['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M d, Y', strtotime($subject['created_at'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                            <button type="submit" name="delete_subject" class="text-red-600 hover:text-red-900" 
                                                    onclick="return confirm('Are you sure you want to delete this subject?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- System Settings -->
        <section id="system_settings" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">System Settings</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Database Backup</h4>
                    <p class="text-gray-600 mb-4">Create a backup of the entire database</p>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-download mr-2"></i>Create Backup
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">System Maintenance</h4>
                    <p class="text-gray-600 mb-4">Perform system maintenance tasks</p>
                    <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-tools mr-2"></i>Run Maintenance
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Email Settings</h4>
                    <p class="text-gray-600 mb-4">Configure email notifications</p>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-envelope mr-2"></i>Configure Email
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Security Settings</h4>
                    <p class="text-gray-600 mb-4">Manage security and access controls</p>
                    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-shield-alt mr-2"></i>Security Settings
                    </button>
                </div>
            </div>
        </section>

        <!-- Reports & Analytics -->
        <section id="reports" class="content-section hidden">
            <h3 class="text-2xl font-semibold mb-6 text-gray-800">Reports & Analytics</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Student Performance</h4>
                    <p class="text-gray-600 mb-4">Generate student performance reports</p>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-chart-line mr-2"></i>Generate Report
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">System Usage</h4>
                    <p class="text-gray-600 mb-4">View system usage statistics</p>
                    <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-chart-pie mr-2"></i>View Statistics
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Export Data</h4>
                    <p class="text-gray-600 mb-4">Export system data to various formats</p>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-file-export mr-2"></i>Export Data
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Audit Log</h4>
                    <p class="text-gray-600 mb-4">View system activity logs</p>
                    <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-list-alt mr-2"></i>View Logs
                    </button>
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
</body>
</html>
