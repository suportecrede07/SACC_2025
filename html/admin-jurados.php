<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once("../php/Connect.php");


$sql = "SELECT 
j.id_jurados, 
j.nome, 
j.usuario, 
j.senha, 
j.cpf,
GROUP_CONCAT(DISTINCT CONCAT(c.nome_categoria, ':::', IFNULL(a.nome_area, '')) ORDER BY c.id_categoria SEPARATOR '|||') AS categoria_area_pares,
co.email, 
co.telefone
FROM Jurados j
LEFT JOIN Contatos co ON j.id_contatos = co.id_contatos
LEFT JOIN Jurados_Categorias_Areas jca ON j.id_jurados = jca.id_jurados
LEFT JOIN Categorias c ON jca.id_categoria = c.id_categoria
LEFT JOIN Areas a ON jca.id_area = a.id_area
GROUP BY 
    j.id_jurados, 
    j.nome, 
    j.usuario, 
    j.senha, 
    j.cpf, 
    co.email, 
    co.telefone;
";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Jurados</title>


  <link href="../bootstrap/CSS/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../bootstrap/CSS/bootstrap-icons.css">
  <script src="../bootstrap/JS/bootstrap.bundle.min.js"></script>
  <script src="../boostrap/JS/jquery.min.js"></script>
  <link rel="stylesheet" href="../assets/styles/listajurados.css">
</head>

<body>
  <div id="overlay" onclick="closeMobileSidebar()"></div>
  <button id="mobile-toggle" onclick="toggleSidebar()">
    <i><img src="../assets/img/menu.png"></i>
  </button>
  <div id="sidebar" style="background-color: #4C8F5A;">
    <div>
      <button class="toggle-btn" onclick="toggleSidebar()">
        <img src="../assets/img/SIMBOLO.png" alt="SAFC">
        <span class="brand-text">SAFC</span>
      </button>
      <ul class="nav flex-column">
        <li><a href="admin-dashboard.php"><i><img src="../assets/img/dashboard.png" class="dashboard"></i> <span class="label-text">Dashboard</span></a></li>
        <li><a href="admin-escolas.php"><i><img src="../assets/img/escola.png" class="escola"></i> <span class="label-text">Escolas</span></a></li>
        <li><a href="admin-trabalhos.php"><i><img src="../assets/img/trabalho.png" class="trabalho"></i> <span class="label-text">Trabalhos</span></a></li>
        <li><a href="admin-jurados.php"><i><img src="../assets/img/Jurados.png" class="jurado"></i> <span class="label-text">Jurados</span></a></li>
        <li><a href="admin-relatorios.php"><i><img src="../assets/img/relatorio.png" class="relatorio"></i> <span class="label-text">Relatórios</span></a></li>
      </ul>
    </div>
    <ul class="nav flex-column bottom-nav">
      <li><a href="../php/AdmLogout.php"><img src="../assets/img/sair.png" class="sair"> <span class="label-text">Sair</span></a></li>
    </ul>
  </div >
  <main id="main">
    <h2>Jurados</h2>
    <br>
    <hr><br>
    <!-- Tabela de trabalhos -->
     <div class="table-responsive" style="overflow-x:auto;">
    <table class="table" id="workTable">
      <thead class="table-success">
        <tr>
          <th>Nome</th>
          <th>Usuário</th>
          <th>Senha</th>
          <th>CPF</th>
          <th>E-mail</th>
          <th>Contato</th>
          <th>Categoria 1</th>
          <th>Área 1</th>
          <th>Categoria 2</th>
          <th>Área 2</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($user_data = $result->fetch(PDO::FETCH_ASSOC)) {
          $pares = explode('|||', $user_data['categoria_area_pares'] ?? '');

          $categoria1 = $categoria2 = '';
          $area1 = $area2 = '';

          if (isset($pares[0])) {
            $split = explode(':::', $pares[0]);
            $categoria1 = $split[0] ?? '';
            $area1 = $split[1] ?? '';
          }

          if (isset($pares[1])) {
            $split = explode(':::', $pares[1]);
            $categoria2 = $split[0] ?? '';
            $area2 = $split[1] ?? '';
          }
          
          echo '<tr>';
          echo '<td>' . htmlspecialchars($user_data['nome'] ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($user_data['usuario'] ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($user_data['senha'] ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($user_data['cpf'] ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($user_data['email'] ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($user_data['telefone'] ?? '') . '</td>';

          echo '<td>' . htmlspecialchars($categoria1 ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($area1 ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($categoria2 ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($area2 ?? '') . '</td>';

          echo '<td>';
          echo '<a href="../php/Editajurados.php?id=' . urlencode($user_data['id_jurados']) . '"><img src="../assets/img/editar.png" alt="Editar"></a> ';
          echo '<a href="../php/Excluirjurados.php?id=' . urlencode($user_data['id_jurados']) . '"><img src="../assets/img/deletar.png" alt="Deletar"></a>';
          echo '</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    </div>
  </main>

  <script>
    function toggleSidebar() {
      if (window.innerWidth <= 768) {
        $('#sidebar').toggleClass('mobile-open');
        $('#overlay').toggleClass('show');
      } else {
        $('#sidebar').toggleClass('collapsed');
        $('#main').toggleClass('collapsed');
      }
    }

    function closeMobileSidebar() {
      $('#sidebar').removeClass('mobile-open');
      $('#overlay').removeClass('show');
    }
    $(window).on('resize', function() {
      if (window.innerWidth > 768) {
        $('#sidebar').removeClass('mobile-open');
        $('#overlay').removeClass('show');
      }
    });
  </script>
</body>

</html>