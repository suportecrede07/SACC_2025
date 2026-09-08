<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once("../php/Connect.php");

$sql = "SELECT 
    t.id_trabalhos,
    t.titulo,
    e.nome AS escola,
    c.nome_categoria,
    a.nome_area
FROM Trabalhos t
LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
LEFT JOIN Areas a ON t.id_areas = a.id_area
WHERE 1=1";

$result = $pdo->query($sql);
$sql .= " ORDER BY t.id_trabalhos DESC";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trabalhos</title>


  <link href="../bootstrap/CSS/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../bootstrap/CSS/bootstrap-icons.css">
  <script src="../bootstrap/JS/bootstrap.bundle.min.js"></script>
  <script src="../boostrap/JS/jquery.min.js"></script>
  <link rel="stylesheet" href="../assets/styles/listatrabalho.css">
</head>

<body>
  <div id="overlay" onclick="closeMobileSidebar()"></div>
  <button id="mobile-toggle" onclick="toggleSidebar()">
    <i><img src="../assets/img/menu.png"></i>
  </button>
  <div id="sidebar" style="background-color: #4C8F5A;">
    <div>
      <button class="toggle-btn" onclick="toggleSidebar()">
        <img src="../assets/img/SIMBOLO.png" alt="SAFE">
        <span class="brand-text">SAFE</span>
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
  </div>
  <main id="main">
    <h2>Trabalhos</h2>
    <br>
    <hr><br>
    <!-- Tabela de trabalhos -->
    <table class="table table-bordered" id="workTable">
      <thead>
        <tr>
          <th>Título do Trabalho</th>
          <th>Escola</th>
          <th>Categoria</th>
          <th>Área</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
          echo '<tr>';
          echo '<td>' . $row['titulo'] . '</td>';
          echo '<td>' . $row['escola'] . '</td>';
          echo '<td>' . $row['nome_categoria'] . '</td>';
          echo '<td>' . $row['nome_area'] . '</td>';
          echo '<td>';
          echo '<a href="../php/Editatrabalhos.php?id=' . $row['id_trabalhos'] . '"><img src="../assets/img/editar.png" alt="Editar"></a> ';
          echo '<a href="../php/Excluirtrabalhos.php?id=' . $row['id_trabalhos'] . '"><img src="../assets/img/deletar.png" alt="Deletar"></a>';
          echo '</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
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