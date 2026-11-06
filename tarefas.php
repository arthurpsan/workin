<?php
require 'config.php';
exige_login();

// move a tarefa entre as colunas e registra quando foi fechada
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarefa = R::load('tarefa', (int)$_POST['id']);
    if ($tarefa->id && in_array($_POST['status'], $STATUS)) {
        $tarefa->status = $_POST['status'];
        $tarefa->fechada_em = ($_POST['status'] == 'Fechada') ? date('Y-m-d H:i:s') : null;
        R::store($tarefa);
    }
    header('Location: tarefas.php?' . $_POST['qs']); exit;
}

$hoje = date('Y-m-d');
$mais7 = date('Y-m-d', strtotime('+7 days'));
$fp = $_GET['p'] ?? '';
$fd = $_GET['d'] ?? '';
$fo = $_GET['o'] ?? 'prazo';

// filtros e ordenacao por prioridade e prazo
$onde = 'excluida = 0';
$par = [];
if (in_array($fp, $PRIORIDADES)) { $onde .= ' AND prioridade = ?'; $par[] = $fp; }
if ($fd == 'atrasadas') { $onde .= " AND status != 'Fechada' AND prazo < ?"; $par[] = $hoje; }
if ($fd == 'hoje') { $onde .= ' AND prazo = ?'; $par[] = $hoje; }
if ($fd == 'semana') { $onde .= ' AND prazo >= ? AND prazo <= ?'; $par[] = $hoje; $par[] = $mais7; }
if ($fd == 'sem') { $onde .= ' AND prazo IS NULL'; }
$ordem = 'prazo IS NULL, prazo ASC, id DESC';
if ($fo == 'prioridade') { $ordem = "FIELD(prioridade, 'Alta', 'Média', 'Baixa'), prazo IS NULL, prazo ASC"; }
if ($fo == 'recentes') { $ordem = 'id DESC'; }
$tarefas = R::find('tarefa', "$onde ORDER BY $ordem", $par);

function cartao($t, $qs) {
    global $hoje;
    $id = $t->id;
    $titulo = h($t->titulo);
    $descricao = h($t->descricao);
    $tag = tag_prioridade($t->prioridade);
    $prazo = '';
    if ($t->prazo) {
        $data = date('d/m', strtotime($t->prazo));
        $alerta = ($t->prazo < $hoje && $t->status != 'Fechada') ? ' alerta' : '';
        $prazo = '<span class="tag prazo' . $alerta . '">' . icone('calendario') . " $data</span>";
    }
    $total = R::count('item', 'tarefa_id = ?', [$id]);
    $feitos = R::count('item', 'tarefa_id = ? AND feito = 1', [$id]);
    $progresso = $total ? '<span class="progresso">' . icone('check') . " $feitos/$total</span>" : '';
    $resp = responsaveis_html($id);
    $qs = h($qs);

    $botoes = '';
    if ($t->status == 'Nova') { $botoes = '<button class="botao-mini" name="status" value="Ativa">Iniciar</button>'; }
    if ($t->status == 'Ativa') { $botoes = '<button class="botao-mini" name="status" value="Fechada">Concluir</button><button class="botao-mini claro" name="status" value="Nova">Voltar</button>'; }
    if ($t->status == 'Fechada') { $botoes = '<button class="botao-mini claro" name="status" value="Ativa">Reabrir</button>'; }

    return <<<HTML
<div class="tcard">
  <a class="tcard-titulo" href="tarefa_ver.php?id=$id">$titulo</a>
  <p>$descricao</p>
  <div class="linha">$tag $prazo $progresso</div>
  <div class="tcard-rodape">
    <div class="pilha">$resp</div>
    <form method="POST" class="mover">
      <input type="hidden" name="id" value="$id"><input type="hidden" name="qs" value="$qs">
      $botoes
    </form>
  </div>
</div>
HTML;
}

$colunas = ['Nova' => 'Novas', 'Ativa' => 'Ativas', 'Fechada' => 'Fechadas'];
$grupos = ['Nova' => '', 'Ativa' => '', 'Fechada' => ''];
$totais = ['Nova' => 0, 'Ativa' => 0, 'Fechada' => 0];
foreach ($tarefas as $t) {
    $s = isset($grupos[$t->status]) ? $t->status : 'Nova';
    $grupos[$s] .= cartao($t, $_SERVER['QUERY_STRING'] ?? '');
    $totais[$s]++;
}

function opcoes_chave($lista, $atual) {
    $html = '';
    foreach ($lista as $valor => $rotulo) {
        $sel = ($valor == $atual) ? ' selected' : '';
        $html .= "<option value=\"$valor\"$sel>$rotulo</option>";
    }
    return $html;
}
$op_p = opcoes_chave(['' => 'Todas', 'Alta' => 'Alta', 'Média' => 'Média', 'Baixa' => 'Baixa'], $fp);
$op_d = opcoes_chave(['' => 'Todos', 'atrasadas' => 'Atrasadas', 'hoje' => 'Vencem hoje', 'semana' => 'Próximos 7 dias', 'sem' => 'Sem prazo'], $fd);
$op_o = opcoes_chave(['prazo' => 'Prazo mais próximo', 'prioridade' => 'Prioridade', 'recentes' => 'Mais recentes'], $fo);

topo('Tarefas', 'tarefas', '<a class="botao" href="tarefa_nova.php">+ Nova tarefa</a>');
echo <<<HTML
<form method="GET" class="filtros">
  <div><label>Prioridade</label><select name="p">$op_p</select></div>
  <div><label>Prazo</label><select name="d">$op_d</select></div>
  <div><label>Ordenar por</label><select name="o">$op_o</select></div>
  <button class="botao-claro">Filtrar</button>
  <a class="limpar" href="tarefas.php">Limpar</a>
</form>
<div class="quadro">
HTML;
foreach ($colunas as $chave => $rotulo) {
    $n = $totais[$chave];
    $cartoes = $grupos[$chave] ?: '<p class="vazio">Nenhuma tarefa.</p>';
    echo "<div class=\"coluna\"><div class=\"coluna-topo\"><b>$rotulo</b><span class=\"contador\">$n</span></div>$cartoes</div>";
}
echo '</div>';
echo '<p class="nota">Tarefas fechadas ficam visíveis por ' . $DIAS_FECHADA . ' dias e depois são enviadas automaticamente para a Lixeira.</p>';
rodape();
