<?php
require 'config.php';
exige_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$t = R::load('tarefa', $id);
if (!$t->id) { header('Location: tarefas.php'); exit; }

// checklist: adicionar, marcar e remover itens
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao == 'adicionar' && trim($_POST['texto']) != '') {
        $item = R::dispense('item');
        $item->tarefa_id = $id;
        $item->texto = trim($_POST['texto']);
        $item->feito = 0;
        R::store($item);
    } else {
        $item = R::load('item', (int)($_POST['item'] ?? 0));
        if ($item->id && $item->tarefa_id == $id) {
            if ($acao == 'alternar') { $item->feito = $item->feito ? 0 : 1; R::store($item); }
            if ($acao == 'remover') { R::trash($item); }
        }
    }
    header('Location: tarefa_ver.php?id=' . $id); exit;
}

$itens = R::find('item', 'tarefa_id = ? ORDER BY id', [$id]);
$total = count($itens);
$feitos = 0;
$lista = '';
foreach ($itens as $i) {
    $iid = $i->id;
    $texto = h($i->texto);
    $marca = $i->feito ? ' feito' : '';
    if ($i->feito) { $feitos++; }
    $lista .= <<<HTML
<form method="POST" class="check-item">
  <input type="hidden" name="id" value="$id"><input type="hidden" name="item" value="$iid">
  <button class="cbox$marca" name="acao" value="alternar" aria-label="Marcar ou desmarcar"></button>
  <span class="check-texto$marca">$texto</span>
  <button class="remover" name="acao" value="remover">Remover</button>
</form>
HTML;
}
if ($lista == '') { $lista = '<p class="vazio">Nenhum item ainda.</p>'; }
$pct = $total ? round($feitos * 100 / $total) : 0;

$titulo = h($t->titulo);
$descricao = $t->descricao ? nl2br(h($t->descricao)) : '<span class="vazio">Sem descrição.</span>';
$tag = tag_prioridade($t->prioridade);
$prazo = $t->prazo ? date('d/m/Y', strtotime($t->prazo)) . ' · ' . prazo_texto($t->prazo) : 'Sem prazo';
$status = h($t->status);
$resp = responsaveis_html($id, true) ?: '<span class="vazio">Ninguém atribuído.</span>';

topo('Tarefa', 'tarefas', '<a class="botao-claro" href="tarefas.php">Voltar</a>');
echo <<<HTML
<div class="detalhe">
  <div class="bloco">
    <h2 class="sem-margem">$titulo</h2>
    <p>$descricao</p>
    <dl>
      <dt>Status</dt><dd>$status</dd>
      <dt>Prioridade</dt><dd>$tag</dd>
      <dt>Prazo</dt><dd>$prazo</dd>
      <dt>Responsáveis</dt><dd class="linha">$resp</dd>
    </dl>
    <div class="linha">
      <a class="botao" href="tarefa_nova.php?id=$id">Editar</a>
      <a class="botao-perigo" href="tarefa_excluir.php?id=$id">Excluir</a>
    </div>
  </div>
  <div class="bloco">
    <h2 class="sem-margem">Checklist <span class="contador">$feitos/$total</span></h2>
    <div class="barra-progresso"><div style="width: $pct%"></div></div>
    $lista
    <form method="POST" class="novo-item">
      <input type="hidden" name="id" value="$id">
      <input type="text" name="texto" placeholder="Adicionar item" required>
      <button class="botao" name="acao" value="adicionar">Adicionar</button>
    </form>
  </div>
</div>
HTML;
rodape();
