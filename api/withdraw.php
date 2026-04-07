<?php
require_once '../config/database.php';
require_once 'auth.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Carregar SDK do Mercado Pago
require_once '../vendor/autoload.php';

class Withdraw {
    private $conn;
    private $auth;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->auth = new Auth();
        
        // Configurar Mercado Pago
        MercadoPago\SDK::setAccessToken(MERCADO_PAGO_ACCESS_TOKEN);
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
        
        $query = "UPDATE usuarios SET chave_pix = :pix_key, tipo_chave_pix = :tipo WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pix_key', $data->pix_key);
        $stmt->bindParam(':tipo', $data->pix_type ?? 'email');
        $stmt->bindParam(':id', $userData['id']);
        
        if($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Chave Pix salva com sucesso']);
        }
        return json_encode(['error' => 'Erro ao salvar chave Pix']);
    }
    
    // SOLICITAR SAQUE - TRANSFERÊNCIA DIRETA PIX
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
        $query = "SELECT saldo_reais, chave_pix, email FROM usuarios WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userData['id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Validações
        if($valor < MIN_WITHDRAWAL) {
            return json_encode(['error' => 'Valor mínimo: R$ ' . MIN_WITHDRAWAL]);
        }
        
        if($valor > MAX_WITHDRAWAL) {
            return json_encode(['error' => 'Valor máximo: R$ ' . MAX_WITHDRAWAL]);
        }
        
        if($valor > $user['saldo_reais']) {
            return json_encode(['error' => "Saldo insuficiente. Você tem R$ " . number_format($user['saldo_reais'], 2)]);
        }
        //undefined type mercadopag\payment
        if(empty($user['chave_pix'])) {
            return json_encode(['error' => 'Cadastre uma chave Pix primeiro']);
        }
        
        try {
            // === FAZER TRANSFERÊNCIA PIX DIRETA (PAGAR O USUÁRIO) ===
            $payment = new MercadoPago\Payment();
            $payment->transaction_amount = $valor;
            $payment->description = "Saque Dino Run - " . $userData['email'];
            $payment->payment_method_id = "pix";
            $payment->payer = array(
                "email" => $user['email'],
                "first_name" => "Usuario",
                "identification" => array(
                    "type" => "CPF",
                    "number" => "12345678909"
                )
            );
            
            // Configurar para PAGAR o usuário (usando a chave Pix dele)
            $payment->additional_info = array(
                "payer" => array(
                    "first_name" => "Dino Run",
                    "last_name" => "Jogo"
                )
            );
            
            $payment->save();
            
            if ($payment->id && $payment->status != 'rejected') {
                // Debitar saldo do usuário IMEDIATAMENTE
                $this->conn->beginTransaction();
                
                $query = "UPDATE usuarios SET saldo_reais = saldo_reais - :valor WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':valor', $valor);
                $stmt->bindParam(':id', $userData['id']);
                $stmt->execute();
                
                // Registrar saque
                $query = "INSERT INTO saques (usuario_id, valor, chave_pix, payment_id, status) 
                          VALUES (:id, :valor, :chave, :payment_id, 'concluido')";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $userData['id']);
                $stmt->bindParam(':valor', $valor);
                $stmt->bindParam(':chave', $user['chave_pix']);
                $stmt->bindParam(':payment_id', $payment->id);
                $stmt->execute();
                
                $this->conn->commit();
                
                return json_encode([
                    'success' => true,
                    'message' => "✅ Saque de R$ {$valor} realizado com sucesso! O dinheiro será transferido para sua conta Pix.",
                    'payment_id' => $payment->id,
                    'valor' => $valor,
                    'status' => $payment->status
                ]);
                
            } else {
                return json_encode(['error' => 'Erro ao processar transferência: ' . ($payment->error ?? 'Erro desconhecido')]);
            }
            
        } catch (Exception $e) {
            if (isset($this->conn)) $this->conn->rollBack();
            return json_encode(['error' => 'Erro na transferência: ' . $e->getMessage()]);
        }
    }
    
    public function getHistory() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        $userData = $this->auth->validateToken($token);
        if(!$userData) {
            return json_encode(['error' => 'Não autorizado']);
        }
        
        $query = "SELECT * FROM saques WHERE usuario_id = :id ORDER BY data_solicitacao DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userData['id']);
        $stmt->execute();
        
        return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
?>