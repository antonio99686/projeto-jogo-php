<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

echo json_encode([
    "status" => "success",
    "message" => "API do Dino Run está funcionando!",
    "server" => "WampServer",
    "timestamp" => date('Y-m-d H:i:s')
]);
?>