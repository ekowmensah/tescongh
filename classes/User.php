<?php
class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $email;
    public $password;
    public $role;
    public $status;
    public $email_verified;
    public $phone_verified;
    public $last_login;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Login user with email
     */
    public function login($email, $password) {
        $query = "SELECT u.id, u.email, u.password, u.role, u.status, m.id as member_id 
                  FROM " . $this->table . " u
                  LEFT JOIN members m ON u.id = m.user_id
                  WHERE u.email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if ($row['status'] !== 'Active') {
                return ['success' => false, 'message' => 'Account is not active'];
            }

            if (password_verify($password, $row['password'])) {
                // Check if account is verified (for Members only, registered after cutoff date)
                if ($row['role'] === 'Member') {
                    // Only check verification for members registered after verification system was implemented
                    $cutoffDate = defined('VERIFICATION_CUTOFF_DATE') ? VERIFICATION_CUTOFF_DATE : '2024-11-25 00:00:00';
                    $verifyQuery = "SELECT m.id, m.created_at FROM members m
                                   LEFT JOIN verification_tokens vt ON vt.member_id = m.id AND vt.type = 'signup'
                                   WHERE m.user_id = :user_id 
                                   AND m.created_at >= :cutoff_date
                                   AND (vt.id IS NULL OR vt.is_used = 0)
                                   LIMIT 1";
                    $verifyStmt = $this->conn->prepare($verifyQuery);
                    $verifyStmt->bindParam(':user_id', $row['id']);
                    $verifyStmt->bindParam(':cutoff_date', $cutoffDate);
                    $verifyStmt->execute();
                    
                    if ($verifyStmt->rowCount() > 0) {
                        return ['success' => false, 'message' => 'Please verify your account first. Check your phone for the verification link.'];
                    }
                }
                
                // Update last login
                $this->updateLastLogin($row['id']);

                return [
                    'success' => true,
                    'user' => [
                        'id' => $row['id'],
                        'email' => $row['email'],
                        'role' => $row['role'],
                        'member_id' => $row['member_id'] ?? null
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Invalid password'];
            }
        }

        return ['success' => false, 'message' => 'User not found'];
    }

    /**
     * Login user with student ID
     */
    public function loginWithStudentId($studentId, $password) {
        $query = "SELECT u.id, u.email, u.password, u.role, u.status, m.id as member_id
                  FROM " . $this->table . " u
                  INNER JOIN members m ON u.id = m.user_id
                  WHERE m.student_id = :student_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if ($row['status'] !== 'Active') {
                return ['success' => false, 'message' => 'Account is not active'];
            }

            if (password_verify($password, $row['password'])) {
                // Check if account is verified (for Members only, registered after cutoff date)
                if ($row['role'] === 'Member') {
                    // Only check verification for members registered after verification system was implemented
                    $cutoffDate = defined('VERIFICATION_CUTOFF_DATE') ? VERIFICATION_CUTOFF_DATE : '2024-11-25 00:00:00';
                    $checkDateQuery = "SELECT created_at FROM members WHERE id = :member_id";
                    $checkDateStmt = $this->conn->prepare($checkDateQuery);
                    $checkDateStmt->bindParam(':member_id', $row['member_id']);
                    $checkDateStmt->execute();
                    $memberData = $checkDateStmt->fetch();
                    
                    // Only enforce verification for members registered after cutoff date
                    if ($memberData && $memberData['created_at'] >= $cutoffDate) {
                        $verifyQuery = "SELECT id FROM verification_tokens 
                                       WHERE member_id = :member_id 
                                       AND type = 'signup' 
                                       AND is_used = 1
                                       LIMIT 1";
                        $verifyStmt = $this->conn->prepare($verifyQuery);
                        $verifyStmt->bindParam(':member_id', $row['member_id']);
                        $verifyStmt->execute();
                        
                        if ($verifyStmt->rowCount() === 0) {
                            return ['success' => false, 'message' => 'Please verify your account first. Check your phone for the verification link.'];
                        }
                    }
                }
                
                // Update last login
                $this->updateLastLogin($row['id']);

                return [
                    'success' => true,
                    'user' => [
                        'id' => $row['id'],
                        'email' => $row['email'],
                        'role' => $row['role'],
                        'member_id' => $row['member_id']
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Invalid password'];
            }
        }

        return ['success' => false, 'message' => 'Student ID not found'];
    }

    /**
     * Register new user
     */
    public function register($email, $password, $role = 'Member') {
        // Check if email already exists
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $query = "INSERT INTO " . $this->table . " (email, password, role, status) VALUES (:email, :password, :role, 'Active')";
        $stmt = $this->conn->prepare($query);

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $this->conn->lastInsertId()];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Update last login
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'password') {
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
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $query = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $userId);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        }

        return ['success' => false, 'message' => 'Failed to change password'];
    }

    /**
     * Get all users
     */
    public function getAllUsers($limit = 20, $offset = 0, $search = '') {
        $query = "SELECT id, email, role, status, email_verified, phone_verified, last_login, created_at 
                  FROM " . $this->table;
        
        if (!empty($search)) {
            $query .= " WHERE email LIKE :search";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchParam = "%$search%";
            $stmt->bindParam(':search', $searchParam);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Count users
     */
    public function countUsers($search = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        if (!empty($search)) {
            $query .= " WHERE email LIKE :search";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchParam = "%$search%";
            $stmt->bindParam(':search', $searchParam);
        }
        
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }

    /**
     * Delete user
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
