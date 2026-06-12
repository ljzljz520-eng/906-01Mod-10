<?php

class SearchHistory {
    private $conn;
    private $table_name = "search_history";

    public $id;
    public $user_id;
    public $keyword;
    public $query;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, keyword=:keyword, query=:query";
        $stmt = $this->conn->prepare($query);

        $this->keyword = htmlspecialchars(strip_tags($this->keyword));
        $this->query = htmlspecialchars(strip_tags($this->query));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":keyword", $this->keyword);
        $stmt->bindParam(":query", $this->query);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function findAllByUser($userId, $limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id OR user_id IS NULL 
                  ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll($limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteAllByUser($userId) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id OR user_id IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function deleteAll() {
        $query = "DELETE FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
