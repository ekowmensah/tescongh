<?php
class VotingRegion {
    private $conn;
    private $table = 'voting_regions';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all voting regions
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get voting region by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create new voting region
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (name, code) VALUES (:name, :code)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':code', $data['code']);
        
        return $stmt->execute();
    }

    /**
     * Update voting region
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET name = :name, code = :code WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Delete voting region
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Count voting regions
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->query($query);
        $row = $stmt->fetch();
        return $row['total'];
    }
}
