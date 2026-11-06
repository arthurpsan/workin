<?php
require 'config.php';
exige_login();
$hoje = date('Y-m-d');
$limite = date('Y-m-d', strtotime('+7 days'));

$novas = R::count('tarefa', 'excluida = 0 AND status = ?', ['Nova']);
$ativas = R::count('tarefa', 'excluida = 0 AND status = ?', ['Ativa']);
$fechadas = R::count('tarefa', 'excluida = 0 AND status = ?', ['Fechada']);
$atrasadas = R::count('tarefa', 'excluida = 0 AND status != ? AND prazo < ?', ['Fechada', $hoje]);

$proximas = R::find('tarefa', 'excluida = 0 AND status != ? AND prazo IS NOT NULL AND prazo <= ? ORDER BY prazo ASC LIMIT 8', ['Fechada', $limite]);
$linhas = '';
foreach ($proximas as $t) {
    $id = $t->id;
    $titulo = h($t->titulo);
    $quando = prazo_texto($t->prazo) . ' · ' . date('d/m', strtotime($t->prazo));
    $classe = ($t->prazo < $hoje) ? ' atrasada' : '';
    $tag = tag_prioridade($t->prioridade);
    $resp = responsaveis_html($id);
    $linhas .= "<a class=\"lista-linha$classe\" href=\"tarefa_ver.php?id=$id\"><div class=\"lista-texto\"><b>$titulo</b><span>$quando</span></div><div class=\"pilha\">$resp</div>$tag</a>";
}
if ($linhas == '') { $linhas = '<p class="vazio">Nenhuma tarefa vencendo nos próximos 7 dias.</p>'; }

topo('Início', 'landing', '<a class="botao" href="tarefa_nova.php">+ Nova tarefa</a>');
echo <<<HTML
<div class="resumo">
  <a class="stat c-nova" href="tarefas.php"><b>$novas</b><span>Novas</span></a>
  <a class="stat c-ativa" href="tarefas.php"><b>$ativas</b><span>Ativas</span></a>
  <a class="stat c-fechada" href="tarefas.php"><b>$fechadas</b><span>Fechadas</span></a>
  <a class="stat c-atraso" href="tarefas.php?d=atrasadas"><b>$atrasadas</b><span>Atrasadas</span></a>
</div>
<h2>Próximas do vencimento</h2>
<div class="lista">$linhas</div>
HTML;
rodape();
