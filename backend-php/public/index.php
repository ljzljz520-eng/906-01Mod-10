<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../controllers/ApiController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$api = new ApiController();

if ($uri === '/health' && $method === 'GET') {
    $api->healthCheck();
}
elseif ($uri === '/api/access-context' && $method === 'GET') {
    echo json_encode([
        'success' => true,
        'data' => $api->getAccessContext()
    ]);
}
elseif ($uri === '/api/login' && $method === 'POST') {
    $api->login();
}
elseif ($uri === '/api/providers' && $method === 'GET') {
    $api->getProviders();
}
elseif (preg_match('#^/api/search/([^/]+)/([^/]+)(?:/([^/]+))?$#', $uri, $matches) && $method === 'GET') {
    $keyword = urldecode($matches[1]);
    $query = urldecode($matches[2]);
    $page = isset($matches[3]) ? $matches[3] : 1;
    $api->search($keyword, $query, $page);
}
elseif ($uri === '/api/history' && $method === 'GET') {
    $api->getHistory();
}
elseif ($uri === '/api/history' && $method === 'DELETE') {
    $api->clearHistory();
}
elseif ($uri === '/api/favorites' && $method === 'GET') {
    $api->getFavorites();
}
elseif ($uri === '/api/favorites' && $method === 'POST') {
    $api->addFavorite();
}
elseif (preg_match('#^/api/favorites/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    $id = $matches[1];
    $api->deleteFavorite($id);
}
elseif ($uri === '/api/users' && $method === 'GET') {
    $api->getUsers();
}
elseif ($uri === '/api/users' && $method === 'POST') {
    $api->createUser();
}
elseif (preg_match('#^/api/users/(\d+)/status$#', $uri, $matches) && $method === 'PUT') {
    $id = $matches[1];
    $api->updateUserStatus($id);
}
elseif ($uri === '/api/intranet-resources' && $method === 'GET') {
    $api->getIntranetResources();
}
elseif (preg_match('#^/api/intranet-resources/(\d+)$#', $uri, $matches) && $method === 'GET') {
    $id = $matches[1];
    $api->getIntranetResource($id);
}
elseif ($uri === '/api/intranet-resources' && $method === 'POST') {
    $api->createIntranetResource();
}
elseif (preg_match('#^/api/intranet-resources/(\d+)$#', $uri, $matches) && $method === 'PUT') {
    $id = $matches[1];
    $api->updateIntranetResource($id);
}
elseif (preg_match('#^/api/intranet-resources/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    $id = $matches[1];
    $api->deleteIntranetResource($id);
}
elseif ($uri === '/api/intranet-resources/expiring' && $method === 'GET') {
    $api->getExpiringResources();
}
elseif ($uri === '/api/intranet-resources/maintenance' && $method === 'POST') {
    $api->runMaintenance();
}
elseif ($uri === '/api/ip-whitelist' && $method === 'GET') {
    $api->getIPWhitelist();
}
elseif ($uri === '/api/ip-whitelist' && $method === 'POST') {
    $api->createIPWhitelist();
}
elseif (preg_match('#^/api/ip-whitelist/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    $id = $matches[1];
    $api->deleteIPWhitelist($id);
}
elseif (preg_match('#^/api/ip-whitelist/(\d+)$#', $uri, $matches) && $method === 'PUT') {
    $id = $matches[1];
    $api->updateIPWhitelist($id);
}
elseif (preg_match('#^/api/intranet-resources/(\d+)/toggle$#', $uri, $matches) && $method === 'POST') {
    $id = $matches[1];
    $api->toggleIntranetResource($id);
}
elseif ($uri === '/api/intranet-resources/reassign-maintainer' && $method === 'POST') {
    $api->reassignMaintainer();
}
elseif ($uri === '/api/intranet-resources/orphaned' && $method === 'GET') {
    $api->getOrphanedResources();
}
elseif ($uri === '/api/system-logs' && $method === 'GET') {
    $api->getSystemLogs();
}
else {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found", "uri" => $uri]);
}
