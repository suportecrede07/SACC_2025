<?php
/**
 * Calcula a classificação dos trabalhos usando critérios/pesos configurados
 * em Criterios_avaliacao e regras de desempate de Criterios_desempate.
 *
 * Regras de desempate aceitas:
 * - Nota de critério de avaliação ativo;
 * - Campo dinâmico ativo do formulário de escola;
 * - Campo dinâmico ativo do formulário de trabalho.
 *
 * A prioridade e a direção ASC/DESC são lidas do banco, não ficam fixas aqui.
 */
/**
 * Retorna os critérios ativos na ordem configurada, padronizando seus tipos.
 * Usado tanto pela classificação quanto pelos relatórios de avaliação.
 */
function obterCriteriosAvaliacaoAtivos(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, nome, peso, nota_minima, nota_maxima, ordem
         FROM Criterios_avaliacao
         WHERE ativo = 1
         ORDER BY ordem ASC, id ASC"
    );

    $criterios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($criterios as &$criterio) {
        $criterio['id'] = (int) $criterio['id'];
        $criterio['nome'] = (string) $criterio['nome'];
        $criterio['peso'] = (float) $criterio['peso'];
        $criterio['ordem'] = (int) $criterio['ordem'];
    }
    unset($criterio);

    return $criterios;
}

/**
 * Calcula a média ponderada de uma avaliação individual usando os critérios
 * ativos e seus pesos atuais. Notas ausentes e pesos não positivos são ignorados.
 */
function calcularMediaPonderadaCriteriosAtivos(array $notasPorCriterio, array $criterios): ?float
{
    $somaNotas = 0.0;
    $somaPesos = 0.0;

    foreach ($criterios as $criterio) {
        $idCriterio = (int) $criterio['id'];
        $peso = (float) $criterio['peso'];

        if ($peso <= 0 || !array_key_exists($idCriterio, $notasPorCriterio) || $notasPorCriterio[$idCriterio] === null) {
            continue;
        }

        $somaNotas += (float) $notasPorCriterio[$idCriterio] * $peso;
        $somaPesos += $peso;
    }

    return $somaPesos > 0 ? ($somaNotas / $somaPesos) * 10 : null;
}

function obterClassificacaoTrabalhos(
    PDO $pdo,
    ?int $idCategoria = null,
    ?int $idArea = null,
    bool $incluirSemNota = false
): array {
    // 1. Carrega critérios ativos pela função compartilhada com os relatórios.
    $criterios = obterCriteriosAvaliacaoAtivos($pdo);

    if (!$criterios) {
        throw new RuntimeException('Não há critérios de avaliação ativos.');
    }

    $criteriosPorId = [];
    $idsCriterios = [];
    $temPesoPositivo = false;

    foreach ($criterios as $criterio) {
        $id = (int) $criterio['id'];
        $criteriosPorId[$id] = $criterio;
        $idsCriterios[] = $id;

        if ($criterio['peso'] > 0) {
            $temPesoPositivo = true;
        }
    }

    if (!$temPesoPositivo) {
        throw new RuntimeException('Não há critérios ativos com peso maior que zero.');
    }

    // 2. Carrega os trabalhos filtrados por categoria e/ou área.
    $where = [];
    $parametros = [];

    if ($idCategoria !== null) {
        $where[] = 't.id_categoria = ?';
        $parametros[] = $idCategoria;
    }
    if ($idArea !== null) {
        $where[] = 't.id_areas = ?';
        $parametros[] = $idArea;
    }

    $sqlTrabalhos =
        "SELECT
            t.id_trabalhos AS id_trabalho,
            t.titulo,
            t.id_escolas AS id_escola,
            t.id_categoria,
            t.id_areas,
            t.ordem,
            e.nome AS escola,
            e.id_categoria_escola,
            ce.categoria_da_escola AS modalidade,
            c.nome_categoria AS categoria,
            a.nome_area AS area
         FROM Trabalhos t
         LEFT JOIN Escolas e ON e.id_escolas = t.id_escolas
         LEFT JOIN Categoria_escolas ce ON ce.id = e.id_categoria_escola
         LEFT JOIN Categorias c ON c.id_categoria = t.id_categoria
         LEFT JOIN Areas a ON a.id_area = t.id_areas";

    if ($where) {
        $sqlTrabalhos .= ' WHERE ' . implode(' AND ', $where);
    }
    $sqlTrabalhos .= ' ORDER BY t.ordem ASC, t.id_trabalhos ASC';

    $stmtTrabalhos = $pdo->prepare($sqlTrabalhos);
    $stmtTrabalhos->execute($parametros);
    $trabalhosBanco = $stmtTrabalhos->fetchAll(PDO::FETCH_ASSOC);

    if (!$trabalhosBanco) {
        return [];
    }

    // 3. Busca somente notas para trabalhos filtrados e critérios ativos.
    $idsTrabalhos = array_map(
        static fn(array $trabalho): int => (int) $trabalho['id_trabalho'],
        $trabalhosBanco
    );

    $placeholdersCriterios = implode(',', array_fill(0, count($idsCriterios), '?'));
    $placeholdersTrabalhos = implode(',', array_fill(0, count($idsTrabalhos), '?'));

    $sqlAvaliacoes =
        "SELECT id_trabalho, id_jurado, criterio, nota
         FROM Avaliacoes
         WHERE criterio IN ($placeholdersCriterios)
           AND id_trabalho IN ($placeholdersTrabalhos)";

    $stmtAvaliacoes = $pdo->prepare($sqlAvaliacoes);
    $stmtAvaliacoes->execute(array_merge($idsCriterios, $idsTrabalhos));

    $notasPorTrabalho = [];
    $juradosPresentesPorTrabalho = [];

    while ($linha = $stmtAvaliacoes->fetch(PDO::FETCH_ASSOC)) {
        $idTrabalho = (int) $linha['id_trabalho'];
        $idJurado = (int) $linha['id_jurado'];
        $idCriterio = (int) $linha['criterio'];

        $notasPorTrabalho[$idTrabalho][$idJurado][$idCriterio] = (float) $linha['nota'];
        $juradosPresentesPorTrabalho[$idTrabalho][$idJurado] = true;
    }

    $juradosPorTrabalho = [];
    foreach ($juradosPresentesPorTrabalho as $idTrabalho => $juradosPresentes) {
        $juradosPorTrabalho[(int) $idTrabalho] = count($juradosPresentes);
    }

    // 4. Obtém a média por critério dos dois primeiros jurados por ID.
    // Essa média é usada quando uma regra de desempate seleciona um critério.
    $mediasPorTrabalho = [];
    foreach ($notasPorTrabalho as $idTrabalho => $notasDosJurados) {
        $idsJurados = array_keys($notasDosJurados);
        sort($idsJurados, SORT_NUMERIC);
        $idsJuradosConsiderados = array_slice($idsJurados, 0, 2);

        $notasJurado1 = isset($idsJuradosConsiderados[0])
            ? $notasDosJurados[$idsJuradosConsiderados[0]]
            : [];
        $notasJurado2 = isset($idsJuradosConsiderados[1])
            ? $notasDosJurados[$idsJuradosConsiderados[1]]
            : [];

        foreach ($idsCriterios as $idCriterio) {
            $nota1 = $notasJurado1[$idCriterio] ?? null;
            $nota2 = $notasJurado2[$idCriterio] ?? null;

            if ($nota1 !== null && $nota2 !== null) {
                $mediasPorTrabalho[(int) $idTrabalho][$idCriterio] = ($nota1 + $nota2) / 2;
            } elseif ($nota1 !== null) {
                $mediasPorTrabalho[(int) $idTrabalho][$idCriterio] = $nota1;
            } elseif ($nota2 !== null) {
                $mediasPorTrabalho[(int) $idTrabalho][$idCriterio] = $nota2;
            } else {
                $mediasPorTrabalho[(int) $idTrabalho][$idCriterio] = null;
            }
        }
    }

    // 5. Mesmo cálculo do dashboard antigo: média ponderada por jurado,
    // multiplicada por 10; depois média dos dois primeiros jurados por ID.
    $calculaMediaPonderadaJurado = static function (array $notasJurado) use ($criterios): ?float {
        return calcularMediaPonderadaCriteriosAtivos($notasJurado, $criterios);
    };

    // 6. Busca a fila de desempate configurada em admin-config.php.
    // Cada regra ativa aponta para um critério de avaliação ou campo dinâmico.
    $sqlRegras =
        "SELECT
            cd.id,
            cd.ordem_prioridade,
            cd.nome_regra,
            cd.id_criterio_avaliacao,
            cd.id_campo_formulario,
            cd.direcao,
            cf.formulario,
            cf.tipo_campo,
            cf.ativo AS campo_ativo
         FROM Criterios_desempate cd
         LEFT JOIN Campos_formulario cf ON cf.id = cd.id_campo_formulario
         WHERE cd.ativo = 1
         ORDER BY cd.ordem_prioridade ASC, cd.id ASC";

    $stmtRegras = $pdo->query($sqlRegras);
    $regras = [];
    $idsCamposDesempate = [];

    foreach ($stmtRegras->fetchAll(PDO::FETCH_ASSOC) as $regra) {
        if ($regra['id_criterio_avaliacao'] !== null) {
            $idCriterioRegra = (int) $regra['id_criterio_avaliacao'];

            // Não usa regra que aponta para critério inativo ou inexistente.
            if (!isset($criteriosPorId[$idCriterioRegra])) {
                continue;
            }

            $regra['tipo_regra'] = 'criterio';
            $regra['id_criterio_avaliacao'] = $idCriterioRegra;
            $regra['tipo_campo'] = 'numero';
            $regras[] = $regra;
            continue;
        }

        if (
            $regra['id_campo_formulario'] !== null &&
            (int) ($regra['campo_ativo'] ?? 0) === 1 &&
            in_array($regra['formulario'], ['escola', 'trabalho'], true)
        ) {
            $regra['tipo_regra'] = 'campo';
            $regra['id_campo_formulario'] = (int) $regra['id_campo_formulario'];
            $idsCamposDesempate[] = $regra['id_campo_formulario'];
            $regras[] = $regra;
        }
    }

    // 7. Carrega em lote valores dos campos dinâmicos que participam no desempate.
    $valoresCampos = [];
    $idsCamposDesempate = array_values(array_unique($idsCamposDesempate));

    if ($idsCamposDesempate) {
        $placeholdersCampos = implode(',', array_fill(0, count($idsCamposDesempate), '?'));
        $stmtValores = $pdo->prepare(
            "SELECT id_campo, id_registro, valor
             FROM valores_campos
             WHERE id_campo IN ($placeholdersCampos)"
        );
        $stmtValores->execute($idsCamposDesempate);

        while ($linha = $stmtValores->fetch(PDO::FETCH_ASSOC)) {
            $valoresCampos[(int) $linha['id_campo']][(int) $linha['id_registro']] = $linha['valor'];
        }
    }

    // Converte valores conforme o tipo do campo, para comparar datas/números/textos corretamente.
    $normalizarValorDesempate = static function ($valor, string $tipoCampo) {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $texto = trim((string) $valor);
        switch ($tipoCampo) {
            case 'numero':
                $numero = str_replace(',', '.', $texto);
                return is_numeric($numero) ? (float) $numero : null;

            case 'sim_nao':
                $normalizado = function_exists('mb_strtolower')
                    ? mb_strtolower($texto, 'UTF-8')
                    : strtolower($texto);
                if (in_array($normalizado, ['sim', 's', 'yes', 'y', '1'], true)) {
                    return 1;
                }
                if (in_array($normalizado, ['não', 'nao', 'n', 'no', '0'], true)) {
                    return 0;
                }
                return null;

            case 'data':
                $data = strtotime($texto);
                return $data === false ? null : $data;

            case 'texto':
            case 'modalidade':
            default:
                return function_exists('mb_strtolower')
                    ? mb_strtolower($texto, 'UTF-8')
                    : strtolower($texto);
        }
    };

    // Retorna a comparação de dois valores seguindo ASC/DESC. Valores ausentes
    // ficam depois dos preenchidos; dois ausentes permitem avaliar a próxima regra.
    $compararValoresDesempate = static function (
        $valorA,
        $valorB,
        string $tipoCampo,
        string $direcao
    ) use ($normalizarValorDesempate): int {
        $a = $normalizarValorDesempate($valorA, $tipoCampo);
        $b = $normalizarValorDesempate($valorB, $tipoCampo);

        if ($a === null && $b !== null) {
            return 1;
        }
        if ($a !== null && $b === null) {
            return -1;
        }
        if ($a === null && $b === null) {
            return 0;
        }

        if (is_string($a) && is_string($b)) {
            $comparacao = strnatcasecmp($a, $b);
        } else {
            $comparacao = $a <=> $b;
        }

        return strtoupper($direcao) === 'ASC'
            ? ($comparacao <=> 0)
            : (-1 * ($comparacao <=> 0));
    };

    // Obtém o valor de uma regra para um trabalho.
    $obterValorRegraDesempate = static function (array $trabalho, array $regra) use (
        $mediasPorTrabalho,
        $valoresCampos
    ) {
        $idTrabalho = (int) $trabalho['id_trabalho'];

        if ($regra['tipo_regra'] === 'criterio') {
            $idCriterio = (int) $regra['id_criterio_avaliacao'];
            return $mediasPorTrabalho[$idTrabalho][$idCriterio] ?? null;
        }

        $idCampo = (int) $regra['id_campo_formulario'];
        if ($regra['formulario'] === 'trabalho') {
            $idRegistro = $idTrabalho;
        } elseif ($regra['formulario'] === 'escola') {
            $idRegistro = isset($trabalho['id_escola']) ? (int) $trabalho['id_escola'] : null;
        } else {
            $idRegistro = null;
        }

        if ($idRegistro === null) {
            return null;
        }
        return $valoresCampos[$idCampo][$idRegistro] ?? null;
    };

    // 8. Calcula a nota final por trabalho.
    $trabalhosParaOrdenar = [];
    foreach ($trabalhosBanco as $trabalho) {
        $idTrabalho = (int) $trabalho['id_trabalho'];
        $notasDosJurados = $notasPorTrabalho[$idTrabalho] ?? [];
        $idsJurados = array_keys($notasDosJurados);
        sort($idsJurados, SORT_NUMERIC);
        $idsJuradosConsiderados = array_slice($idsJurados, 0, 2);

        $mediaJurado1 = isset($idsJuradosConsiderados[0])
            ? $calculaMediaPonderadaJurado($notasDosJurados[$idsJuradosConsiderados[0]])
            : null;
        $mediaJurado2 = isset($idsJuradosConsiderados[1])
            ? $calculaMediaPonderadaJurado($notasDosJurados[$idsJuradosConsiderados[1]])
            : null;

        if ($mediaJurado1 !== null && $mediaJurado2 !== null) {
            $notaFinal = ($mediaJurado1 + $mediaJurado2) / 2;
        } elseif ($mediaJurado1 !== null) {
            $notaFinal = $mediaJurado1;
        } elseif ($mediaJurado2 !== null) {
            $notaFinal = $mediaJurado2;
        } elseif (!$incluirSemNota) {
            continue;
        } else {
            $notaFinal = null;
        }

        $trabalho['id_trabalho'] = $idTrabalho;
        $trabalho['id_escola'] = $trabalho['id_escola'] !== null
            ? (int) $trabalho['id_escola']
            : null;
        $trabalho['media_final'] = $notaFinal !== null
            ? round($notaFinal, 2, PHP_ROUND_HALF_UP)
            : null;
        $trabalho['jurados'] = $juradosPorTrabalho[$idTrabalho] ?? 0;
        $trabalho['jurado1'] = [
            'id' => $idsJuradosConsiderados[0] ?? null,
            'media_ponderada' => $mediaJurado1,
            'criterios' => $notasDosJurados[$idsJuradosConsiderados[0] ?? -1] ?? [],
        ];
        $trabalho['jurado2'] = [
            'id' => $idsJuradosConsiderados[1] ?? null,
            'media_ponderada' => $mediaJurado2,
            'criterios' => $notasDosJurados[$idsJuradosConsiderados[1] ?? -1] ?? [],
        ];
        $trabalho['criterios_medios'] = $mediasPorTrabalho[$idTrabalho] ?? [];
        $trabalho['criterio_desempate'] = null;
        $trabalhosParaOrdenar[] = $trabalho;
    }

    if (!$trabalhosParaOrdenar) {
        return [];
    }

    // 9. FUNÇÃO DE DESEMPATE: compara primeiro a nota final e, quando igual,
    // percorre as regras ativas de admin-config pela ordem_prioridade configurada.
    // Também devolve a primeira regra que efetivamente diferenciou os trabalhos.
    $compararTrabalhosComDesempate = static function (array $a, array $b) use (
        $regras,
        $obterValorRegraDesempate,
        $compararValoresDesempate
    ): array {
        // Notas ausentes são tratadas como zero apenas para manter a ordenação
        // preliminar já usada no dashboard. A ausência não fabrica um desempate.
        $notaA = $a['media_final'] === null ? 0.0 : (float) $a['media_final'];
        $notaB = $b['media_final'] === null ? 0.0 : (float) $b['media_final'];
        $comparacaoNota = $notaB <=> $notaA;
        if ($comparacaoNota !== 0) {
            return [$comparacaoNota, null];
        }

        foreach ($regras as $regra) {
            $valorA = $obterValorRegraDesempate($a, $regra);
            $valorB = $obterValorRegraDesempate($b, $regra);
            $tipoCampo = (string) ($regra['tipo_campo'] ?? 'texto');
            $direcao = strtoupper((string) ($regra['direcao'] ?? 'DESC'));
            if (!in_array($direcao, ['ASC', 'DESC'], true)) {
                $direcao = 'DESC';
            }

            $comparacao = $compararValoresDesempate($valorA, $valorB, $tipoCampo, $direcao);
            if ($comparacao !== 0) {
                return [$comparacao, $regra];
            }
        }

        // Se nenhuma regra configurada diferenciar os trabalhos, permanecem empatados.
        return [0, null];
    };

    usort($trabalhosParaOrdenar, static function (array $a, array $b) use ($compararTrabalhosComDesempate): int {
        return $compararTrabalhosComDesempate($a, $b)[0];
    });

    // 10. Define posições. Se nota e todas as regras forem iguais, compartilham posição.
    $resultado = [];
    $posicaoAtual = 1;

    foreach ($trabalhosParaOrdenar as $indice => $trabalho) {
        if ($indice > 0) {
            $comparacaoAnterior = $compararTrabalhosComDesempate(
                $trabalhosParaOrdenar[$indice - 1],
                $trabalho
            );
            if ($comparacaoAnterior[0] !== 0) {
                $posicaoAtual = $indice + 1;
            }
        }

        $resultado[] = [
            'posicao' => $posicaoAtual,
            'id_trabalho' => (int) $trabalho['id_trabalho'],
            'id_escola' => $trabalho['id_escola'] !== null ? (int) $trabalho['id_escola'] : null,
            'titulo' => (string) $trabalho['titulo'],
            'escola' => $trabalho['escola'] !== null && $trabalho['escola'] !== ''
                ? (string) $trabalho['escola']
                : 'Não informada',
            'Modalidade' => (string) ($trabalho['modalidade'] ?? ''),
            'categoria' => (string) ($trabalho['categoria'] ?? '-'),
            'area' => (string) ($trabalho['area'] ?? 'Sem área'),
            'media_final' => $trabalho['media_final'] !== null
                ? (float) $trabalho['media_final']
                : null,
            'jurados' => (int) $trabalho['jurados'],
            'jurados_detalhes' => [
                1 => $trabalho['jurado1'],
                2 => $trabalho['jurado2'],
            ],
            'criterios' => $trabalho['criterios_medios'],
            'criterio_desempate' => null,
        ];
    }

    // Associa à linha do trabalho melhor classificado a regra que o colocou à frente
    // do próximo trabalho com a mesma nota final (para poder exibi-la em tabelas/PDF).
    for ($i = 0; $i < count($trabalhosParaOrdenar) - 1; $i++) {
        $atual = $trabalhosParaOrdenar[$i];
        $proximo = $trabalhosParaOrdenar[$i + 1];

        $notaAtual = $atual['media_final'] === null ? 0.0 : (float) $atual['media_final'];
        $notaProxima = $proximo['media_final'] === null ? 0.0 : (float) $proximo['media_final'];
        if ($notaAtual === $notaProxima) {
            [, $regraUsada] = $compararTrabalhosComDesempate($atual, $proximo);
            if ($regraUsada !== null) {
                $resultado[$i]['criterio_desempate'] = [
                    'indice' => (int) $regraUsada['ordem_prioridade'],
                    'criterio' => (string) $regraUsada['nome_regra'],
                ];
            }
        }
    }

    return $resultado;
}
