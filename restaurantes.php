<?php
session_start();
require_once __DIR__ . '/scripts/db.php';

$isCliente = isset($_SESSION['user_id']) && ($_SESSION['tipo'] ?? '') === 'cliente';
$isLogged = isset($_SESSION['user_id']);

$result = $db->query("SELECT r.id AS restaurante_id, r.nome AS restaurante_nome, r.categoria, r.morada, r.ativo,
                             p.id AS produto_id, p.nome AS produto_nome, p.descricao, p.preco, p.disponivel
                      FROM restaurantes r
                      LEFT JOIN produtos p ON p.restaurante_id = r.id
                      WHERE r.ativo = 1
                      ORDER BY r.nome ASC, p.nome ASC");
$restaurantes = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $rid = (int)$row['restaurante_id'];
    if (!isset($restaurantes[$rid])) {
        $restaurantes[$rid] = [
            'id' => $rid,
            'nome' => $row['restaurante_nome'],
            'categoria' => $row['categoria'],
            'morada' => $row['morada'],
            'produtos' => []
        ];
    }
    if (!empty($row['produto_id'])) {
        $restaurantes[$rid]['produtos'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Restaurantes | FoodToGo</title><link rel="stylesheet" href="styles/styles.css"></head>
<body class="pagina-restaurantes">
<header>
    <h1>FoodToGo</h1>
    <nav>
        <a href="index.html">Home</a>
        <a href="restaurantes.php">Restaurantes</a>
        <a href="carrinho.php">Carrinho (<span data-cart-count>0</span>)</a>
        <?php if ($isLogged): ?>
            <span class="nav-utilizador">Olá, <?= h($_SESSION['nome']) ?></span>
            <?php if ($_SESSION['tipo'] === 'admin'): ?><a href="paineladmin.php">Painel Admin</a><?php endif; ?>
            <?php if ($_SESSION['tipo'] === 'restaurante'): ?><a href="painelrestaurante.php">Painel Restaurante</a><?php endif; ?>
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
        <p>Visitantes podem consultar os menus. Para encomendar, é necessário iniciar sessão como cliente.</p>
    </div>
    <?php if (empty($restaurantes)): ?>
        <div class="mensagem-info">Ainda não existem restaurantes ativos.</div>
    <?php else: ?>
        <table class="tabela-restaurantes">
            <thead><tr><th>Restaurante</th><th>Menu</th><th>Estado</th><th>Ação</th></tr></thead>
            <tbody>
            <?php foreach ($restaurantes as $r): ?>
                <tr>
                    <td><strong><?= h($r['nome']) ?></strong><br><span><?= h($r['categoria'] ?? '') ?></span><br><span><?= h($r['morada'] ?? '') ?></span></td>
                    <td>
                        <?php if (empty($r['produtos'])): ?>
                            <span>Sem produtos disponíveis.</span>
                        <?php else: ?>
                            <?php foreach ($r['produtos'] as $p): ?>
                                <div class="produto-linha">
                                    <div>
                                        <strong><?= h($p['produto_nome']) ?></strong><br>
                                        <span><?= h($p['descricao'] ?? '') ?></span><br>
                                        <strong><?= number_format((float)$p['preco'], 2, ',', '.') ?> €</strong>
                                        <?php if (!(int)$p['disponivel']): ?><span class="estado fechado">Indisponível</span><?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($isCliente && (int)$p['disponivel']): ?>
                                            <button type="button" class="botao-menu" data-add-product data-produto-id="<?= (int)$p['produto_id'] ?>" data-restaurante-id="<?= (int)$r['id'] ?>" data-restaurante-nome="<?= h($r['nome']) ?>" data-produto-nome="<?= h($p['produto_nome']) ?>" data-preco="<?= (float)$p['preco'] ?>">Adicionar</button>
                                        <?php elseif (!$isLogged): ?>
                                            <a class="botao-menu" href="login.html">Iniciar sessão</a>
                                        <?php else: ?>
                                            <span class="small">Apenas clientes encomendam</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="estado aberto">Ativo</span></td>
                    <td><?php if ($isCliente): ?><a href="carrinho.php" class="botao-verde">Ver carrinho</a><?php else: ?><a href="login.html" class="botao-laranja">Criar conta / Entrar</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
</main>
<footer><p>© 2026 FoodToGo - Todos os direitos reservados.</p></footer>
<script src="scripts/app.js"></script>
</body>
</html>
