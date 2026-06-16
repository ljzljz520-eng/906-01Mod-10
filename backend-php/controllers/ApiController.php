<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SearchHistory.php';
require_once __DIR__ . '/../models/Favorite.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/IntranetResource.php';
require_once __DIR__ . '/../models/IPWhitelist.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/IpWhitelistMiddleware.php';

class ApiController {
    private $db;
    private $searchHistory;
    private $favorite;
    private $user;
    private $intranetResource;
    private $ipWhitelist;
    private $authMiddleware;
    private $ipMiddleware;
    private $currentUser;
    private $isIntranet;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        if ($this->db) {
            try {
                $this->searchHistory = new SearchHistory($this->db);
                $this->favorite = new Favorite($this->db);
                $this->user = new User($this->db);
                $this->intranetResource = new IntranetResource($this->db);
                $this->ipWhitelist = new IPWhitelist($this->db);
                $this->authMiddleware = new AuthMiddleware($this->db);
                $this->ipMiddleware = new IpWhitelistMiddleware($this->db);
                $this->currentUser = $this->authMiddleware->authenticate();
                $this->isIntranet = $this->ipMiddleware->isIntranetAccess();
            } catch (Exception $e) {
                error_log('ApiController init error: ' . $e->getMessage());
                $this->currentUser = $this->getFallbackGuestUser();
                $this->isIntranet = false;
            }
        } else {
            $this->currentUser = $this->getFallbackGuestUser();
            $this->isIntranet = false;
        }
    }

    private function getFallbackGuestUser() {
        return [
            'id' => null,
            'username' => 'guest',
            'role' => 'guest',
            'status' => 'active',
            'is_guest' => true
        ];
    }

    public function getCurrentUser() {
        return $this->currentUser;
    }

    public function isIntranetAccess() {
        return $this->isIntranet;
    }

    public function getAccessContext() {
        $clientIp = '';
        if ($this->ipMiddleware && method_exists($this->ipMiddleware, 'getCurrentIp')) {
            try {
                $clientIp = $this->ipMiddleware->getCurrentIp();
            } catch (Exception $e) {
                $clientIp = 'unknown';
            }
        }
        
        return [
            'user' => [
                'id' => $this->currentUser['id'],
                'username' => $this->currentUser['username'],
                'role' => $this->currentUser['role'],
                'is_guest' => $this->currentUser['role'] === 'guest'
            ],
            'is_intranet' => $this->isIntranet,
            'client_ip' => $clientIp
        ];
    }

    public function healthCheck() {
        $dbStatus = $this->db ? 'connected' : 'disconnected';
        $dbDetails = [];
        
        if ($this->db) {
            try {
                $stmt = $this->db->query("SELECT 1");
                $dbDetails['ping'] = 'ok';
                
                $tables = ['users', 'ip_whitelist', 'intranet_resources', 'favorites', 'search_history', 'user_tokens', 'system_logs'];
                $tableStatus = [];
                foreach ($tables as $table) {
                    try {
                        $this->db->query("SELECT 1 FROM $table LIMIT 1");
                        $tableStatus[$table] = 'exists';
                    } catch (PDOException $e) {
                        $tableStatus[$table] = 'missing';
                    }
                }
                $dbDetails['tables'] = $tableStatus;
            } catch (Exception $e) {
                $dbDetails['ping'] = 'error';
                $dbDetails['error'] = $e->getMessage();
            }
        }
        
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'Torrent Search API (PHP)',
            'database' => [
                'status' => $dbStatus,
                'details' => $dbDetails
            ],
            'access_context' => $this->getAccessContext()
        ]);
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
            return;
        }

        $user = $this->user->findByUsername($data->username);
        
        if (!$user || $user['status'] !== 'active') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => '用户名或密码错误，或账户已被禁用']);
            return;
        }

        if (!$this->user->verifyPassword($data->password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
            return;
        }

        $token = $this->user->createToken($user['id']);
        
        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'real_name' => $user['real_name'],
                    'department' => $user['department']
                ]
            ]
        ]);
    }

    public function getProviders() {
        $providers = [
            'all' => ['1337x', 'Yts', 'ThePirateBay', 'Rarbg', 'Torrent9'],
            'active' => ['1337x', 'Yts']
        ];

        if ($this->isIntranet && $this->currentUser['role'] !== 'guest') {
            $providers['all'][] = 'intranet';
            $providers['active'][] = 'intranet';
        }

        echo json_encode([
            'success' => true,
            'data' => $providers,
            'access_context' => $this->getAccessContext()
        ]);
    }

    public function search($keyword, $query, $page = 1) {
        $this->searchHistory->user_id = $this->currentUser['id'];
        $this->searchHistory->keyword = $keyword;
        $this->searchHistory->query = $query;
        $this->searchHistory->create();

        $results = [];

        if ($keyword === 'intranet') {
            if (!$this->isIntranet || $this->currentUser['role'] === 'guest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '内网资源仅允许内网员工访问']);
                return;
            }
            $results = $this->intranetResource->search($query);
            $results = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'Name' => $item['name'] . ' ' . $item['version'],
                    'ResourceType' => 'intranet',
                    'IntranetType' => $item['resource_type'],
                    'Version' => $item['version'],
                    'Description' => $item['description'],
                    'Url' => $item['url'],
                    'Maintainer' => $item['maintainer_name'],
                    'MaintainerId' => $item['maintainer_id'],
                    'ExpireDate' => $item['expire_date'],
                    'ExpireStatus' => $item['expire_status'],
                    'Category' => $this->getResourceTypeName($item['resource_type']),
                    'Source' => 'intranet'
                ];
            }, $results);
        } else {
            $results = $this->generateDemoData($query, $keyword, $page);
        }

        echo json_encode([
            'success' => true,
            'data' => $results,
            'meta' => [
                'keyword' => $keyword,
                'query' => $query,
                'page' => (int)$page,
                'count' => count($results),
                'demo' => $keyword !== 'intranet'
            ]
        ]);
    }

    public function getHistory() {
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
        
        if ($this->currentUser['role'] === 'guest') {
            $history = $this->searchHistory->findAll($limit);
        } else {
            $history = $this->searchHistory->findAllByUser($this->currentUser['id'], $limit);
        }

        echo json_encode([
            'success' => true,
            'data' => $history
        ]);
    }

    public function clearHistory() {
        if ($this->currentUser['role'] === 'guest') {
            $result = $this->searchHistory->deleteAll();
        } else {
            $result = $this->searchHistory->deleteAllByUser($this->currentUser['id']);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => '搜索历史已清空']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '清空搜索历史失败']);
        }
    }

    public function addFavorite() {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        if ($this->currentUser['role'] === 'guest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '请先登录后再收藏']);
            return;
        }

        $resourceType = isset($data->resource_type) ? $data->resource_type : 'public';

        if ($resourceType === 'intranet') {
            if (!$this->isIntranet) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '内网资源仅允许内网访问时收藏']);
                return;
            }
            
            if ($this->favorite->findOneByIntranetResourceAndUser($data->intranet_resource_id, $this->currentUser['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '该资源已在收藏列表中']);
                return;
            }

            $this->favorite->user_id = $this->currentUser['id'];
            $this->favorite->resource_type = 'intranet';
            $this->favorite->intranet_resource_id = $data->intranet_resource_id;
            $this->favorite->name = $data->name;
            $this->favorite->category = $data->category;
        } else {
            if ($this->favorite->findOneByMagnetAndUser($data->magnet, $this->currentUser['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '该资源已在收藏列表中']);
                return;
            }

            $this->favorite->user_id = $this->currentUser['id'];
            $this->favorite->resource_type = 'public';
            $this->favorite->name = $data->name;
            $this->favorite->magnet = $data->magnet;
            $this->favorite->size = $data->size;
            $this->favorite->seeders = $data->seeders ?? 0;
            $this->favorite->leechers = $data->leechers ?? 0;
            $this->favorite->category = $data->category;
            $this->favorite->source = $data->source;
        }

        if ($this->favorite->create()) {
            echo json_encode([
                'success' => true, 
                'message' => '收藏成功', 
                'data' => $this->favorite
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '收藏失败']);
        }
    }

    public function getFavorites() {
        if ($this->currentUser['role'] === 'guest') {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }

        $resourceType = isset($_GET['type']) ? $_GET['type'] : null;
        
        if ($this->currentUser['role'] === 'admin') {
            $favorites = $this->favorite->findAll();
        } else {
            $favorites = $this->favorite->findAllByUser($this->currentUser['id'], $resourceType);
        }

        if (!$this->isIntranet) {
            $favorites = array_filter($favorites, function($item) {
                return $item['resource_type'] !== 'intranet';
            });
        }

        echo json_encode([
            'success' => true,
            'data' => array_values($favorites)
        ]);
    }

    public function deleteFavorite($id) {
        if ($this->currentUser['role'] === 'guest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $this->currentUser['role'] === 'admin' ? null : $this->currentUser['id'];
        
        if ($this->favorite->delete($id, $userId)) {
            echo json_encode(['success' => true, 'message' => '删除成功']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '收藏不存在']);
        }
    }

    public function getUsers() {
        $this->authMiddleware->requireRole(['admin']);
        
        $role = isset($_GET['role']) ? $_GET['role'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        $users = $this->user->findAll($role, $status);
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    }

    public function createUser() {
        $this->authMiddleware->requireRole(['admin']);
        
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->username) || empty($data->email) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请填写完整信息']);
            return;
        }

        $this->user->username = $data->username;
        $this->user->email = $data->email;
        $this->user->password_hash = $this->user->hashPassword($data->password);
        $this->user->role = $data->role ?? 'employee';
        $this->user->status = $data->status ?? 'active';
        $this->user->real_name = $data->real_name ?? '';
        $this->user->department = $data->department ?? '';

        if ($this->user->create()) {
            echo json_encode([
                'success' => true,
                'message' => '用户创建成功',
                'data' => $this->user->findById($this->user->id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '用户创建失败']);
        }
    }

    public function updateUserStatus($id) {
        $this->authMiddleware->requireRole(['admin']);
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || empty($data->status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '状态不能为空']);
            return;
        }

        if ($this->user->updateStatus($id, $data->status)) {
            $message = '用户状态更新成功';
            if ($data->status === 'resigned') {
                $message = '用户已标记为离职，其内网资源收藏已自动收回';
            }
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新失败']);
        }
    }

    public function getIntranetResources() {
        if (!$this->isIntranet || $this->currentUser['role'] === 'guest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '内网资源仅允许内网员工访问']);
            return;
        }

        $resourceType = isset($_GET['type']) ? $_GET['type'] : null;
        $includeExpired = isset($_GET['include_expired']) ? $_GET['include_expired'] === 'true' : false;
        
        $resources = $this->intranetResource->findAll($resourceType, $includeExpired);
        
        echo json_encode([
            'success' => true,
            'data' => $resources
        ]);
    }

    public function getIntranetResource($id) {
        if (!$this->isIntranet || $this->currentUser['role'] === 'guest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '内网资源仅允许内网员工访问']);
            return;
        }

        $resource = $this->intranetResource->findById($id);
        
        if ($resource) {
            echo json_encode(['success' => true, 'data' => $resource]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '资源不存在']);
        }
    }

    public function createIntranetResource() {
        $this->authMiddleware->requireRole(['admin', 'employee']);
        $this->ipMiddleware->requireIntranet();
        
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->name) || empty($data->resource_type) || empty($data->version) || empty($data->maintainer_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请填写必填信息']);
            return;
        }

        $this->intranetResource->name = $data->name;
        $this->intranetResource->resource_type = $data->resource_type;
        $this->intranetResource->version = $data->version;
        $this->intranetResource->description = $data->description ?? '';
        $this->intranetResource->url = $data->url ?? '';
        $this->intranetResource->maintainer_id = $data->maintainer_id;
        $this->intranetResource->expire_date = !empty($data->expire_date) ? $data->expire_date : null;
        $this->intranetResource->is_active = $data->is_active ?? 1;

        if ($this->intranetResource->create()) {
            echo json_encode([
                'success' => true,
                'message' => '资源创建成功',
                'data' => $this->intranetResource->findById($this->intranetResource->id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '资源创建失败']);
        }
    }

    public function updateIntranetResource($id) {
        $this->authMiddleware->requireRole(['admin', 'employee']);
        $this->ipMiddleware->requireIntranet();
        
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->name) || empty($data->resource_type) || empty($data->version) || empty($data->maintainer_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请填写必填信息']);
            return;
        }

        $this->intranetResource->name = $data->name;
        $this->intranetResource->resource_type = $data->resource_type;
        $this->intranetResource->version = $data->version;
        $this->intranetResource->description = $data->description ?? '';
        $this->intranetResource->url = $data->url ?? '';
        $this->intranetResource->maintainer_id = $data->maintainer_id;
        $this->intranetResource->expire_date = !empty($data->expire_date) ? $data->expire_date : null;
        $this->intranetResource->is_active = $data->is_active ?? 1;

        if ($this->intranetResource->update($id)) {
            echo json_encode([
                'success' => true,
                'message' => '资源更新成功',
                'data' => $this->intranetResource->findById($id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '资源更新失败']);
        }
    }

    public function deleteIntranetResource($id) {
        $this->authMiddleware->requireRole(['admin']);
        $this->ipMiddleware->requireIntranet();
        
        if ($this->intranetResource->delete($id)) {
            echo json_encode(['success' => true, 'message' => '资源删除成功，相关收藏已同步移除']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '资源不存在']);
        }
    }

    public function getExpiringResources() {
        $this->authMiddleware->requireRole(['admin', 'employee']);
        $this->ipMiddleware->requireIntranet();
        
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $expiring = $this->intranetResource->getExpiringResources($days);
        $expired = $this->intranetResource->getExpiredResources();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'expiring' => $expiring,
                'expired' => $expired,
                'stats' => [
                    'expiring_count' => count($expiring),
                    'expired_count' => count($expired)
                ]
            ]
        ]);
    }

    public function runMaintenance() {
        $this->authMiddleware->requireRole(['admin']);
        
        $result = $this->intranetResource->deactivateExpiredResources();
        
        echo json_encode([
            'success' => true,
            'message' => '维护任务执行完成',
            'data' => $result
        ]);
    }

    public function getIPWhitelist() {
        $this->authMiddleware->requireRole(['admin']);
        
        $whitelist = $this->ipWhitelist->findAll();
        
        echo json_encode([
            'success' => true,
            'data' => $whitelist
        ]);
    }

    public function createIPWhitelist() {
        $this->authMiddleware->requireRole(['admin']);
        
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->ip_start) || empty($data->ip_end)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请填写IP范围']);
            return;
        }

        $this->ipWhitelist->ip_start = $data->ip_start;
        $this->ipWhitelist->ip_end = $data->ip_end;
        $this->ipWhitelist->description = $data->description ?? '';
        $this->ipWhitelist->created_by = $this->currentUser['id'];

        if ($this->ipWhitelist->create()) {
            echo json_encode([
                'success' => true,
                'message' => 'IP白名单添加成功',
                'data' => $this->ipWhitelist->findById($this->ipWhitelist->id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '添加失败']);
        }
    }

    public function deleteIPWhitelist($id) {
        $this->authMiddleware->requireRole(['admin']);
        
        if ($this->ipWhitelist->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'IP白名单删除成功']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '记录不存在']);
        }
    }

    public function updateIPWhitelist($id) {
        $this->authMiddleware->requireRole(['admin']);
        
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->ip_start) || empty($data->ip_end)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请填写IP范围']);
            return;
        }

        $this->ipWhitelist->ip_start = $data->ip_start;
        $this->ipWhitelist->ip_end = $data->ip_end;
        $this->ipWhitelist->description = $data->description ?? '';

        if ($this->ipWhitelist->update($id)) {
            echo json_encode([
                'success' => true,
                'message' => 'IP白名单更新成功',
                'data' => $this->ipWhitelist->findById($id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新失败']);
        }
    }

    public function toggleIntranetResource($id) {
        $this->authMiddleware->requireRole(['admin']);
        $this->ipMiddleware->requireIntranet();
        
        $data = json_decode(file_get_contents("php://input"));
        $isActive = isset($data->is_active) ? (int)$data->is_active : 1;
        
        if ($this->intranetResource->toggleActive($id, $isActive)) {
            echo json_encode([
                'success' => true,
                'message' => $isActive ? '资源已启用' : '资源已停用，相关收藏已移除'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '操作失败']);
        }
    }

    public function reassignMaintainer() {
        $this->authMiddleware->requireRole(['admin']);
        $this->ipMiddleware->requireIntranet();
        
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->old_maintainer_id) || empty($data->new_maintainer_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请指定原负责人和新负责人']);
            return;
        }

        if ($this->intranetResource->reassignMaintainer($data->old_maintainer_id, $data->new_maintainer_id)) {
            echo json_encode([
                'success' => true,
                'message' => '负责人重新分配成功'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '操作失败']);
        }
    }

    public function getOrphanedResources() {
        $this->authMiddleware->requireRole(['admin']);
        $this->ipMiddleware->requireIntranet();
        
        $query = "SELECT ir.*, u.real_name as maintainer_name, u.status as maintainer_status
                  FROM intranet_resources ir
                  LEFT JOIN users u ON ir.maintainer_id = u.id
                  WHERE ir.is_active = 1 AND (u.status != 'active' OR u.id IS NULL)
                  ORDER BY ir.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $resources,
                'stats' => [
                    'count' => count($resources)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '查询失败: ' . $e->getMessage()]);
        }
    }

    public function getSystemLogs() {
        $this->authMiddleware->requireRole(['admin']);
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $logType = isset($_GET['type']) ? $_GET['type'] : null;
        
        $query = "SELECT * FROM system_logs WHERE 1=1";
        $params = [];
        
        if ($logType) {
            $query .= " AND log_type = :log_type";
            $params[':log_type'] = $logType;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $logs
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '查询失败: ' . $e->getMessage()]);
        }
    }

    private function getResourceTypeName($type) {
        $types = [
            'component' => '组件仓库',
            'design' => '设计规范',
            'deploy' => '部署脚本'
        ];
        return $types[$type] ?? $type;
    }

    private function generateDemoData($query, $provider, $page) {
        $demoTorrents = [];
        
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $randomString = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
            
            $item = [
                'Name' => "$query Result " . ($i + 1) . " [$provider] [1080p]",
                'Magnet' => "magnet:?xt=urn:btih:DEMO$randomString&dn=" . urlencode($query),
                'Size' => rand(1, 20) . "." . rand(0, 99) . " GB",
                'Seeders' => rand(50, 2000),
                'Leechers' => rand(10, 500),
                'Category' => 'Movies',
                'Url' => "https://example.com/torrent/" . strtolower(str_replace(' ', '-', $query)) . "-$i",
                'DateUploaded' => rand(1, 30) . ' days ago',
                'ResourceType' => 'public'
            ];
            $demoTorrents[] = $item;
        }
        return $demoTorrents;
    }
}
