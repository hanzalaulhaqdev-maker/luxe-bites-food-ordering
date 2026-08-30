<?php
/**
 * User Model Class
 * Handles user registration, authentication, and profile management
 */
require_once __DIR__ . '/Database.php';

class User {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new user
     */
    public function register(string $name, string $email, string $phone, string $password): bool {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, phone, password) 
            VALUES (:name, :email, :phone, :password)
        ");
        
        return $stmt->execute([
            ':name' => sanitize($name),
            ':email' => sanitizeEmail($email),
            ':phone' => sanitize($phone),
            ':password' => $hashedPassword
        ]);
    }
    
    /**
     * Authenticate user
     */
    public function login(string $email, string $password): ?array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, password 
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => sanitizeEmail($email)]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        
        return null;
    }
    
    /**
     * Get user by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, created_at 
            FROM users 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get user by email
     */
    public function getByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, created_at 
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => sanitizeEmail($email)]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute([':email' => sanitizeEmail($email)]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Update user profile
     */
    public function update(int $id, array $data): bool {
        $allowedFields = ['name', 'email', 'phone'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = sanitize($data[$field]);
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Change password
     */
    public function changePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute([':password' => $hashed, ':id' => $id]);
    }
    
    /**
     * Get all users (for admin)
     */
    public function getAll(int $limit = 100, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Count total users
     */
    public function count(): int {
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
    
    /**
     * Delete user
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
