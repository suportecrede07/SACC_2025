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

$stmtUser = $pdo->prepare("SELECT nome FROM Jurados WHERE id_jurados = ?");
$stmtUser->execute([$idJurado]);
$result = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userName = $result ? $result['nome'] : 'Usuário';

$stmt = $pdo->prepare("
  SELECT 
    t.id_trabalhos, 
    t.titulo, 
    e.nome AS nome_escola, 
    c.nome_categoria, 
    a.nome_area,
    (
      SELECT COUNT(*) 
      FROM Avaliacoes av 
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
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Jurado Dashboard</title>
  <link rel="stylesheet" href="../assets/styles/dashboard.css" />
  <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css" />
  <script src="../boostrap/JS/jquery.min.js"></script>
  <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
  <style>
    .is-invalid {
      border-color: #dc3545;
    }
  </style>

</head>

<body>

  <header class="menu-superior">
    <div class="container-fluid" style="width: 100%; display: flex; justify-content: center; align-items: center; position: relative;">
      <a href="../php/JuradoLogout.php" class="left-item" style="position: absolute; left: 15px;"><img src="../assets/img/sair.png" alt="Sair" /></a>
      <img src="../assets/img/cearacientifico.png" class="center-item" style="max-width: 150px;" alt="Logo Ceará Científico" />
    </div>
  </header>


  <main class="conteudo">
    <div style="text-align: center; width: 100%;">
      <h2>Lista de Trabalhos para <?= htmlspecialchars($userName) ?></h2>
    </div>
    <br />
    <table class="table table-striped table-bordered ">
      <thead class="table-success" style="border: 1px solid;">
        <tr>
          <th>Título</th>
          <th>Escola</th>
          <th>Categoria</th>
          <th>Área</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody style="border: 1px solid;">
        <?php if (count($trabalhos) === 0): ?>
          <tr>
            <td colspan="6">Nenhum trabalho associado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($trabalhos as $trabalho): ?>
            <tr>
              <td><?= htmlspecialchars($trabalho['titulo']) ?></td>
              <td><?= htmlspecialchars($trabalho['nome_escola'] ?? 'N/D') ?></td>
              <td><?= htmlspecialchars($trabalho['nome_categoria'] ?? 'N/D') ?></td>
              <td><?= htmlspecialchars($trabalho['nome_area'] ?? 'N/D') ?></td>
              <td>
                <?php if ($trabalho['avaliacao_existente'] == 0): ?>
                  <button
                    class="btn btn-success abrir-modal-avaliacao"
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
                  <span class="text-success">Avaliado</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </main>

  <!-- Modal Avaliar -->
  <div class="modal fade" id="avaliarModal" tabindex="-1" aria-labelledby="avaliarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title" id="avaliarModalLabel">Avaliação do Trabalho</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body">
          <div class="container">
            <div class="mb-4">
              <p style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">

              <div class="d-flex align-items-start" style="min-width: 100%;">
                <strong class="me-2" style="min-width: 80px;">Titulo:</strong>
                <span id="modalTitulo" class="text-break"><?= htmlspecialchars($trabalho['titulo']) ?></span>
              </div>

              <div class="d-flex align-items-start" style="min-width: 100%;">
                <strong class="me-2" style="min-width: 80px;">Escola:</strong>
                <span id="modalEscola" class="text-break"><?= htmlspecialchars($trabalho['nome_escola'] ?? 'N/D') ?></span>
              </div>

              <div class="d-flex align-items-start" style="min-width: 100%;">
                <strong class="me-2" style="min-width: 80px;">Categoria:</strong>
                <span id="modalCategoria" class="text-break"><?= htmlspecialchars($trabalho['nome_categoria'] ?? 'N/D') ?></span>
              </div>

              <div class="d-flex align-items-start" style="min-width: 100%;">
                <strong class="me-2" style="min-width: 80px;">Área:</strong>
                <span id="modalArea" class="text-break"><?= htmlspecialchars($trabalho['nome_area'] ?? 'N/D') ?></span>
              </div>

              </p>
            </div>


            <form id="formAvaliacao" action="../php/SalvarAvaliacao.php" method="post">
              <input type="hidden" name="id_trabalho" id="id_trabalho" value="" />
              <table class="table table-bordered">
                <thead class="table-success" style="border: 1px solid;">
                  <tr>
                    <th>Critério</th>
                    <th style="width: 100px;">Nota</th>
                    <th>Comentário</th>
                  </tr>
                </thead>
                <tbody style="border: 1px solid;">
                  <tr>
                    <td><b>Criatividade e Inovação</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio1" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario1" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>

                  <tr>
                    <td><b>Relevância da pesquisa</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio2" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario2" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Conhecimento científico fundamentado e contextualização do problema abordado</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio3" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario3" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Impacto para a construção de uma sociedade que promova Ciência, Cidadania e Convivência Democrática: o conhecimento a serviço da vida coletiva</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio4" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario4" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Metodologia científica conectada com os objetivos, resultados e conclusões</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio5" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario5" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Clareza e objetividade na linguagem apresentada</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio6" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario6" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Banner</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio7" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario7" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Caderno de campo</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio8" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario8" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                  <tr>
                    <td><b>Processo participativo e solidário</b></td>
                    <td style="width: 100px;"><input type="text" inputmode="numeric" class="form-control nota-auto" name="criterio9" maxlength="5" style="border: 1px solid;" required /></td>
                    <td><textarea class="form-control" name="comentario9" cols="50" style="max-height: 30px; border: 1px solid;"></textarea></td>
                  </tr>
                </tbody>
              </table>

              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="button" class="btn btn-success" id="btnAbrirConfirmacao">Finalizar Avaliação</button>
              </div>
              <div class="modal fade" id="confirmarEnvioModal" tabindex="-1" aria-labelledby="confirmarEnvioModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">

                    <div class="modal-header">
                      <h5 class="modal-title" id="confirmarEnvioModalLabel">Confirmar Avaliação</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                      Tem certeza que deseja finalizar a avaliação? Você não poderá alterar depois.
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                      <button type="button" class="btn btn-success" id="confirmarEnvioBtn">Sim, finalizar</button>
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
    document.addEventListener('DOMContentLoaded', () => {
      const inputsNotas = document.querySelectorAll('.nota-auto');
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
        });

        input.addEventListener('blur', () => {
          let valor = input.value.trim();

          if (valor === '') {
            input.value = '0,00';
            input.classList.remove('is-invalid');
            return;
          }

          let numero = parseFloat(valor.replace(',', '.'));

          if (isNaN(numero) || numero < 0 || numero > 10) {
            input.classList.add('is-invalid');
            return;
          }

          const decimalPart = valor.split(',')[1];
          if (decimalPart && decimalPart.length > 2) {
            input.classList.add('is-invalid');
            return;
          }

          input.value = numero.toFixed(2).replace('.', ',');
          input.classList.remove('is-invalid');
        });
      });

      botaoAbrirConfirmacao.addEventListener('click', () => {
        let valido = true;

        inputsNotas.forEach(input => {
          const valorNumerico = parseFloat(input.value.replace(',', '.'));
          if (isNaN(valorNumerico) || valorNumerico < 0 || valorNumerico > 10) {
            input.classList.add('is-invalid');
            valido = false;
          } else {
            const decimais = input.value.split(',')[1];
            if (decimais && decimais.length > 2) {
              input.classList.add('is-invalid');
              valido = false;
              return;
            }
            input.classList.remove('is-invalid');
            input.value = valorNumerico.toFixed(2).replace('.', ',');
          }
        });

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

    $('.abrir-modal-avaliacao').on('click', function() {
      $('#modalTitulo').text($(this).data('titulo'));
      $('#modalEscola').text($(this).data('escola'));
      $('#modalCategoria').text($(this).data('categoria'));
      $('#modalArea').text($(this).data('area'));
      $('#id_trabalho').val($(this).data('id'));
    });
  </script>
</body>

</html>