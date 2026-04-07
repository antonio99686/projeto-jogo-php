<?php
// TESTE: Verificar se cURL está ativo ANTES de usar SDK
if (!extension_loaded('curl')) {
    die('❌ cURL NÃO está ativo! Siga os passos para ativar no WAMP.');
}
echo '✅ cURL está ativo!<br><br>';

require_once 'vendor/autoload.php';

// Configure as credenciais do Mercado Pago
MercadoPago\SDK::setAccessToken('APP_USR-1228299603673792-062511-2cf7ec6e1d129bd3c26d70331d1b71ab-474362529');

// Id do pagamento a ser consultado (coloque um ID real)
$payment_id = "ID_DO_PAGAMENTO_REAL";

try {
    // Consultar o pagamento
    $payment = MercadoPago\Payment::find_by_id($payment_id);
    
    if ($payment && $payment->id) {
        // Exibir detalhes do pagamento
        echo "Status: " . $payment->status . "<br>";
        echo "Valor: " . $payment->transaction_amount . "<br>";
        echo "Método de Pagamento: " . $payment->payment_method_id . "<br>";
        echo "Email do Pagador: " . $payment->payer->email . "<br>";
    } else {
        echo "Pagamento não encontrado";
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>