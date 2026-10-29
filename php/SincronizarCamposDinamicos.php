<?php
/**
 * Cria e sincroniza colunas SQL para os campos dinâmicos do formulário de escolas.
 *
 * O nome da coluna é derivado do ID do campo (`campo_dinamico_ID`), evitando colisões
 * com colunas fixas e mantendo o nome estável mesmo se o campo for renomeado.
 * A tabela Valores_campos continua sendo mantida como armazenamento compatível
 * com a lógica atual de formulários e critérios de desempate.
 */

if (!function_exists('sincronizarColunasDinamicasEscola')) {
    function sincronizarColunasDinamicasEscola(PDO $pdo): array
    {
        $stmtCampos = $pdo->query(
            "SELECT id, formulario, nome_campo, identificador, tipo_campo, obrigatorio, ativo
             FROM Campos_formulario
             WHERE formulario = 'escola'
             ORDER BY id ASC"
        );
        $campos = $stmtCampos->fetchAll(PDO::FETCH_ASSOC);

        $stmtColunas = $pdo->query(
            "SELECT COLUMN_NAME, DATA_TYPE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = LOWER('Escolas')"
        );

        $colunasExistentes = [];
        foreach ($stmtColunas->fetchAll(PDO::FETCH_ASSOC) as $coluna) {
            $colunasExistentes[strtolower((string) $coluna['COLUMN_NAME'])] = $coluna['DATA_TYPE'];
        }

        if (!$colunasExistentes) {
            throw new RuntimeException('A tabela Escolas não foi encontrada no banco de dados atual.');
        }

        $camposPorId = [];
        $colunasCriadas = [];

        foreach ($campos as $campo) {
            $idCampo = (int) $campo['id'];

            // Nome determinístico e seguro: não depende do nome do campo nem de identificadores informados.
            $colunaSql = 'campo_dinamico_' . $idCampo;

            $tipoCampo = (string) ($campo['tipo_campo'] ?? 'texto');
            $definicaoSql = match ($tipoCampo) {
                'numero' => 'DECIMAL(15,4) NULL DEFAULT NULL',
                'data' => 'DATE NULL DEFAULT NULL',
                'sim_nao' => 'TINYINT(1) NULL DEFAULT NULL',
                default => 'VARCHAR(255) NULL DEFAULT NULL',
            };

            $chaveColuna = strtolower($colunaSql);
            if (!isset($colunasExistentes[$chaveColuna])) {
                // $colunaSql foi construído acima a partir de caracteres autorizados.
                $pdo->exec("ALTER TABLE `Escolas` ADD COLUMN `{$colunaSql}` {$definicaoSql}");
                $colunasExistentes[$chaveColuna] = strtolower(strtok($definicaoSql, ' '));
                $colunasCriadas[] = $colunaSql;
            }

            $campo['coluna_sql'] = $colunaSql;
            $camposPorId[$idCampo] = $campo;
        }

        // Migra para as colunas novas os valores que já estavam em Valores_campos.
        // Só copia dados válidos e não sobrescreve um valor que já exista na coluna SQL.
        $normalizarParaColuna = static function ($valor, string $tipoCampo) {
            if ($valor === null || is_array($valor) || is_object($valor)) {
                return null;
            }

            $texto = trim((string) $valor);
            if ($texto === '') {
                return null;
            }

            if ($tipoCampo === 'numero') {
                $numero = str_replace(',', '.', $texto);
                return is_numeric($numero) ? $numero : null;
            }

            if ($tipoCampo === 'data') {
                $data = DateTime::createFromFormat('!Y-m-d', $texto);
                $erros = DateTime::getLastErrors();
                if (
                    $data === false ||
                    ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0)) ||
                    $data->format('Y-m-d') !== $texto
                ) {
                    return null;
                }
                return $texto;
            }

            if ($tipoCampo === 'sim_nao') {
                $normalizado = function_exists('mb_strtolower')
                    ? mb_strtolower($texto, 'UTF-8')
                    : strtolower($texto);
                if (in_array($normalizado, ['1', 'sim', 's', 'yes', 'y'], true)) {
                    return 1;
                }
                if (in_array($normalizado, ['0', 'não', 'nao', 'n', 'no'], true)) {
                    return 0;
                }
                return null;
            }

            return strlen($texto) <= 255 ? $texto : substr($texto, 0, 255);
        };

        $stmtValores = $pdo->prepare(
            'SELECT id_registro, valor FROM Valores_campos WHERE id_campo = ?'
        );

        foreach ($camposPorId as $idCampo => $campo) {
            $stmtValores->execute([$idCampo]);
            $sqlColuna = $campo['coluna_sql'];
            $tipoCampo = (string) ($campo['tipo_campo'] ?? 'texto');
            $stmtAtualiza = $pdo->prepare(
                "UPDATE `Escolas`
                 SET `{$sqlColuna}` = ?
                 WHERE `id_escolas` = ? AND `{$sqlColuna}` IS NULL"
            );

            while ($linha = $stmtValores->fetch(PDO::FETCH_ASSOC)) {
                $valor = $normalizarParaColuna($linha['valor'] ?? null, $tipoCampo);
                if ($valor === null) {
                    continue;
                }
                try {
                    $stmtAtualiza->execute([$valor, (int) $linha['id_registro']]);
                } catch (PDOException $e) {
                    // Um valor legado incompatível não deve impedir a abertura das configurações.
                    error_log(
                        'Não foi possível migrar o valor do campo dinâmico ' . $idCampo .
                        ' para Escolas.' . $sqlColuna . ': ' . $e->getMessage()
                    );
                }
            }
            $stmtValores->closeCursor();
        }

        return [
            'campos' => $camposPorId,
            'colunas_criadas' => $colunasCriadas,
        ];
    }
}
