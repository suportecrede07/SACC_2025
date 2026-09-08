<?php
session_start();
$error = '';

if (isset($_SESSION['login_error'])) {
  $error = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
}
?>

<!DOCTYPE html>
<html lang="pt-bt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAFE - Home</title>
  <link rel="stylesheet" href="../assets/styles/login_adm.css">
  <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
  <link rel="alternate" href="../assets/img/SIMBOLO.png" type="application/atom+xml" title="Atom">
  <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
</head>
<body>
  <nav class="navbar bg-body-tertiary">
    <div class="d-flex">
      <a class="navbar-brand" href="#">
        <div id="corpo-img"> 
          <img src="../assets/img/SACC.png" alt="Logo" id="img-sacc" class="d-inline-block align-text-top">
        </div>
      </a>
    </div>
  </nav>
  <div class="container d-flex justify-content-center align-items-center">
    <div class="container-fluid " id="login-box" style="width:50%">
      <h1 class="h1" id="tit" style="font-weight: bold;">Admin</h1>
      <div class="row d-flex justify-content-center align-items-center" id="coluna">
        <form method="POST" action="../php/Adm.php" id="loginForm">
           <div class="form-group">
             <label for="usuario">Usuário:</label><br>
             <input class="form-control-md" type="text" name="usuario"  required placeholder="Digite seu usuário" />
           </div>
           <br>
           <div class="form-group">
             <label for="senha">Senha:</label><br>
             <input class="form-control-md" type="password" name="senha"  placeholder="Digite sua senha" />
           </div>
           <button type="submit">Entrar</button>
         </form>
         <?php if (!empty($error)) : ?>
           <div style="color: red; margin-top: 10px; text-align: center;">
             <?= htmlspecialchars($error) ?>
           </div>
         <?php endif; ?>
  </div>
  <img src="../assets/img/crede7.png" style="width: 120px;">
</body>

</html>