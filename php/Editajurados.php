<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

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
    <title>Editar Jurado - SAFC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
    <link rel="stylesheet" href="../boostrap/CSS/bootstrap-icons.css">
    <script src="../boostrap/JS/jquery.min.js"></script>
    <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            padding: 32px;
            max-width: 680px;
            margin: 40px auto;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: #000000 !important;
            margin-bottom: 6px;
        }
        .form-control, .form-select, select.form-control {
            height: 44px;
            border-radius: 8px;
            border: 1.5px solid #94a3b8;
            background-color: #ffffff;
            color: #1e293b;
            font-size: 0.9rem;
            padding: 10px 14px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .form-control:focus, .form-select:focus, select.form-control:focus {
            border-color: #3b7e4a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 126, 74, 0.18);
            background-color: #ffffff;
        }
        .btn-success {
            background-color: #3b7e4a !important;
            border-color: #3b7e4a !important;
            border-radius: 8px;
            padding: 9px 20px;
        }
        .btn-success:hover {
            background-color: #2c633a !important;
            border-color: #2c633a !important;
        }
        .btn-secondary {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: #ffffff !important;
            border-radius: 8px;
            padding: 9px 20px;
        }
        .btn-secondary:hover {
            background-color: #5c636a !important;
            border-color: #565e64 !important;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card-custom">
            <h3 class="fw-bold text-dark mb-4 border-bottom pb-3">Editar Jurado</h3>

            <form action="../php/Atualizajurados.php" method="POST" id="idCadJurado">
                <input type="hidden" name="id" value="<?= $jurado['id_jurados'] ?>">

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome"
                        placeholder="Digite seu nome"
                        value="<?= htmlspecialchars($jurado['nome'] ?? '') ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="telefone" name="telefone"
                            placeholder="Digite seu telefone"
                            value="<?= htmlspecialchars($jurado['telefone'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cpf" name="cpf"
                            placeholder="Digite seu CPF"
                            value="<?= htmlspecialchars($jurado['cpf'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail SIC-CED <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                        placeholder="Digite seu e-mail"
                        value="<?= htmlspecialchars($jurado['email'] ?? '') ?>" required>
                </div>

                <div class="border-top pt-3 mt-4">
                    <h6 class="fw-bold text-dark mb-3">Atribuição de Categorias e Áreas</h6>

                    <!-- Categoria 1 -->
                    <div class="mb-3">
                        <label for="categoria1" class="form-label">Categoria 1 <span class="text-danger">*</span></label>
                        <select id="categoria1" class="form-control" name="categoria1" required>
                            <option disabled <?= !isset($categoria1) ? 'selected' : '' ?>>Selecione...</option>
                            <option value="1" <?= $categoria1 == 1 ? 'selected' : '' ?>>I - Ensino Médio</option>
                            <option value="2" <?= $categoria1 == 2 ? 'selected' : '' ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                            <option value="3" <?= $categoria1 == 3 ? 'selected' : '' ?>>III - Pesquisa Júnior</option>
                            <option value="4" <?= $categoria1 == 4 ? 'selected' : '' ?>>IV - PcD</option>
                        </select>
                    </div>

                    <div id="area1-container" class="mb-3" style="display:none;">
                        <label for="area1" class="form-label">Área 1</label>
                        <select class="form-control" id="area1" name="area1">
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
                    <div class="mb-3">
                        <label for="categoria2" class="form-label">Categoria 2</label>
                        <select id="categoria2" class="form-control" name="categoria2">
                            <option value="" <?= !$categoria2 ? 'selected' : '' ?>>Nenhuma</option>
                            <option value="1" <?= $categoria2 == 1 ? 'selected' : '' ?>>I - Ensino Médio</option>
                            <option value="2" <?= $categoria2 == 2 ? 'selected' : '' ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                            <option value="3" <?= $categoria2 == 3 ? 'selected' : '' ?>>III - Pesquisa Júnior</option>
                            <option value="4" <?= $categoria2 == 4 ? 'selected' : '' ?>>IV - PcD</option>
                        </select>
                    </div>

                    <div id="area2-container" class="mb-3" style="display:none;">
                        <label for="area2" class="form-label">Área 2</label>
                        <select class="form-control" id="area2" name="area2">
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
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-success px-4 fw-semibold">Salvar Alterações</button>
                    <button type="button" id="btnResetarSenha" class="btn btn-outline-danger fw-semibold">Resetar Senha</button>
                    <a href="../html/admin-jurados.php" class="btn btn-secondary px-4 fw-semibold ms-auto">Cancelar</a>
                </div>
            </form>
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