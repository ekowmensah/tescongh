<?php
class VotingConstituency {
    private $conn;
    private $table = 'voting_constituencies';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all voting constituencies
     */
    public function getAll($voting_region_id = null) {
        $query = "SELECT vc.*, vr.name as voting_region_name, vr.code as voting_region_code 
                  FROM " . $this->table . " vc
                  INNER JOIN voting_regions vr ON vc.voting_region_id = vr.id";
        
        if ($voting_region_id) {
            $query .= " WHERE vc.voting_region_id = :voting_region_id";
        }
        
        $query .= " ORDER BY vr.name ASC, vc.name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($voting_region_id) {
            $stmt->bindParam(':voting_region_id', $voting_region_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get voting constituency by ID
     */
    public function getById($id) {
        $query = "SELECT vc.*, vr.name as voting_region_name 
                  FROM " . $this->table . " vc
                  INNER JOIN voting_regions vr ON vc.voting_region_id = vr.id
                  WHERE vc.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get constituencies by voting region
     */
    public function getByVotingRegion($voting_region_id) {
        return $this->getAll($voting_region_id);
    }

    /**
     * Create new voting constituency
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (name, voting_region_id) 
                  VALUES (:name, :voting_region_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':voting_region_id', $data['voting_region_id']);
        
        return $stmt->execute();
    }

    /**
     * Update voting constituency
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, voting_region_id = :voting_region_id 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':voting_region_id', $data['voting_region_id']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Delete voting constituency
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Count voting constituencies
     */
    public function count($voting_region_id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        if ($voting_region_id) {
            $query .= " WHERE voting_region_id = :voting_region_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($voting_region_id) {
            $stmt->bindParam(':voting_region_id', $voting_region_id);
        }
        
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }
}
