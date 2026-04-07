<?php
require_once '../config/database.php';
require_once 'auth.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

class Withdraw {
    private $conn;
    private $auth;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->auth = new Auth();
    }
    
    public function savePixKey() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        $userData = $this->auth->validateToken($token);
        if(!$userData) {
            return json_encode(['error' => 'Não autorizado']);
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if(!isset($data->pix_key) || empty($data->pix_key)) {
            return json_encode(['error' => 'Chave Pix é obrigatória']);
        }
        
        $query = "UPDATE usuarios SET chave_pix = :pix_key WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pix_key', $data->pix_key);
        $stmt->bindParam(':id', $userData['id']);
        
        if($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Chave Pix salva com sucesso']);
        }
        return json_encode(['error' => 'Erro ao salvar chave Pix']);
    }
    
    public function requestWithdraw() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        $userData = $this->auth->validateToken($token);
        if(!$userData) {
            return json_encode(['error' => 'Não autorizado']);
        }
        
        $data = json_decode(file_get_contents("php://input"));
        $valor = floatval($data->valor ?? 0);
        
        // Buscar dados do usuário
        $query = "SELECT saldo_reais, chave_pix FROM usuarios WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userData['id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($valor < 5) {
            return json_encode(['error' => 'Valor mínimo: R$ 5,00']);
        }
        
        if($valor > $user['saldo_reais']) {
            return json_encode(['error' => "Saldo insuficiente. Você tem R$ " . number_format($user['saldo_reais'], 2)]);
        }
        
        if(empty($user['chave_pix'])) {
            return json_encode(['error' => 'Cadastre uma chave Pix primeiro']);
        }
        
        // QR Code de exemplo (modo teste)
        $qrCodeBase64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        $paymentId = 'TEST_' . time() . '_' . $userData['id'];
        
        // Registrar saque
        $query = "INSERT INTO saques (usuario_id, valor, chave_pix, payment_id, qr_code_base64, status) 
                  VALUES (:id, :valor, :chave, :payment_id, :qr_code, 'processando')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userData['id']);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':chave', $user['chave_pix']);
        $stmt->bindParam(':payment_id', $paymentId);
        $stmt->bindParam(':qr_code', $qrCodeBase64);
        
        if($stmt->execute()) {
            return json_encode([
                'success' => true,
                'qr_code_base64' => $qrCodeBase64,
                'copy_paste' => '00020126360014br.gov.bcb.pix0114' . $user['chave_pix'] . '5204000053039865404' . number_format($valor, 2, '', '') . '5802BR5913DINO RUN6008BRASILIA62070503***6304E2CA',
                'payment_id' => $paymentId,
                'message' => 'Saque solicitado com sucesso!'
            ]);
        }
        
        return json_encode(['error' => 'Erro ao registrar saque']);
    }
}
?>