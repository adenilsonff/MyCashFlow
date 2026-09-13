<?php
// Informe o token existente aqui OU defina BRAPI_TOKEN no config.php/ambiente.
// Não compartilhe este arquivo depois de preencher suas credenciais.
return [
    'brapi_token' => defined('BRAPI_TOKEN') ? BRAPI_TOKEN : (getenv('BRAPI_TOKEN') ?: ''),
    'awesome_token' => getenv('AWESOME_API_KEY') ?: '',
    'ttl' => 180,
    'retry_after' => 60,
    'stale_max' => 604800,
    'batch_size' => 10,
    'cache_dir' => sys_get_temp_dir() . '/mycashflow-mercado-' . substr(hash('sha256', __DIR__), 0, 16),
];
