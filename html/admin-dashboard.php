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
$categoriaFiltro = isset($_POST['categoria']) && $_POST['categoria'] !== ''
  && filter_var($_POST['categoria'], FILTER_VALIDATE_INT) !== false
  ? (int) $_POST['categoria'] : null;
$areaFiltro = isset($_POST['area']) && $_POST['area'] !== ''
  && filter_var($_POST['area'], FILTER_VALIDATE_INT) !== false
  ? (int) $_POST['area'] : null;
$categorias = $pdo->query("SELECT id_categoria, nome_categoria FROM Categorias")->fetchAll(PDO::FETCH_ASSOC);

$areas = [];
if (!empty($_POST['categoria'])) {
  $stmt = $pdo->prepare("
      SELECT DISTINCT a.id_area, a.nome_area
      FROM Trabalhos t
      INNER JOIN Areas a ON t.id_areas = a.id_area
      WHERE t.id_categoria = ?
      ORDER BY a.nome_area
  ");
  $stmt->execute([$_POST['categoria']]);
  $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$jurados = [];
if (!empty($_POST['categoria']) && !empty($_POST['area'])) {
  $stmt = $pdo->prepare("
        SELECT j.id_jurados, j.nome
        FROM Jurados j
        INNER JOIN Jurados_Categorias_Areas jca 
            ON j.id_jurados = jca.id_jurados
        WHERE jca.id_categoria = ? 
          AND jca.id_area = ?
    ");
  $stmt->execute([$_POST['categoria'], $_POST['area']]);
  $jurados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$trabalhos = [];
if (!empty($_POST['jurado']) && !empty($_POST['categoria']) && !empty($_POST['area'])) {
  $stmt = $pdo->prepare("SELECT t.id_trabalhos, t.titulo, a.nome_area 
                         FROM trabalhos t
                         JOIN areas a ON t.id_area = a.id_area
                         WHERE t.id_categoria = ? AND t.id_area = ?");
  $stmt->execute([$_POST['categoria'], $_POST['area']]);
  $trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($_POST['trabalhos']) && !empty($_POST['jurado'])) {
  foreach ($_POST['trabalhos'] as $id_trabalho) {
    $stmt = $pdo->prepare("INSERT INTO jurado_trabalho (id_jurado, id_trabalho) VALUES (?, ?)");
    $stmt->execute([$_POST['jurado'], $id_trabalho]);
  }
  $mensagem_sucesso = "Associação realizada com sucesso!";
}

// A classificação é calculada centralmente por CalculoRanking.php.


$stmt = $pdo->query("SELECT id_area,nome_area FROM Areas ORDER BY nome_area ASC");
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id_escolas,nome, id_categoria_escola, ce.categoria_da_escola AS categoria_escola FROM Escolas LEFT JOIN Categoria_escolas ce ON escolas.id_categoria_escola = ce.id ORDER BY nome ASC");
$escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id_categoria,nome_categoria FROM Categorias ORDER BY nome_categoria ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->query("SELECT id_jurados,nome FROM Jurados ORDER BY nome ASC");
$jurados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// area destinada as consultas de estado dos cards em tempo real do dashboard do admin
$stmt = $pdo->query("SELECT COUNT(*) AS total_escolas FROM Escolas");
$total_escolas = $stmt->fetch(PDO::FETCH_ASSOC)['total_escolas'];

$stmt = $pdo->query("SELECT COUNT(*) AS total_trabalhos FROM Trabalhos");
$total_trabalhos = $stmt->fetch(PDO::FETCH_ASSOC)['total_trabalhos'];

$stmt = $pdo->query("SELECT COUNT(*) AS total_jurados FROM Jurados");
$total_jurados = $stmt->fetch(PDO::FETCH_ASSOC)['total_jurados'];

$trabalhosCadastrados = $pdo->query("SELECT id_escolas, id_areas, titulo FROM Trabalhos")->fetchAll(PDO::FETCH_ASSOC);

// Busca todas as modalidades da tabela categoria_escolas
$stmtCatEscolas = $pdo->query("SELECT id, categoria_da_escola FROM categoria_escolas ORDER BY id ASC");
$modalidades_lista = $stmtCatEscolas->fetchAll(PDO::FETCH_ASSOC);

// Busca os campos dinâmicos ativos para o formulário de escolas
$stmtCamposEscola = $pdo->query("SELECT * FROM Campos_formulario WHERE ativo = 1 AND formulario = 'escola' ORDER BY id ASC");
$campos_escola = $stmtCamposEscola->fetchAll(PDO::FETCH_ASSOC);

// Busca os campos dinâmicos ativos para o formulário de trabalhos
$stmtCamposTrabalho = $pdo->query("SELECT * FROM Campos_formulario WHERE ativo = 1 AND formulario = 'trabalho' ORDER BY id ASC");
$campos_trabalho = $stmtCamposTrabalho->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Administrativo</title>
  <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <link href="../boostrap/CSS/bootstrap-icons.css" rel="stylesheet">
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
        <li class="active"><a href="admin-dashboard.php"><i><img src="../assets/img/dashboard.svg" class="dashboard"></i> <span
              class="label-text">Dashboard</span></a></li>
        <li><a href="admin-escolas.php"><i><img src="../assets/img/escolas.svg" class="escola"></i> <span
              class="label-text">Escolas</span></a></li>
        <li><a href="admin-trabalhos.php"><i><img src="../assets/img/trabalhos.svg" class="trabalho"></i> <span
              class="label-text">Trabalhos</span></a></li>
        <li><a href="admin-jurados.php"><i><img src="../assets/img/jurados.svg" class="jurado"></i> <span
              class="label-text">Jurados</span></a></li>
        <li><a href="admin-relatorios.php"><i><img src="../assets/img/relatorios.svg" class="relatorio"></i> <span
              class="label-text">Relatórios</span></a></li>
        <li><a href="admin-graficos.php"><i><img src="../assets/img/grafico.svg" class="grafico"></i> <span
              class="label-text">Graficos</span></a></li>
        <li><a href="admin-config.php"><i><img src="../assets/img/config.svg" class="grafico"></i> <span
              class="label-text">Configurações</span></a></li>
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
          <h1 class="page-title">Dashboard Administrativo</h1>
          <p class="page-subtitle">Escolha uma das opções abaixo para realizar cadastros ou acompanhar as métricas em tempo real.</p>
        </div>
      </div>
      <?php if ($_SESSION['Nivel_permissao'] === 0): ?>

        <div class="d-flex flex-wrap gap-2 mb-4">
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalInstituicao">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Cadastrar Instituição
          </button>
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalJurado">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
              <circle cx="8.5" cy="7" r="4" />
              <path d="M20 8v6M23 11h-6" />
            </svg>
            Cadastrar Jurado
          </button>
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalTrabalho">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Cadastrar Trabalho
          </button>
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalAssociacao">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            Associar Jurado
          </button>
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalUsuario" style="background-color: #10b981;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
              <circle cx="8.5" cy="7" r="4" />
              <path d="M20 8v6M23 11h-6" />
            </svg>
            Cadastrar Usuário
          </button>
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalUsuarioEditar" style="background-color: #f59e0b;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
              <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
            </svg>
            Editar Usuário
          </button>
          <button class="admin-action-btn" data-bs-toggle="modal" data-bs-target="#modalUsuarioExcluir" style="background-color: #ef4444;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Apagar Usuários
          </button>
        </div>

        <div class="modal fade" id="modalInstituicao" tabindex="-1" aria-labelledby="modalInstituicaoLabel"
          aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalInstituicaoLabel">Cadastrar Instituição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body">
                <form method="POST" action="../php/Cadescola.php" id="idCadEscola">

                  <!-- SEÇÃO 1: DADOS FIXOS PADRÃO DA INSTITUIÇÃO -->
                  <h6 class="fw-bold text-dark mb-3">Dados da Instituição</h6>

                  <label for="instituicao-categoria" class="form-label">Modalidade da Instituição <span class="text-danger">*</span></label>
                  <select id="instituicao-categoria" class="form-control" name="categoriaEscola" required>
                    <option selected disabled value="">Selecione...</option>
                    <?php foreach ($modalidades_lista as $mod): ?>
                      <option value="<?= htmlspecialchars($mod['id']) ?>">
                        <?= htmlspecialchars($mod['categoria_da_escola']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <div id="campo-digitacaoEscola" style="display: none;">
                    <label for="instituicao-nome" class="form-label mt-2">Nome da Instituição <span class="text-danger">*</span></label>
                    <input type="text" id="instituicao-nome" class="form-control" name="nome" placeholder="Digite o nome da escola" required>
                  </div>

                  <label for="instituicao-municipio" class="form-label mt-2">Município <span class="text-danger">*</span></label>
                  <select id="instituicao-municipio" class="form-control" name="municipio" required>
                    <option selected disabled value="">Selecione...</option>
                    <option value="1">Caridade</option>
                    <option value="2">Canindé</option>
                    <option value="3">Paramoti</option>
                    <option value="4">General Sampaio</option>
                    <option value="5">Santa Quitéria</option>
                    <option value="6">Itatira</option>
                  </select>

                  <!-- SEÇÃO 2: CAMPOS DINÂMICOS ADICIONAIS -->
                  <?php if (!empty($campos_escola)): ?>
                    <div class="mt-4 pt-3 border-top">
                      <h6 class="fw-bold text-success mb-2">Informações Adicionais (Campos Extras)</h6>
                      <p class="text-muted small mb-3">Campos complementares configurados para o cadastro de escolas.</p>
                      <?php foreach ($campos_escola as $campo): ?>
                        <label for="campo_dinamico_<?= $campo['id'] ?>" class="form-label mt-2">
                          <?= htmlspecialchars($campo['nome_campo']) ?>
                          <?= $campo['obrigatorio'] ? '<span class="text-danger">*</span>' : '' ?>
                        </label>

                        <?php if ($campo['tipo_campo'] === 'modalidade'): ?>
                          <select id="campo_dinamico_<?= $campo['id'] ?>" class="form-control" name="campos_dinamicos[<?= $campo['id'] ?>]" <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                            <option selected disabled value="">Selecione...</option>
                            <?php foreach ($modalidades_lista as $mod): ?>
                              <option value="<?= htmlspecialchars($mod['id']) ?>">
                                <?= htmlspecialchars($mod['categoria_da_escola']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>

                        <?php elseif ($campo['tipo_campo'] === 'sim_nao'): ?>
                          <select id="campo_dinamico_<?= $campo['id'] ?>" class="form-control" name="campos_dinamicos[<?= $campo['id'] ?>]" <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                            <option selected disabled value="">Selecione...</option>
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                          </select>

                        <?php else:
                          $tipoInput = 'text';
                          if ($campo['tipo_campo'] === 'numero') $tipoInput = 'number';
                          if ($campo['tipo_campo'] === 'data') $tipoInput = 'date';
                        ?>
                          <input type="<?= $tipoInput ?>"
                            id="campo_dinamico_<?= $campo['id'] ?>"
                            class="form-control"
                            name="campos_dinamicos[<?= $campo['id'] ?>]"
                            placeholder="Insira <?= htmlspecialchars($campo['nome_campo']) ?>"
                            <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <input type="submit" value="Enviar" class="btn btn-success mt-3">
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="modal fade" id="modalJurado" tabindex="-1" aria-labelledby="modalJuradoLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalJuradoLabel">Cadastrar Jurado</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <form action="../php/Cadjurado.php" method="POST" id="idCadJurado">

                <label for="jurado-cad-nome" class="form-label">Nome</label>
                <input type="text" id="jurado-cad-nome" class="form-control" name="nome" placeholder="Digite seu nome" required>

                <label for="jurado-cad-telefone" class="form-label mt-2">Telefone</label>
                <input type="text" id="jurado-cad-telefone" class="form-control" name="telefone" placeholder="Digite seu telefone" required>

                <label for="jurado-cad-cpf" class="form-label mt-2">CPF</label>
                <input type="text" id="jurado-cad-cpf" class="form-control" name="cpf" placeholder="Digite seu CPF" required>

                <label for="jurado-cad-email" class="form-label mt-2">E-mail SIC-CED</label>
                <input type="email" id="jurado-cad-email" class="form-control" name="email" placeholder="Digite seu e-mail" required>

                <label for="jurado-categoria" class="form-label mt-2">Categoria</label>
                <select id="jurado-categoria" class="form-control" name="categoria[]" required>
                  <option selected disabled>Selecione...</option>
                  <option value="1">I - Ensino Médio</option>
                  <option value="2">II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                  <option value="3">III - Pesquisa Júnior</option>
                  <option value="4">IV - PcD</option>
                </select>
                <div id="jurado-area" style="display:none;">
                  <label for="jurado-area-select" class="form-label mt-2">Área</label>
                  <select id="jurado-area-select" class="form-control" name="area[]">
                    <option selected disabled>Selecione...</option>
                    <option value="1">Linguagens, Códigos e suas Tecnologias - LC</option>
                    <option value="2">Matemática e suas Tecnologias - MT</option>
                    <option value="3">Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
                    <option value="4">Ciências Humanas e Sociais Aplicadas - CH</option>
                    <option value="5">Robótica, Automação e Aplicação das TIC</option>
                  </select>
                </div>
                <div id="jurado-area2" style="display:none;">
                  <label for="jurado-area2-select" class="form-label mt-2">Área</label>
                  <select id="jurado-area2-select" class="form-control" name="area[]">
                    <option selected disabled>Selecione...</option>
                    <option value="6">Ensino Fundamental</option>
                    <option value="7">Ensino Médio</option>
                  </select>
                </div>
                <div class="mb-3" style="display: none;">
                  <label for="Adicionar-sec-categoria" class="form-label mt-2">Categoria</label>
                  <select name="categoria[]" id="Adicionar-sec-categoria" class="form-control">
                    <option selected disabled>Selecione...</option>
                    <option value="1">I - Ensino Médio</option>
                    <option value="2">II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                    <option value="3">III - Pesquisa Júnior</option>
                    <option value="4">IV - PcD</option>
                  </select>
                </div>
                <div class="mb-3" id="areajuradosec" style="display: none;">
                  <label for="area1" class="form-label mt-2">Área</label>
                  <select name="area[]" id="area1" class="form-control">
                    <option selected disabled>Selecione...</option>
                    <option value="1">Linguagens, Códigos e suas Tecnologias - LC</option>
                    <option value="2">Matemática e suas Tecnologias - MT</option>
                    <option value="3">Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
                    <option value="4">Ciências Humanas e Sociais Aplicadas - CH</option>
                    <option value="5">Robótica, Automação e Aplicação das TIC</option>
                  </select>
                </div>
                <div class="mb-3" id="area2juradosec" style="display: none;">
                  <label for="area2" class="form-label mt-2">Área</label>
                  <select name="area[]" id="area2" class="form-control">
                    <option selected disabled>Selecione...</option>
                    <option value="6">Ensino Fundamental</option>
                    <option value="7">Ensino Médio</option>
                  </select>
                </div>
                <input type="submit" value="Enviar" class="btn btn-success" style="margin-top:10px; float: left;">
                <button type="button" class="btn btn-success" id="adicionar-categoria-btn"
                  style="background-color: #2071b4ff; float:right; margin-top:10px;">Adicionar Categoria</button>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalJuradoLabel">Cadastrar Usuario</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <form action="../php/CadUsuario.php" method="POST" id="idCadUsuario">

                <label for="usuario-cad-nome" class="form-label">Usuário</label>
                <input type="text" id="usuario-cad-nome" class="form-control" name="usuario" placeholder="Digite o usuário" required>

                <label for="usuario-cad-senha" class="form-label mt-2">Senha</label>
                <input type="password" id="usuario-cad-senha" class="form-control" name="senha" placeholder="Digite a senha" required>

                <label for="usuario-nivel" class="form-label mt-2">Nível de permissão</label>
                <select id="usuario-nivel" class="form-control" name="Nivel_permissao" required>
                  <option value="" selected disabled>Selecione...</option>
                  <option value="0">ADMINISTRATIVO</option>
                  <option value="1">PADRÃO</option>
                </select>

                <input type="submit" value="Enviar" class="btn btn-success" style="margin-top:10px;">

              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="modalUsuarioEditar" tabindex="-1" aria-labelledby="modalUsuarioEditarLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalUsuarioEditarLabel">Editar Usuário</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <form action="../php/EditarUsuario.php" method="POST" id="formEditarUsuario">
                <label for="usuario-atual" class="form-label">Usuário atual</label>
                <select id="usuario-atual" class="form-control" name="usuario_atual" required>
                  <option value="" selected disabled>Selecione o usuário...</option>

                  <?php
                  $stmtUsuarios = $pdo->query("SELECT usuario FROM administracao ORDER BY usuario ASC");
                  while ($usuario = $stmtUsuarios->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . htmlspecialchars($usuario['usuario']) . '">'
                      . htmlspecialchars($usuario['usuario'])
                      . '</option>';
                  }
                  ?>
                </select>

                <label for="novo-usuario" class="form-label mt-2">Novo usuário</label>

                <input
                  type="text"
                  id="novo-usuario"
                  class="form-control"
                  name="novo_usuario"
                  placeholder="Digite o novo usuário"
                  required>

                <label for="nova-senha" class="form-label mt-2">Nova senha</label>

                <input
                  type="password"
                  id="nova-senha"
                  class="form-control"
                  name="nova_senha"
                  placeholder="Digite a nova senha"
                  required>

                <label for="novo-nivel" class="form-label mt-2">Nível de permissão</label>

                <select
                  id="novo-nivel"
                  class="form-control"
                  name="novo_nivel"
                  required>
                  <option value="" selected disabled>Selecione...</option>
                  <option value="0">ADMINISTRATIVO</option>
                  <option value="1">PADRÃO</option>
                </select>

                <input
                  type="submit"
                  value="Salvar alterações"
                  class="btn btn-success"
                  style="margin-top:10px;">
              </form>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal">
                Fechar
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="modalUsuarioExcluir" tabindex="-1" aria-labelledby="modalUsuarioExcluirLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalUsuarioExcluirLabel">Excluir Usuário</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <form action="../php/ExcluirUsuario.php" method="POST" id="idExcluirUsuario">
                <label for="usuario-excluir" class="form-label">
                  Selecione o usuário
                </label>
                <select id="usuario-excluir" class="form-control" name="id_admin" required>
                  <option value="" selected disabled>
                    Selecione...
                  </option>
                  <?php
                  $stmtUsuarios = $pdo->query("
                            SELECT id_admin, usuario
                            FROM administracao
                            ORDER BY usuario ASC
                        ");

                  $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
                  foreach ($usuarios as $usuario):
                  ?>
                    <option value="<?= htmlspecialchars($usuario['id_admin']) ?>">
                      <?= htmlspecialchars($usuario['usuario']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="alert alert-warning mt-3">
                  <strong>Atenção!</strong>
                  Essa ação excluirá permanentemente o usuário selecionado.
                </div>
                <input
                  type="submit"
                  value="Excluir Usuário"
                  class="btn btn-danger"
                  style="margin-top:10px;">
              </form>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal">
                Fechar
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="modalTrabalho" tabindex="-1" aria-labelledby="modalTrabalhoLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalTrabalhoLabel">Cadastrar Trabalho</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <form action="../php/Cadtrabalho.php" method="POST" id="idCadTrabalho">
                <label for="trabalho-titulo" class="form-label">Título do Trabalho</label>
                <input type="text" id="trabalho-titulo" class="form-control" name="titulo" placeholder="Digite o nome do Trabalho" required>
                <label for="escola" class="form-label mt-2">Escola</label>
                <select name="escola" id="escola" class="form-control" required>
                  <option value="">Selecione a Escola</option>
                  <?php foreach ($escolas as $escola): ?>
                    <option value="<?= htmlspecialchars($escola['id_escolas']) ?>">
                      <?= $escola['categoria_escola'] . " " .  htmlspecialchars($escola['nome']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <label for="trabalho-categoria" class="form-label mt-2">Categoria</label>
                <select id="trabalho-categoria" name="categoria" class="form-control" required>
                  <option selected disabled>Selecione...</option>
                  <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>">
                      <?= htmlspecialchars($categoria['nome_categoria']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="trabalho-area" style="display:none;">
                  <label class="form-label mt-2">Área</label>
                  <select name="area" class="form-control">
                    <option selected disabled>Selecione...</option>
                    <?php foreach ($areas as $area): ?>
                      <option value="<?= htmlspecialchars($area['id_area']) ?>">
                        <?= htmlspecialchars($area['nome_area']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div id="trabalho-area2" style="display:none;">
                  <label class="form-label mt-2">Área</label>
                  <select name="area" class="form-control">
                    <option selected disabled>Selecione...</option>
                    <option value="6">Ensino Fundamental</option>
                    <option value="7">Ensino Médio</option>
                  </select>
                </div>
                <label for="trabalho-ordem" class="form-label mt-2">Digite a ordem do trabalho</label>
                <input type="text" id="trabalho-ordem" class="form-control" name="ordem" placeholder="Insira...." required>

                <!-- SEÇÃO CAMPOS DINÂMICOS ADICIONAIS -->
                <?php if (!empty($campos_trabalho)): ?>
                  <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold text-success mb-2">Informações Adicionais (Campos Extras)</h6>
                    <p class="text-muted small mb-3">Campos complementares configurados para o cadastro de trabalhos.</p>
                    <?php foreach ($campos_trabalho as $campo): ?>
                      <label for="campo_dinamico_trabalho_<?= $campo['id'] ?>" class="form-label mt-2">
                        <?= htmlspecialchars($campo['nome_campo']) ?>
                        <?= $campo['obrigatorio'] ? '<span class="text-danger">*</span>' : '' ?>
                      </label>

                      <?php if ($campo['tipo_campo'] === 'modalidade'): ?>
                        <select id="campo_dinamico_trabalho_<?= $campo['id'] ?>" class="form-control" name="campos_dinamicos[<?= $campo['id'] ?>]" <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                          <option selected disabled value="">Selecione...</option>
                          <?php foreach ($modalidades_lista as $mod): ?>
                            <option value="<?= htmlspecialchars($mod['id']) ?>">
                              <?= htmlspecialchars($mod['categoria_da_escola']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                      <?php elseif ($campo['tipo_campo'] === 'sim_nao'): ?>
                        <select id="campo_dinamico_trabalho_<?= $campo['id'] ?>" class="form-control" name="campos_dinamicos[<?= $campo['id'] ?>]" <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                          <option selected disabled value="">Selecione...</option>
                          <option value="1">Sim</option>
                          <option value="0">Não</option>
                        </select>

                      <?php else:
                        $tipoInput = 'text';
                        if ($campo['tipo_campo'] === 'numero') $tipoInput = 'number';
                        if ($campo['tipo_campo'] === 'data') $tipoInput = 'date';
                      ?>
                        <input type="<?= $tipoInput ?>"
                          id="campo_dinamico_trabalho_<?= $campo['id'] ?>"
                          class="form-control"
                          name="campos_dinamicos[<?= $campo['id'] ?>]"
                          placeholder="Insira <?= htmlspecialchars($campo['nome_campo']) ?>"
                          <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <input type="submit" value="Enviar" class="btn btn-success mt-3">
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="modalAssociacao" tabindex="-1" aria-labelledby="modalAssociacaoLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST" id="idCadAssociacao" action="../php/AssociarJuradoTrabalho.php">
              <div class="modal-header">
                <h5 class="modal-title" id="modalAssociacaoLabel">Associar Jurado a Trabalho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body">
                <?php if (!empty($mensagem_sucesso)): ?>
                  <div class="alert alert-success"><?= $mensagem_sucesso ?></div>
                <?php endif; ?>
                <div class="mb-3">
                  <label for="associar-categoria" class="form-label">Categoria</label>
                  <select name="categoria" class="form-control" id="associar-categoria" required>
                    <option disabled selected>Selecione...</option>
                    <?php foreach ($categorias as $cat): ?>
                      <option value="<?= $cat['id_categoria'] ?>">
                        <?= htmlspecialchars($cat['nome_categoria']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-3" id="area-div" style="display:none;">
                  <label for="area-select" class="form-label">Área</label>
                  <select name="area" class="form-control" id="area-select">
                    <option disabled selected>Selecione...</option>
                  </select>
                </div>

                <div class="mb-3" id="jurado-div" style="display:none;">
                  <label for="jurado-select" class="form-label">Jurado</label>
                  <select name="jurado" class="form-control" id="jurado-select" required>
                    <option disabled selected>Selecione o Jurado</option>
                  </select>
                </div>

                <div class="mb-3" id="trabalho-div" style="display:none;">
                  <label class="form-label">Trabalhos</label>
                  <table class="table table-hover">
                    <tbody id="trabalho-tbody"></tbody>
                  </table>
                  <button type="button" class="btn btn-sm btn-primary mb-2" id="selecionar-todos">Selecionar Todos</button>
                </div>

              </div>
              <div class="modal-footer">
                <input type="submit" class="btn btn-success" value="Enviar">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
              </div>
            </form>
          </div>
        </div>
      </div>


      <div class="row g-3 stat-row">
        <div class="col-md-4">
          <div class="stat-box stat-primary">
            <i><img src="../assets/img/escola.png" class="escola" style="width: 28px; height: 28px;"></i>
            <h2 id="total-escolas" class="mt-2"><?php echo $total_escolas; ?></h2>
            <p>Escolas Cadastradas</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-box stat-primary">
            <i><img src="../assets/img/trabalho.png" class="trabalho" style="width: 28px; height: 28px;"></i>
            <h2 id="total-trabalhos" class="mt-2"><?php echo $total_trabalhos; ?></h2>
            <p>Trabalhos Cadastrados</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-box stat-primary">
            <i><img src="../assets/img/Jurados.png" class="jurado" style="width: 28px; height: 28px;"></i>
            <h2 id="total-jurados" class="mt-2"><?php echo $total_jurados; ?></h2>
            <p>Jurados Cadastrados</p>
          </div>
        </div>
      </div>


      <div class="admin-card-filter mt-4">
        <h3 class="admin-card-title text-center justify-content-center">Ranking Preliminar</h3>
        <form action="" method="POST">
          <div class="row g-3 align-items-end">
            <div class="col-md-5">
              <label class="admin-form-label">Categoria</label>
              <select id="ranking-categoria" class="form-select admin-form-select" name="categoria">
                <option value="1" <?= ($categoriaFiltro ?? '') == "1" ? "selected" : "" ?>>I - Ensino Médio</option>
                <option value="2" <?= ($categoriaFiltro ?? '') == "2" ? "selected" : "" ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                <option value="3" <?= ($categoriaFiltro ?? '') == "3" ? "selected" : "" ?>>III - Pesquisa Júnior</option>
                <option value="4" <?= ($categoriaFiltro ?? '') == "4" ? "selected" : "" ?>>IV - PcD</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="admin-form-label">Área</label>
              <select id="ranking-area" class="form-select admin-form-select" name="area">
                <option value="">-- Selecione a Área --</option>
                <option value="1" <?= ($areaFiltro ?? '') == "1" ? "selected" : "" ?>>Linguagens, Códigos e suas Tecnologias - LC</option>
                <option value="2" <?= ($areaFiltro ?? '') == "2" ? "selected" : "" ?>>Matemática e suas Tecnologias - MT</option>
                <option value="3" <?= ($areaFiltro ?? '') == "3" ? "selected" : "" ?>>Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
                <option value="4" <?= ($areaFiltro ?? '') == "4" ? "selected" : "" ?>>Ciências Humanas e Sociais Aplicadas - CH</option>
                <option value="5" <?= ($areaFiltro ?? '') == "5" ? "selected" : "" ?>>Robótica, Automação e Aplicação das TIC</option>
                <option value="6" <?= ($areaFiltro ?? '') == "6" ? "selected" : "" ?>>Ensino Fundamental</option>
                <option value="7" <?= ($areaFiltro ?? '') == "7" ? "selected" : "" ?>>Ensino Médio</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="admin-action-btn w-100 justify-content-center">Filtrar</button>
            </div>
          </div>
        </form>

        <?php
        // O dashboard usa a mesma função central de ranking dos demais módulos.
        // A função aplica somente as regras ativas configuradas em Criterios_desempate;
        // não há fallback fixo para colunas IDEB ou total_trabalhos.
        $erroRanking = false;
        try {
          $dados = obterClassificacaoTrabalhos(
            $pdo,
            $categoriaFiltro,
            $areaFiltro,
            true // mantém os trabalhos sem notas no ranking preliminar
          );
        } catch (Throwable $e) {
          error_log('Erro ao calcular ranking do dashboard: ' . $e->getMessage());
          $dados = [];
          $erroRanking = true;
        }
        ?>

        <div class="admin-card-table mt-4">
          <table class="table admin-table admin-table-compact align-middle text-center mb-0">
            <thead>
              <tr>
                <th class="text-center" style="width: 80px;">Classificação</th>
                <th class="text-start">Título</th>
                <th class="text-start">Escola</th>
                <th>Categoria</th>
                <th>Área</th>
                <th>Jurado 1</th>
                <th>Jurado 2</th>
                <th>Nota Final</th>
                <th>Critério de Desempate</th>
              </tr>
            </thead>
            <tbody id="ranking-tbody">
              <?php if (count($dados) > 0) {
                $posicao = 1;
                foreach ($dados as $trab) {
                  $posicaoLinha = (int) ($trab['posicao'] ?? $posicao);
                  $juradosDetalhes = $trab['jurados_detalhes'] ?? [];
                  echo '<tr>';
                  echo '<td class="text-center"><span class="rank-badge">' . $posicaoLinha . 'º</span></td>';
                  echo '<td class="text-start td-item-title">' . htmlspecialchars($trab['titulo']) . '</td>';
                  echo '<td class="text-start">' . $trab['Modalidade'] . ' ' . htmlspecialchars($trab['escola']) . '</td>';
                  echo '<td><span class="category-pill">' . htmlspecialchars($trab['categoria']) . '</span></td>';
                  echo '<td>' . htmlspecialchars($trab['area'] ?? 'Sem área') . '</td>';
                  echo '<td class="fw-semibold">' . (
                    isset($juradosDetalhes[1]['media_ponderada']) && $juradosDetalhes[1]['media_ponderada'] !== null
                    ? number_format($juradosDetalhes[1]['media_ponderada'], 2, ',', '')
                    : '-'
                  ) . '</td>';

                  echo '<td class="fw-semibold">' . (
                    isset($juradosDetalhes[2]['media_ponderada']) && $juradosDetalhes[2]['media_ponderada'] !== null
                    ? number_format($juradosDetalhes[2]['media_ponderada'], 2, ',', '')
                    : '-'
                  ) . '</td>';

                  echo '<td class="fw-bold text-success">' . (
                    $trab['media_final'] !== null
                    ? number_format($trab['media_final'], 2, ',', '')
                    : '-'
                  ) . '</td>';
                  if (
                    isset($trab['criterio_desempate'])
                    && $trab['criterio_desempate'] !== null
                  ) {
                    $crit = $trab['criterio_desempate'];

                    echo '<td class="text-center"
            style="width: 80px; max-width: 80px; padding: 4px;
            overflow-wrap: anywhere;">
            <span class="badge bg-light text-dark border px-2 py-2"
                style="white-space: normal;">'
                      . htmlspecialchars(
                        (string) ($crit['criterio'] ?? '-'),
                        ENT_QUOTES,
                        'UTF-8'
                      )
                      . '</span>
        </td>';
                  } else {
                    echo '<td class="text-center text-muted"
        style="width: 80px; max-width: 80px; padding: 4px;">-</td>';
                  }

                  echo '</tr>';
                  $posicao = $posicaoLinha + 1;
                }
              } else {
                $mensagemRanking = $erroRanking
                  ? 'Não foi possível calcular o ranking. Verifique a configuração dos critérios e o log do PHP.'
                  : 'Nenhum trabalho encontrado para os filtros selecionados.';
                echo '<tr><td colspan="9" class="text-center py-4 text-muted">' . htmlspecialchars($mensagemRanking, ENT_QUOTES, 'UTF-8') . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <footer class="footer-copy text-center py-4 text-muted" style="font-size: 0.82rem;">
      Sistema SAFC &copy; <?= date('Y') ?> &bull; Iniciativa Crede 7
    </footer>
  </main>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Sidebar
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

    $('#idCadEscola').submit(function() {
      const categoria = $('#instituicao-categoria option:selected').text();
      const nome = $('#instituicao-nome').val();

      $('#categoria_escolas').val(categoria + ' - ' + nome);
    });

    $(window).on('resize', function() {
      if (window.innerWidth > 768) {
        $('#sidebar').removeClass('mobile-open');
        $('#overlay').removeClass('show');
      }
    });
    $('#instituicao-tipo').change(function() {
      ($(this).val() === '1') ? $('#campo-ide').slideDown(): $('#campo-ide').slideUp();
    });
    $('#instituicao-categoria').change(function() {
      if ($(this).val()) {
        $('#campo-digitacaoEscola').slideDown();
      } else {
        $('#campo-digitacaoEscola').slideUp();
        $('#instituicao-nome').val('');
      }
    });
    $('#trabalho-categoria').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#trabalho-area').slideDown();
        $('#trabalho-area2').slideUp();
      } else if (categoria === '4') {
        $('#trabalho-area').slideUp();
        $('#trabalho-area2').slideDown();
      } else {
        $('#trabalho-area, #trabalho-area2').slideUp();
      }
    });
    $('#jurado-categoria').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#jurado-area').slideDown();
        $('#jurado-area2').slideUp();
      } else if (categoria === '4') {
        $('#jurado-area').slideUp();
        $('#jurado-area2').slideDown();
      } else {
        $('#jurado-area, #jurado-area2').slideUp();
      }
    });
    $('#adicionar-categoria-btn').click(function(event) {
      event.preventDefault();
      var container = $('#Adicionar-sec-categoria').parent();
      if (container.is(':visible')) {
        container.slideUp();
        $('#Adicionar-sec-categoria').val('');
        $('#areajuradosec, #area2juradosec').slideUp();
      } else {
        container.slideDown();
      }
    });
    $('#Adicionar-sec-categoria').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#areajuradosec').slideDown();
        $('#area2juradosec').slideUp();
      } else if (categoria === '4') {
        $('#area2juradosec').slideDown();
        $('#areajuradosec').slideUp();
      } else {
        $('#areajuradosec, #area2juradosec').slideUp();
      }
    });
    $('#associar-categoria').change(function() {
      var categoria = $(this).val();
      if (categoria === '1' || categoria === '2') {
        $('#areajurado').slideDown();
        $('#area2jurado').slideUp();
      } else if (categoria === '4') {
        $('#area2jurado').slideDown();
        $('#areajurado').slideUp();
      } else if (categoria === '3') {
        $('#atribuir-jurado').slideDown();
        $('#areajurado, #area2jurado').slideUp();
      }
    });
    $('#area1, #area2').change(function() {
      $('#atribuir-jurado').slideDown();
    });
    $('#atribuir-jurado').change(function() {
      $('#trabalhojurado').slideDown();
    });

    $('#selecionar-todos').click(function() {
      const checkboxes = $('#trabalho-tbody input[type="checkbox"]');
      const todosSelecionados = checkboxes.length === checkboxes.filter(':checked').length;

      if (todosSelecionados) {
        checkboxes.prop('checked', false);
        $(this).text('Selecionar Todos');
      } else {
        checkboxes.prop('checked', true);
        $(this).text('Desmarcar Todos');
      }
    });

    const trabalhosCadastrados = <?= json_encode($trabalhosCadastrados, JSON_UNESCAPED_UNICODE) ?>;

    $('#idCadTrabalho').submit(function(event) {
      const escola = $('#escola').val();
      const categoria = $('#trabalho-categoria').val();
      const area = $('#trabalho-area').is(':visible') ? $('#trabalho-area select[name="area"]').val() : $('#trabalho-area2 select[name="area"]').val();
      const titulo = $('#trabalho-titulo').val().trim().toLowerCase();

      if (!escola || !area || !titulo) {
        return;
      }

      const mesmoTrabalho = trabalhosCadastrados.some(function(trabalho) {
        return String(trabalho.id_escolas) === String(escola) &&
          String(trabalho.id_areas) === String(area) &&
          trabalho.titulo.trim().toLowerCase() === titulo;
      });

      if (mesmoTrabalho) {
        event.preventDefault();
        alert('Esta escola já possui um trabalho nesta área.');
        return;
      }

      const trabalhoEmOutraArea = trabalhosCadastrados.some(function(trabalho) {
        return String(trabalho.id_escolas) === String(escola) &&
          trabalho.titulo.trim().toLowerCase() === titulo &&
          String(trabalho.id_areas) !== String(area);
      });

      if (trabalhoEmOutraArea) {
        event.preventDefault();
        alert('Este trabalho já está cadastrado em outra área.');
        return;
      }

      const escolaNaArea = trabalhosCadastrados.some(function(trabalho) {
        return String(trabalho.id_escolas) === String(escola) &&
          String(trabalho.id_areas) === String(area);
      });

      if (escolaNaArea) {
        event.preventDefault();
        alert('Esta escola já possui um trabalho cadastrado nesta área.');
        return;
      }
    });

    $(document).ready(function() {
      $('#associar-categoria').on('change', function() {
        let categoriaId = $(this).val();
        if (!categoriaId) {
          $('#area-div, #jurado-div, #trabalho-div').hide();
          $('#area-select, #jurado-select').empty().append('<option disabled selected>Selecione...</option>');
          $('#trabalho-tbody').empty();
          return;
        }

        if (categoriaId === '3') {
          $('#area-div').hide();
          $('#jurado-div').show();
          $('#trabalho-div').hide();
          $('#area-select').empty().append('<option disabled selected>NÃ£o aplicÃ¡vel</option>');
          $('#area-select').empty().append('<option disabled selected>Não aplicável</option>');

          $.ajax({
            url: '../php/BuscarJurados.php',
            method: 'GET',
            data: {
              categoria: categoriaId
            },
            dataType: 'json',
            success: function(data) {
              let options = '<option disabled selected>Selecione o Jurado</option>';
              data.forEach(jurado => {
                options += `<option value="${jurado.id_jurados}">${jurado.nome}</option>`;
              });
              $('#jurado-select').html(options);
            },
            error: function() {
              alert('Erro ao carregar jurados.');
            }
          });

          $('#trabalho-tbody').empty();

        } else {
          $('#area-div').show();
          $('#jurado-div, #trabalho-div').hide();
          $('#jurado-select').empty().append('<option disabled selected>Selecione o Jurado</option>');
          $('#trabalho-tbody').empty();

          $.ajax({
            url: '../php/BuscarAreas.php',
            method: 'GET',
            data: {
              categoria: categoriaId
            },
            dataType: 'json',
            success: function(data) {
              let options = '<option disabled selected>Selecione...</option>';
              data.forEach(area => {
                options += `<option value="${area.id_area}">${area.nome_area}</option>`;
              });
              $('#area-select').html(options);
            },
            error: function() {
              alert('Erro ao carregar áreas.');
              alert('Erro ao carregar áreas.');
            }
          });
        }
      });


      $('#area-select').on('change', function() {
        let categoriaId = $('#associar-categoria').val();
        let areaId = $(this).val();

        if (!areaId) {
          $('#jurado-div, #trabalho-div').hide();
          $('#jurado-select').empty().append('<option disabled selected>Selecione o Jurado</option>');
          $('#trabalho-tbody').empty();
          return;
        }

        $('#jurado-div').show();
        $('#trabalho-div').hide();
        $('#trabalho-tbody').empty();

        $.ajax({
          url: '../php/BuscarJurados.php',
          method: 'GET',
          data: {
            categoria: categoriaId,
            area: areaId
          },
          dataType: 'json',
          success: function(data) {
            let options = '<option disabled selected>Selecione o Jurado</option>';
            data.forEach(jurado => {
              options += `<option value="${jurado.id_jurados}">${jurado.nome}</option>`;
            });
            $('#jurado-select').html(options);
          },
          error: function() {
            alert('Erro ao carregar jurados.');
          }
        });
      });

      $('#jurado-select').on('change', function() {
        let categoriaId = $('#associar-categoria').val();
        let areaId = $('#area-select').val();
        let juradoId = $(this).val();

        if (!juradoId) {
          $('#trabalho-div').hide();
          $('#trabalho-tbody').empty();
          return;
        }

        $('#trabalho-div').show();

        let dataAjax = {
          categoria: categoriaId,
          jurado: juradoId
        };
        if (categoriaId !== '3') {
          dataAjax.area = areaId;
        }

        $.ajax({
          url: '../php/BuscarTrabalhos.php',
          method: 'GET',
          data: dataAjax,
          dataType: 'json',
          success: function(data) {
            let rows = '';
            data.forEach(trabalho => {
              const nomeArea = trabalho.nome_area ?? ''; // se for null, mostra string vazia
              rows += `
              <tr>
                <td><input type="checkbox" name="trabalhos[]" value="${trabalho.id_trabalhos}"></td>
                <td>${trabalho.titulo}</td>
                <td>${nomeArea}</td>
              </tr>`;
            });

            $('#trabalho-tbody').html(rows);
          },
          error: function() {
            alert('Erro ao carregar trabalhos.');
          }
        });
      });
    });
  </script>
</body>

</html>