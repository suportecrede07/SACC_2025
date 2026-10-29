<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../php/Connect.php';
require_once '../php/CalculoRanking.php';
if (!isset($_SESSION['id_admin']) || !isset($_SESSION['usuario'])) {
  header('Location: ../html/login_adm.php');
  exit();
}

// Dados consulta dos filtros superiores (Painel Geral)
$filtro_municipio        = $_GET['municipio'] ?? '';
$filtro_escola           = $_GET['escola'] ?? '';
$filtro_jurado           = $_GET['jurado'] ?? '';
$filtro_area             = $_GET['area'] ?? '';
$filtro_categoria        = $_GET['categoria'] ?? '';
$filtro_categoria_escola = $_GET['categoria_escola'] ?? '';

// Filtros EXCLUSIVOS do Ranking dos Trabalhos
$filtro_cat_ranking      = $_GET['categoria_ranking'] ?? '';
$filtro_area_ranking     = $_GET['area_ranking'] ?? '';

// Listas para preencher os selects do formulário
$lista_municipios        = $pdo->query("SELECT DISTINCT municipio FROM escolas WHERE municipio IS NOT NULL AND municipio != '' ORDER BY municipio ASC")->fetchAll(PDO::FETCH_COLUMN);
$lista_escolas           = $pdo->query("SELECT id_escolas, nome FROM escolas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$lista_areas             = $pdo->query("SELECT id_area, nome_area FROM areas ORDER BY nome_area ASC")->fetchAll(PDO::FETCH_ASSOC);
$lista_categorias        = $pdo->query("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria ASC")->fetchAll(PDO::FETCH_ASSOC);
$lista_jurados           = $pdo->query("SELECT id_jurados, nome FROM jurados ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$lista_categoria_escola  = $pdo->query("SELECT id, categoria_da_escola FROM Categoria_escolas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Construção dinâmica das condições dos filtros superiores
$where = [];
$params = [];

if (!empty($filtro_municipio)) {
    $where[] = "e.municipio = ?";
    $params[] = $filtro_municipio;
}
if (!empty($filtro_escola)) {
    $where[] = "e.id_escolas = ?";
    $params[] = $filtro_escola;
}
if (!empty($filtro_categoria_escola)) {
    $where[] = "e.id_categoria_escola = ?";
    $params[] = $filtro_categoria_escola;
}
if (!empty($filtro_area)) {
    $where[] = "EXISTS (SELECT 1 FROM Trabalhos t WHERE t.id_escolas = e.id_escolas AND t.id_areas = ?)";
    $params[] = $filtro_area;
}
if (!empty($filtro_categoria)) {
    $where[] = "EXISTS (SELECT 1 FROM Trabalhos t WHERE t.id_escolas = e.id_escolas AND t.id_categoria = ?)";
    $params[] = $filtro_categoria;
}
if (!empty($filtro_jurado)) {
    $where[] = "EXISTS (SELECT 1 FROM Trabalhos t INNER JOIN jurado_trabalho jt ON jt.id_trabalho = t.id_trabalhos WHERE t.id_escolas = e.id_escolas AND jt.id_jurado = ?)";
    $params[] = $filtro_jurado;
}

$clausulaWhere = $where ? "WHERE " . implode(" AND ", $where) : "";

// 1. Gráfico de Barras: Distribuição de Escolas por Categoria
if (!empty($clausulaWhere)) {
    $sqlCat = "SELECT
                  ce.categoria_da_escola AS categoria_escola,
                  COUNT(DISTINCT e.id_escolas) AS quantidade
               FROM Categoria_escolas ce
               INNER JOIN Escolas e ON e.id_categoria_escola = ce.id
               $clausulaWhere
               GROUP BY ce.id, ce.categoria_da_escola
               ORDER BY ce.id ASC";
} else {
    $sqlCat = "SELECT
                  ce.categoria_da_escola AS categoria_escola,
                  COUNT(DISTINCT e.id_escolas) AS quantidade
               FROM Categoria_escolas ce
               LEFT JOIN Escolas e ON e.id_categoria_escola = ce.id
               GROUP BY ce.id, ce.categoria_da_escola
               ORDER BY ce.id ASC";
}

$stmtCategorias = $pdo->prepare($sqlCat);
$stmtCategorias->execute($params);
$dados_categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// 2. Escolas
$sqlEscolas = "SELECT DISTINCT 
                  e.id_escolas,
                  e.nome, 
                  e.total_trabalhos,
                  ce.categoria_da_escola AS tipo_escola
               FROM Escolas e 
               LEFT JOIN Categoria_escolas ce ON e.id_categoria_escola = ce.id
               $clausulaWhere 
               ORDER BY e.nome ASC";

$stmtEscolas = $pdo->prepare($sqlEscolas);
$stmtEscolas->execute($params);
$dados_Escola = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

$total_escolas_count = count($dados_Escola);

// Filtros para Trabalhos e Avaliações
$whereTrabalho = [];
$paramsTrabalho = [];

if (!empty($filtro_municipio)) {
    $whereTrabalho[] = "e.municipio = ?";
    $paramsTrabalho[] = $filtro_municipio;
}
if (!empty($filtro_escola)) {
    $whereTrabalho[] = "t.id_escolas = ?";
    $paramsTrabalho[] = $filtro_escola;
}
if (!empty($filtro_categoria_escola)) {
    $whereTrabalho[] = "e.id_categoria_escola = ?";
    $paramsTrabalho[] = $filtro_categoria_escola;
}
if (!empty($filtro_area)) {
    $whereTrabalho[] = "t.id_areas = ?";
    $paramsTrabalho[] = $filtro_area;
}
if (!empty($filtro_categoria)) {
    $whereTrabalho[] = "t.id_categoria = ?";
    $paramsTrabalho[] = $filtro_categoria;
}
if (!empty($filtro_jurado)) {
    $whereTrabalho[] = "jt.id_jurado = ?";
    $paramsTrabalho[] = $filtro_jurado;
}

$clausulaWhereTrab = $whereTrabalho ? "WHERE " . implode(" AND ", $whereTrabalho) : "";

// Contagens de Trabalhos e Jurados filtrados
$sqlContagens = "
    SELECT 
        COUNT(DISTINCT t.id_trabalhos) as total_trabalhos_filtrados,
        COUNT(DISTINCT jt.id_jurado) as total_jurados_filtrados,
        COUNT(DISTINCT av.id_trabalho) as total_avaliados_filtrados
    FROM Trabalhos t
    LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
    LEFT JOIN jurado_trabalho jt ON jt.id_trabalho = t.id_trabalhos
    LEFT JOIN avaliacoes av ON av.id_trabalho = t.id_trabalhos
    $clausulaWhereTrab
";
$stmtContagens = $pdo->prepare($sqlContagens);
$stmtContagens->execute($paramsTrabalho);
$contagens = $stmtContagens->fetch(PDO::FETCH_ASSOC);

$total_trabalhos_banco   = (int)($contagens['total_trabalhos_filtrados'] ?? 0);
$total_jurados_banco     = (int)($contagens['total_jurados_filtrados'] ?? 0);
$total_avaliacoes_feitas = (int)($contagens['total_avaliados_filtrados'] ?? 0);

// Maior categoria
$maior_categoria_nome = 'N/A';
$maior_categoria_qtd = 0;
foreach ($dados_categorias as $cat) {
    if ((int)$cat['quantidade'] > $maior_categoria_qtd) {
        $maior_categoria_qtd = (int)$cat['quantidade'];
        $maior_categoria_nome = $cat['categoria_escola'];
    }
}
$maior_categoria_perc = $total_escolas_count > 0 ? round(($maior_categoria_qtd / $total_escolas_count) * 100, 1) : 0;

// Critérios Avaliativos Dinâmicos da tabela Criterios_avaliacao
$stmtCritOficiais = $pdo->query("SELECT id, nome, peso, ordem FROM Criterios_avaliacao WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
$criterios_banco = $stmtCritOficiais->fetchAll(PDO::FETCH_ASSOC);

// Mapeia os critérios (id => nome e id => peso)
$todos_criterios_oficiais = [];
$pesos_criterios = [];
foreach ($criterios_banco as $cb) {
    $todos_criterios_oficiais[$cb['id']] = $cb['nome'];
    $pesos_criterios[$cb['id']] = (float)$cb['peso'];
}

// Médias dos critérios para o Gráfico Radar
$sqlCriterios = "
    SELECT av.criterio, ROUND(AVG(av.nota), 1) as media_criterio 
    FROM avaliacoes av
    INNER JOIN Trabalhos t ON av.id_trabalho = t.id_trabalhos
    LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
    LEFT JOIN jurado_trabalho jt ON jt.id_trabalho = t.id_trabalhos
    $clausulaWhereTrab
    GROUP BY av.criterio 
    ORDER BY av.criterio ASC
";
$stmtCriterios = $pdo->prepare($sqlCriterios);
$stmtCriterios->execute($paramsTrabalho);
$dados_radar_banco = $stmtCriterios->fetchAll(PDO::FETCH_KEY_PAIR);

$radar_labels = [];
$radar_valores = [];
$melhor_criterio_nome = 'Nenhum';
$melhor_criterio_nota = 0.0;

foreach ($todos_criterios_oficiais as $numCrit => $nomeCrit) {
    $notaCrit = isset($dados_radar_banco[$numCrit]) ? (float)$dados_radar_banco[$numCrit] : 0.0;
    $radar_labels[] = $nomeCrit;
    $radar_valores[] = $notaCrit;

    if ($notaCrit > $melhor_criterio_nota) {
        $melhor_criterio_nota = $notaCrit;
        $melhor_criterio_nome = $nomeCrit;
    }
}

$json_radar_labels = json_encode($radar_labels, JSON_UNESCAPED_UNICODE);
$json_radar_valores = json_encode($radar_valores);


$categorias = [];
$contagem_categorias = [];
foreach ($dados_categorias as $categoria) {
    $categorias[] = $categoria['categoria_escola'];
    $contagem_categorias[] = (int) $categoria['quantidade'];
}

$json_labels = json_encode($categorias, JSON_UNESCAPED_UNICODE);
$json_dados = json_encode($contagem_categorias);

// --- CÁLCULO OFICIAL DO RANKING DE TRABALHOS (PADRONIZADO COM O PDF) ---
$dados_ranking_trabalhos = [];
$erroRanking = null;

try {
    $idCatRank = !empty($filtro_cat_ranking) ? (int)$filtro_cat_ranking : null;
    $idAreaRank = !empty($filtro_area_ranking) ? (int)$filtro_area_ranking : null;
    $dados_ranking_trabalhos = obterClassificacaoTrabalhos($pdo, $idCatRank, $idAreaRank);
} catch (Throwable $e) {
    $erroRanking = $e->getMessage();
    $dados_ranking_trabalhos = [];
}

// Busca detalhes complementares de exibição para cada trabalho do ranking (Categoria, Área, Modalidade)
$detalhes_trabalhos_ranking = [];
if (!empty($dados_ranking_trabalhos)) {
    $idsTrabsRank = array_column($dados_ranking_trabalhos, 'id_trabalho');
    $placeholdersRank = implode(',', array_fill(0, count($idsTrabsRank), '?'));
    $stmtDetRank = $pdo->prepare("
        SELECT 
            t.id_trabalhos,
            c.nome_categoria,
            a.nome_area,
            ce.categoria_da_escola AS modalidade
        FROM Trabalhos t
        LEFT JOIN categorias c ON t.id_categoria = c.id_categoria
        LEFT JOIN areas a ON t.id_areas = a.id_area
        LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
        LEFT JOIN Categoria_escolas ce ON e.id_categoria_escola = ce.id
        WHERE t.id_trabalhos IN ($placeholdersRank)
    ");
    $stmtDetRank->execute($idsTrabsRank);
    while ($rowDet = $stmtDetRank->fetch(PDO::FETCH_ASSOC)) {
        $detalhes_trabalhos_ranking[$rowDet['id_trabalhos']] = $rowDet;
    }
}

// Preparação dos dados para o gráfico horizontal do Ranking (Top 10)
$ranking_trab_titulos = [];
$ranking_trab_notas = [];
foreach (array_slice($dados_ranking_trabalhos, 0, 10) as $rt) {
    $ranking_trab_titulos[] = $rt['titulo'] ?? 'Trabalho #' . $rt['id_trabalho'];
    $ranking_trab_notas[] = isset($rt['media_final']) && $rt['media_final'] !== null ? round((float)$rt['media_final'], 2) : 0;
}
$json_ranking_trab_titulos = json_encode($ranking_trab_titulos, JSON_UNESCAPED_UNICODE);
$json_ranking_trab_notas = json_encode($ranking_trab_notas);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel Analítico de Desempenho Geral - SAFC</title>
  <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <link href="../boostrap/CSS/bootstrap-icons.css" rel="stylesheet">
  <script src="../boostrap/JS/jquery.min.js"></script>
  <link rel="stylesheet" href="../assets/styles/dashboard-admin.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/styles/graficos.css?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Biblioteca Chart.js -->
  <script src="../assets/js/chart.umd.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-surface">
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
        <li><a href="admin-jurados.php"><i><img src="../assets/img/jurados.svg" class="jurado"></i> <span class="label-text">Jurados</span></a></li>
        <li><a href="admin-relatorios.php"><i><img src="../assets/img/relatorios.svg" class="relatorio"></i> <span class="label-text">Relatórios</span></a></li>
        <li class="active"><a href="admin-graficos.php"><i><img src="../assets/img/grafico.svg" class="grafico"></i> <span class="label-text">Gráficos</span></a></li>
        <li><a href="admin-config.php"><i><img src="../assets/img/config.svg" class="grafico"></i> <span class="label-text">Configurações</span></a></li>
      </ul>
    </div>
    <ul class="nav flex-column bottom-nav">
      <li><a href="../php/AdmLogout.php"><img src="../assets/img/sair.png" class="sair"> <span class="label-text">Sair</span></a></li>
    </ul>
  </div>

  <main id="main-content" class="analitico-layout">
    <!-- Topo da Página -->
    <div class="header-analitico d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h1 class="page-main-title">Painel Analítico de Desempenho das Escolas</h1>
      <span class="status-badge-live">
        <span class="pulse-dot"></span> Dados Atualizados
      </span>
    </div>

    <!-- Cards Superiores (KPIs) -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-panel-card">
          <div class="d-flex justify-content-between align-items-start">
            <span class="kpi-label">TOTAL DE ESCOLAS</span>
            <div class="kpi-icon-box green-soft">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                <path d="M9 22v-4h6v4"></path>
                <path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path>
              </svg>
            </div>
          </div>
          <div class="kpi-number"><?= $total_escolas_count ?></div>
          <div class="kpi-meta text-success-meta">
            <strong><?= $total_trabalhos_banco ?> trabalhos</strong> submetidos
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-panel-card">
          <div class="d-flex justify-content-between align-items-start">
            <span class="kpi-label">TRABALHOS AVALIADOS</span>
            <div class="kpi-icon-box green-soft">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
          </div>
          <div class="kpi-number"><?= $total_avaliacoes_feitas ?><span class="kpi-subnum">/<?= $total_trabalhos_banco ?></span></div>
          <div class="kpi-meta text-success-meta">
            <strong><?= $total_trabalhos_banco > 0 ? round(($total_avaliacoes_feitas / $total_trabalhos_banco) * 100, 1) : 0 ?>%</strong> concluídos
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-panel-card">
          <div class="d-flex justify-content-between align-items-start">
            <span class="kpi-label">MAIOR CATEGORIA</span>
            <div class="kpi-icon-box green-soft">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
              </svg>
            </div>
          </div>
          <div class="kpi-number text-truncate" title="<?= htmlspecialchars($maior_categoria_nome) ?>"><?= htmlspecialchars($maior_categoria_nome) ?></div>
          <div class="kpi-meta text-dark-meta">
            <strong><?= $maior_categoria_qtd ?> unidades</strong> (<?= $maior_categoria_perc ?>% do total)
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-panel-card">
          <div class="d-flex justify-content-between align-items-start">
            <span class="kpi-label">JURADOS CADASTRADOS</span>
            <div class="kpi-icon-box green-soft">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
          </div>
          <div class="kpi-number"><?= $total_jurados_banco ?></div>
          <div class="kpi-meta text-muted-meta">
            Avaliando trabalhos da rede
          </div>
        </div>
      </div>
    </div>

    <!-- Barra de Filtros Geral (Topo) -->
    <div class="filter-pills-container mb-4">
      <form method="GET" action="admin-graficos.php" id="formFiltros" class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Preserva os filtros do ranking se já existirem na URL -->
        <input type="hidden" name="categoria_ranking" value="<?= htmlspecialchars($filtro_cat_ranking) ?>">
        <input type="hidden" name="area_ranking" value="<?= htmlspecialchars($filtro_area_ranking) ?>">

        <div class="pill-select-wrapper">
          <select name="municipio" class="form-select pill-select" onchange="document.getElementById('formFiltros').submit()">
            <option value="">Todos os Municípios</option>
            <?php foreach ($lista_municipios as $mun): ?>
              <option value="<?= htmlspecialchars($mun) ?>" <?= $filtro_municipio === $mun ? 'selected' : '' ?>><?= htmlspecialchars($mun) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="pill-select-wrapper">
          <select name="escola" class="form-select pill-select" onchange="document.getElementById('formFiltros').submit()">
            <option value="">Todas as Escolas</option>
            <?php foreach ($lista_escolas as $esc): ?>
              <option value="<?= $esc['id_escolas'] ?>" <?= (string)$filtro_escola === (string)$esc['id_escolas'] ? 'selected' : '' ?>><?= htmlspecialchars($esc['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="pill-select-wrapper">
          <select name="categoria" class="form-select pill-select" onchange="document.getElementById('formFiltros').submit()">
            <option value="">Todas as Categorias</option>
            <?php foreach ($lista_categorias as $cat): ?>
              <option value="<?= $cat['id_categoria'] ?>" <?= (string)$filtro_categoria === (string)$cat['id_categoria'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome_categoria']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="pill-select-wrapper">
          <select name="area" class="form-select pill-select" onchange="document.getElementById('formFiltros').submit()">
            <option value="">Todas as Áreas</option>
            <?php foreach ($lista_areas as $ar): ?>
              <option value="<?= $ar['id_area'] ?>" <?= (string)$filtro_area === (string)$ar['id_area'] ? 'selected' : '' ?>><?= htmlspecialchars($ar['nome_area']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="pill-select-wrapper">
          <select name="jurado" class="form-select pill-select" onchange="document.getElementById('formFiltros').submit()">
            <option value="">Todos os Jurados</option>
            <?php foreach ($lista_jurados as $jur): ?>
              <option value="<?= $jur['id_jurados'] ?>" <?= (string)$filtro_jurado === (string)$jur['id_jurados'] ? 'selected' : '' ?>><?= htmlspecialchars($jur['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (!empty($filtro_municipio) || !empty($filtro_escola) || !empty($filtro_categoria) || !empty($filtro_area) || !empty($filtro_jurado)): ?>
          <a href="admin-graficos.php" class="btn btn-sm btn-outline-danger pill-btn-clear">
            <i class="bi bi-x-circle me-1"></i>Limpar
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Bloco 1: Distribuição de Escolas e Gráfico Radar de Critérios -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-7">
        <div class="main-card-analytic card-categorias h-100">
          <div class="card-header-clean d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="d-flex align-items-center gap-2">
                <h2 class="section-title">Distribuição de Escolas por Categoria</h2>
                <span class="badge-count-pill"><?= count($dados_categorias) ?> Categorias</span>
              </div>
              <p class="section-subtitle">Volume absoluto de unidades escolares ativas por tipologia de ensino</p>
            </div>
            <div class="legend-header-group d-flex align-items-center gap-3">
              <div class="legend-item"><span class="legend-box green"></span> Número de Escolas</div>
              <div class="legend-divider">|</div>
              <div class="legend-total">Total: <strong><?= $total_escolas_count ?> escolas</strong></div>
            </div>
          </div>

          <div class="chart-main-box" style="height: 380px;">
            <canvas id="graficoBarra"></canvas>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-5">
        <div class="radar-box-card h-100 d-flex flex-column justify-content-between p-4">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="radar-card-title mb-0">Equilíbrio por Critérios</h5>
              <span class="badge-consolidado"><?= count($todos_criterios_oficiais) ?> CRITÉRIOS</span>
            </div>
            <p class="radar-card-desc">Visualização sintetizada dos <?= count($todos_criterios_oficiais) ?> critérios oficiais de avaliação científica de todos os trabalhos da rede.</p>
            
            <div class="radar-canvas-wrap my-3" style="position: relative; height: 280px;">
              <canvas id="graficoRadar"></canvas>
            </div>
          </div>

          <div class="radar-metrics-bottom pt-3 border-top">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted" style="font-size: 0.85rem;">Critério com Maior Média:</span>
              <strong style="color: var(--verde-primario); font-size: 0.85rem;"><?= htmlspecialchars($melhor_criterio_nome) ?> (<?= number_format($melhor_criterio_nota, 1) ?>)</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted" style="font-size: 0.85rem;">Jurados Cadastrados:</span>
              <strong style="font-size: 0.85rem;"><?= $total_jurados_banco ?> jurados</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted" style="font-size: 0.85rem;">Trabalhos Avaliados:</span>
              <strong style="font-size: 0.85rem;"><?= $total_avaliacoes_feitas ?> de <?= $total_trabalhos_banco ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>



    <!-- SEÇÃO: RANKING DOS TRABALHOS -->
    <div class="header-analitico d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 mt-5">
      <h1 class="page-main-title">Ranking dos trabalhos</h1>
      <span class="status-badge-live">
        <span class="pulse-dot"></span> Classificação Oficial SAFC
      </span>
    </div>

    <!-- Barra de Filtros EXCLUSIVA do Ranking -->
    <div class="filter-pills-container mb-4">
      <form method="GET" action="admin-graficos.php" id="formFiltrosRanking" class="d-flex flex-wrap gap-2 align-items-center">
        
        <!-- Preserva os filtros superiores caso estejam selecionados -->
        <input type="hidden" name="municipio" value="<?= htmlspecialchars($filtro_municipio) ?>">
        <input type="hidden" name="escola" value="<?= htmlspecialchars($filtro_escola) ?>">
        <input type="hidden" name="categoria" value="<?= htmlspecialchars($filtro_categoria) ?>">
        <input type="hidden" name="area" value="<?= htmlspecialchars($filtro_area) ?>">
        <input type="hidden" name="jurado" value="<?= htmlspecialchars($filtro_jurado) ?>">

        <!-- Categoria Exclusiva do Ranking -->
        <div class="pill-select-wrapper">
          <select name="categoria_ranking" class="form-select pill-select" onchange="document.getElementById('formFiltrosRanking').submit()">
            <option value="">Todas as Categorias (Ranking)</option>
            <?php foreach ($lista_categorias as $cat): ?>
              <option value="<?= $cat['id_categoria'] ?>" <?= (string)$filtro_cat_ranking === (string)$cat['id_categoria'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome_categoria']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Área Exclusiva do Ranking -->
        <div class="pill-select-wrapper">
          <select name="area_ranking" class="form-select pill-select" onchange="document.getElementById('formFiltrosRanking').submit()">
            <option value="">Todas as Áreas (Ranking)</option>
            <?php foreach ($lista_areas as $ar): ?>
              <option value="<?= $ar['id_area'] ?>" <?= (string)$filtro_area_ranking === (string)$ar['id_area'] ? 'selected' : '' ?>><?= htmlspecialchars($ar['nome_area']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (!empty($filtro_cat_ranking) || !empty($filtro_area_ranking)): ?>
          <a href="admin-graficos.php?municipio=<?= urlencode($filtro_municipio) ?>&escola=<?= urlencode($filtro_escola) ?>&categoria=<?= urlencode($filtro_categoria) ?>&area=<?= urlencode($filtro_area) ?>&jurado=<?= urlencode($filtro_jurado) ?>" class="btn btn-sm btn-outline-danger pill-btn-clear">
            <i class="bi bi-x-circle me-1"></i>Limpar Filtros do Ranking
          </a>
        <?php endif; ?>

      </form>
    </div>

    <!-- Bloco Principal do Ranking -->
    <div class="main-card-analytic mb-4">
      <div class="card-header-clean d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h2 class="section-title">Desempenho e Classificação dos Trabalhos com maior nota</h2>
          <p class="section-subtitle">Matriz de pontuação por projeto com listagem e gráfico</p>
        </div>
        <div class="target-legend-pills d-flex align-items-center gap-3 flex-wrap">
          <span class="legend-tag"><span class="dot-meta green"></span> Nota &ge; 8.0 (Destaque)</span>
          <span class="legend-tag"><span class="dot-meta yellow"></span> Nota 6.0 - 7.9 (Bom)</span>
          <span class="legend-tag"><span class="dot-meta red"></span> Nota &lt; 6.0 (Abaixo do esperado)</span>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-12">
          <div class="table-subheaders d-flex justify-content-between px-3 mb-2 text-uppercase text-muted" style=" font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em;">
            <span>TÍTULO DO TRABALHO / ESCOLA</span>
            <div class="d-flex gap-5">
              <span>MÉDIA FINAL</span>
              <span style="width: 190px; text-align: right; margin-right: 145px;">DESEMPATE</span>
            </div>
          </div>

          <div class="school-bars-list d-flex flex-column">
            <?php 
              $top_trabalhos_lista = array_slice($dados_ranking_trabalhos, 0, 10);
              $idxTrab = 1;
              foreach ($top_trabalhos_lista as $trab): 
                $valNota = isset($trab['media_final']) && $trab['media_final'] !== null ? (float)$trab['media_final'] : null;
                $percBarraTrab = $valNota !== null ? min(100, max(5, ($valNota / 10) * 100)) : 0;
                
                if ($valNota !== null && $valNota >= 8.0) {
                  $barColor = 'var(--verde-saeb)';
                } elseif ($valNota !== null && $valNota >= 7.0) {
                  $barColor = '#f59e0b';
                } else {
                  $barColor = '#ef4444';
                }

                $idTrabAtual = (int)($trab['id_trabalho'] ?? 0);
                $det = $detalhes_trabalhos_ranking[$idTrabAtual] ?? [];
                $nomeEscola = $trab['escola'] ?? 'Não informada';
                $modEscola = !empty($det['modalidade']) ? $det['modalidade'] . ' ' : '';
                $catTexto = !empty($det['nome_categoria']) ? $det['nome_categoria'] : '-';
                $areaTexto = !empty($det['nome_area']) ? $det['nome_area'] : '-';
            ?>
              <div class="school-row-card w-100">
                <div class="school-info-col">
                  <span class="school-index"><?= sprintf('%02d', $trab['posicao'] ?? $idxTrab++) ?></span>
                  <div class="d-flex flex-column text-truncate">
                    <span class="school-name text-truncate" title="<?= htmlspecialchars($trab['titulo']) ?>">
                      <?= htmlspecialchars($trab['titulo']) ?>
                    </span>
                    <small class="text-muted" style="font-size: 0.75rem;">
                      <?= htmlspecialchars($modEscola . $nomeEscola) ?> &bull; 
                      <span class="text-secondary"><?= htmlspecialchars($catTexto) ?> / <?= htmlspecialchars($areaTexto) ?></span>
                    </small>
                  </div>
                </div>

                <div class="school-metric-col d-flex gap-5 ">
                  <span class="school-score-val" style="color: <?= $barColor ?>;">
                    <?= $valNota !== null ? number_format($valNota, 2, ',', '') : '-' ?>
                  </span>
                  <div style="width: 360px; text-align: center; flex-shrink: 0;">
                    <?php if (!empty($trab['criterio_desempate']['criterio'])): ?>
                      <span class="badge bg-warning text-dark" style="font-size: 0.7rem;" title="Desempate aplicado: <?= htmlspecialchars($trab['criterio_desempate']['criterio']) ?>">
                        <?= htmlspecialchars($trab['criterio_desempate']['criterio']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted" style="font-size: 0.8rem;">-</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (empty($dados_ranking_trabalhos)): ?>
              <div class="text-center py-4 text-muted">
                <?= !empty($erroRanking) ? htmlspecialchars($erroRanking) : 'Nenhum trabalho encontrado para os filtros selecionados.' ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 w-100" style="margin-top: 30px;">
          <div class="radar-box-card h-100 d-flex flex-column justify-content-between p-4">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="radar-card-title mb-0">Gráfico do Ranking</h5>
                <span class="badge-consolidado">TOP 10 TRABALHOS</span>
              </div>
              <p class="radar-card-desc">Visualização gráfica horizontal das maiores médias ponderadas dos projetos avaliados.</p>
              
              <div class="radar-canvas-wrap my-3" style="position: relative; height: 400px;">
                <canvas id="graficoRankingTrabalhos"></canvas>
              </div>
            </div>

            <div class="radar-metrics-bottom pt-3 border-top">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted" style="font-size: 0.85rem;">Total de Trabalhos:</span>
                <strong style="font-size: 0.85rem;"><?= count($dados_ranking_trabalhos) ?> projetos</strong>
              </div>
              <div class="d-flex justify-content-between">
                <strong style="color: var(--verde-primario); font-size: 0.85rem;"></strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rodapé -->
    <footer class="footer-copy text-center py-4 text-muted" style="font-size: 0.82rem;">
      Sistema SAFC &copy; <?= date('Y') ?> &bull; Iniciativa Crede 7
    </footer>
  </main>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      const isOpen = sidebar.classList.toggle('mobile-open');
      overlay.classList.toggle('show', isOpen);
    }

    function closeMobileSidebar() {
      document.getElementById('sidebar').classList.remove('mobile-open');
      document.getElementById('overlay').classList.remove('show');
    }

    // Dados do Gráfico de Barras
    const labelsCategorias = <?php echo $json_labels; ?>;
    const dadosQuantidades = <?php echo $json_dados; ?>;

    const paletaCores = ['#3b7e4a', '#2563eb', '#f59e0b', '#7c3aed', '#db2777', '#0891b2', '#059669', '#475569'];
    const bgColors = labelsCategorias.map((_, i) => paletaCores[i % paletaCores.length]);

    // Plugin para desenhar o número na ponta de cada barra
    const pluginValoresBarras = {
      id: 'pluginValoresBarras',
      afterDatasetsDraw(chart) {
        const { ctx } = chart;
        chart.data.datasets.forEach((dataset, i) => {
          const meta = chart.getDatasetMeta(i);
          meta.data.forEach((bar, index) => {
            const val = dataset.data[index];
            if (val > 0) {
              ctx.save();
              ctx.fillStyle = '#000000';
              ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
              ctx.textAlign = 'left';
              ctx.textBaseline = 'middle';
              ctx.fillText(val + (val === 1 ? ' escola' : ' escolas'), bar.x + 10, bar.y);
              ctx.restore();
            }
          });
        });
      }
    };

    const ctxBarra = document.getElementById('graficoBarra').getContext('2d');
    new Chart(ctxBarra, {
      type: 'bar',
      data: {
        labels: labelsCategorias,
        datasets: [{
          label: 'Número de Escolas',
          data: dadosQuantidades,
          backgroundColor: bgColors,
          borderRadius: 8,
          borderSkipped: false,
          barThickness: 20
        }]
      },
      plugins: [pluginValoresBarras],
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: { right: 75 }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8,
            titleFont: { size: 13, weight: 'bold' }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            grace: '10%',
            grid: { color: '#e2e8f0', drawBorder: false },
            ticks: { stepSize: 2, color: '#000000', font: { weight: '700', size: 11 } }
          },
          y: {
            grid: { display: false },
            ticks: { color: '#000000', font: { weight: '800', size: 12 } }
          }
        }
      }
    });

    // Gráfico de Radar
    const radarLabels = <?php echo $json_radar_labels; ?>;
    const radarValores = <?php echo $json_radar_valores; ?>;

    const ctxRadar = document.getElementById('graficoRadar').getContext('2d');
    new Chart(ctxRadar, {
      type: 'radar',
      data: {
        labels: radarLabels,
        datasets: [{
          label: 'Média de Avaliação',
          data: radarValores,
          fill: true,
          backgroundColor: 'rgba(59, 126, 74, 0.25)',
          borderColor: '#2c633a',
          pointBackgroundColor: '#2c633a',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverBackgroundColor: '#ffffff',
          pointHoverBorderColor: '#2c633a',
          borderWidth: 2.5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 10 },
        plugins: { legend: { display: false } },
        scales: {
          r: {
            angleLines: { color: '#cbd5e1', lineWidth: 1.2 },
            grid: { color: '#e2e8f0', lineWidth: 1.2 },
            pointLabels: { 
              font: { size: 11, weight: '800', family: "'Plus Jakarta Sans', sans-serif" }, 
              color: '#000000' 
            },
            ticks: { display: false, min: 0, max: 10 }
          }
        }
      }
    });
    // Gráfico de Barras Horizontal do Ranking de Trabalhos (Gradiente Sequencial de Intensidade)
    const rankingTrabTitulos = <?php echo $json_ranking_trab_titulos; ?>;
    const rankingTrabNotas = <?php echo $json_ranking_trab_notas; ?>;

    const paletaVerdeGradiente = [
      '#14532d', // 1º Lugar: Verde Floresta Profundo
      '#166534', // 2º Lugar: Verde Escuro
      '#15803d', // 3º Lugar: Verde Esmeralda
      '#16a34a', // 4º Lugar: Verde Institucional
      '#22c55e', // 5º Lugar: Verde Vibrante
      '#10b981', // 6º Lugar: Verde Jade
      '#059669', // 7º Lugar: Verde Médio
      '#34d399', // 8º Lugar: Verde Menta Intenso
      '#4ade80', // 9º Lugar: Verde Claro Vivo
      '#6ee7b7'  // 10º Lugar: Verde Suave Firme
    ];

    const coresRankingPodio = rankingTrabNotas.map((_, idx) => paletaVerdeGradiente[idx % paletaVerdeGradiente.length]);
    const hoverCoresPodio = rankingTrabNotas.map(() => '#0f3d1f');

    const menorNotaReal = Math.min(...rankingTrabNotas.filter(n => n > 0), 98);
    const pisoEixoX = Math.min(95, Math.floor(menorNotaReal) - 1);

    const ctxRankingTrab = document.getElementById('graficoRankingTrabalhos').getContext('2d');
    new Chart(ctxRankingTrab, {
      type: 'bar',
      data: {
        labels: rankingTrabTitulos,
        datasets: [{
          label: 'Média Final',
          data: rankingTrabNotas,
          backgroundColor: coresRankingPodio,
          hoverBackgroundColor: hoverCoresPodio,
          borderRadius: 6,
          borderSkipped: false,
          barThickness: 22
        }]
      },
      plugins: [{
        id: 'exibirNumeroExatoBarras',
        afterDatasetsDraw(chart) {
          const { ctx } = chart;
          chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            meta.data.forEach((bar, index) => {
              const val = dataset.data[index];
              if (val > 0) {
                ctx.save();
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                ctx.shadowColor = 'rgba(0, 0, 0, 0.55)';
                ctx.shadowBlur = 4;
                const textoFormatado = Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                ctx.fillText(textoFormatado, bar.x - 12, bar.y);
                ctx.restore();
              }
            });
          });
        }
      }],
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: { right: 20 }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8,
            titleFont: { size: 13, weight: 'bold' },
            callbacks: {
              label: function(context) {
                const pos = context.dataIndex + 1;
                const prefix = pos <= 3 ? `🏆 ${pos}º Lugar - ` : `${pos}º Lugar - `;
                return ` ${prefix}Média Final: ${Number(context.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
              }
            }
          }
        },
        scales: {
          x: {
            min: pisoEixoX,
            max: 100.5,
            grid: { color: '#e2e8f0', drawBorder: false },
            ticks: { 
              stepSize: 1,
              color: '#000000', 
              font: { weight: '700', size: 11 } 
            }
          },
          y: {
            grid: { display: false },
            ticks: {
              color: '#000000',
              font: { weight: '700', size: 11 },
              callback: function(val, index) {
                const label = this.getLabelForValue(val);
                return typeof label === 'string' && label.length > 38 
                  ? label.substring(0, 36) + '...' 
                  : label;
              }
            }
          }
        }
      }
    });
  </script>
</body>
</html>