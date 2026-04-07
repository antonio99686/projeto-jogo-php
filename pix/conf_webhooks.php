<?php
// Verificação de cURL
if (!extension_loaded('curl')) {
    // Fallback usando file_get_contents
    function curl_get_contents($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n" .
                           "Authorization: Bearer APP_USR-1228299603673792-062511-2cf7ec6e1d129bd3c26d70331d1b71ab-474362529\r\n"
            ]
        ];
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }
    
    echo "Usando fallback sem cURL\n";
}

// Configure o SDK mesmo sem cURL
require_once 'vendor/autoload.php';
MercadoPago\SDK::setAccessToken('APP_USR-1228299603673792-062511-2cf7ec6e1d129bd3c26d70331d1b71ab-474362529');

// Restante do seu código...