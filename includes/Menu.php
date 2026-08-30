<?php
/**
 * Menu Model Class
 * Handles menu items, categories, and featured items system
 */
require_once __DIR__ . '/Database.php';

class Menu {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all categories
     */
    public function getCategories(): array {
        $stmt = $this->db->query("
            SELECT * FROM categories 
            WHERE is_active = TRUE 
            ORDER BY display_order ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get category by ID
     */
    public function getCategoryById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Create category
     */
    public function createCategory(string $name, string $slug, int $displayOrder = 0): bool {
        $stmt = $this->db->prepare("
            INSERT INTO categories (name, slug, display_order) 
            VALUES (:name, :slug, :display_order)
        ");
        return $stmt->execute([
            ':name' => sanitize($name),
            ':slug' => sanitize($slug),
            ':display_order' => $displayOrder
        ]);
    }
    
    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): bool {
        $allowed = ['name', 'slug', 'display_order', 'is_active'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = is_bool($data[$field]) ? (int)$data[$field] : sanitize($data[$field]);
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE categories SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete category
     */
    public function deleteCategory(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get all menu items with optional filtering
     */
    public function getItems(int $categoryId = null, bool $availableOnly = true): array {
        $sql = "
            SELECT m.*, c.name as category_name, c.slug as category_slug
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            WHERE 1=1
        ";
        $params = [];
        
        if ($availableOnly) {
            $sql .= " AND m.is_available = TRUE";
        }
        
        if ($categoryId) {
            $sql .= " AND m.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        
        $sql .= " ORDER BY m.display_order ASC, m.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get featured items sorted by priority
     */
    public function getFeaturedItems(int $limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name, c.slug as category_slug
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            WHERE m.is_featured = TRUE 
              AND m.is_available = TRUE
            ORDER BY 
                m.is_top_priority DESC,
                m.display_order ASC,
                m.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get top priority items
     */
    public function getTopPriorityItems(int $limit = 4): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name, c.slug as category_slug
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            WHERE m.is_top_priority = TRUE 
              AND m.is_available = TRUE
            ORDER BY m.display_order ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get items for homepage (top priority first, then featured)
     */
    public function getHomepageItems(int $limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name, c.slug as category_slug
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            WHERE m.is_featured = TRUE 
              AND m.is_available = TRUE
            ORDER BY 
                m.is_top_priority DESC,
                m.display_order ASC,
                m.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get item by ID
     */
    public function getItemById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            WHERE m.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Create menu item
     */
    public function createItem(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO menu_items 
            (name, description, price, category_id, image, is_featured, is_top_priority, display_order)
            VALUES 
            (:name, :description, :price, :category_id, :image, :is_featured, :is_top_priority, :display_order)
        ");
        
        return $stmt->execute([
            ':name' => sanitize($data['name']),
            ':description' => sanitize($data['description'] ?? ''),
            ':price' => floatval($data['price']),
            ':category_id' => intval($data['category_id']),
            ':image' => sanitize($data['image'] ?? ''),
            ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
            ':is_top_priority' => !empty($data['is_top_priority']) ? 1 : 0,
            ':display_order' => intval($data['display_order'] ?? 999)
        ]);
    }
    
    /**
     * Update menu item
     */
    public function updateItem(int $id, array $data): bool {
        $allowed = ['name', 'description', 'price', 'category_id', 'image', 
                    'is_featured', 'is_top_priority', 'display_order', 'is_available'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                if (is_bool($data[$field])) {
                    $params[":$field"] = $data[$field] ? 1 : 0;
                } elseif (in_array($field, ['price'])) {
                    $params[":$field"] = floatval($data[$field]);
                } elseif (in_array($field, ['category_id', 'display_order'])) {
                    $params[":$field"] = intval($data[$field]);
                } else {
                    $params[":$field"] = sanitize($data[$field]);
                }
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE menu_items SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete menu item
     */
    public function deleteItem(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM menu_items WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Count total items
     */
    public function countItems(): int {
        return $this->db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    }
    
    /**
     * Count featured items
     */
    public function countFeatured(): int {
        return $this->db->query("SELECT COUNT(*) FROM menu_items WHERE is_featured = TRUE")->fetchColumn();
    }
    
    /**
     * Search items
     */
    public function search(string $query): array {
        $search = "%$query%";
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            WHERE m.is_available = TRUE
              AND (m.name LIKE :query OR m.description LIKE :query)
            ORDER BY m.name ASC
        ");
        $stmt->execute([':query' => $search]);
        return $stmt->fetchAll();
    }
}
