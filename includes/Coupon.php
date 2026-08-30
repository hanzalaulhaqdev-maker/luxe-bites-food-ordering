<?php
/**
 * Coupon Model Class
 * Advanced coupon system with public, private, and exclusive types
 */
require_once __DIR__ . '/Database.php';

class Coupon {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new coupon
     */
    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO coupons 
            (code, discount, type, user_id, usage_limit, expiry_date, is_active, description)
            VALUES 
            (:code, :discount, :type, :user_id, :usage_limit, :expiry_date, :is_active, :description)
        ");
        
        return $stmt->execute([
            ':code' => strtoupper(sanitize($data['code'])),
            ':discount' => intval($data['discount']),
            ':type' => sanitize($data['type']),
            ':user_id' => !empty($data['user_id']) ? intval($data['user_id']) : null,
            ':usage_limit' => !empty($data['usage_limit']) ? intval($data['usage_limit']) : null,
            ':expiry_date' => sanitize($data['expiry_date']),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':description' => sanitize($data['description'] ?? '')
        ]);
    }
    
    /**
     * Get all coupons
     */
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT c.*, u.name as user_name, u.email as user_email
            FROM coupons c
            LEFT JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get active public coupons
     */
    public function getPublicCoupons(): array {
        $stmt = $this->db->prepare("
            SELECT * FROM coupons 
            WHERE type = 'public' 
              AND is_active = TRUE 
              AND expiry_date >= CURDATE()
              AND (usage_limit IS NULL OR used_count < usage_limit)
            ORDER BY discount DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get coupons for specific user (including exclusive)
     */
    public function getUserCoupons(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM coupons 
            WHERE is_active = TRUE 
              AND expiry_date >= CURDATE()
              AND (usage_limit IS NULL OR used_count < usage_limit)
              AND (
                  type = 'public'
                  OR (type = 'exclusive' AND user_id = :user_id)
              )
            ORDER BY 
                CASE type 
                    WHEN 'exclusive' THEN 1 
                    WHEN 'public' THEN 2 
                    ELSE 3 
                END,
                discount DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get exclusive coupons for a user
     */
    public function getExclusiveCoupons(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM coupons 
            WHERE type = 'exclusive'
              AND user_id = :user_id
              AND is_active = TRUE
            ORDER BY expiry_date ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's active and expired coupons for My Discounts page
     */
    public function getUserCouponHistory(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM coupons 
            WHERE 
                (type = 'public' AND is_active = TRUE AND expiry_date >= CURDATE())
                OR (type = 'exclusive' AND user_id = :user_id)
            ORDER BY 
                CASE 
                    WHEN expiry_date < CURDATE() THEN 1
                    WHEN is_active = FALSE THEN 2
                    ELSE 0
                END,
                discount DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Validate a coupon code
     */
    public function validate(string $code, ?int $userId = null): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM coupons 
            WHERE code = :code 
            LIMIT 1
        ");
        $stmt->execute([':code' => strtoupper(sanitize($code))]);
        $coupon = $stmt->fetch();
        
        if (!$coupon) return null;
        
        // Check if active
        if (!$coupon['is_active']) {
            return ['valid' => false, 'error' => 'Coupon is inactive'];
        }
        
        // Check expiry
        if (strtotime($coupon['expiry_date']) < strtotime(date('Y-m-d'))) {
            return ['valid' => false, 'error' => 'Coupon has expired'];
        }
        
        // Check usage limit
        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'error' => 'Coupon usage limit reached'];
        }
        
        // Check exclusive coupon ownership
        if ($coupon['type'] === 'exclusive') {
            if ($userId === null) {
                return ['valid' => false, 'error' => 'Please login to use this exclusive coupon'];
            }
            if ($coupon['user_id'] != $userId) {
                return ['valid' => false, 'error' => 'This coupon is not available for your account'];
            }
        }
        
        return ['valid' => true, 'coupon' => $coupon];
    }
    
    /**
     * Increment used count
     */
    public function incrementUsage(int $id): bool {
        $stmt = $this->db->prepare("
            UPDATE coupons 
            SET used_count = used_count + 1 
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get coupon by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as user_name 
            FROM coupons c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Update coupon
     */
    public function update(int $id, array $data): bool {
        $allowed = ['code', 'discount', 'type', 'user_id', 'usage_limit', 
                    'expiry_date', 'is_active', 'description'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                if ($field === 'user_id') {
                    $params[":$field"] = $data[$field] ? intval($data[$field]) : null;
                } elseif (in_array($field, ['discount', 'usage_limit'])) {
                    $params[":$field"] = intval($data[$field]);
                } elseif ($field === 'is_active') {
                    $params[":$field"] = $data[$field] ? 1 : 0;
                } else {
                    $params[":$field"] = sanitize($data[$field]);
                }
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE coupons SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete coupon
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get coupon stats
     */
    public function getStats(): array {
        $total = $this->db->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
        $active = $this->db->query("SELECT COUNT(*) FROM coupons WHERE is_active = TRUE")->fetchColumn();
        $expired = $this->db->query("
            SELECT COUNT(*) FROM coupons 
            WHERE expiry_date < CURDATE()
        ")->fetchColumn();
        
        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired
        ];
    }
}
