<?php
/**
 * Admin Model Class
 * Handles admin authentication and management
 */
require_once __DIR__ . '/Database.php';

class Admin {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate admin
     */
    public function login(string $username, string $password): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, password, name, email 
            FROM admin 
            WHERE username = :username 
            LIMIT 1
        ");
        $stmt->execute([':username' => sanitize($username)]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Update last login
            $updateStmt = $this->db->prepare("
                UPDATE admin SET last_login = NOW() WHERE id = :id
            ");
            $updateStmt->execute([':id' => $admin['id']]);
            
            unset($admin['password']);
            return $admin;
        }
        
        return null;
    }
    
    /**
     * Get admin by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, name, email, last_login, created_at 
            FROM admin 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Create new admin
     */
    public function create(string $username, string $password, string $name, string $email): bool {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("
            INSERT INTO admin (username, password, name, email)
            VALUES (:username, :password, :name, :email)
        ");
        
        return $stmt->execute([
            ':username' => sanitize($username),
            ':password' => $hashedPassword,
            ':name' => sanitize($name),
            ':email' => sanitizeEmail($email)
        ]);
    }
    
    /**
     * Update admin profile
     */
    public function update(int $id, array $data): bool {
        $allowed = ['username', 'name', 'email'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = ($field === 'email') 
                    ? sanitizeEmail($data[$field]) 
                    : sanitize($data[$field]);
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE admin SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Change admin password
     */
    public function changePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE admin SET password = :password WHERE id = :id");
        return $stmt->execute([':password' => $hashed, ':id' => $id]);
    }
    
    /**
     * Get all admins
     */
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT id, username, name, email, last_login, created_at 
            FROM admin 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Delete admin
     */
    public function delete(int $id): bool {
        // Prevent deleting the last admin
        $count = $this->db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
        if ($count <= 1) {
            throw new Exception("Cannot delete the last admin account");
        }
        
        $stmt = $this->db->prepare("DELETE FROM admin WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array {
        $today = date('Y-m-d');
        
        $totalOrders = $this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $todayOrders = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = :today");
        $todayOrders->execute([':today' => $today]);
        $todayOrders = $todayOrders->fetchColumn();
        
        $pendingOrders = $this->db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        $totalRevenue = floatval($this->db->query("
            SELECT COALESCE(SUM(final_total), 0) FROM orders WHERE status != 'cancelled' AND status != 'rejected'
        ")->fetchColumn());
        
        $todayRevenue = $this->db->prepare("
            SELECT COALESCE(SUM(final_total), 0) FROM orders 
            WHERE DATE(created_at) = :today AND status != 'cancelled' AND status != 'rejected'
        ");
        $todayRevenue->execute([':today' => $today]);
        $todayRevenue = floatval($todayRevenue->fetchColumn());
        
        $totalCustomers = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalItems = $this->db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
        $activeRiders = $this->db->query("SELECT COUNT(*) FROM riders WHERE is_active = TRUE")->fetchColumn();
        
        return [
            'total_orders' => $totalOrders,
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'total_revenue' => $totalRevenue,
            'today_revenue' => $todayRevenue,
            'total_customers' => $totalCustomers,
            'total_items' => $totalItems,
            'active_riders' => $activeRiders
        ];
    }
    
    /**
     * Get recent orders for dashboard
     */
    public function getRecentOrders(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT o.*, r.name as rider_name
            FROM orders o
            LEFT JOIN riders r ON o.rider_id = r.id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
