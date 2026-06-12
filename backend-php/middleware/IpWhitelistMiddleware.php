<?php

require_once __DIR__ . '/../models/IPWhitelist.php';

class IpWhitelistMiddleware {
    private $db;
    private $ipWhitelistModel;

    public function __construct($db) {
        $this->db = $db;
        $this->ipWhitelistModel = new IPWhitelist($db);
    }

    public function isIntranetAccess() {
        $clientIp = $this->getClientIp();
        return $this->ipWhitelistModel->isIpInWhitelist($clientIp);
    }

    public function requireIntranet() {
        if (!$this->isIntranetAccess()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '该资源仅允许内网访问']);
            exit;
        }
        return true;
    }

    private function getClientIp() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipArray[0]);
        } elseif (isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        return $ip;
    }

    public function getCurrentIp() {
        return $this->getClientIp();
    }
}
