<?php
require_once '../config/database.php';
require_once 'auth.php';

class Game {
    private $conn;
    private $auth;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->auth = new Auth();
    }
    
    public function updateScore() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        $userData = $this->auth->validateToken($token);
        if(!$userData) {
            http_response_code(401);
            return json_encode(['error' => 'Não autorizado']);
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if(!isset($data->pontos)) {
            http_response_code(400);
            return json_encode(['error' => 'Pontos não informados']);
        }
        
        $query = "UPDATE usuarios SET pontos = :pontos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pontos', $data->pontos);
        $stmt->bindParam(':id', $userData['id']);
        
        if($stmt->execute()) {
            return json_encode(['success' => true]);
        }
        
        return json_encode(['error' => 'Erro ao atualizar pontuação']);
    }
    
    public function convertPoints() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        $userData = $this->auth->validateToken($token);
        if(!$userData) {
            http_response_code(401);
            return json_encode(['error' => 'Não autorizado']);
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if(!isset($data->pontos) || $data->pontos <= 0) {
            http_response_code(400);
            return json_encode(['error' => 'Pontos inválidos']);
        }
        
        $valorReais = $data->pontos / 100;
        
        // Verificar se tem pontos suficientes
        $query = "SELECT pontos FROM usuarios WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userData['id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user['pontos'] < $data->pontos) {
            http_response_code(400);
            return json_encode(['error' => 'Pontos insuficientes']);
        }
        
        // Converter pontos em saldo
        $query = "UPDATE usuarios SET pontos = pontos - :pontos, saldo_reais = saldo_reais + :valor WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pontos', $data->pontos);
        $stmt->bindParam(':valor', $valorReais);
        $stmt->bindParam(':id', $userData['id']);
        
        if($stmt->execute()) {
            return json_encode([
                'success' => true,
                'valor_reais' => $valorReais,
                'message' => "Convertido R$ {$valorReais} com sucesso!"
            ]);
        }
        
        return json_encode(['error' => 'Erro na conversão']);
    }
}
?>