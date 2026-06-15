<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/scripts/db.php';

$isCliente = isset($_SESSION['user_id']) && ($_SESSION['tipo'] ?? '') === 'cliente';
$isLogged = isset($_SESSION['user_id']);

$result = $db->query("
    SELECT 
        r.id AS restaurante_id,
        r.nome AS restaurante_nome,
        r.categoria,
        r.morada,
        r.ativo,
        p.id AS produto_id,
        p.nome AS produto_nome,
        p.descricao,
        p.preco,
        p.disponivel
    FROM restaurantes r
    LEFT JOIN produtos p ON p.restaurante_id = r.id
    WHERE r.ativo = 1
      AND r.utilizador_id IS NOT NULL
    ORDER BY r.nome ASC, p.nome ASC
");

$restaurantes = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $rid = (int)$row['restaurante_id'];

    if (!isset($restaurantes[$rid])) {
        $restaurantes[$rid] = [
            'id' => $rid,
            'nome' => $row['restaurante_nome'],
            'categoria' => $row['categoria'],
            'morada' => $row['morada'],
            'produtos' => [],
            'tem_produto_disponivel' => false
        ];
    }

    if (!empty($row['produto_id'])) {
        $produto = [
            'id' => (int)$row['produto_id'],
            'nome' => $row['produto_nome'],
            'descricao' => $row['descricao'],
            'preco' => (float)$row['preco'],
            'disponivel' => (int)$row['disponivel']
        ];

        $restaurantes[$rid]['produtos'][] = $produto;

        if ($produto['disponivel'] === 1) {
            $restaurantes[$rid]['tem_produto_disponivel'] = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurantes | FoodToGo</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="pagina-restaurantes">

<header>
    <h1 class="logo"><a href="index.php">Food<span>ToGo</span></a></h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="restaurantes.php">Restaurantes</a>

        <?php if ($isCliente): ?>
            <a href="carrinho.php">Carrinho</a>
        <?php endif; ?>

        <?php if ($isLogged): ?>
            <span class="nav-utilizador">
                Olá, <?= h($_SESSION['nome']) ?>
            </span>

            <?php if ($_SESSION['tipo'] === 'admin'): ?>
                <a href="paineladmin.php">Painel Admin</a>
            <?php endif; ?>

            <?php if ($_SESSION['tipo'] === 'restaurante'): ?>
                <a href="painelrestaurante.php">Painel Restaurante</a>
            <?php endif; ?>

            <a href="scripts/logout.php" class="nav-destaque">Sair</a>
        <?php else: ?>
            <a href="login.html" class="nav-destaque">Login/Registo</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="restaurantes-container">
        <div class="titulo-pagina">
            <h2>Restaurantes disponíveis</h2>
            <p>Consulte os menus dos restaurantes disponíveis. Para encomendar, é necessário iniciar sessão como cliente.</p>
        </div>

        <?php if (empty($restaurantes)): ?>

            <div class="mensagem-info">
                Ainda não existem restaurantes ativos.
            </div>

        <?php else: ?>

            <table class="tabela-restaurantes">
                <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th>Menu</th>
                        <th>Estado</th>
                        <th>Ação</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($restaurantes as $r): ?>
                        <tr>
                            <td>
                                <strong><?= h($r['nome']) ?></strong><br>

                                <?php if (!empty($r['categoria'])): ?>
                                    <span><?= h($r['categoria']) ?></span><br>
                                <?php endif; ?>

                                <?php if (!empty($r['morada'])): ?>
                                    <span><?= h($r['morada']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (empty($r['produtos'])): ?>

                                    <span>Sem menu disponível.</span>

                                <?php else: ?>

                                    <?php foreach ($r['produtos'] as $p): ?>
                                        <div class="produto-linha">
                                            <div>
                                                <strong><?= h($p['nome']) ?></strong><br>

                                                <?php if (!empty($p['descricao'])): ?>
                                                    <span><?= h($p['descricao']) ?></span><br>
                                                <?php endif; ?>

                                                <strong><?= number_format((float)$p['preco'], 2, ',', '.') ?> €</strong>

                                                <?php if ((int)$p['disponivel'] !== 1): ?>
                                                    <br>
                                                    <span class="estado fechado">Indisponível</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="estado aberto">Ativo</span>
                            </td>

                            <td>
                                <?php if (!$r['tem_produto_disponivel']): ?>

                                    <span class="small">Sem produtos disponíveis</span>

                                <?php elseif ($isCliente): ?>

                                    <a 
                                        href="carrinho.php?restaurante_id=<?= (int)$r['id'] ?>" 
                                        class="botao-verde"
                                    >
                                        Encomendar
                                    </a>

                                <?php elseif (!$isLogged): ?>

                                    <a href="login.html" class="botao-laranja">
                                        Iniciar sessão
                                    </a>

                                <?php else: ?>

                                    <span class="small">Apenas clientes encomendam</span>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </section>
</main>

<footer class="rodape-app">
    <button
        type="button"
        class="rodape-info-toggle"
        data-info-grupo
        data-tooltip="Clique para ver as informações do grupo"
        title="Clique para ver as informações do grupo"
        aria-expanded="false"
        aria-controls="info-projeto-grupo"
    >
        © 2026 FoodToGo - Todos os direitos reservados.
    </button>

    <div id="info-projeto-grupo" class="rodape-info" hidden>
        <strong>Sobre o projeto</strong>
        <p>FoodToGo é uma aplicação web de encomenda de alimentos que liga clientes e restaurantes numa plataforma simples e organizada.</p>
        <strong>Grupo</strong>
        <ul>
            <li>1231707 - Erick de Abreu Gomes</li>
            <li>1250756 - André Gonçalves Monteiro</li>
            <li>1251415 - Rodrigo Luís Nunes Alves Ribeiro</li>
        </ul>
    </div>
</footer>

<script src="scripts/app.js"></script>
</body>
</html>
