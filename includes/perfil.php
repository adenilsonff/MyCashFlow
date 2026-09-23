<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

function mcfTexto(array $dados, string $campo): string {
    if (!is_string($dados[$campo] ?? '')) throw new DomainException('Formato de campo inválido.');
    return $dados[$campo] ?? '';
}
function mcfNome(string $nome): string {
    if (!mb_check_encoding($nome, 'UTF-8') || preg_match('/[\p{C}<>]/u', $nome)) throw new DomainException('O nome não pode conter HTML ou caracteres de controle.');
    $nome = trim($nome);
    if (!mb_check_encoding($nome, 'UTF-8') || mb_strlen($nome, 'UTF-8') < 2 || mb_strlen($nome, 'UTF-8') > 100 || preg_match('/[\p{C}<>]/u', $nome)) {
        throw new DomainException('Use um nome de 2 a 100 caracteres, sem HTML ou caracteres de controle.');
    }
    return $nome;
}
function mcfEmail(string $email): string {
    $email = strtolower(trim($email));
    if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Informe um e-mail válido com até 100 caracteres.');
    return $email;
}
function mcfValidarSenha(string $senha, string $confirmacao): void {
    if (strlen($senha) < 12 || strlen($senha) > 72 || str_contains($senha, "\0")) throw new DomainException('Use uma senha de 12 a 72 bytes (letras acentuadas podem ocupar mais de um byte).');
    if ($senha !== $confirmacao) throw new DomainException('As senhas não coincidem.');
}
function mcfRotuloUsuario(): string {
    return trim($_SESSION['usuario_nome'] ?? '') ?: ($_SESSION['usuario_email'] ?? 'Minha conta');
}
function mcfAtualizarPerfil(mysqli $conn, array $dados): bool {
    $uid = mcfUsuarioId();
    $acao = mcfTexto($dados, 'acao');
    if (!in_array($acao, ['dados', 'senha'], true)) throw new DomainException('Operação inválida.');
    $conn->begin_transaction();
    try {
        $s = $conn->prepare('SELECT nome,email,senha FROM usuarios WHERE id=? FOR UPDATE');
        $s->bind_param('i', $uid); $s->execute(); $u = $s->get_result()->fetch_assoc(); $s->close();
        // Revalida após adquirir a trava: outra sessão pode ter trocado a senha.
        if (!$u || !hash_equals(hash('sha256', $u['senha']), $_SESSION['auth_version'] ?? '')) throw new DomainException('Sua sessão expirou. Entre novamente.');
        if ($acao === 'dados') {
            $nome = mcfNome(mcfTexto($dados, 'nome'));
            $email = mcfEmail(mcfTexto($dados, 'email'));
            if ($email !== strtolower(trim($u['email'])) && !password_verify(mcfTexto($dados, 'senha_atual'), $u['senha'])) throw new DomainException('A senha atual está incorreta.');
            $s = $conn->prepare('UPDATE usuarios SET nome=?,email=? WHERE id=?');
            $s->bind_param('ssi', $nome, $email, $uid);
        } else {
            if (!password_verify(mcfTexto($dados, 'senha_atual'), $u['senha'])) throw new DomainException('A senha atual está incorreta.');
            $senha = mcfTexto($dados, 'nova_senha');
            mcfValidarSenha($senha, mcfTexto($dados, 'confirmar_senha'));
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $s = $conn->prepare('UPDATE usuarios SET senha=? WHERE id=?');
            $s->bind_param('si', $hash, $uid);
        }
        $s->execute(); $s->close(); $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) throw new DomainException('Este e-mail já está cadastrado.');
        throw $e;
    }
    if ($acao === 'senha') {
        if (!session_regenerate_id(true)) {
            $_SESSION = []; session_destroy(); mcfFalhar(401, 'Senha alterada. Entre novamente.');
        }
        $_SESSION = ['usuario_id'=>$uid, 'usuario_nome'=>$u['nome'], 'usuario_email'=>$u['email'],
            'auth_version'=>hash('sha256', $hash), 'mcf_csrf'=>bin2hex(random_bytes(32))];
    } else {
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;
    }
    return $acao === 'senha';
}
