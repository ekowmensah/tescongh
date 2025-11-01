<?php
class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all payments
     */
    public function getAll($limit = 20, $offset = 0, $filters = []) {
        $query = "SELECT p.*, m.fullname, m.phone, d.year, d.amount as dues_amount 
                  FROM " . $this->table . " p
                  LEFT JOIN members m ON p.member_id = m.id
                  LEFT JOIN dues d ON p.dues_id = d.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['member_id'])) {
            $query .= " AND p.member_id = :member_id";
            $params[':member_id'] = $filters['member_id'];
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Count payments
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['member_id'])) {
            $query .= " AND member_id = :member_id";
            $params[':member_id'] = $filters['member_id'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        $query = "SELECT p.*, m.fullname, m.phone, u.email, d.year, d.amount as dues_amount 
                  FROM " . $this->table . " p
                  LEFT JOIN members m ON p.member_id = m.id
                  LEFT JOIN users u ON m.user_id = u.id
                  LEFT JOIN dues d ON p.dues_id = d.id
                  WHERE p.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create payment
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (member_id, dues_id, amount, payment_method, transaction_id, hubtel_reference, status, payment_date, notes) 
                  VALUES (:member_id, :dues_id, :amount, :payment_method, :transaction_id, :hubtel_reference, :status, :payment_date, :notes)";
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
     * Update payment
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
     * Delete payment
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Get payment statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total payments
        $query = "SELECT COUNT(*) as total, SUM(amount) as total_amount FROM " . $this->table . " WHERE status = 'completed'";
        $stmt = $this->conn->query($query);
        $row = $stmt->fetch();
        $stats['total_payments'] = $row['total'];
        $stats['total_amount'] = $row['total_amount'] ?? 0;
        
        // Pending payments
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'pending'";
        $stmt = $this->conn->query($query);
        $stats['pending_payments'] = $stmt->fetch()['total'];
        
        // Failed payments
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'failed'";
        $stmt = $this->conn->query($query);
        $stats['failed_payments'] = $stmt->fetch()['total'];
        
        return $stats;
    }
}
