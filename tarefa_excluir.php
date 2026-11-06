<?php
require 'config.php';
exige_login();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$tarefa = R::load('tarefa', $id);
if (!$tarefa->id) { header('Location: tarefas.php'); exit; }

// exclusao leve: envia para a lixeira sem apagar de verdade
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarefa->excluida = 1;
    $tarefa->excluida_em = date('Y-m-d H:i:s');
    R::store($tarefa);
    header('Location: tarefas.php'); exit;
}

$titulo = h($tarefa->titulo);
topo('Excluir tarefa', 'tarefas');
echo <<<HTML
<form method="POST" class="formulario">
  <p>Deseja enviar a tarefa <b>$titulo</b> para a lixeira?</p>
  <input type="hidden" name="id" value="$id">
  <button class="botao-perigo">Sim, excluir</button>
  <a href="tarefas.php">Cancelar</a>
</form>
HTML;
rodape();
