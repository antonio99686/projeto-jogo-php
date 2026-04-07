<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Log para debug (opcional - remove em produção)
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - " . file_get_contents('php://input') . "\n", FILE_APPEND);

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar se é notificação de pagamento
if(isset($data['type']) && $data['type'] == 'payment') {
    $paymentId = $data['data']['id'];
    
    // Configurar Mercado Pago
    require_once '../vendor/autoload.php';
    
    // CORREÇÃO: Usar a classe correta do Mercado Pago
    MercadoPago\SDK::setAccessToken(MERCADO_PAGO_ACCESS_TOKEN);
    
    // CORREÇÃO: Buscar o pagamento corretamente
    try {
        $payment = MercadoPago\Payment::find_by_id($paymentId);
        
        // Verificar se o pagamento foi aprovado
        if($payment && $payment->status == 'approved') {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Verificar se o saque existe e está pendente
            $checkQuery = "SELECT id, usuario_id, valor, status FROM saques WHERE payment_id = :payment_id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':payment_id', $paymentId);
            $checkStmt->execute();
            $saqueExistente = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if($saqueExistente && $saqueExistente['status'] != 'concluido') {
                // Atualizar status do saque para concluído
                $query = "UPDATE saques SET status = 'concluido', data_processamento = NOW() WHERE payment_id = :payment_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':payment_id', $paymentId);
                $stmt->execute();
                
                // Debitar saldo do usuário
                $query = "UPDATE usuarios SET saldo_reais = saldo_reais - :valor WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':valor', $saqueExistente['valor']);
                $stmt->bindParam(':id', $saqueExistente['usuario_id']);
                $stmt->execute();
                
                // Registrar log de sucesso
                file_put_contents('webhook_log.txt', "Saque {$paymentId} confirmado para usuário {$saqueExistente['usuario_id']}\n", FILE_APPEND);
            }
        }
    } catch (Exception $e) {
        // Registrar erro
        file_put_contents('webhook_log.txt', "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Sempre retornar 200 OK para o Mercado Pago
http_response_code(200);
echo json_encode(['status' => 'ok']);
?>