<?php
echo "=== DIAGNÓSTICO DO WAMP ===<br><br>";

echo "1. PHP versão: " . phpversion() . "<br><br>";

echo "2. Arquivo php.ini carregado: " . php_ini_loaded_file() . "<br><br>";

echo "3. Extensões carregadas:<br>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach($extensions as $ext) {
    if(strpos($ext, 'curl') !== false || strpos($ext, 'openssl') !== false) {
        echo "✅ $ext<br>";
    }
}
echo "<br>";

echo "4. Verificando cURL especificamente:<br>";
if(function_exists('curl_version')) {
    $curl_version = curl_version();
    echo "✅ cURL está ATIVO! Versão: " . $curl_version['version'] . "<br>";
} else {
    echo "❌ cURL está DESATIVADO!<br>";
}

echo "<br>5. Diretório de extensões: " . ini_get('extension_dir') . "<br>";

echo "<br>6. Arquivos DLL no diretório de extensões:<br>";
$ext_dir = ini_get('extension_dir');
if(is_dir($ext_dir)) {
    if(file_exists($ext_dir . '/php_curl.dll')) {
        echo "✅ php_curl.dll encontrado<br>";
    } else {
        echo "❌ php_curl.dll NÃO encontrado!<br>";
    }
}
?>