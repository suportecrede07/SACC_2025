<?php
session_start();
$error = '';

if (isset($_SESSION['login_error'])) {
  $error = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SACC - Jurado</title>
  <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
  <link rel="alternate" href="../assets/img/SIMBOLO.png" type="application/atom+xml" title="Atom">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <style>
    body {
      background-color: #4C8F5A;
      min-height: 100vh;
      margin: 0;
      overflow-x: hidden;
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
    }
    @media (max-width: 768px) {
      body {
        overflow-y: auto;
        height: auto;
        min-height: 100vh;
      }
    }
    .form-control {
      border-radius: 8px;
      padding: 10px 15px;
      border: 1px solid #ccc;
    }
    .form-control:focus {
      border-color: #4C8F5A;
      box-shadow: 0 0 0 0.2rem rgba(76, 143, 90, 0.25);
    }
    .btn-login {
      background-color: #4C8F5A;
      color: white;
      font-weight: 500;
      border-radius: 8px;
      padding: 10px;
      width: 100%;
      border: none;
      transition: opacity 0.2s;
    }
    .btn-login:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>

  <!-- Header Branco Padronizado -->
  <header style="background-color: #ffffff; width: 100%; height: 120px; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #e2e8f0;">
    <a href="../index.html" class="d-flex align-items-center justify-content-center">
      <img src="../assets/img/SACC.png" alt="SACC Logo" style="height: 85px; max-height: 85px; width: auto; object-fit: contain;">
    </a>
  </header>

  <!-- Container Principal -->
  <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center" style="padding: 15px;">
    
    <!-- Card de Login -->
    <div class="bg-white rounded-4 shadow-lg p-4 p-md-5 w-100 d-flex flex-column" style="max-width: 450px; border: 1px solid #f2f2f2;">
      
      <div class="text-center mb-4">
        <h1 style="font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif; font-size: 1.8rem; font-weight: 500; color: #222;">Jurado</h1>
        <p class="text-muted" style="font-size: 0.9rem;">Acesse o Sistema de Avaliação</p>
      </div>
      
      <form method="POST" action="../php/Jurado.php" id="loginForm">
        
        <div class="mb-3 text-start">
          <label for="usuario" class="form-label" style="font-weight: 500; font-size: 0.9rem;">Usuário:</label>
          <input class="form-control" type="text" inputmode="numeric" name="usuario" maxlength="6" required placeholder="Digite seu usuário" />
        </div>
        
        <div class="mb-4 text-start">
          <label for="senha" class="form-label" style="font-weight: 500; font-size: 0.9rem;">Senha:</label>
          <div class="position-relative">
            <input class="form-control" type="password" name="senha" id="senha" required placeholder="Digite sua senha" style="padding-right: 40px;" />
            <span class="position-absolute" id="togglePassword" style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;">
              <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
              </svg>
            </span>
          </div>
        </div>
        
        <button type="submit" class="btn-login">Entrar</button>
        
        <?php if (!empty($error)) : ?>
          <div style="color: #d9534f; margin-top: 15px; text-align: center; font-size: 0.9rem; font-weight: 500;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

      </form>
      
      <hr class="mt-4 mb-3" style="border-color: #eee;">

      <!-- Logo Crede 7 -->
      <div class="text-center mt-2">
        <p class="text-muted mb-1" style="font-size: 0.75rem;">Uma iniciativa de</p>
        <img src="../assets/img/crede7.png" alt="Crede 7 Canindé" class="img-fluid" style="height: 45px; width: auto;">
      </div>

    </div>
  </main>

  <script>
    document.getElementById('togglePassword').addEventListener('click', function (e) {
      const password = document.getElementById('senha');
      const eyeIcon = document.getElementById('eyeIcon');
      
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      
      if (type === 'password') {
        eyeIcon.innerHTML = '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>';
      } else {
        eyeIcon.innerHTML = '<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>';
      }
    });
  </script>

</body>
</html>