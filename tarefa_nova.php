<?php
require 'config.php';
exige_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id) {
    $tarefa = R::load('tarefa', $id);
    if (!$tarefa->id) { header('Location: tarefas.php'); exit; }
} else {
    $tarefa = R::dispense('tarefa');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarefa->titulo = $_POST['titulo'];
    $tarefa->descricao = $_POST['descricao'];
    $tarefa->prioridade = $_POST['prioridade'];
    $prazo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['prazo']) ? $_POST['prazo'] : null;
    $tarefa->prazo = $prazo;
    if (!$id) {
        $tarefa->status = 'Nova';
        $tarefa->excluida = 0;
        $tarefa->criador_id = $_SESSION['usuario_id'];
        $tarefa->criada_em = date('Y-m-d H:i:s');
    }
    R::store($tarefa);

    foreach (R::find('responsavel', 'tarefa_id = ?', [$tarefa->id]) as $r) { R::trash($r); }
    $marcados = $_POST['responsaveis'] ?? [];
    foreach ($marcados as $uid) {
        $r = R::dispense('responsavel');
        $r->tarefa_id = $tarefa->id;
        $r->usuario_id = (int)$uid;
        R::store($r);
    }
    header('Location: tarefa_ver.php?id=' . $tarefa->id); exit;
}

$atuais = [];
if ($id) {
    foreach (R::find('responsavel', 'tarefa_id = ?', [$id]) as $r) { $atuais[] = $r->usuario_id; }
}
$pessoas = '';
foreach (R::findAll('usuario', 'ORDER BY nome') as $u) {
    $uid = $u->id;
    $nome = h($u->nome);
    $marca = in_array($uid, $atuais) ? ' checked' : '';
    $pessoas .= "<label class=\"pessoa\"><input type=\"checkbox\" name=\"responsaveis[]\" value=\"$uid\"$marca> $nome</label>";
}

$titulo = h($tarefa->titulo);
$descricao = h($tarefa->descricao);
$prazo = h($tarefa->prazo);
$prioridades = opcoes($PRIORIDADES, $tarefa->prioridade ?: 'Média');
$pagina = $id ? 'Editar tarefa' : 'Nova tarefa';
$botao = $id ? 'Salvar alterações' : 'Cadastrar';

topo($pagina, 'tarefas');
echo <<<HTML
<form method="POST" class="formulario">
  <input type="hidden" name="id" value="$id">
  <label>Título</label><input type="text" name="titulo" value="$titulo" required>
  <label>Descrição</label><textarea name="descricao" rows="4">$descricao</textarea>
  <div class="duas-colunas">
    <div><label>Prazo (deadline)</label><input type="date" name="prazo" value="$prazo"></div>
    <div><label>Prioridade</label><select name="prioridade">$prioridades</select></div>
  </div>
  <label>Responsáveis</label>
  <div class="pessoas">$pessoas</div>
  <button class="botao">$botao</button>
  <a href="tarefas.php">Cancelar</a>
</form>
HTML;
rodape();
