<?php
// Configurações do sistema
define('JWT_SECRET', 'seu-segredo-super-seguro-aqui-mude-para-algo-forte');
define('POINTS_TO_REAL', 100); // 100 pontos = R$ 1
define('MIN_WITHDRAWAL', 5);   // Mínimo R$ 5 para saque
define('MAX_WITHDRAWAL', 500); // Máximo R$ 500 por saque
define('DAILY_WITHDRAWAL_LIMIT', 200); // Limite diário R$ 200

// Configurações do Mercado Pago
// Use SEU token real do Mercado Pago
define('MERCADO_PAGO_ACCESS_TOKEN', 'APP_USR-5067385382129862-070111-7ebd87d96e82e1d80afd27f795bc2571-474362529');define('MERCADO_PAGO_PUBLIC_KEY', 'APP_USR-xxxxxxxxxxxxxx');
define('MERCADO_PAGO_WEBHOOK_URL', 'https://seudominio.com/webhook.php');

// Configurações do sistema
define('SITE_NAME', 'Dino Run');
define('SITE_URL', 'http://localhost/algo'); // Mude para seu domínio

// Headers para API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
?>