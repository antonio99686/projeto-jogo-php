<?php
require_once '../config/database.php';
require_once '../config/config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            return json_encode(['error' => 'Email e senha são obrigatórios']);
        }
        
        // Verificar se email já existe
        $query = "SELECT id FROM usuarios WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            http_response_code(400);
            return json_encode(['error' => 'Email já cadastrado']);
        }
        
        // Criar usuário
        $senha_hash = password_hash($data->password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios (email, senha_hash) VALUES (:email, :senha_hash)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':senha_hash', $senha_hash);
        
        if($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Usuário criado com sucesso']);
        } else {
            http_response_code(500);
            return json_encode(['error' => 'Erro ao criar usuário']);
        }
    }
    
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(!isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            return json_encode(['error' => 'Email e senha são obrigatórios']);
        }
        
        $query = "SELECT id, email, senha_hash, pontos, saldo_reais FROM usuarios WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($data->password, $user['senha_hash'])) {
                // Gerar token simples (base64)
                $payload = json_encode([
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'exp' => time() + (7 * 24 * 60 * 60)
                ]);
                $token = base64_encode($payload);
                
                return json_encode([
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'pontos' => $user['pontos'],
                        'saldo_reais' => $user['saldo_reais']
                    ]
                ]);
            }
        }
        
        http_response_code(401);
        return json_encode(['error' => 'Credenciais inválidas']);
    }
    
    public function getUser() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        $userData = $this->validateToken($token);
        if(!$userData) {
            http_response_code(401);
            return json_encode(['error' => 'Token inválido']);
        }
        
        $query = "SELECT id, email, pontos, saldo_reais, chave_pix FROM usuarios WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userData['id']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return json_encode($user);
    }
    
    public function validateToken($token) {
        $decoded = json_decode(base64_decode($token), true);
        
        if(!$decoded || !isset($decoded['id'])) {
            return false;
        }
        
        if($decoded['exp'] < time()) {
            return false;
        }
        
        return $decoded;
    }
}
?>