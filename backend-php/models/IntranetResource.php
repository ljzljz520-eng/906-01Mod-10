<?php

class IntranetResource {
    private $conn;
    private $table_name = "intranet_resources";

    public $id;
    public $name;
    public $resource_type;
    public $version;
    public $description;
    public $url;
    public $maintainer_id;
    public $expire_date;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findAll($resourceType = null, $includeExpired = false) {
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.username as maintainer_username,
                  CASE 
                    WHEN ir.expire_date IS NULL THEN 'valid'
                    WHEN ir.expire_date >= CURDATE() THEN 'valid'
                    ELSE 'expired' 
                  END as expire_status
                  FROM " . $this->table_name . " ir 
                  LEFT JOIN users u ON ir.maintainer_id = u.id 
                  WHERE 1=1";

        $params = [];
        
        if ($resourceType) {
            $query .= " AND ir.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        if (!$includeExpired) {
            $query .= " AND ir.is_active = 1 AND (ir.expire_date IS NULL OR ir.expire_date >= CURDATE())";
        }

        $query .= " ORDER BY ir.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.username as maintainer_username,
                  CASE 
                    WHEN ir.expire_date IS NULL THEN 'valid'
                    WHEN ir.expire_date >= CURDATE() THEN 'valid'
                    ELSE 'expired' 
                  END as expire_status
                  FROM " . $this->table_name . " ir 
                  LEFT JOIN users u ON ir.maintainer_id = u.id 
                  WHERE ir.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function search($keyword, $resourceType = null) {
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.username as maintainer_username,
                  CASE 
                    WHEN ir.expire_date IS NULL THEN 'valid'
                    WHEN ir.expire_date >= CURDATE() THEN 'valid'
                    ELSE 'expired' 
                  END as expire_status
                  FROM " . $this->table_name . " ir 
                  LEFT JOIN users u ON ir.maintainer_id = u.id 
                  WHERE ir.is_active = 1 AND (ir.expire_date IS NULL OR ir.expire_date >= CURDATE())
                  AND (ir.name LIKE :keyword OR ir.description LIKE :keyword)";

        $params = [':keyword' => '%' . $keyword . '%'];
        
        if ($resourceType) {
            $query .= " AND ir.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $query .= " ORDER BY ir.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiringResources($days = 30) {
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.email as maintainer_email,
                  DATEDIFF(ir.expire_date, CURDATE()) as days_until_expiry
                  FROM " . $this->table_name . " ir 
                  LEFT JOIN users u ON ir.maintainer_id = u.id 
                  WHERE ir.is_active = 1 
                  AND ir.expire_date IS NOT NULL 
                  AND DATEDIFF(ir.expire_date, CURDATE()) BETWEEN 0 AND :days
                  ORDER BY ir.expire_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredResources() {
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.email as maintainer_email,
                  DATEDIFF(CURDATE(), ir.expire_date) as days_expired
                  FROM " . $this->table_name . " ir 
                  LEFT JOIN users u ON ir.maintainer_id = u.id 
                  WHERE ir.is_active = 1 
                  AND ir.expire_date IS NOT NULL 
                  AND ir.expire_date < CURDATE()
                  ORDER BY ir.expire_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, resource_type=:resource_type, version=:version, 
                      description=:description, url=:url, maintainer_id=:maintainer_id,
                      expire_date=:expire_date, is_active=:is_active";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":resource_type", $this->resource_type);
        $stmt->bindParam(":version", $this->version);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":url", $this->url);
        $stmt->bindParam(":maintainer_id", $this->maintainer_id);
        $stmt->bindParam(":expire_date", $this->expire_date);
        $stmt->bindParam(":is_active", $this->is_active);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, resource_type=:resource_type, version=:version, 
                      description=:description, url=:url, maintainer_id=:maintainer_id,
                      expire_date=:expire_date, is_active=:is_active
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":resource_type", $this->resource_type);
        $stmt->bindParam(":version", $this->version);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":url", $this->url);
        $stmt->bindParam(":maintainer_id", $this->maintainer_id);
        $stmt->bindParam(":expire_date", $this->expire_date);
        $stmt->bindParam(":is_active", $this->is_active);

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
            $this->deleteRelatedFavorites($id);
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    private function deleteRelatedFavorites($resourceId) {
        $query = "DELETE FROM favorites WHERE intranet_resource_id = :resource_id AND resource_type = 'intranet'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":resource_id", $resourceId);
        $stmt->execute();
    }

    public function deactivateExpiredResources() {
        try {
            $this->conn->beginTransaction();

            $query = "SELECT id, name, version FROM " . $this->table_name . " 
                      WHERE is_active = 1 AND expire_date IS NOT NULL AND expire_date < CURDATE()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $expiredResources = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($expiredResources)) {
                $this->conn->commit();
                return ['deactivated' => 0, 'favorites_removed' => 0];
            }

            $expiredIds = array_column($expiredResources, 'id');
            $placeholders = implode(',', array_fill(0, count($expiredIds), '?'));
            
            $updateQuery = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id IN ($placeholders)";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute($expiredIds);

            $favRemoved = $this->deleteRelatedFavoritesBatch($expiredIds);

            $resourceList = array_map(function($r) {
                return "{$r['name']}({$r['version']})";
            }, $expiredResources);
            $resourceSummary = implode(', ', array_slice($resourceList, 0, 5));
            if (count($resourceList) > 5) {
                $resourceSummary .= ' 等' . count($resourceList) . '个';
            }

            $logQuery = "INSERT INTO system_logs (log_type, message, created_at) VALUES 
                         ('resource_expired', CONCAT('自动失效处理：停用 :count 个过期资源，移除 :fav 条收藏。资源: ', :resources), NOW())";
            $logStmt = $this->conn->prepare($logQuery);
            $logStmt->bindValue(':count', count($expiredIds));
            $logStmt->bindValue(':fav', $favRemoved);
            $logStmt->bindValue(':resources', $resourceSummary);
            @$logStmt->execute();

            $this->conn->commit();
            return [
                'deactivated' => count($expiredIds),
                'favorites_removed' => $favRemoved
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("deactivateExpiredResources error: " . $e->getMessage());
            return ['deactivated' => 0, 'favorites_removed' => 0, 'error' => $e->getMessage()];
        }
    }

    public function getResourcesByMaintainer($maintainerId) {
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.username as maintainer_username,
                  CASE 
                    WHEN ir.expire_date IS NULL THEN 'valid'
                    WHEN ir.expire_date >= CURDATE() THEN 'valid'
                    ELSE 'expired' 
                  END as expire_status
                  FROM " . $this->table_name . " ir 
                  LEFT JOIN users u ON ir.maintainer_id = u.id 
                  WHERE ir.maintainer_id = :maintainer_id
                  ORDER BY ir.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":maintainer_id", $maintainerId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reassignMaintainer($oldMaintainerId, $newMaintainerId) {
        $query = "UPDATE " . $this->table_name . " SET maintainer_id = :new_id WHERE maintainer_id = :old_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":new_id", $newMaintainerId);
        $stmt->bindParam(":old_id", $oldMaintainerId);
        return $stmt->execute();
    }

    private function deleteRelatedFavoritesBatch($resourceIds) {
        if (empty($resourceIds)) return 0;
        $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
        $query = "DELETE FROM favorites WHERE intranet_resource_id IN ($placeholders) AND resource_type = 'intranet'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($resourceIds);
        return $stmt->rowCount();
    }

    public function toggleActive($id, $isActive) {
        $query = "UPDATE " . $this->table_name . " SET is_active=:is_active WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":is_active", $isActive);
        
        if($stmt->execute()) {
            if (!$isActive) {
                $this->deleteRelatedFavorites($id);
            }
            return true;
        }
        return false;
    }

    private function sanitize() {
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->resource_type = htmlspecialchars(strip_tags($this->resource_type));
        $this->version = htmlspecialchars(strip_tags($this->version));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->url = htmlspecialchars(strip_tags($this->url ?? ''));
    }
}
