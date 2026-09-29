<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/backup_conta.php';
$uid=mcfUsuarioId();$erro='';$sucesso='';$preview=null;
if(($_SESSION['backup_preview']['expira']??0)<time())unset($_SESSION['backup_preview']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $acao=$_POST['acao']??'';
        if($acao==='baixar'){
            $json=mcfBackupGerar($conn,$uid);session_write_close();
            header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="mycashflow-conta-'.date('Ymd-His').'.json"');header('Cache-Control: no-store, private');header('Content-Length: '.strlen($json));echo $json;exit;
        }elseif($acao==='verificar'){
            unset($_SESSION['backup_preview']);$file=$_FILES['backup']??null;
            if(!$file||is_array($file['error']??null)||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']))throw new DomainException('Envie um arquivo de backup válido. O limite também depende do upload permitido pelo servidor.');
            if($file['size']>MCF_BACKUP_MAX)throw new DomainException('O arquivo deve ter no máximo 10 MB.');
            $data=mcfBackupValidar($conn,file_get_contents($file['tmp_name']));
            $_SESSION['backup_preview']=['token'=>bin2hex(random_bytes(24)),'expira'=>time()+600,'uid'=>$uid,'dados'=>$data];$preview=$_SESSION['backup_preview'];
        }elseif($acao==='restaurar'){
            $pending=$_SESSION['backup_preview']??null;$token=$_POST['token']??'';
            if(!$pending||$pending['uid']!==$uid||!is_string($token)||!hash_equals($pending['token'],$token))throw new DomainException('A conferência expirou. Envie o arquivo novamente.');
            if(($_POST['confirmar']??'')!=='1')throw new DomainException('Confirme a recuperação na conta conectada.');
            $senha=$_POST['senha']??'';if(!is_string($senha))throw new DomainException('Senha inválida.');
            if(($_SESSION['backup_senha_tentativa']??0)>time()-3)throw new DomainException('Aguarde alguns segundos antes de tentar novamente.');$_SESSION['backup_senha_tentativa']=time();
            $s=$conn->prepare('SELECT senha FROM usuarios WHERE id=?');$s->bind_param('i',$uid);$s->execute();$hash=$s->get_result()->fetch_assoc()['senha'];$s->close();
            if(!password_verify($senha,$hash))throw new DomainException('Senha atual incorreta.');
            $count=mcfBackupRestaurar($conn,$uid,$pending['dados']);unset($_SESSION['backup_preview']);$sucesso='Recuperação concluída: '.$count.' registros. Seu login e suas permissões não foram alterados.';
        }else throw new DomainException('Ação inválida.');
    }catch(DomainException $e){http_response_code(422);$erro=$e->getMessage();}
    catch(Throwable $e){http_response_code(500);$erro='Não foi possível concluir. Nenhum registro parcial foi importado. Verifique a compatibilidade da versão do sistema.';}
}
if(!$preview)$preview=$_SESSION['backup_preview']??null;
$cssPagina='/MyCashFlow/assets/css/perfil.css';require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/menu.php';
function backupH($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<main class="perfil conta-configuracao">
<a class="mcf-back" href="configuracao.php">← Voltar para Configuração</a><h2 class="mcf-page-title">Backup da minha conta</h2>
<p>Conta conectada: <strong><?=backupH($_SESSION['usuario_email'])?></strong></p>
<?php if($erro):?><p role="alert"><?=backupH($erro)?></p><?php endif;?>
<?php if($sucesso):?><p class="perfil-sucesso" role="status"><?=backupH($sucesso)?></p><?php endif;?>
<section><h3>Baixar uma cópia</h3>
<p>Inclui seus cadastros, contas, receitas, despesas, cartões, investimentos, proventos, day trade, análises e preferências de módulos. Dados de outras contas compartilhados com você não entram na cópia.</p>
<p>Senhas, sessões, permissões de compartilhamento, histórico de alterações e preferências guardadas apenas no navegador não são incluídos. O arquivo contém dados pessoais e financeiros, sem criptografia: guarde-o em um local privado, fora do Git e da pasta pública do site.</p>
<form method="post"><?=mcfCsrfField()?><input type="hidden" name="acao" value="baixar"><button type="submit">Baixar backup da minha conta</button></form>
<p>O download não altera seus dados nem mantém uma cópia pública no servidor. Limite: 10 MB e 100.000 registros.</p></section>
<section><h3>Recuperar em uma conta vazia</h3>
<p>Use a mesma versão do MyCashFlow. Crie e entre em uma conta sem cadastros, lançamentos ou preferências de módulos salvas. Confira o arquivo antes de confirmar. Nenhum dado existente será substituído; os vínculos entre registros serão ajustados para a conta conectada.</p>
<form method="post" enctype="multipart/form-data"><?=mcfCsrfField()?><input type="hidden" name="acao" value="verificar"><label>Arquivo de backup (.json)<input type="file" name="backup" accept=".json,application/json" required></label><button type="submit">Conferir arquivo</button></form>
<?php if($preview):?><h3>Conferência do arquivo</h3><p>Destino: <strong><?=backupH($_SESSION['usuario_email'])?></strong>. Esta conferência vale por 10 minutos.</p><ul><?php foreach($preview['dados']['tabelas'] as $t=>$rows):?><li><?=backupH(mcfBackupRotulos()[$t])?>: <?=count($rows)?> registros</li><?php endforeach;?></ul>
<form method="post"><?=mcfCsrfField()?><input type="hidden" name="acao" value="restaurar"><input type="hidden" name="token" value="<?=backupH($preview['token'])?>"><label>Senha atual da conta conectada<input type="password" name="senha" autocomplete="current-password" required></label><label><input type="checkbox" name="confirmar" value="1" required> Confirmo a recuperação destes registros na minha conta vazia.</label><button type="submit">Recuperar dados na minha conta</button></form><?php endif;?>
</section></main><?php require __DIR__.'/../includes/footer.php';?>
