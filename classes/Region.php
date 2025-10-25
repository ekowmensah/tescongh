<?php
class Region {
    private $conn;
    private $table = 'regions';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all regions
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get region by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create region
     */
    public function create($name, $code, $createdBy) {
        $query = "INSERT INTO " . $this->table . " (name, code, created_by) VALUES (:name, :code, :created_by)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':created_by', $createdBy);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false];
    }

    /**
     * Update region
     */
    public function update($id, $name, $code) {
        $query = "UPDATE " . $this->table . " SET name = :name, code = :code WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Delete region
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
