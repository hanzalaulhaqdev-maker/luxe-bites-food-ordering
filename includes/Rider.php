<?php
/**
 * Rider Model Class
 * Handles delivery riders CRUD operations
 */
require_once __DIR__ . '/Database.php';

class Rider {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new rider
     */
    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO riders (name, phone, email, image, is_active)
            VALUES (:name, :phone, :email, :image, :is_active)
        ");
        
        return $stmt->execute([
            ':name' => sanitize($data['name']),
            ':phone' => sanitize($data['phone']),
            ':email' => !empty($data['email']) ? sanitizeEmail($data['email']) : null,
            ':image' => sanitize($data['image'] ?? ''),
            ':is_active' => !empty($data['is_active']) ? 1 : 0
        ]);
    }
    
    /**
     * Get all riders
     */
    public function getAll(bool $activeOnly = false): array {
        $sql = "SELECT * FROM riders";
        if ($activeOnly) {
            $sql .= " WHERE is_active = TRUE";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get rider by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM riders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Update rider
     */
    public function update(int $id, array $data): bool {
        $allowed = ['name', 'phone', 'email', 'image', 'is_active'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                if ($field === 'is_active') {
                    $params[":$field"] = $data[$field] ? 1 : 0;
                } elseif ($field === 'email') {
                    $params[":$field"] = !empty($data[$field]) ? sanitizeEmail($data[$field]) : null;
                } else {
                    $params[":$field"] = sanitize($data[$field]);
                }
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE riders SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete rider
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM riders WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Count active riders
     */
    public function countActive(): int {
        return $this->db->query("SELECT COUNT(*) FROM riders WHERE is_active = TRUE")->fetchColumn();
    }
    
    /**
     * Get rider's current delivery count
     */
    public function getDeliveryCount(int $riderId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE rider_id = :rider_id 
              AND status IN ('out_for_delivery', 'preparing')
        ");
        $stmt->execute([':rider_id' => $riderId]);
        return $stmt->fetchColumn();
    }
}
