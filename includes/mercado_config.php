<?php
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

// Informe o token existente aqui OU defina BRAPI_TOKEN no config.php/ambiente.
// Não compartilhe este arquivo depois de preencher suas credenciais.
return [
    'brapi_token' => defined('BRAPI_TOKEN') ? BRAPI_TOKEN : (getenv('BRAPI_TOKEN') ?: ''),
    'awesome_token' => getenv('AWESOME_API_KEY') ?: '',
    'ttl' => 180,
    'retry_after' => 60,
    'stale_max' => 604800,
    'batch_size' => 10,
    'cache_dir' => __DIR__ . '/cache/mercado',
];
