<?php
class Constituency {
    private $conn;
    private $table = 'constituencies';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all constituencies
     */
    public function getAll() {
        $query = "SELECT c.*, r.name as region_name 
                  FROM " . $this->table . " c
                  LEFT JOIN regions r ON c.region_id = r.id
                  ORDER BY r.name, c.name ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get constituency by ID
     */
    public function getById($id) {
        $query = "SELECT c.*, r.name as region_name 
                  FROM " . $this->table . " c
                  LEFT JOIN regions r ON c.region_id = r.id
                  WHERE c.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get constituencies by region
     */
    public function getByRegion($regionId) {
        $query = "SELECT * FROM " . $this->table . " WHERE region_id = :region_id ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':region_id', $regionId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create constituency
     */
    public function create($name, $regionId, $createdBy) {
        $query = "INSERT INTO " . $this->table . " (name, region_id, created_by) VALUES (:name, :region_id, :created_by)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':region_id', $regionId);
        $stmt->bindParam(':created_by', $createdBy);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false];
    }

    /**
     * Update constituency
     */
    public function update($id, $name, $regionId) {
        $query = "UPDATE " . $this->table . " SET name = :name, region_id = :region_id WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':region_id', $regionId);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Delete constituency
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
