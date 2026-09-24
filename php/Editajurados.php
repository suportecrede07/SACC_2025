<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../php/Connect.php';
$id = $_GET['id'] ?? null;
if (!$id) {
    echo 'ID não fornecido';
}
$stmt = $pdo->prepare("SELECT 
    j.id_jurados,
    j.nome,
    j.cpf,
    co.telefone,
    co.email
    FROM Jurados j
    LEFT JOIN Contatos co ON j.id_contatos = co.id_contatos
    WHERE j.id_jurados = ?
    ");

$stmt->execute([$id]);
$jurado = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT 
    id_categoria, 
    id_area
    FROM Jurados_Categorias_Areas
    WHERE id_jurados = ?
    ");

$stmt2->execute([$id]);
$categoriasAreas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if (!$jurado) {
    echo 'Jurado não encontrado!';
}

$categoria1 = $categoria2 = null;
$area1 = $area2 = null;

if (isset($categoriasAreas[0])) {
    $categoria1 = $categoriasAreas[0]['id_categoria'];
    $area1 = $categoriasAreas[0]['id_area'];
}

if (isset($categoriasAreas[1])) {
    $categoria2 = $categoriasAreas[1]['id_categoria'];
    $area2 = $categoriasAreas[1]['id_area'];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Jurado</title>
    <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/styles/admin-dashboard.css">
    <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
    <script src="../boostrap/JS/jquery.min.js"></script>
    <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
    <link href="../boostrap/CSS/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <style>
        .modal-body {
            margin: 40px;
        }
    </style>

    <div class="modal-body">
        <h5 class="modal-title" id="modalJuradoLabel">Editar Jurado</h5>
        <form action="../php/Atualizajurados.php" method="POST" id="idCadJurado">
            <input type="hidden" name="id" value="<?= $jurado['id_jurados'] ?>">
            <label for="nome">Nome</label>
            <input type="text" class="form-control" name="nome"
                placeholder="Digite seu nome"
                value="<?= htmlspecialchars($jurado['nome'] ?? '') ?>" required>

            <label for="telefone">Telefone</label>
            <input type="text" class="form-control" name="telefone"
                placeholder="Digite seu telefone"
                value="<?= htmlspecialchars($jurado['telefone'] ?? '') ?>" required>

            <label for="cpf">CPF</label>
            <input type="text" class="form-control" name="cpf"
                placeholder="Digite seu CPF"
                value="<?= htmlspecialchars($jurado['cpf'] ?? '') ?>" required>

            <label for="email">E-mail SIC-CED</label>
            <input type="text" class="form-control" name="email"
                placeholder="Digite seu e-mail"
                value="<?= htmlspecialchars($jurado['email'] ?? '') ?>" required>

            <!-- Categoria 1 -->
            <label for="categoria1">Categoria 1</label>
            <select id="categoria1" class="form-control" name="categoria1" required>
                <option disabled <?= !isset($categoria1) ? 'selected' : '' ?>>Selecione...</option>
                <option value="1" <?= $categoria1 == 1 ? 'selected' : '' ?>>I - Ensino Médio</option>
                <option value="2" <?= $categoria1 == 2 ? 'selected' : '' ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                <option value="3" <?= $categoria1 == 3 ? 'selected' : '' ?>>III - Pesquisa Júnior</option>
                <option value="4" <?= $categoria1 == 4 ? 'selected' : '' ?>>IV - PcD</option>
            </select>

            <div id="area1-container" style="display:none;">
                <label for="area1">Área 1</label>
                <select class="form-control" name="area1">
                    <option disabled <?= !isset($area1) ? 'selected' : '' ?>>Selecione...</option>
                    <option value="1" <?= $area1 == 1 ? 'selected' : '' ?>>Linguagens, Códigos e suas Tecnologias - LC</option>
                    <option value="2" <?= $area1 == 2 ? 'selected' : '' ?>>Matemática e suas Tecnologias - MT</option>
                    <option value="3" <?= $area1 == 3 ? 'selected' : '' ?>>Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
                    <option value="4" <?= $area1 == 4 ? 'selected' : '' ?>>Ciências Humanas e Sociais Aplicadas - CH</option>
                    <option value="5" <?= $area1 == 5 ? 'selected' : '' ?>>Robótica, Automação e Aplicação das TIC</option>
                    <option value="6" <?= $area1 == 6 ? 'selected' : '' ?>>Ensino Fundamental</option>
                    <option value="7" <?= $area1 == 7 ? 'selected' : '' ?>>Ensino Médio</option>
                </select>
            </div>

            <!-- Categoria 2 -->
            <label for="categoria2">Categoria 2</label>
            <select id="categoria2" class="form-control" name="categoria2">
                <option value="" <?= !$categoria2 ? 'selected' : '' ?>>Nenhuma</option>
                <option value="1" <?= $categoria2 == 1 ? 'selected' : '' ?>>I - Ensino Médio</option>
                <option value="2" <?= $categoria2 == 2 ? 'selected' : '' ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                <option value="3" <?= $categoria2 == 3 ? 'selected' : '' ?>>III - Pesquisa Júnior</option>
                <option value="4" <?= $categoria2 == 4 ? 'selected' : '' ?>>IV - PcD</option>
            </select>

            <div id="area2-container" style="display:none;">
                <label for="area2">Área 2</label>
                <select class="form-control" name="area2">
                    <option disabled <?= !isset($area2) ? 'selected' : '' ?>>Selecione...</option>
                    <option value="1" <?= $area2 == 1 ? 'selected' : '' ?>>Linguagens, Códigos e suas Tecnologias - LC</option>
                    <option value="2" <?= $area2 == 2 ? 'selected' : '' ?>>Matemática e suas Tecnologias - MT</option>
                    <option value="3" <?= $area2 == 3 ? 'selected' : '' ?>>Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
                    <option value="4" <?= $area2 == 4 ? 'selected' : '' ?>>Ciências Humanas e Sociais Aplicadas - CH</option>
                    <option value="5" <?= $area2 == 5 ? 'selected' : '' ?>>Robótica, Automação e Aplicação das TIC</option>
                    <option value="6" <?= $area2 == 6 ? 'selected' : '' ?>>Ensino Fundamental</option>
                    <option value="7" <?= $area2 == 7 ? 'selected' : '' ?>>Ensino Médio</option>
                </select>
            </div>


            <input type="submit" value="Atualizar" class="btn btn-success mt-3" style="margin-top:10px;">
            <button type="button" id="btnResetarSenha" class="btn btn-danger mt-3" style="margin-top:10px;">Resetar senha</button>
            <a href="../html/admin-jurados.php" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    </div>
    <div class="modal-footer">

    </div>
    </div>
    </div>
    </div>

    <script>
        // Lógica dos modais e exibição de áreas
        $('#instituicao-tipo').change(function() {
            ($(this).val() === '1') ? $('#campo-ide').slideDown(): $('#campo-ide').slideUp();
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

        $('#associar-categoria').change(function() {
            var categoria = $(this).val();
            if (categoria === '1' || categoria === '2') {
                $('#areajurado').slideDown();
                $('#area2jurado').slideUp();
            } else if (categoria === '4') {
                $('#area2jurado').slideDown();
                $('#areajurado').slideUp();
            } else if (categoria === '3') {
                $('#trabalhojurado').slideDown();
                $('#areajurado, #area2jurado').slideUp();
            }
        });
        $('#area1, #area2').change(function() {
            $('#trabalhojurado').slideDown();
        });
        $('#idCadJurado').submit(function(e) {
            var categoria = $('#jurado-categoria').val();
            var area = null;

            if (categoria === '1' || categoria === '2') {
                area = $('select[name="area1"]').val();
            } else if (categoria === '4') {
                area = $('select[name="area2"]').val();
            } else if (categoria === '3') {
                // Pesquisa Júnior - não tem área, então area fica null
                area = null;
            }

            if ((categoria === '1' || categoria === '2' || categoria === '4') && !area) {
                e.preventDefault();
                alert('Por favor, selecione uma área para essa categoria.');
                return false;
            }

            $('#area-hidden').remove();

            if (area !== null) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'area-hidden',
                    name: 'area',
                    value: area
                }).appendTo('#idCadJurado');
            }
        });

        function toggleArea(categoriaId, areaContainerId) {
            const categoria = $(`#${categoriaId}`).val();
            // Usar == para comparar valor mesmo que tipo diferente
            if (categoria == "1" || categoria == "2" || categoria == "4") {
                $(`#${areaContainerId}`).slideDown();
            } else {
                $(`#${areaContainerId}`).slideUp();
            }
        }

        $(document).ready(function() {
            toggleArea("categoria1", "area1-container");
            toggleArea("categoria2", "area2-container");

            $("#categoria1").change(() => toggleArea("categoria1", "area1-container"));
            $("#categoria2").change(() => toggleArea("categoria2", "area2-container"));
        });

        $('#btnResetarSenha').click(function() {
            const idJurado = $('input[name="id"]').val();
            if (!confirm('Tem certeza que deseja resetar a senha deste jurado?')) {
                return;
            }

            $.ajax({
                url: '../php/ResetarSenha.php',
                type: 'POST',
                data: {
                    id_jurados: idJurado
                },
                dataType: 'json',

                success: function(response) {
                    if (response.status === 'sucesso') {
                        alert(
                            'Senha resetada com sucesso!\n\n' +
                            'Senha temporária: ' + response.senha_temporaria
                        );
                    } else {
                        alert(response.mensagem || 'Não foi possível resetar a senha.');
                    }
                },

                error: function() {
                    alert('Erro ao tentar resetar a senha.');
                }
            });

        });
    </script>
</body>

</html>