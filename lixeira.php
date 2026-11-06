<?php
require 'config.php';
exige_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarefa = R::load('tarefa', (int)$_POST['id']);
    if ($tarefa->id) {
        if ($_POST['acao'] == 'restaurar') {
            $tarefa->excluida = 0;
            if ($tarefa->status == 'Fechada') { $tarefa->status = 'Nova'; $tarefa->fechada_em = null; }
            R::store($tarefa);
        } else {
            // apagar de vez remove tambem os responsaveis e o checklist
            foreach (R::find('responsavel', 'tarefa_id = ?', [$tarefa->id]) as $r) { R::trash($r); }
            foreach (R::find('item', 'tarefa_id = ?', [$tarefa->id]) as $i) { R::trash($i); }
            R::trash($tarefa);
        }
    }
    header('Location: lixeira.php'); exit;
}

$tarefas = R::find('tarefa', 'excluida = 1 ORDER BY excluida_em DESC');

topo('Lixeira', 'lixeira');
if (!$tarefas) { echo '<p class="vazio">A lixeira está vazia.</p>'; }

foreach ($tarefas as $t) {
    $id = $t->id;
    $titulo = h($t->titulo);
    $data = date('d/m/Y H:i', strtotime($t->excluida_em));
    $motivo = ($t->status == 'Fechada') ? "Fechada e descartada em $data" : "Excluída em $data";
    $resp = responsaveis_html($id);
    echo <<<HTML
<div class="lista-linha fixa">
  <div class="lista-texto"><b>$titulo</b><span>$motivo</span></div>
  <div class="pilha">$resp</div>
  <form method="POST" class="mover">
    <input type="hidden" name="id" value="$id">
    <button class="botao-mini" name="acao" value="restaurar">Restaurar</button>
    <button class="botao-mini escuro" name="acao" value="apagar">Apagar de vez</button>
  </form>
</div>
HTML;
}
rodape();
