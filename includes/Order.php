<?php
/**
 * Order Model Class
 * Handles orders, order items, and status management
 */
require_once __DIR__ . '/Database.php';

class Order {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new order
     */
    public function create(array $data): int {
        $this->db->beginTransaction();
        
        try {
            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO orders 
                (user_id, customer_name, customer_email, customer_phone, customer_address, 
                 total, discount_amount, coupon_code, final_total, status)
                VALUES 
                (:user_id, :customer_name, :customer_email, :customer_phone, :customer_address,
                 :total, :discount_amount, :coupon_code, :final_total, :status)
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'] ?? null,
                ':customer_name' => sanitize($data['customer_name']),
                ':customer_email' => sanitizeEmail($data['customer_email']),
                ':customer_phone' => sanitize($data['customer_phone']),
                ':customer_address' => sanitize($data['customer_address']),
                ':total' => floatval($data['total']),
                ':discount_amount' => floatval($data['discount_amount'] ?? 0),
                ':coupon_code' => !empty($data['coupon_code']) ? sanitize($data['coupon_code']) : null,
                ':final_total' => floatval($data['final_total']),
                ':status' => 'pending'
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Insert order items
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items 
                (order_id, item_id, item_name, qty, price, subtotal)
                VALUES 
                (:order_id, :item_id, :item_name, :qty, :price, :subtotal)
            ");
            
            foreach ($data['items'] as $item) {
                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':item_id' => intval($item['id']),
                    ':item_name' => sanitize($item['name']),
                    ':qty' => intval($item['qty']),
                    ':price' => floatval($item['price']),
                    ':subtotal' => floatval($item['price'] * $item['qty'])
                ]);
            }
            
            // Log status history
            $historyStmt = $this->db->prepare("
                INSERT INTO order_status_history (order_id, status, notes)
                VALUES (:order_id, :status, :notes)
            ");
            $historyStmt->execute([
                ':order_id' => $orderId,
                ':status' => 'pending',
                ':notes' => 'Order placed'
            ]);
            
            $this->db->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get order by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT o.*, r.name as rider_name, r.phone as rider_phone, r.image as rider_image
            FROM orders o
            LEFT JOIN riders r ON o.rider_id = r.id
            WHERE o.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();
        
        if (!$order) return null;
        
        // Get order items
        $itemStmt = $this->db->prepare("
            SELECT * FROM order_items WHERE order_id = :order_id
        ");
        $itemStmt->execute([':order_id' => $id]);
        $order['items'] = $itemStmt->fetchAll();
        
        // Get status history
        $historyStmt = $this->db->prepare("
            SELECT * FROM order_status_history 
            WHERE order_id = :order_id 
            ORDER BY created_at ASC
        ");
        $historyStmt->execute([':order_id' => $id]);
        $order['history'] = $historyStmt->fetchAll();
        
        return $order;
    }
    
    /**
     * Get orders by user ID
     */
    public function getByUser(int $userId, int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT o.*, r.name as rider_name
            FROM orders o
            LEFT JOIN riders r ON o.rider_id = r.id
            WHERE o.user_id = :user_id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get all orders (admin)
     */
    public function getAll(string $status = null, int $limit = 100, int $offset = 0): array {
        $sql = "
            SELECT o.*, r.name as rider_name
            FROM orders o
            LEFT JOIN riders r ON o.rider_id = r.id
            WHERE 1=1
        ";
        $params = [];
        
        if ($status) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Update order status
     */
    public function updateStatus(int $id, string $status, string $notes = ''): bool {
        $this->db->beginTransaction();
        
        try {
            $allowedStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled', 'rejected'];
            if (!in_array($status, $allowedStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
            
            // Log history
            $historyStmt = $this->db->prepare("
                INSERT INTO order_status_history (order_id, status, notes)
                VALUES (:order_id, :status, :notes)
            ");
            $historyStmt->execute([
                ':order_id' => $id,
                ':status' => $status,
                ':notes' => sanitize($notes)
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Assign rider to order
     */
    public function assignRider(int $orderId, int $riderId): bool {
        $stmt = $this->db->prepare("
            UPDATE orders 
            SET rider_id = :rider_id, rider_assigned_at = NOW() 
            WHERE id = :order_id
        ");
        return $stmt->execute([':rider_id' => $riderId, ':order_id' => $orderId]);
    }
    
    /**
     * Get order counts by status
     */
    public function getStatusCounts(): array {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM orders 
            GROUP BY status
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * Count total orders
     */
    public function count(): int {
        return $this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    }
    
    /**
     * Calculate total revenue
     */
    public function getTotalRevenue(): float {
        return floatval($this->db->query("
            SELECT COALESCE(SUM(final_total), 0) FROM orders WHERE status != 'cancelled' AND status != 'rejected'
        ")->fetchColumn());
    }
}
