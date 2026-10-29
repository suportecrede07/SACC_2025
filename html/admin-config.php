<?php

session_start();

require_once '../php/Connect.php';

if (!isset($_SESSION['id_admin']) || !isset($_SESSION['usuario'])) {
  header('Location: ../html/login_adm.php');
  exit();
}

// Cria o token de segurança caso ainda não exista.
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Garante que cada campo dinâmico de escola tenha uma coluna SQL correspondente.
$colunasDinamicasCriadas = [];
$erroSincronizacaoCampos = null;
try {
  require_once __DIR__ . '/../php/SincronizarCamposDinamicos.php';
  $resultadoSincronizacao = sincronizarColunasDinamicasEscola($pdo);
  $colunasDinamicasCriadas = $resultadoSincronizacao['colunas_criadas'] ?? [];
} catch (Throwable $e) {
  error_log('Erro ao sincronizar colunas de campos dinâmicos: ' . $e->getMessage());
  $erroSincronizacaoCampos = 'Não foi possível criar/sincronizar todas as colunas dos campos dinâmicos. Verifique as permissões ALTER da conta do banco e o log do servidor.';
}

// Busca os critérios de avaliação.
$criterios_avaliacao = [];

try {
  $stmtCrit = $pdo->query(
    "SELECT *
         FROM Criterios_avaliacao
         ORDER BY ordem ASC, id ASC"
  );

  $criterios_avaliacao = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $criterios_avaliacao = [];
}

// Busca os campos dinâmicos ativos.
$campos_formulario = [];

try {
  $stmtCampos = $pdo->query(
    "SELECT *
         FROM Campos_formulario
         WHERE ativo = 1
         ORDER BY formulario ASC, id ASC"
  );

  $campos_formulario = $stmtCampos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $campos_formulario = [];
}

// Separa os campos de escolas e trabalhos.
$campos_escola = array_filter(
  $campos_formulario,
  fn($c) => $c['formulario'] === 'escola'
);

$campos_trabalho = array_filter(
  $campos_formulario,
  fn($c) => $c['formulario'] === 'trabalho'
);

// Busca as regras de desempate na ordem configurada.
$regras_desempate = [];

try {
  $stmtDesempate = $pdo->query(
    "SELECT *
         FROM Criterios_desempate
         ORDER BY ordem_prioridade ASC, id ASC"
  );

  $regras_desempate = $stmtDesempate->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $regras_desempate = [];
}

// Mensagens retornadas pelo arquivo de exclusão.
$mensagensDesempate = [
  'desempate_excluido' => [
    'success',
    'Regra de desempate excluída com sucesso.'
  ],
  'desempate_nao_encontrado' => [
    'warning',
    'A regra selecionada não foi encontrada ou já foi excluída.'
  ],
  'desempate_id_invalido' => [
    'warning',
    'Não foi possível identificar a regra que deve ser excluída.'
  ],
  'token_invalido' => [
    'danger',
    'A solicitação expirou ou não é válida. Atualize a página e tente novamente.'
  ],
  'metodo_invalido' => [
    'danger',
    'A solicitação de exclusão não foi enviada corretamente.'
  ],
  'erro_excluir_desempate' => [
    'danger',
    'Ocorreu um erro ao excluir a regra de desempate. Verifique o log do servidor.'
  ]
];

$mensagemAtual = $_GET['msg'] ?? '';

$mensagensCampos = [
  'campo_excluido' => ['success', 'Campo dinâmico excluído com sucesso. Os valores associados e, quando aplicável, a coluna SQL correspondente foram removidos.'],
  'campo_excluido_coluna_pendente' => ['warning', 'O campo foi removido das configurações, mas o MySQL não permitiu excluir a coluna correspondente em Escolas. Verifique a permissão ALTER no usuário configurado em Connect.php e o log do servidor.'],
  'campo_nao_encontrado' => ['warning', 'O campo selecionado não foi encontrado ou já foi excluído.'],
  'campo_id_invalido' => ['warning', 'Não foi possível identificar o campo que deve ser excluído.'],
  'campo_token_invalido' => ['danger', 'A solicitação expirou ou não é válida. Atualize a página e tente novamente.'],
  'campo_metodo_invalido' => ['danger', 'A solicitação de exclusão não foi enviada corretamente.'],
  'erro_excluir_campo' => ['danger', 'Ocorreu um erro ao excluir o campo. Verifique o log do servidor.']
];

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configurações - SAFC Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">

  <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>

  <link href="../boostrap/CSS/bootstrap-icons.css" rel="stylesheet">
  <script src="../boostrap/JS/jquery.min.js"></script>

  <link
    rel="stylesheet"
    href="../assets/styles/dashboard-admin.css?v=<?= time() ?>">

  <link
    rel="stylesheet"
    href="../assets/styles/admin-config.css?v=<?= time() ?>">

  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>

  <div id="overlay" onclick="closeMobileSidebar()"></div>

  <button id="mobile-toggle" onclick="toggleSidebar()">
    <i><img src="../assets/img/menu.png" alt="Menu"></i>
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
        <li>
          <a href="admin-dashboard.php">
            <i><img src="../assets/img/dashboard.svg" class="dashboard" alt=""></i>
            <span class="label-text">Dashboard</span>
          </a>
        </li>

        <li>
          <a href="admin-escolas.php">
            <i><img src="../assets/img/escolas.svg" class="escola" alt=""></i>
            <span class="label-text">Escolas</span>
          </a>
        </li>

        <li>
          <a href="admin-trabalhos.php">
            <i><img src="../assets/img/trabalhos.svg" class="trabalho" alt=""></i>
            <span class="label-text">Trabalhos</span>
          </a>
        </li>

        <li>
          <a href="admin-jurados.php">
            <i><img src="../assets/img/jurados.svg" class="jurado" alt=""></i>
            <span class="label-text">Jurados</span>
          </a>
        </li>

        <li>
          <a href="admin-relatorios.php">
            <i><img src="../assets/img/relatorios.svg" class="relatorio" alt=""></i>
            <span class="label-text">Relatórios</span>
          </a>
        </li>

        <li>
          <a href="admin-graficos.php">
            <i><img src="../assets/img/grafico.svg" class="grafico" alt=""></i>
            <span class="label-text">Gráficos</span>
          </a>
        </li>

        <li class="active">
          <a href="admin-config.php">
            <i><img src="../assets/img/config.svg" class="grafico" alt=""></i>
            <span class="label-text">Configurações</span>
          </a>
        </li>
      </ul>
    </div>

    <ul class="nav flex-column bottom-nav">
      <li>
        <a href="../php/AdmLogout.php">
          <img src="../assets/img/sair.png" class="sair" alt="">
          <span class="label-text">Sair</span>
        </a>
      </li>
    </ul>
  </div>

  <main id="main">
    <div class="container-fluid p-0">

      <div class="page-header-clean mb-4">
        <h1
          class="page-title fw-bold text-dark mb-1"
          style="font-size: 1.85rem; letter-spacing: -0.02em; color: #000000 !important;">
          Configurações do Sistema
        </h1>

        <p
          class="page-subtitle text-dark mb-0 fw-medium"
          style="font-size: 0.95rem; color: #1e293b !important;">
          Gerencie critérios de avaliação, campos customizados e regras de desempate do evento.
        </p>
      </div>

      <?php if ($erroSincronizacaoCampos !== null): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($erroSincronizacaoCampos, ENT_QUOTES, 'UTF-8') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      <?php elseif (!empty($colunasDinamicasCriadas)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          Colunas SQL criadas para os campos de escola: <?= htmlspecialchars(implode(', ', $colunasDinamicasCriadas), ENT_QUOTES, 'UTF-8') ?>.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($mensagensDesempate[$mensagemAtual])): ?>
        <?php
        [$tipoMensagem, $textoMensagem] =
          $mensagensDesempate[$mensagemAtual];
        ?>

        <div
          class="alert alert-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show"
          role="alert">
          <?= htmlspecialchars($textoMensagem, ENT_QUOTES, 'UTF-8') ?>

          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Fechar"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($mensagensCampos[$mensagemAtual])): ?>
        <?php [$tipoMensagemCampo, $textoMensagemCampo] = $mensagensCampos[$mensagemAtual]; ?>
        <div class="alert alert-<?= htmlspecialchars($tipoMensagemCampo, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($textoMensagemCampo, ENT_QUOTES, 'UTF-8') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      <?php endif; ?>

      <!-- Abas de navegação -->
      <div class="config-tabs-wrapper mb-4">
        <div class="d-inline-flex align-items-center gap-2 p-2 bg-white rounded-4 border shadow-sm">

          <button
            type="button"
            class="btn config-tab-btn active d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold border-0"
            id="tab-criterios"
            onclick="switchConfigTab('criterios')">
            <i class="bi bi-clipboard2-check-fill"></i>
            <span>Critérios de Avaliação</span>
          </button>

          <button
            type="button"
            class="btn config-tab-btn d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold border-0 text-dark"
            id="tab-campos"
            onclick="switchConfigTab('campos')">
            <i class="bi bi-sliders"></i>
            <span>Campos Dinâmicos</span>
          </button>

          <button
            type="button"
            class="btn config-tab-btn d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold border-0 text-dark"
            id="tab-desempate"
            onclick="switchConfigTab('desempate')">
            <i class="bi bi-filter-left"></i>
            <span>Critérios de desempate</span>
          </button>

        </div>
      </div>

      <!-- Critérios de avaliação -->
      <div id="content-criterios" class="config-tab-content">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h2
              class="h5 fw-bold text-dark mb-1"
              style="letter-spacing: -0.01em; color: #000000 !important;">
              Parâmetros de Avaliação dos Trabalhos
            </h2>

            <p
              class="text-dark mb-0 small fw-medium"
              style="color: #1e293b !important;">
              Notas atribuídas pelos jurados durante a feira científica.
            </p>
          </div>

          <button
            type="button"
            class="btn btn-novo-criterio d-inline-flex align-items-center gap-2 px-3 py-2 text-white fw-semibold shadow-sm"
            onclick="novoCriterio()">
            <i class="bi bi-plus-lg"></i>
            <span>Novo Critério de Avaliação</span>
          </button>
        </div>

        <div class="admin-card-table">
          <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">

              <thead>
                <tr>
                  <th class="ps-4 text-center" style="width: 80px;">ORDEM</th>
                  <th style="width: 240px;">NOME DO CRITÉRIO</th>
                  <th>DESCRIÇÃO</th>
                  <th class="text-center" style="width: 110px;">PESO</th>
                  <th class="text-center" style="width: 140px;">NOTA MÍN/MÁX</th>
                  <th class="text-center" style="width: 110px;">STATUS</th>
                  <th class="text-center pe-4" style="width: 110px;">AÇÕES</th>
                </tr>
              </thead>

              <tbody>
                <?php if (!empty($criterios_avaliacao)): ?>

                  <?php foreach ($criterios_avaliacao as $index => $crit): ?>
                    <tr>
                      <td class="ps-4 text-center fw-bold text-dark">
                        <?= htmlspecialchars($crit['ordem'] ?? ($index + 1)) ?>º
                      </td>

                      <td class="fw-bold td-item-title text-dark">
                        <?= htmlspecialchars($crit['nome']) ?>
                      </td>

                      <td class="text-dark small fw-medium">
                        <?= htmlspecialchars($crit['descricao'] ?? 'Sem descrição.') ?>
                      </td>

                      <td class="text-center">
                        <span class="badge-peso">
                          <?= number_format((float)($crit['peso'] ?? 1.0), 2, '.', '') ?>
                        </span>
                      </td>

                      <td class="text-center fw-bold text-dark small">
                        <?= number_format((float)($crit['nota_minima'] ?? 0.0), 2, '.', '') ?>
                        /
                        <?= number_format((float)($crit['nota_maxima'] ?? 10.0), 2, '.', '') ?>
                      </td>

                      <td class="text-center">
                        <?php if (!empty($crit['ativo'])): ?>
                          <span class="badge-status-ativo">• Ativo</span>
                        <?php else: ?>
                          <span class="badge bg-secondary bg-opacity-10 text-dark rounded-pill px-3 py-1 fw-bold small">
                            • Inativo
                          </span>
                        <?php endif; ?>
                      </td>

                      <td class="text-center pe-4 text-nowrap">
                        <div class="d-inline-flex gap-2">

                          <button
                            type="button"
                            class="btn-action-edit"
                            onclick='editarCriterio(<?= json_encode($crit, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                            title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-1 3a.5.5 0 0 0 .606.606l3-1a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                              <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h6a.5.5 0 0 0 0-1h-6A1.5 1.5 0 0 0 1 2.5z" />
                            </svg>
                          </button>

                          <button
                            type="button"
                            class="btn-action-delete"
                            onclick="excluirCriterio(<?= (int)$crit['id'] ?>, <?= (int)$crit['ativo'] ?>)"
                            title="Excluir">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
                            </svg>
                          </button>

                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="7" class="text-center py-5 text-dark">
                      <i class="bi bi-clipboard-x d-block fs-1 text-dark mb-2"></i>

                      <span class="fw-bold fs-6 text-dark d-block mb-1">
                        Nenhum critério de avaliação cadastrado ainda.
                      </span>

                      <p class="small text-dark mb-3">
                        Clique no botão "Novo Critério de Avaliação" para adicionar o primeiro.
                      </p>

                      <button
                        type="button"
                        class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold"
                        onclick="novoCriterio()">
                        <i class="bi bi-plus-lg me-1"></i>
                        Adicionar Critério
                      </button>
                    </td>
                  </tr>

                <?php endif; ?>
              </tbody>

            </table>
          </div>
        </div>
      </div>

      <!-- Campos dinâmicos -->
      <div id="content-campos" class="config-tab-content d-none">

        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
              <h2
                class="h5 fw-bold text-dark mb-1"
                style="letter-spacing: -0.01em; color: #000000 !important;">
                Campos Customizados de Formulários
              </h2>

              <p class="text-dark mb-0 small fw-medium">
                Crie perguntas dinâmicas extras para o cadastro de escolas ou trabalhos.
              </p>
            </div>

            <button
              type="button"
              class="btn btn-novo-criterio d-inline-flex align-items-center gap-2 px-3 py-2 text-white fw-semibold shadow-sm"
              onclick="novoCampo()">
              <i class="bi bi-plus-lg"></i>
              <span>Novo Campo Dinâmico</span>
            </button>
          </div>

          <div class="row g-4">

            <!-- Campos de escolas -->
            <div class="col-lg-6">
              <div class="border rounded-4 p-3 bg-light bg-opacity-50 h-100">

                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                  <i class="bi bi-building-fill text-success fs-5"></i>
                  <h3 class="h6 fw-bold mb-0 text-dark">Formulário de Escolas</h3>
                  <span class="badge bg-white text-dark border ms-auto small fw-bold">
                    <?= count($campos_escola) ?> Campos
                  </span>
                </div>

                <?php if (!empty($campos_escola)): ?>
                  <div class="d-flex flex-column gap-2">

                    <?php foreach ($campos_escola as $campo): ?>
                      <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded-3 border">

                        <div>
                          <strong class="d-block text-dark small">
                            <?= htmlspecialchars($campo['nome_campo']) ?>
                          </strong>

                          <span class="text-dark fw-medium" style="font-size: 0.75rem;">
                            Tipo: <?= htmlspecialchars($campo['tipo_campo']) ?>
                          </span>
                        </div>

                        <div class="d-inline-flex gap-1 align-items-center">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-success border-0 p-1"
                            onclick='editarCampo(<?= json_encode($campo, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                            title="Editar">
                            <i class="bi bi-pencil-square"></i>
                          </button>

                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger border-0 p-1"
                            onclick="excluirCampo(<?= (int)$campo['id'] ?>)"
                            title="Excluir">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>

                      </div>
                    <?php endforeach; ?>

                  </div>
                <?php else: ?>
                  <div class="text-center py-5 text-dark">
                    <i class="bi bi-input-cursor-text d-block fs-2 text-dark mb-2"></i>
                    <span class="fw-bold">Nenhum campo extra para escolas.</span>
                    <p class="small text-dark mb-0">Ex: "Zona Rural?", "IDEB", etc.</p>
                  </div>
                <?php endif; ?>

              </div>
            </div>

            <!-- Campos de trabalhos -->
            <div class="col-lg-6">
              <div class="border rounded-4 p-3 bg-light bg-opacity-50 h-100">

                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                  <i class="bi bi-journal-bookmark-fill text-success fs-5"></i>
                  <h3 class="h6 fw-bold mb-0 text-dark">Formulário de Trabalhos</h3>
                  <span class="badge bg-white text-dark border ms-auto small fw-bold">
                    <?= count($campos_trabalho) ?> Campos
                  </span>
                </div>

                <?php if (!empty($campos_trabalho)): ?>
                  <div class="d-flex flex-column gap-2">

                    <?php foreach ($campos_trabalho as $campo): ?>
                      <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded-3 border">

                        <div>
                          <strong class="d-block text-dark small">
                            <?= htmlspecialchars($campo['nome_campo']) ?>
                          </strong>

                          <span class="text-dark fw-medium" style="font-size: 0.75rem;">
                            Tipo: <?= htmlspecialchars($campo['tipo_campo']) ?>
                          </span>
                        </div>

                        <div class="d-inline-flex gap-1 align-items-center">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-success border-0 p-1"
                            onclick='editarCampo(<?= json_encode($campo, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                            title="Editar">
                            <i class="bi bi-pencil-square"></i>
                          </button>

                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger border-0 p-1"
                            onclick="excluirCampo(<?= (int)$campo['id'] ?>)"
                            title="Excluir">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>

                      </div>
                    <?php endforeach; ?>

                  </div>
                <?php else: ?>
                  <div class="text-center py-5 text-dark">
                    <i class="bi bi-input-cursor-text d-block fs-2 text-dark mb-2"></i>
                    <span class="fw-bold">Nenhum campo extra para trabalhos.</span>
                    <p class="small text-dark mb-0">Ex: "Data Nasc. Líder", "Horário", etc.</p>
                  </div>
                <?php endif; ?>

              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Critérios de desempate -->
      <div id="content-desempate" class="config-tab-content d-none">

        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
              <h2
                class="h5 fw-bold text-dark mb-1"
                style="letter-spacing: -0.01em; color: #000000 !important;">
                Fila de Prioridade de Desempate
              </h2>

              <p class="text-dark mb-0 small fw-medium">
                Ordem de critérios que o sistema testará em caso de empate na nota final.
              </p>
            </div>

            <button
              type="button"
              class="btn btn-novo-criterio d-inline-flex align-items-center gap-2 px-3 py-2 text-white fw-semibold shadow-sm"
              onclick="novoDesempate()">
              <i class="bi bi-plus-lg"></i>
              <span>Adicionar Regra de Desempate</span>
            </button>
          </div>

          <?php if (!empty($regras_desempate)): ?>

            <div class="d-flex flex-column gap-2">
              <?php foreach ($regras_desempate as $regra): ?>

                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 border">

                  <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold">
                      <?= (int)$regra['ordem_prioridade'] ?>º Desempate
                    </span>

                    <div>
                      <strong class="text-dark d-block">
                        <?= htmlspecialchars($regra['nome_regra']) ?>
                      </strong>

                      <span class="text-dark small fw-medium">
                        Critério:
                        <?= $regra['direcao'] === 'DESC'
                          ? 'Maior Valor Vence'
                          : 'Menor Valor Vence' ?>
                      </span>
                    </div>
                  </div>

                  <div class="d-inline-flex gap-1 align-items-center">

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-success border-0 p-1"
                      onclick='editarDesempate(<?= json_encode($regra, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                      title="Editar">
                      <i class="bi bi-pencil-square fs-6"></i>
                    </button>

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger border-0 p-1"
                      onclick="excluirDesempate(<?= (int)$regra['id'] ?>)"
                      title="Excluir">
                      <i class="bi bi-trash fs-6"></i>
                    </button>

                  </div>
                </div>

              <?php endforeach; ?>
            </div>

          <?php else: ?>

            <div class="text-center py-5 text-dark border rounded-4 bg-light bg-opacity-25">
              <i class="bi bi-sort-numeric-down d-block fs-1 text-dark mb-2"></i>

              <span class="fw-bold fs-6 text-dark d-block mb-1">
                Nenhuma regra de desempate configurada.
              </span>

              <p class="small text-dark mb-3">
                Defina quais notas ou campos terão prioridade no ranking final.
              </p>

              <button
                type="button"
                class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold"
                data-bs-toggle="modal"
                data-bs-target="#modalNovoDesempate">
                + Configurar 1ª Regra
              </button>
            </div>

          <?php endif; ?>

        </div>
      </div>

    </div>
  </main>

  <!-- Modal de critério de avaliação -->
  <div
    class="modal fade"
    id="modalNovoCriterio"
    tabindex="-1"
    aria-labelledby="modalNovoCriterioLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">

        <div class="modal-header px-4 py-3 border-bottom bg-light">
          <h5 class="modal-title fw-bold text-dark fs-6" id="modalNovoCriterioLabel">
            <i class="bi bi-clipboard2-plus text-success me-2"></i>
            Critério de Avaliação
          </h5>

          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Fechar"></button>
        </div>

        <form id="formCriterio" action="../php/CadCriterioAvaliativo.php" method="POST">
          <div class="modal-body p-4">

            <input type="hidden" name="id" id="criterio_id" value="">

            <div class="mb-3">
              <label for="criterio_nome" class="form-label fw-bold small text-dark">
                Nome do Critério <span class="text-danger">*</span>
              </label>

              <input
                type="text"
                class="form-control rounded-3 text-dark fw-medium"
                id="criterio_nome"
                name="nome_criterio"
                placeholder="Ex: Criatividade e Inovação"
                required>
            </div>

            <div class="mb-3">
              <label for="criterio_descricao" class="form-label fw-bold small text-dark">
                Descrição / Instruções para o Jurado
              </label>

              <textarea
                class="form-control rounded-3 text-dark fw-medium"
                id="criterio_descricao"
                name="desc_criterio_avaliativo"
                rows="2"
                placeholder="Descreva o que os avaliadores devem pontuar neste critério..."></textarea>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label for="criterio_peso" class="form-label fw-bold small text-dark">
                  Peso no Cálculo
                </label>

                <input
                  type="number"
                  step="0.25"
                  min="0.25"
                  max="10.0"
                  class="form-control rounded-3 text-dark fw-medium"
                  id="criterio_peso"
                  name="peso"
                  value="1.00"
                  required>
              </div>

              <div class="col-6">
                <label for="criterio_ordem" class="form-label fw-bold small text-dark">
                  Ordem de Exibição
                </label>

                <input
                  type="number"
                  min="1"
                  class="form-control rounded-3 text-dark fw-medium"
                  id="criterio_ordem"
                  name="ordem"
                  value="1"
                  required>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label for="criterio_nota_min" class="form-label fw-bold small text-dark">
                  Nota Mínima
                </label>

                <input
                  type="number"
                  step="0.5"
                  class="form-control rounded-3 text-dark fw-medium"
                  id="criterio_nota_min"
                  name="nota_minima"
                  value="0.00"
                  required>
              </div>

              <div class="col-6">
                <label for="criterio_nota_max" class="form-label fw-bold small text-dark">
                  Nota Máxima
                </label>

                <input
                  type="number"
                  step="0.5"
                  class="form-control rounded-3 text-dark fw-medium"
                  id="criterio_nota_max"
                  name="nota_maxima"
                  value="10.00"
                  required>
              </div>
            </div>

            <div class="form-check form-switch mt-3">
              <input
                class="form-check-input"
                type="checkbox"
                role="switch"
                id="criterio_ativo"
                name="ativo"
                value="1"
                checked>

              <label class="form-check-label fw-bold text-dark small" for="criterio_ativo">
                Critério Ativo no Evento Atual
              </label>
            </div>
          </div>

          <div class="modal-footer px-4 py-3 bg-light border-top">
            <button
              type="button"
              class="btn btn-outline-secondary rounded-3 px-3 fw-bold btn-sm text-dark"
              data-bs-dismiss="modal">
              Cancelar
            </button>

            <button
              type="submit"
              class="btn btn-novo-criterio rounded-3 px-4 fw-bold btn-sm">
              Salvar Critério
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- Modal de campo dinâmico -->
  <div
    class="modal fade"
    id="modalNovoCampo"
    tabindex="-1"
    aria-labelledby="modalNovoCampoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">

        <div class="modal-header px-4 py-3 border-bottom bg-light">
          <h5 class="modal-title fw-bold text-dark fs-6" id="modalNovoCampoLabel">
            <i class="bi bi-ui-checks text-success me-2"></i>
            Novo Campo Dinâmico
          </h5>

          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Fechar"></button>
        </div>

        <form id="formCampo" action="../php/SalvarCampoDinamico.php" method="POST">
          <div class="modal-body p-4">

            <input type="hidden" name="id" id="campo_id" value="">

            <div class="mb-3">
              <label class="form-label fw-bold small text-dark">
                Em qual formulário esse campo vai aparecer?
                <span class="text-danger">*</span>
              </label>

              <div class="d-flex gap-3">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="formulario"
                    id="form_escola"
                    value="escola"
                    checked>

                  <label class="form-check-label fw-bold text-dark small" for="form_escola">
                    <i class="bi bi-building me-1 text-success"></i>
                    Cadastro de Escolas
                  </label>
                </div>

                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="formulario"
                    id="form_trabalho"
                    value="trabalho">

                  <label class="form-check-label fw-bold text-dark small" for="form_trabalho">
                    <i class="bi bi-journal-text me-1 text-success"></i>
                    Cadastro de Trabalhos
                  </label>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="campo_nome" class="form-label fw-bold small text-dark">
                Nome do Campo / Pergunta <span class="text-danger">*</span>
              </label>

              <input
                type="text"
                class="form-control rounded-3 text-dark fw-medium"
                id="campo_nome"
                name="nome_campo"
                placeholder="Ex: Escola de Zona Rural? ou Menor IDEB"
                required>
            </div>

            <div class="mb-3">
              <label for="campo_tipo" class="form-label fw-bold small text-dark">
                Tipo de Resposta <span class="text-danger">*</span>
              </label>

              <select
                class="form-select rounded-3 text-dark fw-medium"
                id="campo_tipo"
                name="tipo_campo"
                required>
                <option value="numero" selected>Número (Decimal ou Inteiro)</option>
                <option value="data">Data (DD/MM/AAAA)</option>
              </select>
            </div>

            <div class="form-check form-switch mt-3">
              <input
                class="form-check-input"
                type="checkbox"
                role="switch"
                id="campo_obrigatorio"
                name="obrigatorio"
                value="1">

              <label class="form-check-label fw-bold text-dark small" for="campo_obrigatorio">
                Preenchimento Obrigatório no Cadastro
              </label>
            </div>
          </div>

          <div class="modal-footer px-4 py-3 bg-light border-top">
            <button
              type="button"
              class="btn btn-outline-secondary rounded-3 px-3 fw-bold btn-sm text-dark"
              data-bs-dismiss="modal">
              Cancelar
            </button>

            <button
              type="submit"
              class="btn btn-novo-criterio rounded-3 px-4 fw-bold btn-sm">
              Criar Campo
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- Modal de critério de desempate -->
  <div
    class="modal fade"
    id="modalNovoDesempate"
    tabindex="-1"
    aria-labelledby="modalNovoDesempateLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">

        <div class="modal-header px-4 py-3 border-bottom bg-light">
          <h5 class="modal-title fw-bold text-dark fs-6" id="modalNovoDesempateLabel">
            <i class="bi bi-sort-down-alt text-success me-2"></i>
            Nova Regra de Desempate
          </h5>

          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Fechar"></button>
        </div>

        <form
          id="formDesempate"
          action="../php/SalvarCriterioDesempate.php"
          method="POST">
          <div class="modal-body p-4">

            <input type="hidden" name="id" id="desempate_id" value="">

            <div class="mb-3">
              <label for="desempate_nome" class="form-label fw-bold small text-dark">
                Título da Regra <span class="text-danger">*</span>
              </label>

              <input
                type="text"
                class="form-control rounded-3 text-dark fw-medium"
                id="desempate_nome"
                name="nome_regra"
                placeholder="Ex: Maior nota em Metodologia ou Prioridade Zona Rural"
                required>
            </div>

            <div class="mb-3">
              <label for="desempate_origem" class="form-label fw-bold small text-dark">
                Qual dado será usado para desempatar?
                <span class="text-danger">*</span>
              </label>

              <select
                class="form-select rounded-3 text-dark fw-medium"
                id="desempate_origem"
                name="origem_dado"
                required>
                <option value="">Selecione a origem...</option>

                <optgroup label="Critérios de Avaliação (Notas dos Jurados)">
                  <?php if (!empty($criterios_avaliacao)): ?>

                    <?php foreach ($criterios_avaliacao as $c): ?>
                      <option value="crit_<?= (int)$c['id'] ?>">
                        Nota em: <?= htmlspecialchars($c['nome']) ?>
                      </option>
                    <?php endforeach; ?>

                  <?php else: ?>

                    <option value="" disabled>
                      Nenhum critério de jurado cadastrado
                    </option>

                  <?php endif; ?>
                </optgroup>

                <optgroup label="Campos Dinâmicos de Escolas e Trabalhos">

                  <?php
                  $camposValidosDesempate = !empty($campos_formulario)
                    ? array_filter(
                      $campos_formulario,
                      fn($c) => in_array(
                        $c['tipo_campo'],
                        ['numero', 'data'],
                        true
                      )
                    )
                    : [];
                  ?>

                  <?php if (!empty($camposValidosDesempate)): ?>

                    <?php foreach ($camposValidosDesempate as $campo): ?>
                      <option value="campo_<?= (int)$campo['id'] ?>">
                        <?= htmlspecialchars($campo['nome_campo']) ?>
                        (<?= ucfirst($campo['formulario']) ?>)
                      </option>
                    <?php endforeach; ?>

                  <?php else: ?>

                    <option value="" disabled>
                      Nenhum campo dinâmico de número/data cadastrado
                    </option>

                  <?php endif; ?>

                </optgroup>
              </select>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label for="desempate_ordem" class="form-label fw-bold small text-dark">
                  Posição na Fila
                </label>

                <input
                  type="number"
                  min="1"
                  class="form-control rounded-3 text-dark fw-medium"
                  id="desempate_ordem"
                  name="ordem_prioridade"
                  value="1"
                  required>

                <span class="text-dark fw-medium" style="font-size: 0.75rem;">
                  1º desempate, 2º desempate...
                </span>
              </div>

              <div class="col-6">
                <label for="desempate_direcao" class="form-label fw-bold small text-dark">
                  Critério de Vitória
                </label>

                <select
                  class="form-select rounded-3 text-dark fw-medium"
                  id="desempate_direcao"
                  name="direcao"
                  required>
                  <option value="DESC" selected>Maior Valor Vence</option>
                  <option value="ASC">Menor Valor Vence (ex: IDEB)</option>
                </select>
              </div>
            </div>

          </div>

          <div class="modal-footer px-4 py-3 bg-light border-top">
            <button
              type="button"
              class="btn btn-outline-secondary rounded-3 px-3 fw-bold btn-sm text-dark"
              data-bs-dismiss="modal">
              Cancelar
            </button>

            <button
              type="submit"
              class="btn btn-novo-criterio rounded-3 px-4 fw-bold btn-sm">
              Adicionar à Fila
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script>
    function switchConfigTab(tabName) {
      document.querySelectorAll('.config-tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });

      const activeBtn = document.getElementById('tab-' + tabName);

      if (activeBtn) {
        activeBtn.classList.add('active');
      }

      document.querySelectorAll('.config-tab-content').forEach(panel => {
        panel.classList.add('d-none');
      });

      const activeContent = document.getElementById('content-' + tabName);

      if (activeContent) {
        activeContent.classList.remove('d-none');
      }

      const url = new URL(window.location);
      url.searchParams.set('tab', tabName);

      window.history.replaceState({}, '', url);
    }

    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const initialTab = urlParams.get('tab') || 'criterios';

      if (['criterios', 'campos', 'desempate'].includes(initialTab)) {
        switchConfigTab(initialTab);
      }
    });

    // Critérios de avaliação
    function novoCriterio() {
      const form = document.getElementById('formCriterio');

      if (form) {
        form.action = '../php/SalvarCriterioAvaliativo.php';
      }

      document.getElementById('criterio_id').value = '';
      document.getElementById('criterio_nome').value = '';
      document.getElementById('criterio_descricao').value = '';
      document.getElementById('criterio_peso').value = '1.00';
      document.getElementById('criterio_ordem').value = '1';
      document.getElementById('criterio_nota_min').value = '0.00';
      document.getElementById('criterio_nota_max').value = '10.00';
      document.getElementById('criterio_ativo').checked = true;

      document.getElementById('modalNovoCriterioLabel').innerHTML =
        '<i class="bi bi-clipboard2-plus text-success me-2"></i>Novo Critério de Avaliação';

      const modal = new bootstrap.Modal(
        document.getElementById('modalNovoCriterio')
      );

      modal.show();
    }

    function editarCriterio(crit) {
      const form = document.getElementById('formCriterio');

      if (form) {
        form.action = '../php/AtualizaCriterioAvaliativo.php';
      }

      document.getElementById('criterio_id').value = crit.id || '';
      document.getElementById('criterio_nome').value = crit.nome || '';
      document.getElementById('criterio_descricao').value = crit.descricao || '';
      document.getElementById('criterio_peso').value =
        parseFloat(crit.peso || 1.0).toFixed(2);
      document.getElementById('criterio_ordem').value = crit.ordem || 1;
      document.getElementById('criterio_nota_min').value =
        parseFloat(crit.nota_minima || 0.0).toFixed(2);
      document.getElementById('criterio_nota_max').value =
        parseFloat(crit.nota_maxima || 10.0).toFixed(2);
      document.getElementById('criterio_ativo').checked =
        crit.ativo == 1 || crit.ativo === true;

      document.getElementById('modalNovoCriterioLabel').innerHTML =
        '<i class="bi bi-pencil-square text-success me-2"></i>Editar Critério de Avaliação';

      const modal = new bootstrap.Modal(
        document.getElementById('modalNovoCriterio')
      );

      modal.show();
    }

    function excluirCriterio(id, ativo) {
      if (Number(ativo) === 1) {
        alert(
          'Não é permitido apagar um critério ativo. ' +
          'Desative o critério antes de tentar excluí-lo.'
        );
        return;
      }

      if (confirm('Tem certeza de que deseja excluir este critério inativo?')) {
        window.location.href =
          '../php/ExcluirCriterioAvaliacao.php?id=' +
          encodeURIComponent(id);
      }
    }

    // Campos dinâmicos
    function novoCampo() {
      const form = document.getElementById('formCampo');

      if (form) {
        form.action = '../php/SalvarCampoDinamico.php';
      }

      document.getElementById('campo_id').value = '';
      document.getElementById('form_escola').checked = true;
      document.getElementById('campo_nome').value = '';
      document.getElementById('campo_tipo').value = 'numero';
      document.getElementById('campo_obrigatorio').checked = false;

      document.getElementById('modalNovoCampoLabel').innerHTML =
        '<i class="bi bi-ui-checks text-success me-2"></i>Novo Campo Dinâmico';

      const modal = new bootstrap.Modal(
        document.getElementById('modalNovoCampo')
      );

      modal.show();
    }

    function editarCampo(campo) {
      const form = document.getElementById('formCampo');

      if (form) {
        form.action = '../php/AtualizaCampoDinamico.php';
      }

      document.getElementById('campo_id').value = campo.id || '';

      if (campo.formulario === 'trabalho') {
        document.getElementById('form_trabalho').checked = true;
      } else {
        document.getElementById('form_escola').checked = true;
      }

      document.getElementById('campo_nome').value = campo.nome_campo || '';
      document.getElementById('campo_tipo').value = campo.tipo_campo || 'numero';
      document.getElementById('campo_obrigatorio').checked =
        campo.obrigatorio == 1 || campo.obrigatorio === true;

      document.getElementById('modalNovoCampoLabel').innerHTML =
        '<i class="bi bi-pencil-square text-success me-2"></i>Editar Campo Dinâmico';

      const modal = new bootstrap.Modal(
        document.getElementById('modalNovoCampo')
      );

      modal.show();
    }

    // Exclui o campo por POST, com token CSRF para validar a solicitação.
    function excluirCampo(id) {
      if (!confirm('Tem certeza de que deseja excluir este campo customizado? Os valores associados deixarão de ficar disponíveis.')) {
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '../php/ExcluirCampoDinamico.php';
      form.style.display = 'none';

      const campos = {
        id: String(id),
        csrf_token: <?= json_encode(
          $_SESSION['csrf_token'],
          JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>
      };

      Object.entries(campos).forEach(([nome, valor]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = nome;
        input.value = valor;
        form.appendChild(input);
      });

      document.body.appendChild(form);
      form.submit();
    }

    // Critérios de desempate
    function novoDesempate() {
      const form = document.getElementById('formDesempate');

      if (form) {
        form.action = '../php/SalvarCriterioDesempate.php';
      }

      document.getElementById('desempate_id').value = '';
      document.getElementById('desempate_nome').value = '';
      document.getElementById('desempate_origem').value = '';
      document.getElementById('desempate_ordem').value = '1';
      document.getElementById('desempate_direcao').value = 'DESC';

      document.getElementById('modalNovoDesempateLabel').innerHTML =
        '<i class="bi bi-sort-down-alt text-success me-2"></i>Nova Regra de Desempate';

      const modal = new bootstrap.Modal(
        document.getElementById('modalNovoDesempate')
      );

      modal.show();
    }

    function editarDesempate(regra) {
      const form = document.getElementById('formDesempate');

      if (form) {
        form.action = '../php/AtualizaCriterioDesempate.php';
      }

      document.getElementById('desempate_id').value = regra.id || '';
      document.getElementById('desempate_nome').value = regra.nome_regra || '';

      if (regra.id_criterio_avaliacao) {
        document.getElementById('desempate_origem').value =
          'crit_' + regra.id_criterio_avaliacao;
      } else if (regra.id_campo_formulario) {
        document.getElementById('desempate_origem').value =
          'campo_' + regra.id_campo_formulario;
      } else {
        document.getElementById('desempate_origem').value = '';
      }

      document.getElementById('desempate_ordem').value =
        regra.ordem_prioridade || 1;
      document.getElementById('desempate_direcao').value =
        regra.direcao || 'DESC';

      document.getElementById('modalNovoDesempateLabel').innerHTML =
        '<i class="bi bi-pencil-square text-success me-2"></i>Editar Regra de Desempate';

      const modal = new bootstrap.Modal(
        document.getElementById('modalNovoDesempate')
      );

      modal.show();
    }

    // Exclui por POST, enviando o ID e o token CSRF.
    function excluirDesempate(id) {
      if (!confirm('Tem certeza de que deseja remover esta regra da fila de desempate?')) {
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '../php/ExcluirCriterioDesempate.php';
      form.style.display = 'none';

      const campos = {
        id: String(id),
        csrf_token: <?= json_encode(
                      $_SESSION['csrf_token'],
                      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                    ) ?>
      };

      Object.entries(campos).forEach(([nome, valor]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = nome;
        input.value = valor;
        form.appendChild(input);
      });

      document.body.appendChild(form);
      form.submit();
    }

    // Sugere um nome para a regra conforme a origem selecionada.
    document.addEventListener('DOMContentLoaded', function() {
      const origemSelect = document.getElementById('desempate_origem');
      const nomeInput = document.getElementById('desempate_nome');

      if (origemSelect && nomeInput) {
        origemSelect.addEventListener('change', function() {
          const selectedOption = this.options[this.selectedIndex];

          if (selectedOption && selectedOption.value) {
            nomeInput.value =
              'Desempate por ' + selectedOption.textContent.trim();
          }
        });
      }
    });
  </script>

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