<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

echo json_encode([
    "success" => true,
    "qr_code_base64" => "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
    "copy_paste" => "00020126360014br.gov.bcb.pix0114teste@email.com52040000530398654045.005802BR5913Teste Jogo6008BRASILIA62070503***6304E2CA",
    "message" => "Teste funcionando!"
]);
?>