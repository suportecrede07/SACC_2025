<?php


// 1. PEGAR OS PESOS DOS CRITÉRIOS NO BANCO:
//    - Vá na tabela 'Criterios_avaliacao' e veja quais critérios estão ativos e o peso de cada um.
//    - Exemplo: se o Critério 1 tem peso 2 e o Critério 2 tem peso 1, isso deve ser considerado.

// 2. BUSCAR AS NOTAS QUE OS JURADOS DERAM

//
// 3. ORDENAR DO MAIOR PARA O MENOR:
//    - Coloque os trabalhos em ordem: a maior nota final fica em 1º lugar, a segunda em 2º, etc.
//
// 4. RESOLVER OS EMPATES
//    - Se dois trabalhos tirarem a mesma nota final exata (ex: ambos tiraram 9,50):
//    - Consulte a tabela 'Criterios_desempate' para ver qual regra está cadastrada como prioritária.
//    - 1ª Regra: se o administrador configurou que desempata pelo "Critério 1", quem tirou nota maior no Critério 1 fica na frente.
//    - 2ª Regra: se continuarem empatados, olha a próxima regra cadastrada (pode ser outro critério ou um campo dinâmico da escola).
//
// 5. DEVOLVER O RESULTADO PRONTO:
//    - Entregue um array limpo com os trabalhos já colocados do 1º ao último lugar contendo:
//      [
//         'posicao'     => 1,
//         'id_trabalho' => 10,
//         'titulo'      => 'Nome do Trabalho',
//         'escola'      => 'Nome da Escola',
//         'media_final' => 9.85,
//         'jurados'     => 3
//      ]

