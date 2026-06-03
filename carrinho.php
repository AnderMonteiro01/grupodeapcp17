<?php
session_start();
require_once __DIR__ . '/scripts/db.php';

$mensagem = '';
$erro = '';
$limparCarrinho = false;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
if (($_SESSION['tipo'] ?? '') !== 'cliente') {
    $erro = 'Apenas utilizadores do tipo cliente podem confirmar encomendas.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erro) {
    $cartJson = $_POST['cart_json'] ?? '[]';
    $observacoes = trim($_POST['observacoes'] ?? '');
    $cart = json_decode($cartJson, true);
    if (!is_array($cart) || count($cart) === 0) {
        $erro = 'O carrinho está vazio.';
    } else {
        $restauranteId = null;
        $total = 0;
        $itensValidados = [];
        foreach ($cart as $item) {
            $produtoId = (int)($item['produto_id'] ?? 0);
            $quantidade = max(1, (int)($item['quantidade'] ?? 1));
            $stmt = $db->prepare("SELECT p.id, p.nome, p.preco, p.disponivel, p.restaurante_id, r.ativo FROM produtos p JOIN restaurantes r ON r.id = p.restaurante_id WHERE p.id = :id");
            $stmt->bindValue(':id', $produtoId, SQLITE3_INTEGER);
            $produto = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$produto || !(int)$produto['disponivel'] || !(int)$produto['ativo']) {
                $erro = 'Um dos produtos já não está disponível.';
                break;
            }
            if ($restauranteId === null) {
                $restauranteId = (int)$produto['restaurante_id'];
            }
            if ($restauranteId !== (int)$produto['restaurante_id']) {
                $erro = 'A encomenda deve conter produtos de apenas um restaurante.';
                break;
            }
            $subtotal = (float)$produto['preco'] * $quantidade;
            $total += $subtotal;
            $itensValidados[] = ['produto_id'=>$produtoId,'quantidade'=>$quantidade,'preco'=>(float)$produto['preco']];
        }
        if (!$erro && $restauranteId !== null) {
            $stmt = $db->prepare("INSERT INTO encomendas (utilizador_id, restaurante_id, data, estado, total, observacoes) VALUES (:uid, :rid, :data, 'recebida', :total, :obs)");
            $stmt->bindValue(':uid', (int)$_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':rid', $restauranteId, SQLITE3_INTEGER);
            $stmt->bindValue(':data', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->bindValue(':total', $total, SQLITE3_FLOAT);
            $stmt->bindValue(':obs', $observacoes, SQLITE3_TEXT);
            $stmt->execute();
            $encomendaId = $db->lastInsertRowID();
            foreach ($itensValidados as $item) {
                $stmt = $db->prepare("INSERT INTO encomenda_itens (encomenda_id, produto_id, quantidade, preco_unitario) VALUES (:eid, :pid, :qtd, :preco)");
                $stmt->bindValue(':eid', $encomendaId, SQLITE3_INTEGER);
                $stmt->bindValue(':pid', $item['produto_id'], SQLITE3_INTEGER);
                $stmt->bindValue(':qtd', $item['quantidade'], SQLITE3_INTEGER);
                $stmt->bindValue(':preco', $item['preco'], SQLITE3_FLOAT);
                $stmt->execute();
            }
            $mensagem = 'Encomenda confirmada com sucesso.';
            $limparCarrinho = true;
        }
    }
}

$stmt = $db->prepare("SELECT e.id, e.data, e.estado, e.total, r.nome AS restaurante
                      FROM encomendas e JOIN restaurantes r ON r.id = e.restaurante_id
                      WHERE e.utilizador_id = :uid
                      ORDER BY e.data DESC LIMIT 5");
$stmt->bindValue(':uid', (int)$_SESSION['user_id'], SQLITE3_INTEGER);
$ultimas = $stmt->execute();
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Carrinho | FoodToGo</title><link rel="stylesheet" href="styles/styles.css"></head>
<body class="pagina-carrinho" data-clear-cart="<?= $limparCarrinho ? '1' : '0' ?>">
<header>
    <h1>FoodToGo</h1>
    <nav>
        <a href="index.html">Home</a><a href="restaurantes.php">Restaurantes</a><a href="carrinho.php">Carrinho (<span data-cart-count>0</span>)</a>
        <span class="nav-utilizador">Olá, <?= h($_SESSION['nome']) ?></span><a href="scripts/logout.php" class="nav-destaque">Sair</a>
    </nav>
</header>
<main>
<section class="carrinho-container">
    <h2>Carrinho / Encomenda</h2>
    <?php if ($mensagem): ?><div class="mensagem-ok"><?= h($mensagem) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="mensagem-erro"><?= h($erro) ?></div><?php endif; ?>
    <div class="layout-carrinho">
        <section class="area-menu">
            <h3>Produtos no carrinho</h3>
            <table class="tabela-carrinho"><thead><tr><th>Produto</th><th>Preço</th><th>Qtd.</th><th>Subtotal</th><th>Ação</th></tr></thead><tbody id="cart-body"></tbody></table>
            <a class="botao-laranja" href="restaurantes.php">Continuar a escolher</a>
        </section>
        <aside class="area-carrinho">
            <h3>Resumo</h3>
            <p class="total"><strong>Total: <span id="cart-total">0,00 €</span></strong></p>
            <form method="POST" action="carrinho.php">
                <input type="hidden" name="cart_json" id="cart-json">
                <label>Observações</label>
                <textarea name="observacoes" rows="4" placeholder="Ex: sem cebola, entregar à porta..."></textarea>
                <br><br><button type="submit" class="btn-primary">Confirmar encomenda</button>
            </form>
        </aside>
    </div>
    <hr>
    <h3>Últimas encomendas</h3>
    <table class="tabela-admin"><thead><tr><th>Nº</th><th>Restaurante</th><th>Data</th><th>Estado</th><th>Total</th></tr></thead><tbody>
    <?php $tem=false; while($e = $ultimas->fetchArray(SQLITE3_ASSOC)): $tem=true; ?>
        <tr><td>#<?= (int)$e['id'] ?></td><td><?= h($e['restaurante']) ?></td><td><?= h($e['data']) ?></td><td><span class="estado estado-encomenda"><?= h($e['estado']) ?></span></td><td><?= number_format((float)$e['total'],2,',','.') ?> €</td></tr>
    <?php endwhile; if(!$tem): ?><tr><td colspan="5">Ainda não existem encomendas.</td></tr><?php endif; ?>
    </tbody></table>
</section>
</main>
<footer><p>© 2026 FoodToGo</p></footer>
<script src="scripts/app.js"></script>
</body>
</html>
