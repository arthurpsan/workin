<?php
require 'config.php';
exige_login();
$aviso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $aviso = '<p class="erro">E-mail inválido.</p>';
    } elseif (R::findOne('usuario', 'email = ?', [$email])) {
        $aviso = '<p class="erro">Já existe alguém com esse e-mail.</p>';
    } elseif (strlen($_POST['senha']) < 6) {
        $aviso = '<p class="erro">A senha provisória precisa ter pelo menos 6 caracteres.</p>';
    } else {
        $u = R::dispense('usuario');
        $u->nome = $_POST['nome'];
        $u->email = $email;
        $u->senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $u->redefinir = 1;
        R::store($u);
        $aviso = '<p class="ok">Pessoa adicionada! Ela vai trocar a senha no primeiro acesso.</p>';
    }
}

topo('Equipe', 'equipe');
echo $aviso;
echo '<div class="equipe">';
foreach (R::findAll('usuario', 'ORDER BY nome') as $u) {
    $foto = avatar($u, 'media');
    $nome = h($u->nome);
    $email = h($u->email);
    echo "<div class=\"membro\">$foto<div><b>$nome</b><span>$email</span></div></div>";
}
echo <<<HTML
</div>
<h2>Adicionar pessoa</h2>
<form method="POST" class="formulario">
  <label>Nome</label><input type="text" name="nome" required>
  <label>E-mail</label><input type="email" name="email" required>
  <label>Senha provisória</label><input type="text" name="senha" required>
  <button class="botao">Adicionar</button>
</form>
HTML;
rodape();
