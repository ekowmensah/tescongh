<?php
class Position {
    private $conn;
    private $table = 'positions';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all positions
     */
    public function getAll($category = null, $activeOnly = true) {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        
        if ($category) {
            $query .= " AND category = :category";
        }
        
        if ($activeOnly) {
            $query .= " AND is_active = 1";
        }
        
        $query .= " ORDER BY level ASC, name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get position by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get positions by category
     */
    public function getByCategory($category) {
        return $this->getAll($category, true);
    }

    /**
     * Create position
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, category, level, description, created_by) 
                  VALUES 
                  (:name, :category, :level, :description, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':level', $data['level']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        
        return ['success' => false];
    }

    /**
     * Update position
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, category = :category, level = :level, 
                      description = :description, is_active = :is_active 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':level', $data['level']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':is_active', $data['is_active']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Delete position
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id) {
        $query = "UPDATE " . $this->table . " 
                  SET is_active = NOT is_active 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Get executive positions for dropdown
     */
    public function getExecutivePositions() {
        return $this->getByCategory('Executive');
    }

    /**
     * Get patron positions for dropdown
     */
    public function getPatronPositions() {
        return $this->getByCategory('Patron');
    }

    /**
     * Check if position name exists
     */
    public function nameExists($name, $category, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE name = :name AND category = :category";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
