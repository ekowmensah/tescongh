<?php
class Gallery {
    private $conn;
    private $table = 'gallery';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all gallery images
     */
    public function getAll($limit = null, $offset = 0, $filters = []) {
        $query = "SELECT g.*, u.email as uploaded_by_email 
                  FROM " . $this->table . " g
                  LEFT JOIN users u ON g.uploaded_by = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $query .= " AND g.category = :category";
            $params['category'] = $filters['category'];
        }
        
        if (isset($filters['is_featured'])) {
            $query .= " AND g.is_featured = :is_featured";
            $params['is_featured'] = $filters['is_featured'];
        }
        
        $query .= " ORDER BY g.display_order ASC, g.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        if ($limit) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get gallery image by ID
     */
    public function getById($id) {
        $query = "SELECT g.*, u.email as uploaded_by_email 
                  FROM " . $this->table . " g
                  LEFT JOIN users u ON g.uploaded_by = u.id
                  WHERE g.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get featured images for homepage
     */
    public function getFeatured($limit = 6) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE is_featured = 1 
                  ORDER BY display_order ASC, created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all categories
     */
    public function getCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table . " ORDER BY category ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create new gallery image
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (title, description, image_path, category, is_featured, display_order, uploaded_by) 
                  VALUES 
                  (:title, :description, :image_path, :category, :is_featured, :display_order, :uploaded_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':image_path', $data['image_path']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':is_featured', $data['is_featured']);
        $stmt->bindParam(':display_order', $data['display_order']);
        $stmt->bindParam(':uploaded_by', $data['uploaded_by']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false];
    }

    /**
     * Update gallery image
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
    }

    /**
     * Delete gallery image
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Count gallery images
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $query .= " AND category = :category";
            $params['category'] = $filters['category'];
        }
        
        if (isset($filters['is_featured'])) {
            $query .= " AND is_featured = :is_featured";
            $params['is_featured'] = $filters['is_featured'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }
}
