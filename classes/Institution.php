<?php
class Institution {
    private $conn;
    private $table = 'institutions';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all institutions
     */
    public function getAll() {
        $query = "SELECT i.*, r.name as region_name, c.name as constituency_name 
                  FROM " . $this->table . " i
                  LEFT JOIN regions r ON i.region_id = r.id
                  LEFT JOIN constituencies c ON i.constituency_id = c.id
                  ORDER BY i.name ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get institution by ID
     */
    public function getById($id) {
        $query = "SELECT i.*, r.name as region_name, c.name as constituency_name 
                  FROM " . $this->table . " i
                  LEFT JOIN regions r ON i.region_id = r.id
                  LEFT JOIN constituencies c ON i.constituency_id = c.id
                  WHERE i.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create institution
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, type, location, website, logo, region_id, constituency_id, created_by) 
                  VALUES (:name, :type, :location, :website, :logo, :region_id, :constituency_id, :created_by)";
        $stmt = $this->conn->prepare($query);
        
        $allowedFields = ['name', 'type', 'location', 'website', 'logo', 'region_id', 'constituency_id', 'created_by'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false];
    }

    /**
     * Update institution
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
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Delete institution
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
