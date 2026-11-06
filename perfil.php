<?php
require 'config.php';
if (!isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }
$usuario = R::load('usuario', $_SESSION['usuario_id']);
$obrigatorio = $usuario->redefinir ? true : false;
$mensagens = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $erros = [];
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = 'E-mail inválido.'; }
    if (R::findOne('usuario', 'email = ? AND id != ?', [$email, $usuario->id])) { $erros[] = 'Esse e-mail já está em uso.'; }
    if ($obrigatorio) {
        if ($email == 'admin@workin.com') { $erros[] = 'Troque o e-mail padrão por um seu.'; }
        if (strlen($senha) < 6 || $senha == '123456') { $erros[] = 'Defina uma nova senha com pelo menos 6 caracteres.'; }
    } elseif ($senha != '' && strlen($senha) < 6) {
        $erros[] = 'A senha precisa ter pelo menos 6 caracteres.';
    }

    // upload da foto de perfil: valida tipo e tamanho
    $nova_foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        $info = ($_FILES['foto']['error'] == UPLOAD_ERR_OK) ? @getimagesize($_FILES['foto']['tmp_name']) : false;
        if (!$info || !isset($tipos[$info[2]])) {
            $erros[] = 'Foto inválida. Use JPG, PNG, GIF ou WEBP.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $erros[] = 'A foto pode ter no máximo 2 MB.';
        } else {
            if (!is_dir('fotos')) { mkdir('fotos'); }
            $nova_foto = 'foto_' . $usuario->id . '_' . time() . '.' . $tipos[$info[2]];
            move_uploaded_file($_FILES['foto']['tmp_name'], 'fotos/' . $nova_foto);
        }
    }

    if ($erros) {
        foreach ($erros as $e) { $mensagens .= '<p class="erro">' . h($e) . '</p>'; }
    } else {
        $usuario->nome = $_POST['nome'];
        $usuario->email = $email;
        if ($senha != '') { $usuario->senha = password_hash($senha, PASSWORD_DEFAULT); }
        if ($nova_foto != '') {
            if ($usuario->foto && file_exists('fotos/' . $usuario->foto)) { unlink('fotos/' . $usuario->foto); }
            $usuario->foto = $nova_foto;
        }
        $usuario->redefinir = 0;
        R::store($usuario);
        if ($obrigatorio) { header('Location: landing.php'); exit; }
        $mensagens = '<p class="ok">Perfil atualizado!</p>';
    }
}

$nome = h($usuario->nome);
$email = h($usuario->email);
$foto = avatar($usuario, 'grande');
$aviso = '';
if ($usuario->redefinir) {
    $aviso = '<p class="dica">Por segurança, defina agora seu e-mail e uma nova senha para continuar.</p>';
}
topo('Meu Perfil', 'perfil');
echo <<<HTML
$aviso
$mensagens
<form method="POST" enctype="multipart/form-data" class="formulario">
  <div class="foto-perfil">$foto
    <div><label>Foto de perfil (JPG, PNG, GIF ou WEBP, até 2 MB)</label><input type="file" name="foto" accept="image/*"></div>
  </div>
  <label>Nome</label><input type="text" name="nome" value="$nome" required>
  <label>E-mail</label><input type="email" name="email" value="$email" required>
  <label>Nova senha (deixe em branco para manter)</label><input type="password" name="senha">
  <button class="botao">Salvar</button>
</form>
HTML;
rodape();
