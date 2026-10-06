<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once("../php/Connect.php");

$escolas = $pdo->query("SELECT e.id_escolas, e.nome, ce.categoria_da_escola FROM Escolas e LEFT JOIN Categoria_escolas ce ON e.id_categoria_escola = ce.id ORDER BY e.nome")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT id_categoria, nome_categoria FROM Categorias ORDER BY nome_categoria")->fetchAll(PDO::FETCH_ASSOC);
$areas = $pdo->query("SELECT id_area, nome_area FROM Areas ORDER BY nome_area")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT 
    t.id_trabalhos,
    t.titulo,
    t.ordem,
    e.nome AS escola,
    c.nome_categoria,
    a.nome_area,
    ce.categoria_da_escola
FROM Trabalhos t
LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
LEFT JOIN categoria_escolas ce ON e.id_categoria_escola = ce.id
LEFT JOIN Areas a ON t.id_areas = a.id_area
WHERE 1=1";

$sql .= " ORDER BY t.ordem ASC, t.id_trabalhos ASC";
$result = $pdo->query($sql);
$trabalhos = $result->fetchAll(PDO::FETCH_ASSOC);
$total_trabalhos = count($trabalhos);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trabalhos - SAFC Admin</title>

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
        <li class="active"><a href="admin-trabalhos.php"><i><img src="../assets/img/trabalhos.svg" class="trabalho"></i> <span class="label-text">Trabalhos</span></a></li>
        <li><a href="admin-jurados.php"><i><img src="../assets/img/jurados.svg" class="jurado"></i> <span class="label-text">Jurados</span></a></li>
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
        <h1 class="page-title">Trabalhos</h1>
        <span class="badge-count"><?= $total_trabalhos ?> Cadastrados</span>
      </div>
      <p class="page-subtitle">Gerencie e visualize os projetos e trabalhos científicos cadastrados</p>

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

      <br>
      <div class="admin-card-filter mb-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="admin-form-label">Escola</label>
            <select id="Filtro_escola" class="form-select admin-form-select">
              <option value="">Selecione a Escola</option>
              <?php foreach ($escolas as $escola): ?>
                <option value="<?= htmlspecialchars(($escola['categoria_da_escola'] ?? '') . ' ' . $escola['nome']) ?>">
                  <?= htmlspecialchars(($escola['categoria_da_escola'] ?? '') . ' ' . $escola['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>
      </div>

      <div class="col-md-4">
          <label class="admin-form-label">Categoria</label>
          <select id="Filtro_categoria" class="form-select admin-form-select">
            <option value="">Selecione a Categoria</option>
            <?php foreach ($categorias as $categoria): ?>
              <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>">
                <?= htmlspecialchars($categoria['nome_categoria']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
    
      <div class="col-md-4">
            <label class="admin-form-label">Área</label>
            <select id="Filtro_area" class="form-select admin-form-select">
              <option value="">Selecione a Área</option>
              <?php foreach ($areas as $area): ?>
                <option value="<?= htmlspecialchars($area['nome_area']) ?>">
                  <?= htmlspecialchars($area['nome_area']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
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

      #EscolherArquivo {
        margin-top: 5px;
      }

      #importar {
        background-color: #fcb42d;
        border: 2px solid #efeb86;
      }

      th{
        text-align: center;
      }
    </style>
    </div>

    <!-- Tabela de trabalhos estilo card Nítido -->
    <div class="admin-card-table">
      <div class="table-responsive">
        <table class="table admin-table align-middle mb-0" id="workTable">
          <thead>
            <tr>
              <th class="ps-4">TÍTULO DO TRABALHO</th>
              <th>ESCOLA</th>
              <th>CATEGORIA</th>
              <th>ÁREA</th>
              <th>Ordem</th>
              <?php if($_SESSION['Nivel_permissao'] === 0): ?>
                <th class="text-center pe-4">AÇÕES</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($trabalhos)): ?>
              <?php foreach ($trabalhos as $row): ?>
                <tr>
                  <td class="ps-4 td-item-title"><?= htmlspecialchars($row['titulo']) ?></td>
                  <td><?= htmlspecialchars(($row['categoria_da_escola'] ?? '') . ' ' . $row['escola']) ?></td>
                  <td><span class="category-pill"><?=htmlspecialchars($row['nome_categoria']) ?></span></td>
                  <td><?= htmlspecialchars($row['nome_area'] ?? '-') ?></td>
                  <td style="text-align: center;" ><?= htmlspecialchars($row['ordem'] ?? '-') ?></td>
                  <?php if($_SESSION['Nivel_permissao'] === 0): ?>
                    <td class="text-center pe-4 text-nowrap">
                      <div class="d-inline-flex gap-2">
                        <a href="../php/Editatrabalhos.php?id=<?= $row['id_trabalhos'] ?>" class="btn-action-edit" title="Editar">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-1 3a.5.5 0 0 0 .606.606l3-1a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h6a.5.5 0 0 0 0-1h-6A1.5 1.5 0 0 0 1 2.5z" />
                          </svg>
                        </a>
                        <a href="../php/Excluirtrabalhos.php?id=<?= $row['id_trabalhos'] ?>" class="btn-action-delete" onclick="return confirm('Tem certeza que deseja excluir este trabalho?');" title="Excluir">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
                          </svg>
                        </a>
                      </div>
                    </td>
                  <?php endif ?>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">Nenhum trabalho cadastrado.</td>
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

    document.addEventListener("DOMContentLoaded", function() {
        const filtroEscola = document.getElementById("Filtro_escola");
        const filtroCategoria = document.getElementById("Filtro_categoria");
        const filtroArea = document.getElementById("Filtro_area");
        const linhas = document.querySelectorAll("#workTable tbody tr");

        function filtrarTabela() {
          const escolaSelecionada = filtroEscola.value.toLowerCase();
          const categoriaSelecionada = filtroCategoria.options[filtroCategoria.selectedIndex].text.toLowerCase();
          const areaSelecionada = filtroArea.value.toLowerCase();

          linhas.forEach(tr => {
            const escola = tr.children[1].textContent.toLowerCase();
            const categoria = tr.children[2].textContent.toLowerCase();
            const area = tr.children[3].textContent.toLowerCase();

            const escolaOk = !escolaSelecionada || escola.includes(escolaSelecionada);
            const categoriaOk = !filtroCategoria.value || categoria === categoriaSelecionada;
            const areaOk = !areaSelecionada || area.includes(areaSelecionada);

            tr.style.display = (escolaOk && categoriaOk && areaOk) ? "" : "none";
          });
        }

        filtroEscola.addEventListener("change", filtrarTabela);
        filtroCategoria.addEventListener("change", filtrarTabela);
        filtroArea.addEventListener("change", filtrarTabela);
      });
  </script>
</body>

</html>