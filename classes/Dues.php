<?php
class Dues {
    private $conn;
    private $table = 'dues';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all dues
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY year DESC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get dues by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get dues by year
     */
    public function getByYear($year) {
        $query = "SELECT * FROM " . $this->table . " WHERE year = :year LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create dues
     */
    public function create($year, $amount, $description, $dueDate) {
        try {
            $query = "INSERT INTO " . $this->table . " (year, amount, description, due_date) 
                      VALUES (:year, :amount, :description, :due_date)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':due_date', $dueDate);
            
            if ($stmt->execute()) {
                return ['success' => true, 'id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create dues'];
        } catch (PDOException $e) {
            // Check for duplicate entry error
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'message' => "Dues for year {$year} already exists. Please edit the existing record instead."];
            }
            return ['success' => false, 'message' => 'An error occurred while creating dues: ' . $e->getMessage()];
        }
    }

    /**
     * Update dues
     */
    public function update($id, $year, $amount, $description, $dueDate) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET year = :year, amount = :amount, description = :description, due_date = :due_date 
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':due_date', $dueDate);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return ['success' => true];
            }
            return ['success' => false, 'message' => 'Failed to update dues'];
        } catch (PDOException $e) {
            // Check for duplicate entry error
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'message' => "Dues for year {$year} already exists. Please choose a different year."];
            }
            return ['success' => false, 'message' => 'An error occurred while updating dues: ' . $e->getMessage()];
        }
    }

    /**
     * Delete dues
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
