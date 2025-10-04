<?php
require_once __DIR__ . '/../../config/Database.php';
use App\Config\Database;

class RoleController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Switch user role (Admin only)
     */
    public function switchUserRole($admin_id, $user_id, $new_role) {
        // Check if admin has permission to switch roles
        $admin_stmt = $this->db->prepare("SELECT role FROM users WHERE id = :admin_id AND role = 'admin'");
        $admin_stmt->execute([':admin_id' => $admin_id]);
        $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }

        // Validate role
        $valid_roles = ['admin', 'teacher', 'student'];
        if (!in_array($new_role, $valid_roles)) {
            return ['success' => false, 'message' => 'Invalid role'];
        }

        // Update user role
        $update_stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :user_id");
        if ($update_stmt->execute([':role' => $new_role, ':user_id' => $user_id])) {
            return ['success' => true, 'message' => 'Role updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update role'];
        }
    }

    /**
     * Assign role to user (Admin only)
     */
    public function assignRole($admin_id, $user_id, $role) {
        return $this->switchUserRole($admin_id, $user_id, $role);
    }

    /**
     * Get user roles and permissions
     */
    public function getUserPermissions($user_id) {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $permissions = [
            'admin' => [
                'manage_users' => true,
                'manage_teachers' => true,
                'manage_students' => true,
                'view_all_data' => true,
                'assign_roles' => true,
                'system_settings' => true,
                'input_marks' => true,
                'view_student_data' => true,
                'generate_reports' => true
            ],
            'teacher' => [
                'manage_students' => true,
                'input_marks' => true,
                'view_student_data' => true,
                'generate_reports' => true,
                'register_students' => true
            ],
            'student' => [
                'view_own_data' => true,
                'view_report_card' => true
            ]
        ];

        return [
            'role' => $user['role'],
            'permissions' => $permissions[$user['role']] ?? []
        ];
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($user_id, $permission) {
        $user_permissions = $this->getUserPermissions($user_id);
        return $user_permissions && in_array($permission, array_keys($user_permissions['permissions'])) 
               && $user_permissions['permissions'][$permission];
    }

    /**
     * Get all users with their roles (Admin only)
     */
    public function getAllUsersWithRoles($admin_id) {
        // Check if admin
        $admin_stmt = $this->db->prepare("SELECT role FROM users WHERE id = :admin_id AND role = 'admin'");
        $admin_stmt->execute([':admin_id' => $admin_id]);
        $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }

        $stmt = $this->db->prepare("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $users];
    }

    /**
     * Get role statistics (Admin only)
     */
    public function getRoleStatistics($admin_id) {
        // Check if admin
        $admin_stmt = $this->db->prepare("SELECT role FROM users WHERE id = :admin_id AND role = 'admin'");
        $admin_stmt->execute([':admin_id' => $admin_id]);
        $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }

        $stats = [];
        
        // Count by role
        $roles = ['admin', 'teacher', 'student'];
        foreach ($roles as $role) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE role = :role AND status = 'active'");
            $stmt->execute([':role' => $role]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats[$role] = $result['count'];
        }

        // Total users
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total'] = $result['total'];

        return ['success' => true, 'data' => $stats];
    }
}
?>
