<?php
require_once 'vendor/autoload.php';

// Verificar se cURL está ativo
if (!extension_loaded('curl')) {
    die('Erro: Extensão cURL não está ativada. Por favor, ative no php.ini');
}

// Configure o token
MercadoPago\SDK::setAccessToken('APP_USR-1228299603673792-062511-2cf7ec6e1d129bd3c26d70331d1b71ab-474362529');

$payment_id = "474362529";

try {
    $payment = MercadoPago\Payment::find_by_id($payment_id);
    
    if ($payment && $payment->id) {
        $payment->status = "cancelled";
        $payment->update();
        echo "✅ Pagamento cancelado com sucesso!";
    } else {
        echo "❌ Pagamento não encontrado com o ID fornecido.";
    }
} catch (Exception $e) {
    echo '❌ Erro ao cancelar o pagamento: ' . $e->getMessage();
}
?>