<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

$stmt = $pdo->prepare("SELECT * FROM Escolas WHERE id_escolas = ?");
$stmt->execute([$id]);
$escola = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$escola) {
    die('Escola não encontrada');
}

// Busca modalidades cadastradas
$stmtModalidades = $pdo->query("SELECT id, categoria_da_escola FROM categoria_escolas ORDER BY id ASC");
$modalidades = $stmtModalidades->fetchAll(PDO::FETCH_ASSOC);

// Busca campos dinâmicos configurados para escolas
$stmtCampos = $pdo->query("SELECT * FROM Campos_formulario WHERE ativo = 1 AND formulario = 'escola' ORDER BY id ASC");
$campos_escola = $stmtCampos->fetchAll(PDO::FETCH_ASSOC);

// Busca valores salvos dos campos dinâmicos para esta escola
$valores_salvos = [];
if (!empty($campos_escola)) {
    $stmtValores = $pdo->prepare("SELECT id_campo, valor FROM Valores_campos WHERE id_registro = ?");
    $stmtValores->execute([$id]);
    $valores_salvos = $stmtValores->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Escola - SAFC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f6f9;
            color: #212529;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            padding: 28px;
            max-width: 680px;
            margin: 40px auto;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e293b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card-custom">
            <h3 class="fw-bold text-dark mb-4 border-bottom pb-3">Editar Instituição</h3>

            <form method="POST" action="Atualizaescolas.php">
                <input type="hidden" name="id" value="<?= $escola['id_escolas'] ?>">
                
                <!-- SEÇÃO DADOS FIXOS -->
                <div class="mb-3">
                    <label for="modalidade" class="form-label">Modalidade <span class="text-danger">*</span></label>
                    <select name="Modalidade" id="modalidade" class="form-control" required>
                        <option value="" disabled>Selecione...</option>
                        <?php foreach ($modalidades as $mod): ?>
                            <option value="<?= $mod['id'] ?>" <?= ((int)$escola['id_categoria_escola'] === (int)$mod['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mod['categoria_da_escola']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome da Instituição <span class="text-danger">*</span></label>
                    <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($escola['nome'] ?? '') ?>" placeholder="Digite o nome da instituição" required>
                </div>

                <div class="mb-3">
                    <label for="municipio" class="form-label">Município <span class="text-danger">*</span></label>
                    <select name="municipio" id="municipio" class="form-control" required>
                        <option value="" disabled>Selecione...</option>
                        <?php
                        $municipios = [
                            '1' => 'Caridade',
                            '2' => 'Canindé',
                            '3' => 'Paramoti',
                            '4' => 'General Sampaio',
                            '5' => 'Santa Quitéria',
                            '6' => 'Itatira'
                        ];
                        foreach ($municipios as $key => $value) {
                            $selected = ($escola['municipio'] === $value) ? 'selected' : '';
                            echo "<option value='$key' $selected>$value</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- SEÇÃO CAMPOS DINÂMICOS -->
                <?php if (!empty($campos_escola)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-dark mb-3">Informações Adicionais (Campos Dinâmicos)</h6>
                        <?php foreach ($campos_escola as $campo): 
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
                    <a href="../html/admin-escolas.php" class="btn btn-secondary px-4 fw-semibold">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>