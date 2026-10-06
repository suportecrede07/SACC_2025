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
j.primeiro_acesso,
j.avaliacoes_finalizadas,
GROUP_CONCAT(DISTINCT CONCAT(IFNULL(c.nome_categoria, ''), ':::', IFNULL(a.nome_area, ''), ':::', IFNULL(c.id_categoria, 0), ':::', IFNULL(a.id_area, 0)) ORDER BY c.id_categoria SEPARATOR '|||') AS categoria_area_pares,
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
    j.primeiro_acesso, 
    j.avaliacoes_finalizadas, 
    co.email, 
    co.telefone;
";
$result = $pdo->query($sql);
$jurados = $result->fetchAll(PDO::FETCH_ASSOC);
$total_jurados = count($jurados);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Jurados - SAFC Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <script src="../boostrap/JS/jquery.min.js"></script>
  <link rel="stylesheet" href="../assets/styles/dashboard-admin.css?v=<?= time() ?>">
</head>

<body>
  <div id="overlay" onclick="closeMobileSidebar()"></div>
  <button id="mobile-toggle" onclick="toggleSidebar()">
    <i><img src="../assets/img/menu.png"></i>
  </button>
  <div id="sidebar">
    <div>
      <div class="sidebar-top">
        <div class="brand-wrapper">
          <div class="brand-logo-card">
            <img src="../assets/img/SIMBOLO.png" alt="SAFC">
          </div>
          <span class="brand-title-text">SAFC</span>
        </div>
      </div>
      <ul class="nav flex-column">
        <li><a href="admin-dashboard.php"><i><img src="../assets/img/dashboard.svg" class="dashboard"></i> <span class="label-text">Dashboard</span></a></li>
        <li><a href="admin-escolas.php"><i><img src="../assets/img/escolas.svg" class="escola"></i> <span class="label-text">Escolas</span></a></li>
        <li><a href="admin-trabalhos.php"><i><img src="../assets/img/trabalhos.svg" class="trabalho"></i> <span class="label-text">Trabalhos</span></a></li>
        <li class="active"><a href="admin-jurados.php"><i><img src="../assets/img/jurados.svg" class="jurado"></i> <span class="label-text">Jurados</span></a></li>
        <li><a href="admin-relatorios.php"><i><img src="../assets/img/relatorios.svg" class="relatorio"></i> <span class="label-text">Relatórios</span></a></li>
      </ul>
    </div>
    <ul class="nav flex-column bottom-nav">
      <li><a href="../php/AdmLogout.php"><img src="../assets/img/sair.png" class="sair"> <span class="label-text">Sair</span></a></li>
    </ul>
  </div>

  <main id="main">
    <div class="page-header-clean">
      <div class="page-title-row">
        <h1 class="page-title">Jurados</h1>
        <span class="badge-count"><?= $total_jurados ?> Cadastrados</span>
      </div>
      <p class="page-subtitle">Gerencie os avaliadores científicos e suas comissões atribuídas</p>
     
      <!-- este realiza o processo de importar o arquivp (finalizado) -->
       <?php if($_SESSION['Nivel_permissao'] === 0): ?>
        <form action="../pdf/importar_arquivoCSV_trabalhos.php" method="POST" enctype="multipart/form-data">
           <br>
           <p style="margin-bottom: 0px;"><b>Cadratrar Jurados, importando os dados:</b></p>
           <div style="display: flex; align-items: center; flex-direction: row;">
             <input type="file" name="meu_arquivo" id="meu_arquivo" required style="display: none;" required>
             <label for="meu_arquivo" class="botao-arquivo" id="EscolherArquivo">Escolha um Arquivo</label>
             <span id="nome-arquivo" style="margin-left: 5px; font-family: sans-serif; color: #333;">Nenhum arquivo selecionado </span>
           </div>
           <button type="submit" class="botao-arquivo" id="importar">Importar</button>
         </form>
      <?php endif; ?>
    </div>
    </div>

    <script>
      document.getElementById('meu_arquivo').addEventListener('change', function() {
        var nomeArquivo = this.files[0] ? this.files[0].name : "Nenhum arquivo selecionado";
        document.getElementById('nome-arquivo').textContent = nomeArquivo;
      });
    </script>

    <style>
      .botao-arquivo {
        background-color: #63aa65;
        border: 2px solid #86efac;
        color: white;
        padding: 5px 12px;
        border-radius: 5px;
        cursor: pointer;
        display: inline-block;
        font-family: sans-serif;
        transition: background-color 0.3s;
      }

      .botao-arquivo:hover {
        background-color: #45a0498f;
      }

      #EscolherArquivo{
        margin-top: 5px;
      }

      #importar {
        background-color: #fcb42d;
        border: 2px solid #efeb86;
      }

      th{
        text-align: center
      }
    </style>

    <!-- Tabela de jurados em Card Nítido sem rolagem -->
    <div class="admin-card-table">
      <div class="table-responsive" style="overflow-x: hidden;">
        <table class="table admin-table admin-table-compact align-middle mb-0" id="workTable">
          <thead>
            <tr>
              <th class="ps-3">NOME</th>
              <th>USUÁRIO</th>
              <th>SENHA</th>
              <th>CPF</th>
              <th>E-MAIL</th>
              <th>CONTATO</th>
              <th>CATEGORIA 1</th>
              <th>ÁREA 1</th>
              <th>CATEGORIA 2</th>
              <th>ÁREA 2</th>
              <th>STATUS</th>
              <?php 
              if($_SESSION['Nivel_permissao'] === 0){
                echo '<th class="text-center pe-3">AÇÕES</th>';
              }
              ?>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($jurados)): ?>
              <?php foreach ($jurados as $user_data): ?>
                <?php
                $pares = explode('|||', $user_data['categoria_area_pares'] ?? '');
                $categoria1 = $categoria2 = '';
                $area1 = $area2 = '';
                $idCat1 = $idArea1 = $idCat2 = $idArea2 = 0;

                if (isset($pares[0])) {
                  $split = explode(':::', $pares[0]);
                  $categoria1 = $split[0] ?? '';
                  $area1 = $split[1] ?? '';
                  $idCat1 = (int)($split[2] ?? 0);
                  $idArea1 = (int)($split[3] ?? 0);
                }

                if (isset($pares[1])) {
                  $split = explode(':::', $pares[1]);
                  $categoria2 = $split[0] ?? '';
                  $area2 = $split[1] ?? '';
                  $idCat2 = (int)($split[2] ?? 0);
                  $idArea2 = (int)($split[3] ?? 0);
                }

                $rawFin = (string)($user_data['avaliacoes_finalizadas'] ?? '');
                $finList = array_filter(explode(',', $rawFin));

                $fin1 = ($idCat1 > 0 || $idArea1 > 0) && (in_array("{$idCat1}_{$idArea1}", $finList) || $rawFin === '1');
                $fin2 = ($idCat2 > 0 || $idArea2 > 0) && (in_array("{$idCat2}_{$idArea2}", $finList) || $rawFin === '1');
                ?>
                <tr>
                  <td class="ps-3 td-item-title"><?= htmlspecialchars($user_data['nome'] ?? '') ?></td>
                  <td><?= htmlspecialchars($user_data['usuario'] ?? '') ?></td>
                  <td>
                    <?php if (!empty($user_data['primeiro_acesso'])): ?>
                      <span class="text-muted fw-bold" style="font-size: 0.85rem;" title="Senha alterada pelo jurado">-</span>
                    <?php else: ?>
                      <span class="category-pill fw-bold text-success" style="background-color: #f0fdf4; border: 1px solid #b7e4c7;"><?= htmlspecialchars($user_data['senha'] ?? '') ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-nowrap"><?= htmlspecialchars($user_data['cpf'] ?? '') ?></td>
                  <td style="word-break: break-all; max-width: 130px; font-size: 0.78rem;"><?= htmlspecialchars($user_data['email'] ?? '') ?></td>
                  <td class="text-nowrap"><?= htmlspecialchars($user_data['telefone'] ?? '') ?></td>
                  <td><?php if ($categoria1): ?><span class="category-pill"><?= htmlspecialchars($categoria1) ?></span><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                  <td style="max-width: 140px; font-size: 0.76rem; line-height: 1.25;"><?= htmlspecialchars($area1 ?: '-') ?></td>
                  <td><?php if ($categoria2): ?><span class="category-pill"><?= htmlspecialchars($categoria2) ?></span><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                  <td style="max-width: 140px; font-size: 0.76rem; line-height: 1.25;"><?= htmlspecialchars($area2 ?: '-') ?></td>
                  <td>
                    <div class="d-flex flex-column gap-1">
                      <?php if ($categoria1 || $area1): ?>
                        <?php if ($fin1): ?>
                          <span class="badge bg-success" style="font-size: 0.72rem; padding: 4px 8px;">Área 1 Finalizada</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark" style="font-size: 0.72rem; padding: 4px 8px;">Área 1 Pendente</span>
                        <?php endif; ?>
                      <?php endif; ?>

                      <?php if ($categoria2 || $area2): ?>
                        <?php if ($fin2): ?>
                          <span class="badge bg-success" style="font-size: 0.72rem; padding: 4px 8px;">Área 2 Finalizada</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark" style="font-size: 0.72rem; padding: 4px 8px;">Área 2 Pendente</span>
                        <?php endif; ?>
                      <?php endif; ?>

                      <?php if (!$categoria1 && !$area1 && !$categoria2 && !$area2): ?>
                        <span class="text-muted small">-</span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <?php if($_SESSION['Nivel_permissao'] === 0): ?>
                    <td class="text-center pe-3">
                      <div class="d-inline-flex gap-1">
                        <a href="../php/Editajurados.php?id=<?= urlencode($user_data['id_jurados']) ?>" class="btn-action-edit" title="Editar">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-1 3a.5.5 0 0 0 .606.606l3-1a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h6a.5.5 0 0 0 0-1h-6A1.5 1.5 0 0 0 1 2.5z" />
                          </svg>
                        </a>
                        <a href="../php/Excluirjurados.php?id=<?= urlencode($user_data['id_jurados']) ?>" class="btn-action-delete" onclick="return confirm('Tem certeza que deseja excluir este jurado?');" title="Excluir">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.06a.5.5 0 0 0-.998.06l.5 8.5a.5.5 0 1 0 .998-.06z" />
                          </svg>
                        </a>
                      </div>
                    </td>
                  <?php endif; ?>

                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="12" class="text-center py-4 text-muted">Nenhum jurado cadastrado.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
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