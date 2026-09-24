<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['id_jurados']) || !isset($_SESSION['usuario'])) {
  header('Location: ../html/login_jurado.php');
  exit();
}

require_once '../php/Connect.php';

$idJurado = $_SESSION['id_jurados'];

$stmtUser = $pdo->prepare("SELECT nome, avaliacoes_finalizadas FROM Jurados WHERE id_jurados = ?");
$stmtUser->execute([$idJurado]);
$result = $stmtUser->fetch(PDO::FETCH_ASSOC);

$userName = $result ? $result['nome'] : 'Usuário';
$avaliacoesFinalizadas = $result ? (int)$result['avaliacoes_finalizadas'] : 0;

$stmt = $pdo->prepare("
  SELECT 
    t.id_trabalhos, 
    t.titulo, 
    e.nome AS nome_escola, 
    c.nome_categoria, 
    a.nome_area,
    (
      SELECT COUNT(*) 
      FROM avaliacoes av 
      WHERE av.id_trabalho = t.id_trabalhos 
        AND av.id_jurado = jt.id_jurado
    ) AS avaliacao_existente
  FROM Jurado_Trabalho jt
  INNER JOIN Trabalhos t ON jt.id_trabalho = t.id_trabalhos
  LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
  LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
  LEFT JOIN Areas a ON t.id_areas = a.id_area
  WHERE jt.id_jurado = ?
");
$stmt->execute([$idJurado]);
$trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cálculo das estatísticas
$totalAtribuidos = count($trabalhos);
$avaliados = 0;
foreach ($trabalhos as $t) {
  if ($t['avaliacao_existente'] > 0) {
    $avaliados++;
  }
}
$pendentes = $totalAtribuidos - $avaliados;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Jurado Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/styles/dashboard.css?v=<?= time() ?>" />
  <script src="../boostrap/JS/jquery.min.js"></script>
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <style>
    .is-invalid {
      border-color: #dc3545 !important;
    }
  </style>
</head>

<body>

  <header class="dashboard-header-new">
    <div class="d-flex align-items-center flex-grow-1" style="flex-basis: 0;">
      <a href="../php/JuradoLogout.php" class="btn-logout" title="Sair">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
        </svg>
      </a>
      <span class="badge-sistema">Sistema de Avaliação 2026</span>
    </div>
    <div class="d-flex justify-content-center align-items-center flex-grow-1" style="flex-basis: 0;">
      <img src="../assets/img/SACC.png" alt="Logo SACC" class="logo-sacc" />
    </div>
    <div class="d-flex align-items-center justify-content-end flex-grow-1" style="flex-basis: 0;">
      <div class="user-info-text">
        <strong class="user-name"><?= htmlspecialchars($userName) ?></strong>
        <span class="user-role">Avaliador Científico</span>
      </div>
    </div>
  </header>

  <main class="dashboard-container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
      <div class="flex-grow-1" style="min-width: 320px;">
        <h1 class="fw-bolder text-dark mb-1" style="font-size: 1.85rem; letter-spacing: -0.02em;">Lista de Trabalhos para <?= htmlspecialchars($userName) ?></h1>
        <p class="text-secondary mb-0" style="font-size: 0.95rem;">Gerencie e registre suas avaliações de projetos científicos vinculados à sua comissão.</p>

        <!-- Botão Finalizar Avaliações Integrado ao Cabeçalho -->
        <?php if (!isset($avaliacoesFinalizadas) || $avaliacoesFinalizadas == 0): ?>
          <div class="mt-3">
            <button type="button" class="btn text-white fw-bold shadow-sm rounded-3 d-inline-flex align-items-center gap-2" id="btnFinalizarAvaliacoes" style="background-color: #f97316; border: none; padding: 10px 24px; font-size: 0.95rem;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l4.992-5.5a.75.75 0 0 0-.018-1.042z" />
              </svg>
              Finalizar Avaliações
            </button>
          </div>
        <?php endif; ?>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <div class="d-flex flex-column align-items-center justify-content-center py-2 px-3 rounded-3 text-center border bg-white" style="min-width: 125px;">
          <span class="fw-bold text-uppercase mb-1 text-secondary" style="font-size: 0.68rem; letter-spacing: 0.05em;">ATRIBUÍDOS</span>
          <strong class="text-dark fw-bolder" style="font-size: 1.05rem;"><?= $totalAtribuidos ?> Trabalho<?= $totalAtribuidos != 1 ? 's' : '' ?></strong>
        </div>
        <div class="d-flex flex-column align-items-center justify-content-center py-2 px-3 rounded-3 text-center bg-success bg-opacity-10" style="min-width: 125px; border: 1.5px solid #86efac;">
          <span class="fw-bold text-uppercase mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em; color: #16a34a;">AVALIADOS</span>
          <strong class="fw-bolder" style="font-size: 1.05rem; color: #15803d;"><?= $avaliados ?> Concluído<?= $avaliados != 1 ? 's' : '' ?></strong>
        </div>
        <div class="d-flex flex-column align-items-center justify-content-center py-2 px-3 rounded-3 text-center bg-white" style="min-width: 125px; border: 1.5px solid #fcd34d;">
          <span class="fw-bold text-uppercase mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em; color: #ea580c;">PENDENTES</span>
          <strong class="fw-bolder" style="font-size: 1.05rem; color: #c2410c;"><?= $pendentes ?> Restante<?= $pendentes != 1 ? 's' : '' ?></strong>
        </div>
      </div>
    </div>

    <div class="table-container-clean">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 32%;">TÍTULO</th>
            <th style="width: 23%;">ESCOLA</th>
            <th style="width: 17%;">CATEGORIA</th>
            <th style="width: 18%;">ÁREA</th>
            <th style="width: 10%; text-align: center;">AÇÕES</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($trabalhos) === 0): ?>
            <tr>
              <td colspan="5" class="text-center">Nenhum trabalho associado a você no momento.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($trabalhos as $trabalho): ?>
              <tr>
                <td class="td-titulo"><?= htmlspecialchars($trabalho['titulo']) ?></td>
                <td class="td-escola"><?= htmlspecialchars($trabalho['nome_escola'] ?? 'N/D') ?></td>
                <td class="td-categoria"><?= htmlspecialchars($trabalho['nome_categoria'] ?? 'N/D') ?></td>
                <td class="td-area"><?= htmlspecialchars($trabalho['nome_area'] ?? 'N/D') ?></td>
                <td style="text-align: center; vertical-align: middle;">
                  <?php if ($trabalho['avaliacao_existente'] == 0): ?>
                    <button
                      class="btn text-white fw-bold shadow-sm rounded-3 abrir-modal-avaliacao"
                      style="background-color: #f97316; width: 115px; padding: 9px 20px; font-size: 0.9rem;"
                      data-bs-toggle="modal"
                      data-bs-target="#avaliarModal"
                      data-titulo="<?= htmlspecialchars($trabalho['titulo']) ?>"
                      data-escola="<?= htmlspecialchars($trabalho['nome_escola'] ?? 'N/D') ?>"
                      data-categoria="<?= htmlspecialchars($trabalho['nome_categoria'] ?? 'N/D') ?>"
                      data-area="<?= htmlspecialchars($trabalho['nome_area'] ?? 'N/D') ?>"
                      data-id="<?= $trabalho['id_trabalhos'] ?>">
                      Avaliar
                    </button>
                  <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                      <div class="badge rounded-pill bg-success bg-opacity-10 text-success fw-bold" style="width: 115px; padding: 6px 14px; color: #15803d !important; font-size: 0.82rem;">
                        Avaliado
                      </div>
                      <?php if ($avaliacoesFinalizadas == 0): ?>
                        <button
                          class="btn text-white fw-bold shadow-sm rounded-3 d-inline-flex justify-content-center align-items-center gap-1 abrir-modal-editar"
                          style="background-color: #3b8754; width: 115px; padding: 7px 14px; font-size: 0.85rem;"
                          data-bs-toggle="modal"
                          data-bs-target="#avaliarModal"
                          data-titulo="<?= htmlspecialchars($trabalho['titulo']) ?>"
                          data-escola="<?= htmlspecialchars($trabalho['nome_escola'] ?? 'N/D') ?>"
                          data-categoria="<?= htmlspecialchars($trabalho['nome_categoria'] ?? 'N/D') ?>"
                          data-area="<?= htmlspecialchars($trabalho['nome_area'] ?? 'N/D') ?>"
                          data-id="<?= $trabalho['id_trabalhos'] ?>">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
                          </svg>
                          Editar
                        </button>
                      <?php else: ?>
                        <button
                          type="button"
                          class="btn fw-bold rounded-3 d-inline-flex justify-content-center align-items-center gap-1"
                          style="background-color: #d1d5db; color: #6b7280; width: 115px; padding: 7px 14px; font-size: 0.85rem; cursor: not-allowed; opacity: 0.75;"
                          disabled>
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10z" />
                          </svg>
                          Editar
                        </button>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Modal Avaliar/Editar -->
  <div class="modal fade" id="avaliarModal" tabindex="-1" aria-labelledby="avaliarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg bg-white">

        <div class="modal-header border-bottom bg-white px-4 py-3" style="border-bottom-color: #f1f5f9 !important;">
          <h5 class="modal-title fw-bolder text-dark fs-5" id="avaliarModalLabel" style="color: #0f172a !important;">Avaliação do Trabalho</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body px-4 py-3 bg-white">
          <div class="container-fluid p-0">

            <!-- Informações do Trabalho -->
            <div class="modal-info-grid mb-3">
              <div class="row g-2">
                <div class="col-12 d-flex align-items-start">
                  <strong class="info-label">Título:</strong>
                  <span id="modalTitulo" class="info-value text-break"></span>
                </div>
                <div class="col-12 d-flex align-items-start">
                  <strong class="info-label">Escola:</strong>
                  <span id="modalEscola" class="info-value text-break"></span>
                </div>
                <div class="col-12 d-flex align-items-start">
                  <strong class="info-label">Categoria:</strong>
                  <span id="modalCategoria" class="info-value text-break"></span>
                </div>
                <div class="col-12 d-flex align-items-start">
                  <strong class="info-label">Área:</strong>
                  <span id="modalArea" class="info-value text-break"></span>
                </div>
              </div>
            </div>

            <form id="formAvaliacao" action="../php/SalvarAvaliacao.php" method="post">
              <input type="hidden" name="id_trabalho" id="id_trabalho" value="" />

              <?php
              $criterios = [
                1 => "Criatividade e Inovação",
                2 => "Relevância da pesquisa",
                3 => "Conhecimento científico fundamentado e contextualização do problema abordado",
                4 => "Impacto para a construção de uma sociedade que promova a ciência e o desenvolvimento científico",
                5 => "Metodologia científica conectada com os objetivos, resultados e conclusões",
                6 => "Clareza e objetividade na linguagem apresentada",
                7 => "Banner",
                8 => "Caderno de campo",
                9 => "Processo participativo e solidário"
              ];
              ?>

              <!-- VISÃO DESKTOP: Tabela Limpa (Visível em lg+ >= 992px) -->
              <div class="modal-table-container d-none d-lg-block">
                <table class="table modal-table mb-0">
                  <thead>
                    <tr>
                      <th class="th-criterio">CRITÉRIO</th>
                      <th class="th-nota">NOTA</th>
                      <th class="th-comentario">COMENTÁRIO</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($criterios as $index => $tituloCriterio): ?>
                      <tr>
                        <td class="td-criterio-text"><?= $tituloCriterio ?></td>
                        <td class="td-nota-input">
                          <input type="text" inputmode="numeric" class="form-control nota-auto input-nota-clean desk-nota" data-index="<?= $index ?>" maxlength="5" placeholder="0,00" />
                        </td>
                        <td class="td-comentario-input">
                          <textarea class="form-control input-comentario-clean desk-comentario" data-index="<?= $index ?>" rows="1" placeholder=""></textarea>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <!-- VISÃO TABLET E MOBILE: Accordion (Visível em telas < 992px) -->
              <div class="d-lg-none">
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                  <span id="progressoCriterios" class="text-secondary fw-semibold small">0 de 9 critérios avaliados</span>
                  <div class="bg-light px-3 py-1 rounded-pill border fw-bold text-dark small">
                    Média: <span id="mediaCalculada">--</span>
                  </div>
                </div>

                <div class="accordion d-flex flex-column gap-2" id="accordionCriterios">
                  <?php foreach ($criterios as $index => $tituloCriterio): ?>
                    <div class="accordion-item border rounded-3 overflow-hidden shadow-sm">
                      <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button collapsed py-2 px-3 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                          <div class="d-flex align-items-center w-100 me-2">
                            <span class="badge rounded-circle text-white d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 24px; height: 24px; font-size: 0.78rem; background-color: #15803d;"><?= $index ?></span>
                            <strong class="text-dark me-auto text-wrap me-2" style="font-size: 0.88rem; text-align: left;"><?= $tituloCriterio ?></strong>
                            <span class="badge rounded-pill px-3 py-1 me-2 badge-status" id="badgeStatus<?= $index ?>" style="font-size: 0.72rem; background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5;">PENDENTE</span>
                          </div>
                        </button>
                      </h2>
                      <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#accordionCriterios">
                        <div class="accordion-body bg-light p-3 border-top">
                          <div class="row g-3">
                            <div class="col-12">
                              <label class="form-label fw-bold text-dark small mb-1">Nota (0 a 10):</label>
                              <input type="text" inputmode="numeric" class="form-control nota-auto input-nota-clean mob-nota w-100" data-index="<?= $index ?>" maxlength="5" placeholder="0,00" style="width: 100% !important;" />
                            </div>
                            <div class="col-12">
                              <label class="form-label fw-bold text-dark small mb-1">Comentário (opcional):</label>
                              <textarea class="form-control input-comentario-clean mob-comentario w-100" data-index="<?= $index ?>" rows="2" placeholder="Digite seus comentários..." style="width: 100% !important;"></textarea>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>


              </div>

              <!-- CAMPOS REAIS DO FORMULÁRIO QUE SERÃO SUBMETIDOS -->
              <?php for ($i = 1; $i <= 9; $i++): ?>
                <input type="hidden" name="criterio<?= $i ?>" id="real_criterio<?= $i ?>" value="" />
                <input type="hidden" name="comentario<?= $i ?>" id="real_comentario<?= $i ?>" value="" />
              <?php endfor; ?>

              <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                <button type="button" class="btn btn-modal-voltar" data-bs-dismiss="modal">Voltar</button>
                <button type="button" class="btn btn-modal-finalizar" id="btnAbrirConfirmacao">Finalizar Avaliação</button>
              </div>

              <!-- Modal Interno Confirmação -->
              <div class="modal fade" id="confirmarEnvioModal" tabindex="-1" aria-labelledby="confirmarEnvioModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">

                    <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 16px 20px;">
                      <h5 class="modal-title" id="confirmarEnvioModalLabel" style="font-weight: 700; color: #0f172a;">Confirmar Ação</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body" id="textoConfirmacao" style="padding: 20px; color: #334155; font-size: 0.95rem;">
                      Tem certeza que deseja salvar esta avaliação?
                    </div>

                    <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 14px 20px;">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                      <button type="button" class="btn" id="confirmarEnvioBtn" style="background-color: #15803d; color: #ffffff; border-radius: 8px; font-weight: 700;">Sim, salvar</button>
                    </div>

                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    function autoResize(el) {
      if (!el) return;
      el.style.setProperty('overflow', 'hidden', 'important');

      if (el.value.trim() === '') {
        el.style.removeProperty('height');
        return;
      }

      el.style.setProperty('height', 'auto', 'important');
      if (el.scrollHeight > 32) {
        el.style.setProperty('height', el.scrollHeight + 'px', 'important');
      } else {
        el.style.removeProperty('height');
      }
    }

    function syncCriterios(index, valorNota, valorComentario) {
      const deskNota = document.querySelector(`.desk-nota[data-index="${index}"]`);
      const mobNota = document.querySelector(`.mob-nota[data-index="${index}"]`);
      const realNota = document.getElementById(`real_criterio${index}`);

      const deskCom = document.querySelector(`.desk-comentario[data-index="${index}"]`);
      const mobCom = document.querySelector(`.mob-comentario[data-index="${index}"]`);
      const realCom = document.getElementById(`real_comentario${index}`);

      if (valorNota !== undefined) {
        if (deskNota && deskNota.value !== valorNota) deskNota.value = valorNota;
        if (mobNota && mobNota.value !== valorNota) mobNota.value = valorNota;
        if (realNota) realNota.value = valorNota;
      }

      if (valorComentario !== undefined) {
        if (deskCom && deskCom.value !== valorComentario) {
          deskCom.value = valorComentario;
          autoResize(deskCom);
        }
        if (mobCom && mobCom.value !== valorComentario) {
          mobCom.value = valorComentario;
          autoResize(mobCom);
        }
        if (realCom) realCom.value = valorComentario;
      }

      atualizarResumoCriterios();
    }

    function atualizarResumoCriterios() {
      let avaliadosCount = 0;
      let somaNotas = 0;

      for (let i = 1; i <= 9; i++) {
        const realNota = document.getElementById(`real_criterio${i}`);
        const badge = document.getElementById(`badgeStatus${i}`);
        if (!realNota) continue;

        let valorStr = realNota.value.trim();
        let numero = parseFloat(valorStr.replace(',', '.'));

        if (valorStr !== '' && valorStr !== 'Carregando...' && !isNaN(numero) && numero >= 0 && numero <= 10) {
          avaliadosCount++;
          somaNotas += numero;

          if (badge) {
            badge.textContent = numero.toFixed(2).replace('.', ',');
            badge.style.cssText = 'font-size: 0.72rem; background-color: #f0fdf4 !important; color: #15803d !important; border: 1px solid #bbf7d0 !important; font-weight: bold;';
          }
        } else {
          if (badge) {
            badge.textContent = 'PENDENTE';
            badge.style.cssText = 'font-size: 0.72rem; background-color: #fff7ed !important; color: #ea580c !important; border: 1px solid #ffedd5 !important;';
          }
        }
      }

      const progressoEl = document.getElementById('progressoCriterios');
      if (progressoEl) {
        progressoEl.textContent = `${avaliadosCount} de 9 critérios avaliados`;
      }

      const mediaEl = document.getElementById('mediaCalculada');
      if (mediaEl) {
        if (avaliadosCount > 0) {
          let media = somaNotas / avaliadosCount;
          mediaEl.textContent = media.toFixed(2).replace('.', ',');
        } else {
          mediaEl.textContent = '--';
        }
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const inputsNotas = document.querySelectorAll('.nota-auto');
      const textareasComentarios = document.querySelectorAll('.input-comentario-clean');
      const form = document.getElementById('formAvaliacao');
      const botaoAbrirConfirmacao = document.getElementById('btnAbrirConfirmacao');
      const botaoConfirmarEnvio = document.getElementById('confirmarEnvioBtn');
      let formValido = false;

      inputsNotas.forEach(input => {
        input.addEventListener('input', () => {
          input.value = input.value.replace(/[^0-9,]/g, '');

          const partes = input.value.split(',');
          if (partes.length > 2) {
            input.value = partes[0] + ',' + partes[1];
          }

          const idx = input.getAttribute('data-index');
          syncCriterios(idx, input.value, undefined);
        });

        input.addEventListener('blur', () => {
          let valor = input.value.trim();
          const idx = input.getAttribute('data-index');
          if (valor === 'Carregando...') return;
          if (valor === '') {
            input.classList.remove('is-invalid');
            syncCriterios(idx, '', undefined);
            return;
          }

          let numero = parseFloat(valor.replace(',', '.'));

          if (isNaN(numero) || numero < 0 || numero > 10) {
            input.classList.add('is-invalid');
            syncCriterios(idx, valor, undefined);
            return;
          }

          const decimalPart = valor.split(',')[1];
          if (decimalPart && decimalPart.length > 2) {
            input.classList.add('is-invalid');
            syncCriterios(idx, valor, undefined);
            return;
          }

          let formatted = numero.toFixed(2).replace('.', ',');
          input.value = formatted;
          input.classList.remove('is-invalid');
          syncCriterios(idx, formatted, undefined);
        });
      });

      textareasComentarios.forEach(txt => {
        autoResize(txt); // Inicializa com o tamanho correto
        txt.addEventListener('input', () => {
          autoResize(txt);
          const idx = txt.getAttribute('data-index');
          syncCriterios(idx, undefined, txt.value);
        });
      });

      botaoAbrirConfirmacao.addEventListener('click', () => {
        let valido = true;

        for (let i = 1; i <= 9; i++) {
          const realNota = document.getElementById(`real_criterio${i}`);
          const deskInput = document.querySelector(`.desk-nota[data-index="${i}"]`);
          const mobInput = document.querySelector(`.mob-nota[data-index="${i}"]`);
          const valorStr = realNota ? realNota.value.trim() : '';
          const valorNumerico = parseFloat(valorStr.replace(',', '.'));

          if (isNaN(valorNumerico) || valorNumerico < 0 || valorNumerico > 10) {
            if (deskInput) deskInput.classList.add('is-invalid');
            if (mobInput) mobInput.classList.add('is-invalid');
            valido = false;
          } else {
            const decimais = valorStr.split(',')[1];
            if (decimais && decimais.length > 2) {
              if (deskInput) deskInput.classList.add('is-invalid');
              if (mobInput) mobInput.classList.add('is-invalid');
              valido = false;
            } else {
              if (deskInput) deskInput.classList.remove('is-invalid');
              if (mobInput) mobInput.classList.remove('is-invalid');
              let formatted = valorNumerico.toFixed(2).replace('.', ',');
              syncCriterios(i, formatted, undefined);
            }
          }
        }

        if (!valido) {
          alert("Corrija as notas inválidas (valores entre 0,00 e 10,00 com até 2 casas decimais).");
          return;
        }

        formValido = true;
        const confirmarModal = new bootstrap.Modal(document.getElementById('confirmarEnvioModal'));
        confirmarModal.show();
      });

      botaoConfirmarEnvio.addEventListener('click', () => {
        if (formValido) {
          form.submit();
        }
      });
    });

    // Função Nova Avaliação
    $('.abrir-modal-avaliacao').on('click', function() {
      $('#avaliarModalLabel').text('Avaliação do Trabalho');
      $('#formAvaliacao').attr('action', '../php/SalvarAvaliacao.php');
      $('#textoConfirmacao').text('Tem certeza que deseja salvar esta avaliação? Você não poderá alterar depois sem clicar em Editar.');

      $('#modalTitulo').text($(this).data('titulo'));
      $('#modalEscola').text($(this).data('escola'));
      $('#modalCategoria').text($(this).data('categoria'));
      $('#modalArea').text($(this).data('area'));
      $('#id_trabalho').val($(this).data('id'));

      // Limpar campos
      for (let i = 1; i <= 9; i++) {
        syncCriterios(i, '', '');
      }
      $('.nota-auto').removeClass('is-invalid');

      // Fechar accordions no mobile
      $('.accordion-collapse').removeClass('show');
      $('.accordion-button').addClass('collapsed');
    });

    // Função Editar Avaliação Existente
    $('.abrir-modal-editar').on('click', function() {
      $('#avaliarModalLabel').text('Editar Avaliação do Trabalho');
      $('#formAvaliacao').attr('action', '../php/EditarAvaliacao.php');
      $('#textoConfirmacao').text('Tem certeza que deseja salvar as alterações nas notas e comentários?');

      $('#modalTitulo').text($(this).data('titulo'));
      $('#modalEscola').text($(this).data('escola'));
      $('#modalCategoria').text($(this).data('categoria'));
      $('#modalArea').text($(this).data('area'));

      let idTrabalho = $(this).data('id');
      $('#id_trabalho').val(idTrabalho);

      for (let i = 1; i <= 9; i++) {
        syncCriterios(i, '', '');
      }
      $('.nota-auto').removeClass('is-invalid');

      $.ajax({
        url: '../php/BuscarAvaliacao.php',
        type: 'GET',
        data: {
          id_trabalho: idTrabalho
        },
        dataType: 'json',
        success: function(response) {
          if (response.error) {
            alert(response.error);
            return;
          }
          for (let i = 1; i <= 9; i++) {
            let criterioData = response['criterio' + i];
            if (criterioData) {
              let notaFormatada = parseFloat(criterioData.nota).toFixed(2).replace('.', ',');
              syncCriterios(i, notaFormatada, criterioData.comentario);
            } else {
              syncCriterios(i, '', '');
            }
          }
        },
        error: function() {
          alert('Erro ao carregar as notas anteriores. Tente novamente.');
          for (let i = 1; i <= 9; i++) {
            syncCriterios(i, '', '');
          }
        }
      });
    });

    const btnFinalizarAvaliacoes = document.getElementById('btnFinalizarAvaliacoes');
    
    if (btnFinalizarAvaliacoes) {
      btnFinalizarAvaliacoes.addEventListener('click', async () => {
        const confirmar = confirm('Tem certeza que deseja finalizar todas as avaliações? Depois disso, você não poderá mais editar as avaliações.');
        if (!confirmar) {
          return;
        }

        try {
          const response = await fetch('../php/FinalizarAvaliacoes.php', {
            method: 'POST'
          });

          const data = await response.json();
          if (data.status === 'sucesso') {
            alert('Avaliações finalizadas com sucesso.');
            window.location.reload();
          } else {
            alert(data.mensagem || 'Não foi possível finalizar as avaliações.');
          }
        } catch (error) {
          alert('Erro ao finalizar as avaliações.');
        }
      });
    }
  </script>
</body>

</html>