<?php
class Database {
    private $host = "localhost";
    private $db_name = "jogo_pontos";
    private $username = "root";      // Usuário padrão do Wamp
    private $password = "";           // Senha vazia no Wamp padrão
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
            
            // Debug - comentar depois
            error_log("Conexão com banco realizada com sucesso");
            
        } catch(PDOException $e) {
            error_log("Erro na conexão: " . $e->getMessage());
            echo json_encode(["error" => "Erro no banco de dados: " . $e->getMessage()]);
            die();
        }
        
        return $this->conn;
    }
}
?>