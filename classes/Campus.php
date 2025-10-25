<?php
class Campus {
    private $conn;
    private $table = 'campuses';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all campuses
     */
    public function getAll() {
        $query = "SELECT c.id, c.name, c.institution_id, c.location, c.region_id, c.constituency_id, 
                         c.created_by, c.created_at, c.updated_at,
                         i.name as institution_name, 
                         r.name as region_name, 
                         co.name as constituency_name 
                  FROM " . $this->table . " c
                  LEFT JOIN institutions i ON c.institution_id = i.id
                  LEFT JOIN regions r ON c.region_id = r.id
                  LEFT JOIN constituencies co ON c.constituency_id = co.id
                  GROUP BY c.id
                  ORDER BY c.id ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get campus by ID
     */
    public function getById($id) {
        $query = "SELECT c.*, i.name as institution_name, r.name as region_name, co.name as constituency_name 
                  FROM " . $this->table . " c
                  LEFT JOIN institutions i ON c.institution_id = i.id
                  LEFT JOIN regions r ON c.region_id = r.id
                  LEFT JOIN constituencies co ON c.constituency_id = co.id
                  WHERE c.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get campuses by institution
     */
    public function getByInstitution($institutionId) {
        $query = "SELECT * FROM " . $this->table . " WHERE institution_id = :institution_id ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':institution_id', $institutionId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create campus
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, institution_id, location, region_id, constituency_id, created_by) 
                  VALUES (:name, :institution_id, :location, :region_id, :constituency_id, :created_by)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false];
    }

    /**
     * Update campus
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
     * Delete campus
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
