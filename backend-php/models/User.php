<?php

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $role;
    public $status;
    public $real_name;
    public $department;
    public $token;
    public $token_expires_at;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findAll($role = null, $status = null) {
        $query = "SELECT id, username, email, role, status, real_name, department, created_at, updated_at FROM " . $this->table_name . " WHERE 1=1";
        
        if ($role) {
            $query .= " AND role = :role";
        }
        if ($status) {
            $query .= " AND status = :status";
        }
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        if ($role) {
            $stmt->bindParam(":role", $role);
        }
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT id, username, email, role, status, real_name, department, created_at, updated_at FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByToken($token) {
        $query = "SELECT u.* FROM " . $this->table_name . " u 
                  INNER JOIN user_tokens ut ON u.id = ut.user_id 
                  WHERE ut.token = :token AND ut.expires_at > NOW() AND u.status = 'active' 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password_hash=:password_hash, 
                      role=:role, status=:status, real_name=:real_name, department=:department";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":real_name", $this->real_name);
        $stmt->bindParam(":department", $this->department);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, email=:email, role=:role, status=:status, 
                      real_name=:real_name, department=:department
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":real_name", $this->real_name);
        $stmt->bindParam(":department", $this->department);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateStatus($id, $status) {
        try {
            $this->conn->beginTransaction();

            $oldUser = $this->findById($id);
            if (!$oldUser) {
                $this->conn->rollBack();
                return false;
            }

            $query = "UPDATE " . $this->table_name . " SET status=:status WHERE id=:id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":status", $status);
            
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $revokedCount = 0;
            $affectedResources = 0;

            if ($status === 'resigned') {
                $revokedCount = $this->revokeIntranetFavorites($id);
                $this->revokeAllTokens($id);
                $affectedResources = $this->handleResignedMaintainerResources($id);
                
                $this->logSystemEvent('user_resigned', 
                    "用户ID:{$id} ({$oldUser['username']}/{$oldUser['real_name']}) 已标记为离职。" .
                    "收回内网资源收藏 {$revokedCount} 条，待重新分配负责人资源 {$affectedResources} 个。");
            }
            
            if ($status === 'inactive') {
                $this->revokeAllTokens($id);
                $this->logSystemEvent('user_deactivated', 
                    "用户ID:{$id} ({$oldUser['username']}/{$oldUser['real_name']}) 已被禁用，登录令牌已回收。");
            }

            if ($status === 'active' && $oldUser['status'] !== 'active') {
                $this->logSystemEvent('user_activated', 
                    "用户ID:{$id} ({$oldUser['username']}/{$oldUser['real_name']}) 已重新激活。原状态: {$oldUser['status']}");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    private function revokeAllTokens($userId) {
        $query = "DELETE FROM user_tokens WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function handleResignedMaintainerResources($userId) {
        $query = "SELECT id, name FROM intranet_resources WHERE maintainer_id = :user_id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($resources);
        
        if ($count > 0) {
            $resourceNames = array_column($resources, 'name');
            $resourceList = implode(', ', array_slice($resourceNames, 0, 5));
            if ($count > 5) {
                $resourceList .= ' 等' . $count . '个';
            }
            
            $logQuery = "INSERT INTO system_logs (log_type, message, created_at) VALUES 
                         ('maintainer_resigned', CONCAT('用户ID:', :user_id, ' 离职，需重新分配资源。资源: ', :resource_list), NOW())";
            $logStmt = $this->conn->prepare($logQuery);
            $logStmt->bindValue(":user_id", $userId);
            $logStmt->bindValue(":resource_list", $resourceList);
            @$logStmt->execute();
        }
        return $count;
    }

    private function revokeIntranetFavorites($userId) {
        $query = "DELETE FROM favorites WHERE user_id = :user_id AND resource_type = 'intranet'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function logSystemEvent($type, $message) {
        try {
            $query = "INSERT INTO system_logs (log_type, message, created_at) VALUES (:log_type, :message, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":log_type", $type);
            $stmt->bindValue(":message", $message);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("System log error: " . $e->getMessage());
        }
    }

    public function createToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

        $this->conn->exec("CREATE TABLE IF NOT EXISTS user_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $query = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expiresAt);
        
        if($stmt->execute()) {
            return $token;
        }
        return false;
    }

    public function verifyPassword($inputPassword, $passwordHash) {
        return password_verify($inputPassword, $passwordHash);
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function sanitize() {
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->real_name = htmlspecialchars(strip_tags($this->real_name ?? ''));
        $this->department = htmlspecialchars(strip_tags($this->department ?? ''));
    }
}
