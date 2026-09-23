<?php
session_start();

include_once("../php/Connect.php");

$sql = "SELECT Escolas.*, categoria_escolas.categoria_da_escola
FROM Escolas
LEFT JOIN categoria_escolas ON escolas.id_categoria_escola = categoria_escolas.id
ORDER BY Escolas.id_escolas DESC";

$result = $pdo->query($sql);
$escolas = $result->fetchAll(PDO::FETCH_ASSOC);
$total_escolas = count($escolas);
?>

<!DOCTYPE html>

<html lang="PT-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escolas - SAFC Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
    <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
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
                <li>
                    <a href="admin-dashboard.php">
                        <i><img src="../assets/img/dashboard.svg" class="dashboard"></i>
                        <span class="label-text">Dashboard</span>
                    </a>
                </li>

                <li class="active">
                    <a href="admin-escolas.php">
                        <i><img src="../assets/img/escolas.svg" class="escola"></i>
                        <span class="label-text">Escolas</span>
                    </a>
                </li>

                <li>
                    <a href="admin-trabalhos.php">
                        <i><img src="../assets/img/trabalhos.svg" class="trabalho"></i>
                        <span class="label-text">Trabalhos</span>
                    </a>
                </li>

                <li>
                    <a href="admin-jurados.php">
                        <i><img src="../assets/img/jurados.svg" class="jurado"></i>
                        <span class="label-text">Jurados</span>
                    </a>
                </li>

                <li>
                    <a href="admin-relatorios.php">
                        <i><img src="../assets/img/relatorios.svg" class="relatorio"></i>
                        <span class="label-text">Relatórios</span>
                    </a>
                </li>
            </ul>
        </div>

        <ul class="nav flex-column bottom-nav">
            <li>
                <a href="../php/AdmLogout.php">
                    <img src="../assets/img/sair.png" class="sair">
                    <span class="label-text">Sair</span>
                </a>
            </li>
        </ul>
    </div>

    <main id="main">
        <div class="page-header-clean">
            <div class="page-title-row">
                <h1 class="page-title">Escolas</h1>
                <span class="badge-count"><?= $total_escolas ?> Cadastradas</span>
            </div>
            <p class="page-subtitle">Gerencie e visualize as instituições de ensino cadastradas no sistema</p>
        </div>

        <div class="admin-card-table">
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0" id="workTable">
                    <thead>
                        <tr>
                            <th class="ps-4">NOME</th>
                            <th>MUNICÍPIO</th>
                            <th>TIPO</th>
                            <th>IDE MÉDIO</th>
                            <th>IDEB</th>
                            <th>Total de trabalhos na fase escolar</th>
                            <th class="text-center pe-4">AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($escolas)): ?>
                            <?php foreach ($escolas as $user_data): ?>
                                <tr>
                                    <td class="ps-4 td-item-title" id="nome_escolas"><?= htmlspecialchars(($user_data['categoria_da_escola'] ?? '') . ' ' . $user_data['nome']) ?></td>
                                    <td><?= htmlspecialchars($user_data['municipio'] ?? '-') ?></td>
                                    <td>
                                        <span class="category-pill"><?= htmlspecialchars($user_data['focalizada'] ?? '-') ?></span>
                                    </td>
                                    <td ><?= !empty($user_data['ide']) ? htmlspecialchars($user_data['ide']) : '—' ?></td>
                                    <td class="fw-bold text-dark"><?= !empty($user_data['IDEB']) ? htmlspecialchars($user_data['IDEB']) : '—' ?></td>
                                    <td class="fw-bold text-dark"><?= !empty($user_data['total_trabalhos']) ? htmlspecialchars($user_data['total_trabalhos']) : '—' ?></td>

                                    <td class="text-center pe-4 text-nowrap">
                                        <div class="d-inline-flex gap-2">

                                            <a href="../php/Editaescolas.php?id=<?= $user_data['id_escolas'] ?>" class="btn-action-edit" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-1 3a.5.5 0 0 0 .606.606l3-1a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h6a.5.5 0 0 0 0-1h-6A1.5 1.5 0 0 0 1 2.5z" />
                                                </svg>
                                            </a>

                                            <a href="../php/Excluirescolas.php?id=<?= $user_data['id_escolas'] ?>" class="btn-action-delete" onclick="return confirm('Tem certeza que deseja excluir esta escola?');" title="Excluir">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.06a.5.5 0 0 0-.998.06l.5 8.5a.5.5 0 1 0 .998-.06z" />
                                                </svg>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Nenhuma escola cadastrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <style>
        #nome_escolas{
            text-align: left;
        }
        th,td{
            text-align: center;
        }
    </style>

    <script>
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

        $(window).on('resize', function() {
            if (window.innerWidth > 768) {
                $('#sidebar').removeClass('mobile-open');
                $('#overlay').removeClass('show');
            }
        });
    </script>

</body>

</html>