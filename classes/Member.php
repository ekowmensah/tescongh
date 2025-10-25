<?php
class Member {
    private $conn;
    private $table = 'members';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new member
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, fullname, phone, date_of_birth, photo, institution, department, program, year, 
                   student_id, position, region, constituency, hails_from_region, hails_from_constituency, 
                   npp_position, campus_id, membership_status) 
                  VALUES 
                  (:user_id, :fullname, :phone, :date_of_birth, :photo, :institution, :department, :program, :year, 
                   :student_id, :position, :region, :constituency, :hails_from_region, :hails_from_constituency, 
                   :npp_position, :campus_id, :membership_status)";
        
        $stmt = $this->conn->prepare($query);
        
        // Only bind parameters that are in the query
        $allowedFields = ['user_id', 'fullname', 'phone', 'date_of_birth', 'photo', 'institution', 
                          'department', 'program', 'year', 'student_id', 'position', 'region', 
                          'constituency', 'hails_from_region', 'hails_from_constituency', 'npp_position', 
                          'campus_id', 'membership_status'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        try {
            if ($stmt->execute()) {
                return ['success' => true, 'member_id' => $this->conn->lastInsertId()];
            }
        } catch (PDOException $e) {
            error_log("Member create error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create member: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Failed to create member'];
    }

    /**
     * Get member by ID
     */
    public function getById($id) {
        $query = "SELECT m.*, u.email, u.role as user_role, u.status as user_status, 
                         u.last_login, u.email_verified, u.phone_verified,
                         c.name as campus_name, i.name as institution_name
                  FROM " . $this->table . " m
                  LEFT JOIN users u ON m.user_id = u.id
                  LEFT JOIN campuses c ON m.campus_id = c.id
                  LEFT JOIN institutions i ON c.institution_id = i.id
                  WHERE m.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get member by user ID
     */
    public function getByUserId($userId) {
        $query = "SELECT m.*, c.name as campus_name, i.name as institution_name
                  FROM " . $this->table . " m
                  LEFT JOIN campuses c ON m.campus_id = c.id
                  LEFT JOIN institutions i ON c.institution_id = i.id
                  WHERE m.user_id = :user_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get all members
     */
    public function getAll($limit = 20, $offset = 0, $filters = []) {
        $query = "SELECT m.*, u.email, c.name as campus_name, i.name as institution_name
                  FROM " . $this->table . " m
                  LEFT JOIN users u ON m.user_id = u.id
                  LEFT JOIN campuses c ON m.campus_id = c.id
                  LEFT JOIN institutions i ON c.institution_id = i.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $query .= " AND (m.fullname LIKE :search OR m.phone LIKE :search OR u.email LIKE :search OR m.student_id LIKE :search)";
            $params['search'] = "%" . $filters['search'] . "%";
        }
        
        if (!empty($filters['membership_status'])) {
            $query .= " AND m.membership_status = :membership_status";
            $params['membership_status'] = $filters['membership_status'];
        }
        
        if (!empty($filters['position'])) {
            $query .= " AND m.position = :position";
            $params['position'] = $filters['position'];
        }
        
        if (!empty($filters['region'])) {
            $query .= " AND m.region = :region";
            $params['region'] = $filters['region'];
        }
        
        if (!empty($filters['campus_id'])) {
            $query .= " AND m.campus_id = :campus_id";
            $params['campus_id'] = $filters['campus_id'];
        }
        
        $query .= " ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind filter parameters (without colon prefix in array keys)
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        // Bind limit and offset separately as integers
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Member getAll error: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            return [];
        }
    }

    /**
     * Count members
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " m
                  LEFT JOIN users u ON m.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $query .= " AND (m.fullname LIKE :search OR m.phone LIKE :search OR u.email LIKE :search OR m.student_id LIKE :search)";
            $params['search'] = "%" . $filters['search'] . "%";
        }
        
        if (!empty($filters['membership_status'])) {
            $query .= " AND m.membership_status = :membership_status";
            $params['membership_status'] = $filters['membership_status'];
        }
        
        if (!empty($filters['position'])) {
            $query .= " AND m.position = :position";
            $params['position'] = $filters['position'];
        }
        
        if (!empty($filters['region'])) {
            $query .= " AND m.region = :region";
            $params['region'] = $filters['region'];
        }
        
        if (!empty($filters['campus_id'])) {
            $query .= " AND m.campus_id = :campus_id";
            $params['campus_id'] = $filters['campus_id'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters (without colon prefix in array keys)
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }

    /**
     * Update member
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
     * Delete member
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total members
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->query($query);
        $stats['total_members'] = $stmt->fetch()['total'];
        
        // Active members
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE membership_status = 'Active'";
        $stmt = $this->conn->query($query);
        $stats['active_members'] = $stmt->fetch()['total'];
        
        // Executives
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE position = 'Executive'";
        $stmt = $this->conn->query($query);
        $stats['executives'] = $stmt->fetch()['total'];
        
        // Patrons
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE position = 'Patron'";
        $stmt = $this->conn->query($query);
        $stats['patrons'] = $stmt->fetch()['total'];
        
        // Members by region
        $query = "SELECT region, COUNT(*) as count FROM " . $this->table . " 
                  WHERE region IS NOT NULL GROUP BY region ORDER BY count DESC";
        $stmt = $this->conn->query($query);
        $stats['members_by_region'] = $stmt->fetchAll();
        
        // Members by status
        $query = "SELECT membership_status, COUNT(*) as count FROM " . $this->table . " 
                  GROUP BY membership_status";
        $stmt = $this->conn->query($query);
        $stats['members_by_status'] = $stmt->fetchAll();
        
        return $stats;
    }
}
