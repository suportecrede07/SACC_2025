<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

$stmt = $pdo->prepare("
    SELECT 
        titulo,
        id_escolas,
        id_jurados,
        id_areas,
        id_categoria,
        ordem
    FROM Trabalhos
    WHERE id_trabalhos = ?
");
$stmt->execute([$id]);
$trabalho = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabalho) {
    die('Trabalho não encontrado!');
}

$stmtEscolas = $pdo->query("SELECT id_escolas, nome FROM Escolas ORDER BY nome");
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

$stmtModalidades = $pdo->query("SELECT id, categoria_da_escola FROM categoria_escolas ORDER BY id ASC");
$modalidades = $stmtModalidades->fetchAll(PDO::FETCH_ASSOC);

// Busca os campos dinâmicos ativos para trabalhos
$stmtCampos = $pdo->query("SELECT * FROM Campos_formulario WHERE ativo = 1 AND formulario = 'trabalho' ORDER BY id ASC");
$campos_trabalho = $stmtCampos->fetchAll(PDO::FETCH_ASSOC);

// Busca os valores gravados para este trabalho
$valores_salvos = [];
if (!empty($campos_trabalho)) {
    $stmtValores = $pdo->prepare("SELECT id_campo, valor FROM Valores_campos WHERE id_registro = ?");
    $stmtValores->execute([$id]);
    $valores_salvos = $stmtValores->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Trabalho - SAFC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
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
            <h3 class="fw-bold text-dark mb-4 border-bottom pb-3">Editar Trabalho</h3>

            <form action="../php/Atualizatrabalho.php" method="POST" id="formEditaTrabalho">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título do Trabalho <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required value="<?= htmlspecialchars($trabalho['titulo']) ?>">
                </div>

                <div class="mb-3">
                    <label for="escola" class="form-label">Escola <span class="text-danger">*</span></label>
                    <select name="escola" id="escola" class="form-control" required>
                        <option value="">Selecione a Escola</option>
                        <?php foreach($escolas as $esc): ?>
                            <option value="<?= $esc['id_escolas'] ?>" <?= $trabalho['id_escolas'] == $esc['id_escolas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($esc['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoria <span class="text-danger">*</span></label>
                    <select name="categoria" id="categoria" class="form-control" required>
                        <option value="">Selecione a Categoria</option>
                        <option value="1" <?= $trabalho['id_categoria'] == 1 ? 'selected' : '' ?>>I - Ensino Médio</option>
                        <option value="2" <?= $trabalho['id_categoria'] == 2 ? 'selected' : '' ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
                        <option value="3" <?= $trabalho['id_categoria'] == 3 ? 'selected' : '' ?>>III - Pesquisa Júnior</option>
                        <option value="4" <?= $trabalho['id_categoria'] == 4 ? 'selected' : '' ?>>IV - PcD</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="area" class="form-label">Área</label>
                    <select name="area" id="area" class="form-control">
                        <option value="">Selecione a Área</option>
                        <option value="1" <?= $trabalho['id_areas'] == 1 ? 'selected' : '' ?>>Linguagens, Códigos e suas Tecnologias - LC</option>
                        <option value="2" <?= $trabalho['id_areas'] == 2 ? 'selected' : '' ?>>Matemática e suas Tecnologias - MT</option>
                        <option value="3" <?= $trabalho['id_areas'] == 3 ? 'selected' : '' ?>>Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
                        <option value="4" <?= $trabalho['id_areas'] == 4 ? 'selected' : '' ?>>Ciências Humanas e Sociais Aplicadas - CH</option>
                        <option value="5" <?= $trabalho['id_areas'] == 5 ? 'selected' : '' ?>>Robótica, Automação e Aplicação das TIC</option>
                        <option value="6" <?= $trabalho['id_areas'] == 6 ? 'selected' : '' ?>>Ensino Fundamental</option>
                        <option value="7" <?= $trabalho['id_areas'] == 7 ? 'selected' : '' ?>>Ensino Médio</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="ordem" class="form-label">Ordem do Trabalho <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ordem" name="ordem" required value="<?= htmlspecialchars($trabalho['ordem']) ?>">
                </div>

                <!-- SEÇÃO CAMPOS DINÂMICOS ADICIONAIS -->
                <?php if (!empty($campos_trabalho)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-dark mb-3">Informações Adicionais (Campos Dinâmicos)</h6>
                        <?php foreach ($campos_trabalho as $campo): 
                            $valorAtual = $valores_salvos[$campo['id']] ?? '';
                        ?>
                            <div class="mb-3">
                                <label for="campo_dinamico_<?= $campo['id'] ?>" class="form-label">
                                    <?= htmlspecialchars($campo['nome_campo']) ?>
                                    <?= $campo['obrigatorio'] ? '<span class="text-danger">*</span>' : '' ?>
                                </label>

                                <?php if ($campo['tipo_campo'] === 'modalidade'): ?>
                                    <select id="campo_dinamico_<?= $campo['id'] ?>" class="form-control" name="campos_dinamicos[<?= $campo['id'] ?>]" <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($modalidades as $mod): ?>
                                            <option value="<?= htmlspecialchars($mod['id']) ?>" <?= ((string)$valorAtual === (string)$mod['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mod['categoria_da_escola']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php elseif ($campo['tipo_campo'] === 'sim_nao'): ?>
                                    <select id="campo_dinamico_<?= $campo['id'] ?>" class="form-control" name="campos_dinamicos[<?= $campo['id'] ?>]" <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                                        <option value="">Selecione...</option>
                                        <option value="1" <?= ((string)$valorAtual === '1') ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= ((string)$valorAtual === '0') ? 'selected' : '' ?>>Não</option>
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
                                        value="<?= htmlspecialchars($valorAtual) ?>"
                                        placeholder="Insira <?= htmlspecialchars($campo['nome_campo']) ?>"
                                        <?= $campo['obrigatorio'] ? 'required' : '' ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4 pt-2 border-top">
                    <button type="submit" class="btn btn-success px-4 fw-semibold">Salvar Alterações</button>
                    <a href="../html/admin-trabalhos.php" class="btn btn-secondary px-4 fw-semibold">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
