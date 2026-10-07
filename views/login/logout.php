<?php
require_once __DIR__.'/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $cssPagina='/MyCashFlow/assets/css/perfil.css?v='.filemtime(__DIR__.'/../../assets/css/perfil.css');
    require __DIR__.'/../../includes/header.php';
    echo '<main class="perfil"><section><h2>Sair do sistema?</h2><p>Confirme para encerrar sua sessão.</p><form method="post">'.mcfCsrfField().'<button type="submit">Confirmar saída</button></form><p><a href="../dashboard.php">Continuar no sistema</a></p></section></main>';
    require __DIR__.'/../../includes/footer.php';exit;
}
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $p['path'],
        'domain' => $p['domain'],
        'secure' => $p['secure'],
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

session_destroy();

header('Location: login.php');
exit;