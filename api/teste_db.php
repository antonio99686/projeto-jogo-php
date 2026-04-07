<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if($conn) {
    echo json_encode(["status" => "Banco de dados conectado com sucesso!"]);
} else {
    echo json_encode(["status" => "Erro na conexão com banco"]);
}
?>