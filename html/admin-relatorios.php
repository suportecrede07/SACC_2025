<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../php/Connect.php';

$escolas = $pdo->query("SELECT e.id_escolas, e.nome, ce.categoria_da_escola FROM Escolas e LEFT JOIN Categoria_escolas ce ON e.id_categoria_escola = ce.id ORDER BY e.nome")->fetchAll(PDO::FETCH_ASSOC);
$jurados = $pdo->query("SELECT id_jurados, nome FROM Jurados ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT id_categoria, nome_categoria FROM Categorias ORDER BY nome_categoria")->fetchAll(PDO::FETCH_ASSOC);
$areas = $pdo->query("SELECT id_area, nome_area FROM Areas ORDER BY nome_area")->fetchAll(PDO::FETCH_ASSOC);

$trabalhos = $pdo->query("
  SELECT 
    t.id_trabalhos,
    t.titulo,
    e.nome AS escola,
    c.nome_categoria AS categoria,
    a.nome_area AS area,
    ce.categoria_da_escola
  FROM Trabalhos t
  LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
  LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
  LEFT JOIN categoria_escolas ce ON e.id_categoria_escola = ce.id
  LEFT JOIN Areas a ON t.id_areas = a.id_area
  ORDER BY t.titulo
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">


<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Relatórios</title>

  <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <link href="../boostrap/CSS/bootstrap-icons.css" rel="stylesheet">
  <script src="../boostrap/JS/jquery.min.js"></script>
  <link rel="stylesheet" href="../assets/styles/dashboard-admin.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/styles/relatorios.css?v=<?= time() ?>">
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
        <li><a href="admin-dashboard.php"><i><img src="../assets/img/dashboard.svg" class="dashboard"></i> <span
              class="label-text">Dashboard</span></a></li>
        <li><a href="admin-escolas.php"><i><img src="../assets/img/escolas.svg" class="escola"></i> <span
              class="label-text">Escolas</span></a></li>
        <li><a href="admin-trabalhos.php"><i><img src="../assets/img/trabalhos.svg" class="trabalho"></i> <span
              class="label-text">Trabalhos</span></a></li>
        <li><a href="admin-jurados.php"><i><img src="../assets/img/jurados.svg" class="jurado"></i> <span
              class="label-text">Jurados</span></a></li>
        <li class="active"><a href="admin-relatorios.php"><i><img src="../assets/img/relatorios.svg" class="relatorio"></i> <span
              class="label-text">Relatórios</span></a></li>
      </ul>
    </div>
    <ul class="nav flex-column bottom-nav">
      <li><a href="../php/AdmLogout.php"><img src="../assets/img/sair.png" class="sair"> <span
            class="label-text">Sair</span></a></li>
    </ul>
  </div>
  <main id="main">
    <div class="container-fluid p-0">
      <div class="page-header-clean d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h1 class="page-title">Relatórios de Trabalhos</h1>
          <p class="page-subtitle">Gere relatórios sintéticos e analíticos por escola, jurado ou ranking geral.</p>
        </div>
      </div>

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

      <div class="d-flex flex-wrap gap-2 mb-4">
        <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalJurado">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
          Por Jurado
        </button>
        <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalAmbosJurados">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75M9 21v-2a4 4 0 00-3-3.87"/></svg>
          Ambos os Jurados
        </button>
        <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalPorEscola">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          Por Escola
        </button>
        <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalRanking">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          Ranking
        </button>
      </div>

      <div class="admin-card-table">
        <table class="table admin-table align-middle text-center mb-0" id="workTable">
          <thead>
            <tr>
              <th class="text-start">Título do Trabalho</th>
              <th class="text-start">Escola</th>
              <th>Categoria</th>
              <th>Área</th>
              <th class="text-center" style="width: 160px;">Download</th>
            </tr>
          </thead>
          <tbody id="workTbody">
            <?php foreach ($trabalhos as $t): ?>
              <?php
              $stmt = $pdo->prepare("SELECT id_jurado FROM Avaliacoes WHERE id_trabalho = :id_trabalho LIMIT 1");
              $stmt->execute(['id_trabalho' => $t['id_trabalhos']]);
              $juradoRow = $stmt->fetch(PDO::FETCH_ASSOC);

              $id_jurado = $juradoRow['id_jurado'] ?? null;
              ?>
              <tr>
                <td class="text-start td-item-title"><?= htmlspecialchars($t['titulo']) ?></td>
                <td class="text-start"><?= htmlspecialchars(($t['categoria_da_escola'] ?? '') . ' ' . $t['escola']) ?></td>
                <td><span class="category-pill"><?= htmlspecialchars($t['categoria'] ?? '—') ?></span></td>
                <td><?= htmlspecialchars($t['area'] ?? 'Sem área') ?></td>
                <td class="text-center">
                  <?php if ($id_jurado): ?>
                    <div class="d-inline-flex gap-1 justify-content-center">
                      <button class="btn btn-sm btn-danger px-2 py-1 fw-semibold" style="font-size: 0.78rem;"
                        onclick="abrirModalRelatorio(<?= $t['id_trabalhos'] ?>, 'pdf')">PDF</button>
                      <button class="btn btn-sm btn-success px-2 py-1 fw-semibold" style="font-size: 0.78rem;"
                        onclick="abrirModalRelatorio(<?= $t['id_trabalhos'] ?>, 'excel')">Excel</button>
                    </div>
                  <?php else: ?>
                    <span class="text-muted" style="font-size: 0.82rem;">Sem avaliação</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <script>
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
    <div class="modal fade" id="modalPdf" tabindex="-1" aria-labelledby="modalPdfLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalPdfLabel">Selecione o Jurado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex justify-content-center" style="gap: 20px;">
              <a href="#" class="btn btn-primary">Jurado 1</a>
              <a href="#" class="btn btn-primary">Jurado 2</a>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalExcel" tabindex="-1" aria-labelledby="modalExcelLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalExcelLabel">Selecione o Jurado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex justify-content-center" style="gap: 20px;">
              <a href="#" class="btn btn-primary">Jurado 1</a>
              <a href="#" class="btn btn-primary">Jurado 2</a>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalJurado" tabindex="-1" aria-labelledby="modalJuradoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalJuradoLabel">Relatório Por Jurado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <label for="jurado-categoria">Categoria</label>
            <select id="jurado-categoria" class="form-select">
              <option selected disabled>Selecione a Categoria</option>
              <?php foreach ($categorias as $categoria): ?>
                <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>">
                  <?= htmlspecialchars($categoria['nome_categoria']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="jurado-area" style="display:none; margin-top:10px;">
              <label for="jurado-area-select">Área</label>
              <select id="jurado-area-select" class="form-select">
                <option selected disabled>Selecione a Área</option>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= htmlspecialchars($area['id_area']) ?>">
                    <?= htmlspecialchars($area['nome_area']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="jurado-area2" style="display:none; margin-top:10px;">
              <label for="jurado-area2-select">Área</label>
              <select id="jurado-area2-select" class="form-select">
                <option selected disabled>Selecione a Área</option>
                <option value="6">Ensino Fundamental</option>
                <option value="7">Ensino Médio</option>
              </select>
            </div>
            <div id="jurado-nome" style="display:none; margin-top:10px;">
              <label for="jurado-nome-select">Nome do Jurado</label>
              <select id="jurado-nome-select" class="form-select">
                <option selected disabled>Selecione o Jurado</option>
                <?php foreach ($jurados as $j): ?>
                  <option value="<?= htmlspecialchars($j['id_jurados']) ?>">
                    <?= htmlspecialchars($j['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <script>
            function carregarJurados() {
              const categoria = $('#jurado-categoria').val();
              const area = $('#jurado-area-select').val() || $('#jurado-area2-select').val() || '';

              if (!categoria) return;

              $.getJSON('../php/BuscarJurados.php', {
                  categoria: categoria,
                  area: area
                })
                .done(function(data) {
                  const select = $('#jurado-nome-select');
                  select.empty().append('<option value="">Selecione o Jurado</option>');
                  data.forEach(j => {
                    select.append(`<option value="${j.id_jurados}">${j.nome}</option>`);
                  });
                  $('#jurado-nome').slideDown();
                })
            }

            $('#jurado-categoria').on('change', carregarJurados);
            $('#jurado-area-select, #jurado-area2-select').on('change', carregarJurados);

            $.getJSON('../php/BuscarJurados.php', {
                categoria: categoriaSelecionada,
                area: areaSelecionada
              })
              .done(function(data) {
                const select = $('#jurado-nome-select');
                select.empty().append('<option value="">Selecione o Jurado</option>');
                data.forEach(j => {
                  select.append(`<option value="${j.id_jurados}">${j.nome}</option>`);
                });
              });
          </script>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            <button type="button" class="btn btn-success" id="btnGerarRelatorioJurado">Gerar Relatório</button>
          </div>
        </div>
      </div>
      <script>
        $('#jurado-categoria').on('change', function() {
          const val = $(this).val();

          if (val === '4') {
            $('#jurado-area2').slideDown();
            $('#jurado-area').slideUp();
            $('#jurado-nome').slideUp();
          } else if (val === '1' || val === '2') {
            $('#jurado-area').slideDown();
            $('#jurado-area2').slideUp();
            $('#jurado-nome').slideUp();
          } else if (val === '3') {
            $('#jurado-area, #jurado-area2').slideUp();
            $('#jurado-nome').slideDown();
          } else {
            $('#jurado-area, #jurado-area2, #jurado-nome').slideUp();
          }
        });

        $('#jurado-area-select, #jurado-area2-select').on('change', function() {
          $('#jurado-nome').slideDown();
        });

        $('#btnGerarRelatorioJurado').on('click', function() {
          const categoria = $('#jurado-categoria').val();
          const area = $('#jurado-area-select').val() || $('#jurado-area2-select').val() || '';
          const id_jurado = $('#jurado-nome-select').val();

          if (!categoria || !id_jurado) {
            alert('Selecione todos os campos antes de gerar o relatório.');
            return;
          }

          let url = `../html/relat-por-jurado.php?id_jurado=${id_jurado}&id_categoria=${categoria}`;
          if (area) url += `&id_area=${area}`;

          window.open(url, '_blank');
        });
      </script>
    </div>
    </div>
    <!--  MODAL DE AMBOS OS JURADOS - COMEÇO  -->
    <div class="modal fade" id="modalAmbosJurados" tabindex="-1" aria-labelledby="modalAmbosJuradosLabel"
      aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalAmbosJuradosLabel">Relatório por Ambos os Jurados</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <p>Gerar relatório dos trabalhos avaliados por ambos os jurados.</p>

            <label for="categoria-Ambos-select" class="form-label">Categoria</label>
            <select id="categoria-Ambos-select" class="form-select mb-2" name="categoria">
              <option value="">Selecione a Categoria</option>
              <?php foreach ($categorias as $categoria): ?>
                <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>">
                  <?= htmlspecialchars($categoria['nome_categoria']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="area-Ambos-select" style="display: none;">
              <label for="area-Ambos1-select" class="form-label">Área</label>
              <select id="area-Ambos1-select" class="form-select">
                <option selected disabled>Selecione a Área</option>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= htmlspecialchars($area['id_area']) ?>">
                    <?= htmlspecialchars($area['nome_area']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="area-Ambos2-select" style="display: none;">
              <label for="area-Ambos2-select" class="form-label">Área</label>
              <select id="area-Ambos2-select" class="form-select">
                <option selected disabled>Selecione a Área</option>
                <option value="6">Ensino Fundamental</option>
                <option value="7">Ensino Médio</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
              style="margin-top: 17px;">Fechar</button>
            <button type="button" class="btn btn-success mt-3" onclick="gerarRelatorioAmbos()">Gerar Relatório</button>
          </div>
        </div>
        <script>
          function gerarRelatorioAmbos() {
            const categoria = document.getElementById('categoria-Ambos-select').value;
            const area = $('#area-Ambos1-select').val() || $('#area-Ambos2-select').val() || '';


            if (!categoria) {
              alert('Selecione a categoria antes de gerar o relatório.');
              return;
            }

            let url = `../html/relatorios-ambos-jurados.php?catId=${categoria}&areaId=${area}&type=pdf`;
            window.open(url, '_blank');
          }
        </script>
      </div>
    </div>
    <!--  MODAL DE AMBOS OS JURADOS - FIM  -->

    <div class="modal fade" id="modalPorEscola" tabindex="-1" aria-labelledby="modalPorEscolaLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalPorEscolaLabel">Relatório por Escola</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <p>Gerar relatório dos trabalhos submetidos por uma escola específica.</p>

            <label for="escola" class="form-label">Escola</label>
            <select id="selectEscola" name="escola" class="form-control" required>
              <option value="">Selecione a Escola</option>
              <?php foreach ($escolas as $escola): ?>
                <option value="<?= htmlspecialchars($escola['id_escolas']) ?>">
                  <?= htmlspecialchars($escola['categoria_da_escola'] ?? '') .' '. htmlspecialchars($escola['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button type="button" class="btn btn-success mt-3" onclick="gerarRelatorio()">Gerar Relatório</button>

            <script>
              function gerarRelatorio() {
                const idEscola = document.getElementById('selectEscola').value;
                if (!idEscola) {
                  alert('Selecione uma escola antes de gerar o relatório.');
                  return;
                }
                window.open(`../html/relat_escola.php?id_escola=${idEscola}&type=pdf`, '_blank');
              }
            </script>

          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalRanking" tabindex="-1" aria-labelledby="modalRankingLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalRankingLabel">Ranking de Trabalhos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <p>Gerar relatório de classificação dos trabalhos.</p>

            <label for="categoria-Ranking" class="form-label">Categoria</label>
            <select id="categoria-Ranking" name="id_categoria" class="form-select mb-2">
              <option value="">Selecione a Categoria</option>
              <?php foreach ($categorias as $categoria): ?>
                <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>">
                  <?= htmlspecialchars($categoria['nome_categoria']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <div id="area-Ranking" style="display: none;">
              <label for="area-Ranking-select" name="id_areas" class="form-label">Área</label>
              <select id="area-Ranking-select" name="id_areas" class="form-select">
                <option value="">Selecione a Área</option>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= htmlspecialchars($area['id_area']) ?>">
                    <?= htmlspecialchars($area['nome_area']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="area-Ranking2" style="display: none;">
              <label for="area-Ranking2-select" name="id_areas" class="form-label">Área</label>
              <select id="area-Ranking2-select" name="id_areas" class="form-select">
                <option value="">Selecione a Área</option>
                <option value="6">Ensino Fundamental</option>
                <option value="7">Ensino Médio</option>
              </select>

            </div>
            <script>
              function gerarRelatorioRanking() {
                const categoria = document.getElementById('categoria-Ranking').value;
                const area = document.getElementById('area-Ranking-select').value;
                const area2 = document.getElementById('area-Ranking2-select').value;

                if (!categoria) {
                  alert('Selecione uma categoria antes de gerar o relatório.');
                  return;
                }
                let url = `../html/relat_ranking.php?id_categoria=${categoria}`;
                if (area) {
                  url += `&id_areas=${area}`;
                }
                if (area2) {
                  url += `&id_areas=${area2}`;
                }

                window.open(url, '_blank');
              }
            </script>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                style="margin-top: 17px;">Fechar</button>
              <button type="button" class="btn btn-success mt-3" onclick="gerarRelatorioRanking()">Gerar
                Relatório</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
  <script src="../bootstrap/JS/jquery.min.js"></script>
  <script>
    $('#modalPdf').on('show.bs.modal', function(event) {
      const button = $(event.relatedTarget);
      const idTrabalho = button.data('id');
    });

    $('#categoria-Ambos-select').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#area-Ambos-select').slideDown();
        $('#area-Ambos2-select').slideUp();
      } else if (categoria === '4') {
        $('#area-Ambos-select').slideUp();
        $('#area-Ambos2-select').slideDown();
      } else if (categoria === '3') {
        $('#area-Ambos-select, #area-Ambos2-select').slideUp();
      }
    });
    $('#categoria-Ranking').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#area-Ranking').slideDown();
        $('#area-Ranking2').slideUp();
      } else if (categoria === '4') {
        $('#area-Ranking').slideUp();
        $('#area-Ranking2').slideDown();
      } else if (categoria === '3') {
        $('#area-Ranking, #area-Ranking2').slideUp();
      }
    });

    $('#jurado-categoria').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#jurado-area').slideDown();
        $('#jurado-area2').slideUp();
        $('#jurado-nome').slideUp();
      } else if (categoria === '4') {
        $('#jurado-area2').slideDown();
        $('#jurado-area').slideUp();
        $('#jurado-nome').slideUp();
      } else if (categoria === '3') {
        $('#jurado-nome').slideDown();
        $('#jurado-area, #jurado-area2').slideUp();
      } else {
        $('#jurado-area, #jurado-area2, #jurado-nome').slideUp();
      }
    });

    $('#jurado-area, #jurado-area2').change(function() {
      $('#jurado-nome').slideDown();
    });

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
    const areas = {
      "1": ["Linguagens, Códigos e suas Tecnologias - LC", "Matemática e suas Tecnologias - MT", "Ciências da Natureza, Educação Ambiental e Engenharias - CN", "Ciências Humanas e Sociais Aplicadas - CH", "Robótica, Automação e Aplicação das TIC"],
      "2": ["Linguagens, Códigos e suas Tecnologias - LC", "Matemática e suas Tecnologias - MT", "Ciências da Natureza, Educação Ambiental e Engenharias - CN", "Ciências Humanas e Sociais Aplicadas - CH", "Robótica, Automação e Aplicação das TIC"],
      "3": [],
      "4": ["Ensino Fundamental", "Ensino Médio"]
    };

    $('#Filtro_categoria').on('change', function() {
      const cat = $(this).val();
      const areaSelect = $('#Filtro_area');
      areaSelect.html('<option value="">Selecione a Área</option>');
      if (areas[cat]) {
        areas[cat].forEach(a => {
          areaSelect.append(`<option value="${a}">${a}</option>`);
        });
      }
      filterWorks();
    });

    $('#Filtro_escola, #Filtro_area').on('change', filterWorks);

    function filterWorks() {
      const escola = $('#Filtro_escola').val();
      const categoria = $('#Filtro_categoria').val();
      const area = $('#Filtro_area').val();

      const filtered = works.filter(w => {
        const matchEscola = !escola || w.escola === escola;
        const matchCategoria = !categoria || w.categoria === categoria;
        const matchArea = !area || w.area === area;
        return matchEscola && matchCategoria && matchArea;
      });

      const tbody = $('#workTbody');
      tbody.empty();

      if (filtered.length === 0) {
        tbody.append('<tr><td colspan="5">Nenhum trabalho encontrado.</td></tr>');
        return;
      }

      filtered.forEach(w => {
        tbody.append(`
      <tr>
        <td>${w.titulo}</td>
        <td>${w.escola ?? '—'}</td>
        <td>${w.categoria ?? '—'}</td>
        <td>${w.area ?? '—'}</td>
        <td><span class="text-muted">Sem avaliação</span></td>
      </tr>
    `);
      });
    }
    filterWorks();

    function abrirModalRelatorio(idTrabalho, tipo) {
      $.ajax({
        url: '../php/JuradoTrabalho.php',
        method: 'POST',
        data: {
          id_trabalho: idTrabalho
        },
        success: function(data) {
          const jurados = JSON.parse(data);
          const container = tipo === 'pdf' ? $('#modalPdf .modal-body .d-flex') : $('#modalExcel .modal-body .d-flex');
          container.empty();

          if (jurados.length === 0) {
            container.append('<span class="text-danger">Nenhuma avaliação encontrada para esse trabalho.</span>');
            return;
          }

          jurados.forEach(j => {
            const file = tipo === 'pdf' ?
              '../html/relat-trabalho-individual.php' :
              '../html/relat-trabalho-individual-excel.php'; // <<-- mude aqui

            const btn = $(
              `<a href="${file}?id_trabalho=${idTrabalho}&id_jurado=${j.id_jurado}" 
        class="btn btn-primary" target="_blank">${j.nome}</a>`
            );

            container.append(btn);
          });


          const modal = tipo === 'pdf' ? '#modalPdf' : '#modalExcel';
          $(modal).modal('show');
        },
        error: function() {
          alert('Erro ao buscar jurados para o trabalho.');
        }
      });
    }
  </script>
</body>

</html>