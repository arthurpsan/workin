<?php
require 'rb-mysql.php';

$DB_HOST = 'localhost';
$DB_NOME = 'workin';
$DB_USUARIO = 'root';
$DB_SENHA = '';

// cria o banco de dados automaticamente se nao existir
try {
    $pdo = new PDO("mysql:host=$DB_HOST", $DB_USUARIO, $DB_SENHA);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NOME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
} catch (PDOException $e) {
    die('Não consegui conectar ao MySQL. Verifique se ele está ligado e confira usuário/senha no config.php.');
}
R::setup("mysql:host=$DB_HOST;dbname=$DB_NOME", $DB_USUARIO, $DB_SENHA);
session_start();

$STATUS = ['Nova', 'Ativa', 'Fechada'];
$DIAS_FECHADA = 7;
$PRIORIDADES = ['Baixa', 'Média', 'Alta'];

function h($texto) { return htmlspecialchars((string)$texto); }

function exige_login() {
    if (!isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }
    $u = R::load('usuario', $_SESSION['usuario_id']);
    if (!$u->id) { session_destroy(); header('Location: index.php'); exit; }
    migrar_status();
    descartar_fechadas();
    // forca a troca de e-mail e senha no primeiro acesso
    if ($u->redefinir && basename($_SERVER['SCRIPT_NAME']) != 'perfil.php') {
        header('Location: perfil.php'); exit;
    }
}

function opcoes($lista, $atual) {
    $html = '';
    foreach ($lista as $item) {
        $sel = ($item == $atual) ? ' selected' : '';
        $html .= "<option$sel>$item</option>";
    }
    return $html;
}

function migrar_status() {
    try {
        R::exec("UPDATE tarefa SET status = 'Nova' WHERE status = 'A fazer'");
        R::exec("UPDATE tarefa SET status = 'Ativa' WHERE status = 'Em andamento'");
        R::exec("UPDATE tarefa SET status = 'Fechada' WHERE status = 'Concluída'");
    } catch (Exception $e) { }
}

// descarte automatico: tarefas fechadas ha muitos dias vao para a lixeira
function descartar_fechadas() {
    global $DIAS_FECHADA;
    $limite = date('Y-m-d H:i:s', strtotime("-$DIAS_FECHADA days"));
    foreach (R::find('tarefa', 'excluida = 0 AND status = ? AND fechada_em < ?', ['Fechada', $limite]) as $t) {
        $t->excluida = 1;
        $t->excluida_em = date('Y-m-d H:i:s');
        R::store($t);
    }
}

function icone($nome) {
    $p = [
        'landing'    => '<path d="M3 11l9-8 9 8v10H3z"/><path d="M9 21v-7h6v7"/>',
        'tarefas'    => '<rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="10" rx="1.5"/><rect x="17" y="4" width="4" height="13" rx="1.5"/>',
        'equipe'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.8 2.9-6 6.5-6s6.5 2.2 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17.5 14c2.6 0 4.5 1.6 4.5 4.5"/>',
        'lixeira'    => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/>',
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'check'      => '<path d="M4 12l5 5L20 6"/>',
        'sair'       => '<path d="M9 4H5v16h4"/><path d="M16 8l4 4-4 4"/><path d="M20 12H9"/>',
    ];
    return '<svg class="icone" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $p[$nome] . '</svg>';
}

function avatar($u, $extra = '', $titulo = '') {
    $t = ($titulo != '') ? ' title="' . h($titulo) . '"' : '';
    if ($u->foto) {
        $f = h($u->foto);
        return "<img class=\"avatar $extra\" src=\"fotos/$f\" alt=\"\"$t>";
    }
    $inicial = h(mb_strtoupper(mb_substr($u->nome, 0, 1)));
    return "<span class=\"avatar $extra\"$t>$inicial</span>";
}

function responsaveis_html($tarefa_id, $com_nome = false) {
    $html = '';
    foreach (R::find('responsavel', 'tarefa_id = ?', [$tarefa_id]) as $r) {
        $u = R::load('usuario', $r->usuario_id);
        if (!$u->id) { continue; }
        if ($com_nome) {
            $html .= '<span class="resp">' . avatar($u, 'mini') . h($u->nome) . '</span>';
        } else {
            $html .= avatar($u, 'mini', $u->nome);
        }
    }
    return $html;
}

function tag_prioridade($prioridade) {
    $c = 'media';
    if ($prioridade == 'Alta') { $c = 'alta'; }
    if ($prioridade == 'Baixa') { $c = 'baixa'; }
    return '<span class="tag p-' . $c . '">' . h($prioridade) . '</span>';
}

function prazo_texto($prazo) {
    $dias = (int)round((strtotime($prazo) - strtotime(date('Y-m-d'))) / 86400);
    if ($dias < 0) { $n = -$dias; return 'Atrasada há ' . $n . ($n == 1 ? ' dia' : ' dias'); }
    if ($dias == 0) { return 'Vence hoje'; }
    if ($dias == 1) { return 'Vence amanhã'; }
    return 'Vence em ' . $dias . ' dias';
}

function topo($titulo, $ativo = '', $acao = '') {
    $usuario = R::load('usuario', $_SESSION['usuario_id']);
    $nome = h($usuario->nome);
    $foto = avatar($usuario);
    $itens = ['landing' => 'Início', 'tarefas' => 'Tarefas', 'equipe' => 'Equipe', 'lixeira' => 'Lixeira'];
    $menu = '';
    foreach ($itens as $chave => $rotulo) {
        $c = ($chave == $ativo) ? ' ativo' : '';
        $icone = icone($chave);
        $menu .= "<a class=\"item$c\" href=\"$chave.php\">$icone<span>$rotulo</span></a>";
    }
    $sair = icone('sair');
    $versao = filemtime(__DIR__ . '/estilo.css');
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>$titulo - workIn</title>
<link rel="stylesheet" href="estilo.css?v=$versao"></head><body>
<div class="app">
  <aside class="lateral">
    <a class="logo" href="landing.php">work<b>In</b></a>
    <nav class="menu">$menu</nav>
    <div class="conta">
      <a class="conta-perfil" href="perfil.php">$foto<div><b>$nome</b><small>Meu perfil</small></div></a>
      <a class="conta-sair" href="sair.php" title="Sair">$sair<span>Sair</span></a>
    </div>
  </aside>
  <main class="principal">
    <header class="cabecalho"><h1>$titulo</h1><div class="acoes-topo">$acao</div></header>
    <section class="conteudo">
HTML;
}

function rodape() {
    echo "</section></main></div></body></html>";
}
