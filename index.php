<?php
require 'config.php';

// cria o usuario padrao no primeiro acesso
if (R::count('usuario') == 0) {
    $u = R::dispense('usuario');
    $u->nome = 'Administrador';
    $u->email = 'admin@workin.com';
    $u->senha = password_hash('123456', PASSWORD_DEFAULT);
    $u->redefinir = 1;
    R::store($u);
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = R::findOne('usuario', 'email = ?', [$_POST['email']]);
    if ($usuario && password_verify($_POST['senha'], $usuario->senha)) {
        $_SESSION['usuario_id'] = $usuario->id;
        header('Location: landing.php'); exit;
    }
    $erro = '<p class="erro">E-mail ou senha inválidos.</p>';
}

$dica = '';
$padrao = R::findOne('usuario', 'email = ? AND redefinir = 1', ['admin@workin.com']);
if ($padrao) {
    $dica = '<p class="dica">Primeiro acesso? Use <b>admin@workin.com</b> e a senha <b>123456</b>. Você vai trocar os dois logo depois.</p>';
}

$versao = filemtime(__DIR__ . '/estilo.css');
echo <<<HTML
<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - workIn</title>
<link rel="stylesheet" href="estilo.css?v=$versao"></head><body class="fundo-login">
<form class="caixa-login" method="POST">
  <div class="logo grande">work<b>In</b></div>
  <p class="sub">Entre para organizar suas tarefas</p>
  $erro
  <label>E-mail</label><input type="email" name="email" required>
  <label>Senha</label><input type="password" name="senha" required>
  <button class="botao">Entrar</button>
  $dica
</form>
</body></html>
HTML;
