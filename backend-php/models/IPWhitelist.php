<?php

class IPWhitelist {
    private $conn;
    private $table_name = "ip_whitelist";

    public $id;
    public $ip_start;
    public $ip_end;
    public $description;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findAll() {
        $query = "SELECT iw.*, u.username as created_by_name 
                  FROM " . $this->table_name . " iw 
                  LEFT JOIN users u ON iw.created_by = u.id 
                  ORDER BY iw.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isIpInWhitelist($ip) {
        try {
            $ipLong = ip2long($ip);
            if ($ipLong === false) {
                return false;
            }

            $query = "SELECT * FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($whitelist as $range) {
                $startLong = ip2long($range['ip_start']);
                $endLong = ip2long($range['ip_end']);
                if ($ipLong >= $startLong && $ipLong <= $endLong) {
                    return true;
                }
            }

            return false;
        } catch (PDOException $e) {
            error_log('IPWhitelist table check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function tableExists() {
        try {
            $query = "SELECT 1 FROM " . $this->table_name . " LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET ip_start=:ip_start, ip_end=:ip_end, description=:description, created_by=:created_by";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":ip_start", $this->ip_start);
        $stmt->bindParam(":ip_end", $this->ip_end);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":created_by", $this->created_by);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET ip_start=:ip_start, ip_end=:ip_end, description=:description 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":ip_start", $this->ip_start);
        $stmt->bindParam(":ip_end", $this->ip_end);
        $stmt->bindParam(":description", $this->description);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    private function sanitize() {
        $this->ip_start = htmlspecialchars(strip_tags($this->ip_start));
        $this->ip_end = htmlspecialchars(strip_tags($this->ip_end));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
    }
}
