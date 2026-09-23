<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

// Ator autenticado nunca muda. Proprietário só muda por autorização explícita nesta requisição.
function mcfDonoId(): int {
    return isset($GLOBALS['mcf_contexto']) ? (int)$GLOBALS['mcf_contexto']['proprietario_id'] : mcfUsuarioId();
}
function mcfUsuarioId(): int {
    $id = filter_var($_SESSION['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if (!$id) mcfFalhar(401, 'Entre novamente para continuar.');
    return (int)$id;
}
function mcfFalhar(int $status, string $message): never {
    http_response_code($status);
    header('Cache-Control: no-store, private');
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api.php')) {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['ok'=>false,'message'=>$message], JSON_UNESCAPED_UNICODE));
    }
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    if ($status===401) echo ' <a href="/MyCashFlow/views/login/login.php">Entrar</a>';
    exit;
}
function mcfCsrfField(): string {
    return '<input type="hidden" name="mcf_csrf" value="'.htmlspecialchars($_SESSION['mcf_csrf'] ?? '',ENT_QUOTES,'UTF-8').'">';
}
function mcfExigirCorretora(mysqli $conn, int $id): void {
    $uid=mcfDonoId();
    $s=$conn->prepare('SELECT id FROM corretoras WHERE id=? AND usuario_id=?');
    $s->bind_param('ii',$id,$uid); $s->execute(); $exists=$s->get_result()->fetch_assoc(); $s->close();
    if (!$exists) mcfFalhar(404,'Registro não disponível.');
}
function mcfMensagemErro(Throwable $error): string {
    return 'Não foi possível concluir a operação.';
}
function mcfIniciarSessao(): void {
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    session_name(getenv('MCF_SESSION_NAME') ?: 'MCFSESSID');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
function mcfProteger(mysqli $conn): void {
    if (PHP_SAPI === 'cli') return;
    mcfIniciarSessao();
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: SAMEORIGIN');
    $route = str_replace('\\','/',substr(realpath($_SERVER['SCRIPT_FILENAME']),strlen(realpath(__DIR__.'/..'))));
    $public = in_array($route,['/views/login/login.php','/views/login/register.php','/views/login/esqueci_senha.php','/views/login/redefinir_senha.php'],true);
    if (!$public) {
        $uid = mcfUsuarioId();
        $s = $conn->prepare('SELECT senha,nome,email,status_assinatura,data_expiracao FROM usuarios WHERE id=?');
        $s->bind_param('i',$uid); $s->execute(); $u=$s->get_result()->fetch_assoc(); $s->close();
        if (!$u || !isset($_SESSION['auth_version']) || !hash_equals(hash('sha256',$u['senha']),$_SESSION['auth_version'])) {
            $_SESSION=[]; session_destroy(); mcfFalhar(401,'Sua sessão expirou. Entre novamente.');
        }
        if ($route !== '/views/login/logout.php' && ($u['status_assinatura']!=='ativo' || $u['data_expiracao']<date('Y-m-d'))) mcfFalhar(403,'Seu acesso está inativo ou expirado.');
        $_SESSION['usuario_nome'] = $u['nome'];
        $_SESSION['usuario_email'] = $u['email'];
        $_SESSION['status_assinatura'] = $u['status_assinatura'];
        $_SESSION['data_expiracao'] = $u['data_expiracao'];
        // Variável exclusiva desta conexão, sempre sobrescrita e nunca recebida do cliente.
        $s=$conn->prepare('SET @mcf_usuario_id = ?, @mcf_ator_id = ?, @mcf_convite_id = 0, @mcf_convite_versao = 0'); $s->bind_param('ii',$uid,$uid); $s->execute(); $s->close();
    }
    $_SESSION['mcf_csrf'] ??= bin2hex(random_bytes(32));
    $method=$_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method,['GET','HEAD','POST'],true)) mcfFalhar(405,'Método não permitido.');
    if ($method==='POST') {
        $expected=$_SESSION['mcf_csrf']; $token=$_POST['mcf_csrf'] ?? '';
        if ($route==='/views/analise/api.php') { $expected=$_SESSION['csrf_analise']??''; $token=$_SERVER['HTTP_X_CSRF_TOKEN']??''; }
        if (!is_string($token) || $expected==='' || !hash_equals($expected,$token)) mcfFalhar(403,'Formulário expirado. Recarregue a página.');
    }
    require_once __DIR__.'/compartilhamento_contexto.php';
    mcfContextoIniciar($conn,$route);
}
