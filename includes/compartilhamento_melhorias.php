<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
function mcfAcoesCampos(string $modulo, array $flags=[]): string {
 $e=static fn($v)=>htmlspecialchars($v,ENT_QUOTES,'UTF-8');$html='<div class="mcf-permission-actions">Ações permitidas quando houver alteração: ';
 foreach(['cadastrar'=>'Cadastrar','editar'=>'Editar','excluir'=>'Excluir'] as $a=>$label) $html.='<label class="mcf-permission-action"><input type="checkbox" name="acoes['.$e($modulo).']['.$a.']" value="1"'.(!empty($flags[$a])?' checked':'').'> '.$label.'</label>';
 return $html.'</div>';
}
function mcfAcoesRotulo(array $v,string $m): string {
 if(($v['niveis'][$m]??'leitura')!=='edicao') return 'somente visualizar';
 $labels=['cadastrar'=>'cadastrar','editar'=>'editar','excluir'=>'excluir'];$out=[];
 foreach($labels as $a=>$label) if(!empty($v['acoes'][$m][$a])) $out[]=$label;
 return 'visualizar; '.implode(', ',$out);
}
function mcfAvisos(mysqli $c): array {
 $id=mcfUsuarioId();$s=$c->prepare("SELECT c.id,c.versao,c.estado,c.proprietario_id,p.nome AS proprietario_nome,p.email AS proprietario_email,l.nome AS leitor_nome,l.email AS leitor_email FROM compartilhamentos c JOIN usuarios p ON p.id=c.proprietario_id JOIN usuarios l ON l.id=c.leitor_id LEFT JOIN compartilhamento_vistos v ON v.compartilhamento_id=c.id AND v.usuario_id=? WHERE (c.proprietario_id=? OR c.leitor_id=?) AND c.versao>COALESCE(v.versao,0) ORDER BY c.atualizado_em DESC,c.id DESC");
 $s->bind_param('iii',$id,$id,$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;
}
function mcfSeletorConta(mysqli $c): void {
 $route=str_replace('\\','/',substr(realpath($_SERVER['SCRIPT_FILENAME']),strlen(realpath(__DIR__.'/..'))));
 $m=mcfRotasCompartilhadas()[$route]??null;
 if(in_array($route,['/views/compartilhado.php','/views/visao_conjunta.php'],true)) $m=$_GET['modulo']??'investimentos';
 if(!is_string($m)||!isset(mcfModulosCompartilhaveis()[$m])) return;
 $actor=mcfUsuarioId();$s=$c->prepare("SELECT c.id,u.nome,u.email FROM compartilhamentos c JOIN compartilhamento_modulos m ON m.compartilhamento_id=c.id JOIN usuarios u ON u.id=c.proprietario_id WHERE c.leitor_id=? AND c.estado='ativo' AND m.modulo=? AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE() ORDER BY u.nome,u.email");
 $s->bind_param('is',$actor,$m);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
 $selected=$GLOBALS['mcf_contexto']['id']??($route==='/views/compartilhado.php'?($_GET['id']??'minha'):($route==='/views/visao_conjunta.php'?'conjunta':'minha'));
 $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 echo '<form data-mcf-own method="get" action="/MyCashFlow/views/trocar_conta.php" class="mcf-account-selector"><input type="hidden" name="modulo" value="'.$e($m).'"><label>Conta neste módulo <select name="conta"><option value="minha"'.($selected==='minha'?' selected':'').'>Minha conta</option>';
 foreach($rows as $r) echo '<option value="'.(int)$r['id'].'"'.((string)$selected===(string)$r['id']?' selected':'').'>'.$e(($r['nome']?:$r['email']).' — '.$r['email']).'</option>';
 echo '<option value="conjunta"'.($selected==='conjunta'?' selected':'').'>Visão conjunta</option></select></label><button>Mostrar conta</button></form>';
}
