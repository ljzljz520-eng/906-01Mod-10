<?php

require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    public function authenticate() {
        $headers = $this->getAllHeaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if ($token) {
            $user = $this->userModel->findByToken($token);
            if ($user && $user['status'] === 'active') {
                return $user;
            }
        }

        return $this->getGuestUser();
    }

    public function requireRole($roles) {
        $user = $this->authenticate();
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if (!in_array($user['role'], $roles)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '权限不足']);
            exit;
        }
        return $user;
    }

    private function getGuestUser() {
        return [
            'id' => null,
            'username' => 'guest',
            'role' => 'guest',
            'status' => 'active',
            'is_guest' => true
        ];
    }

    private function getAllHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
}
