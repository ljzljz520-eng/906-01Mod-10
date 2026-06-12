<?php

class SystemLog {
    private $conn;
    private $table_name = "system_logs";

    public $id;
    public $log_type;
    public $message;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($logType, $message) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET log_type=:log_type, message=:message, created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);

        $logType = htmlspecialchars(strip_tags($logType));
        $message = htmlspecialchars(strip_tags($message));

        $stmt->bindParam(":log_type", $logType);
        $stmt->bindParam(":message", $message);

        return $stmt->execute();
    }

    public function findAll($limit = 50, $logType = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if ($logType) {
            $query .= " AND log_type = :log_type";
            $params[':log_type'] = $logType;
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
