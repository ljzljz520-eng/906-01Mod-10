<?php

class Favorite {
    private $conn;
    private $table_name = "favorites";

    public $id;
    public $user_id;
    public $resource_type;
    public $intranet_resource_id;
    public $name;
    public $magnet;
    public $size;
    public $seeders;
    public $leechers;
    public $category;
    public $source;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        if ($this->resource_type === 'intranet') {
            return $this->createIntranetFavorite();
        }
        return $this->createPublicFavorite();
    }

    private function createPublicFavorite() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, resource_type='public', name=:name, magnet=:magnet, 
                      size=:size, seeders=:seeders, leechers=:leechers, category=:category, source=:source";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":magnet", $this->magnet);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":seeders", $this->seeders);
        $stmt->bindParam(":leechers", $this->leechers);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":source", $this->source);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    private function createIntranetFavorite() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, resource_type='intranet', intranet_resource_id=:intranet_resource_id, 
                      name=:name, category=:category, source='intranet'";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":intranet_resource_id", $this->intranet_resource_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findOneByMagnetAndUser($magnet, $userId) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE magnet = :magnet AND user_id = :user_id AND resource_type = 'public' 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":magnet", $magnet);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findOneByIntranetResourceAndUser($resourceId, $userId) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE intranet_resource_id = :resource_id AND user_id = :user_id AND resource_type = 'intranet' 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":resource_id", $resourceId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAllByUser($userId, $resourceType = null) {
        $query = "SELECT f.*, 
                  ir.version as intranet_version, ir.url as intranet_url, 
                  ir.maintainer_id as maintainer_id, ir.expire_date as intranet_expire_date,
                  ir.is_active as intranet_is_active, ir.resource_type as intranet_type,
                  CASE 
                    WHEN ir.expire_date IS NULL THEN 'valid'
                    WHEN ir.expire_date >= CURDATE() THEN 'valid'
                    ELSE 'expired' 
                  END as intranet_expire_status,
                  u.real_name as maintainer_name
                  FROM " . $this->table_name . " f
                  LEFT JOIN intranet_resources ir ON f.intranet_resource_id = ir.id
                  LEFT JOIN users u ON ir.maintainer_id = u.id
                  WHERE f.user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($resourceType) {
            $query .= " AND f.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $query .= " ORDER BY f.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll() {
        $query = "SELECT f.*, 
                  ir.version as intranet_version, ir.url as intranet_url, 
                  ir.is_active as intranet_is_active, ir.resource_type as intranet_type,
                  CASE 
                    WHEN ir.expire_date IS NULL THEN 'valid'
                    WHEN ir.expire_date >= CURDATE() THEN 'valid'
                    ELSE 'expired' 
                  END as intranet_expire_status,
                  u.real_name as maintainer_name
                  FROM " . $this->table_name . " f
                  LEFT JOIN intranet_resources ir ON f.intranet_resource_id = ir.id
                  LEFT JOIN users u ON ir.maintainer_id = u.id
                  ORDER BY f.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id, $userId = null) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $params = [':id' => $id];

        if ($userId !== null) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function revokeIntranetFavoritesForUser($userId) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND resource_type = 'intranet'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        return $stmt->execute();
    }

    private function sanitize() {
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->magnet = htmlspecialchars(strip_tags($this->magnet ?? ''));
        $this->size = htmlspecialchars(strip_tags($this->size ?? ''));
        $this->category = htmlspecialchars(strip_tags($this->category ?? ''));
        $this->source = htmlspecialchars(strip_tags($this->source ?? ''));
    }
}
