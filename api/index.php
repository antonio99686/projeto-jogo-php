<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir arquivos
require_once '../config/database.php';
require_once 'auth.php';
require_once 'game.php';
require_once 'withdraw.php';

// Pegar o endpoint da URL
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$endpoint = end($parts);

// Se for requisição vazia, mostrar status
if (empty($endpoint) || $endpoint == 'index.php') {
    echo json_encode([
        'status' => 'online',
        'message' => 'API funcionando!',
        'time' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Instanciar classes
$auth = new Auth();
$game = new Game();
$withdraw = new Withdraw();

// Roteamento
try {
    switch($endpoint) {
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo $auth->register();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo $auth->login();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'user':
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                echo $auth->getUser();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'update-score':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo $game->updateScore();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'convert-points':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo $game->convertPoints();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'save-pix':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo $withdraw->savePixKey();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'withdraw':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo $withdraw->requestWithdraw();
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint não encontrado: ' . $endpoint]);
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
?>