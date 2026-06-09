<?php
session_start();
require_once __DIR__ . '/scripts/db.php';

if (!function_exists('h')) {
    function h($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] ?? '') !== 'restaurante') {
    header('Location: login.html');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$mensagem = '';
$erro = '';

/* Buscar restaurante associado ao utilizador logado */
$stmt = $db->prepare("
    SELECT id, utilizador_id, nome, categoria, morada, ativo
    FROM restaurantes
    WHERE utilizador_id = :utilizador_id
    LIMIT 1
");

$stmt->bindValue(':utilizador_id', $userId, SQLITE3_INTEGER);
$result = $stmt->execute();
$restaurante = $result->fetchArray(SQLITE3_ASSOC);

$restauranteId = $restaurante ? (int)$restaurante['id'] : 0;

/*
    Regra após pré-avaliação:
    O restaurante NÃO altera os dados institucionais do restaurante.
    Nome, categoria, morada e estado ativo/inativo são controlados pelo administrador.
    O restaurante apenas gere menu/produtos e encomendas.
*/

if ($restaurante) {

    /* Adicionar produto/menu */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'adicionar_produto') {
        $nomeProduto = trim($_POST['nome_produto'] ?? '');
        $descricaoProduto = trim($_POST['descricao_produto'] ?? '');
        $precoProduto = str_replace(',', '.', trim($_POST['preco_produto'] ?? ''));
        $disponivel = isset($_POST['disponivel_produto']) ? 1 : 0;

        if ($nomeProduto === '') {
            $erro = 'O nome do produto é obrigatório.';
        } elseif (!is_numeric($precoProduto) || (float)$precoProduto <= 0) {
            $erro = 'O preço do produto deve ser um valor válido.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO produtos (
                    restaurante_id,
                    nome,
                    descricao,
                    preco,
                    disponivel
                )
                VALUES (
                    :restaurante_id,
                    :nome,
                    :descricao,
                    :preco,
                    :disponivel
                )
            ");

            $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
            $stmt->bindValue(':nome', $nomeProduto, SQLITE3_TEXT);
            $stmt->bindValue(':descricao', $descricaoProduto, SQLITE3_TEXT);
            $stmt->bindValue(':preco', (float)$precoProduto, SQLITE3_FLOAT);
            $stmt->bindValue(':disponivel', $disponivel, SQLITE3_INTEGER);
            $stmt->execute();

            $mensagem = 'Produto adicionado ao menu com sucesso.';
        }
    }

    /* Atualizar produto/menu */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_produto') {
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $nomeProduto = trim($_POST['nome_produto'] ?? '');
        $descricaoProduto = trim($_POST['descricao_produto'] ?? '');
        $precoProduto = str_replace(',', '.', trim($_POST['preco_produto'] ?? ''));
        $disponivel = isset($_POST['disponivel_produto']) ? 1 : 0;

        if ($produtoId <= 0) {
            $erro = 'Produto inválido.';
        } elseif ($nomeProduto === '') {
            $erro = 'O nome do produto é obrigatório.';
        } elseif (!is_numeric($precoProduto) || (float)$precoProduto <= 0) {
            $erro = 'O preço do produto deve ser um valor válido.';
        } else {
            $stmt = $db->prepare("
                UPDATE produtos
                SET nome = :nome,
                    descricao = :descricao,
                    preco = :preco,
                    disponivel = :disponivel
                WHERE id = :id
                  AND restaurante_id = :restaurante_id
            ");

            $stmt->bindValue(':nome', $nomeProduto, SQLITE3_TEXT);
            $stmt->bindValue(':descricao', $descricaoProduto, SQLITE3_TEXT);
            $stmt->bindValue(':preco', (float)$precoProduto, SQLITE3_FLOAT);
            $stmt->bindValue(':disponivel', $disponivel, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $produtoId, SQLITE3_INTEGER);
            $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
            $stmt->execute();

            $mensagem = 'Produto atualizado com sucesso.';
        }
    }

    /* Remover produto/menu */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'remover_produto') {
        $produtoId = (int)($_POST['produto_id'] ?? 0);

        if ($produtoId <= 0) {
            $erro = 'Produto inválido.';
        } else {
            /*
                Se o produto já tiver encomendas associadas, não apagamos fisicamente,
                para não quebrar o histórico. Apenas marcamos como indisponível.
            */
            $stmt = $db->prepare("
                SELECT COUNT(*) AS total
                FROM encomenda_itens ei
                INNER JOIN produtos p ON p.id = ei.produto_id
                WHERE p.id = :produto_id
                  AND p.restaurante_id = :restaurante_id
            ");

            $stmt->bindValue(':produto_id', $produtoId, SQLITE3_INTEGER);
            $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $temHistorico = $result->fetchArray(SQLITE3_ASSOC);

            if ((int)$temHistorico['total'] > 0) {
                $stmt = $db->prepare("
                    UPDATE produtos
                    SET disponivel = 0
                    WHERE id = :id
                      AND restaurante_id = :restaurante_id
                ");

                $stmt->bindValue(':id', $produtoId, SQLITE3_INTEGER);
                $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
                $stmt->execute();

                $mensagem = 'Produto já tinha encomendas associadas, por isso foi marcado como indisponível.';
            } else {
                $stmt = $db->prepare("
                    DELETE FROM produtos
                    WHERE id = :id
                      AND restaurante_id = :restaurante_id
                ");

                $stmt->bindValue(':id', $produtoId, SQLITE3_INTEGER);
                $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
                $stmt->execute();

                $mensagem = 'Produto removido com sucesso.';
            }
        }
    }

    /* Atualizar estado da encomenda */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_estado') {
        $encomendaId = (int)($_POST['encomenda_id'] ?? 0);
        $novoEstado = trim($_POST['estado'] ?? '');

        $estadosPermitidos = ['recebida', 'em preparação', 'concluída', 'cancelada'];

        if ($encomendaId <= 0) {
            $erro = 'Encomenda inválida.';
        } elseif (!in_array($novoEstado, $estadosPermitidos, true)) {
            $erro = 'Estado inválido.';
        } else {
            /*
                Garante que esta encomenda pertence a este restaurante.
            */
            $stmt = $db->prepare("
                SELECT e.id
                FROM encomendas e
                INNER JOIN encomenda_itens ei ON ei.encomenda_id = e.id
                INNER JOIN produtos p ON p.id = ei.produto_id
                WHERE e.id = :encomenda_id
                  AND p.restaurante_id = :restaurante_id
                LIMIT 1
            ");

            $stmt->bindValue(':encomenda_id', $encomendaId, SQLITE3_INTEGER);
            $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $encomendaExiste = $result->fetchArray(SQLITE3_ASSOC);

            if (!$encomendaExiste) {
                $erro = 'Esta encomenda não pertence ao seu restaurante.';
            } else {
                $stmt = $db->prepare("
                    UPDATE encomendas
                    SET estado = :estado
                    WHERE id = :id
                ");

                $stmt->bindValue(':estado', $novoEstado, SQLITE3_TEXT);
                $stmt->bindValue(':id', $encomendaId, SQLITE3_INTEGER);
                $stmt->execute();

                $mensagem = 'Estado da encomenda atualizado com sucesso.';
            }
        }
    }
}

/* Recarregar dados do restaurante */
if ($restauranteId > 0) {
    $stmt = $db->prepare("
        SELECT id, utilizador_id, nome, categoria, morada, ativo
        FROM restaurantes
        WHERE id = :id
          AND utilizador_id = :utilizador_id
        LIMIT 1
    ");

    $stmt->bindValue(':id', $restauranteId, SQLITE3_INTEGER);
    $stmt->bindValue(':utilizador_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $restaurante = $result->fetchArray(SQLITE3_ASSOC);
}

/* Buscar produtos do restaurante */
$produtos = [];

if ($restaurante) {
    $stmt = $db->prepare("
        SELECT id, nome, descricao, preco, disponivel
        FROM produtos
        WHERE restaurante_id = :restaurante_id
        ORDER BY nome ASC
    ");

    $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    while ($produto = $result->fetchArray(SQLITE3_ASSOC)) {
        $produtos[] = $produto;
    }
}

/* Buscar encomendas recebidas pelo restaurante */
$encomendas = [];

if ($restaurante) {
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.data,
            e.estado,
            e.total,
            e.morada_entrega,
            e.contacto_cliente,
            e.observacoes,
            u.nome AS cliente_nome,
            u.email AS cliente_email,
            GROUP_CONCAT(p.nome || ' x' || ei.quantidade, ', ') AS itens
        FROM encomendas e
        INNER JOIN utilizadores u ON u.id = e.utilizador_id
        INNER JOIN encomenda_itens ei ON ei.encomenda_id = e.id
        INNER JOIN produtos p ON p.id = ei.produto_id
        WHERE p.restaurante_id = :restaurante_id
        GROUP BY e.id
        ORDER BY e.data DESC
    ");

    $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $encomendas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Restaurante | FoodToGo</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="pagina-painel">

<header>
    <h1 class="logo">Food<span>ToGo</span></h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="restaurantes.php">Restaurantes</a>

        <span class="nav-utilizador">
            Restaurante: <?= h($_SESSION['nome']) ?>
        </span>

        <a href="scripts/logout.php" class="nav-destaque">Sair</a>
    </nav>
</header>

<main>
    <section class="painel-container">
        <h2>Painel do Restaurante</h2>

        <?php if ($mensagem !== ''): ?>
            <div class="mensagem-ok">
                <?= h($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="mensagem-erro">
                <?= h($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!$restaurante): ?>

            <div class="mensagem-info">
                Ainda não existe restaurante associado à sua conta.
                Contacte o administrador para associar um restaurante ao seu utilizador.
            </div>

        <?php else: ?>

            <section class="gestao-restaurante">
                <h3>Restaurante associado</h3>

                <div class="card">
                    <p><strong>Nome:</strong> <?= h($restaurante['nome']) ?></p>
                    <p><strong>Categoria:</strong> <?= h($restaurante['categoria'] ?? 'Sem categoria') ?></p>
                    <p><strong>Morada:</strong> <?= h($restaurante['morada'] ?? 'Sem morada') ?></p>
                    <p>
                        <strong>Estado:</strong>
                        <?php if ((int)$restaurante['ativo'] === 1): ?>
                            <span class="estado aberto">Ativo</span>
                        <?php else: ?>
                            <span class="estado fechado">Inativo</span>
                        <?php endif; ?>
                    </p>

                    <p class="small">
                        Os dados principais do restaurante são geridos pelo administrador.
                        Neste painel pode gerir apenas o menu e as encomendas.
                    </p>
                </div>
            </section>

            <section class="gestao-menu">
                <h3>Adicionar produto ao menu</h3>

                <form method="POST" action="painelrestaurante.php" class="form-admin">
                    <input type="hidden" name="acao" value="adicionar_produto">

                    <label>Nome do produto</label>
                    <input 
                        type="text" 
                        name="nome_produto" 
                        placeholder="Ex: Frango grelhado com arroz"
                        required
                    >

                    <label>Descrição</label>
                    <input 
                        type="text" 
                        name="descricao_produto" 
                        placeholder="Ex: Menu com bebida incluída"
                    >

                    <label>Preço</label>
                    <input 
                        type="text" 
                        name="preco_produto" 
                        placeholder="Ex: 9.50"
                        required
                    >

                    <label class="checkbox-linha">
                        <input 
                            type="checkbox" 
                            name="disponivel_produto" 
                            value="1"
                            checked
                        >
                        Produto disponível
                    </label>

                    <button type="submit" class="btn-primary">
                        Adicionar produto
                    </button>
                </form>
            </section>

            <section class="gestao-menu">
                <h3>Menu / Produtos</h3>

                <?php if (empty($produtos)): ?>

                    <p>Este restaurante ainda não tem produtos registados.</p>

                <?php else: ?>

                    <table class="tabela-restaurantes">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Descrição</th>
                                <th>Preço</th>
                                <th>Disponibilidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <form method="POST" action="painelrestaurante.php">
                                        <input type="hidden" name="acao" value="atualizar_produto">
                                        <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">

                                        <td>
                                            <input 
                                                type="text" 
                                                name="nome_produto" 
                                                value="<?= h($produto['nome']) ?>" 
                                                required
                                            >
                                        </td>

                                        <td>
                                            <input 
                                                type="text" 
                                                name="descricao_produto" 
                                                value="<?= h($produto['descricao'] ?? '') ?>"
                                            >
                                        </td>

                                        <td>
                                            <input 
                                                type="text" 
                                                name="preco_produto" 
                                                value="<?= number_format((float)$produto['preco'], 2, '.', '') ?>" 
                                                required
                                            >
                                        </td>

                                        <td>
                                            <label class="checkbox-linha">
                                                <input 
                                                    type="checkbox" 
                                                    name="disponivel_produto" 
                                                    value="1"
                                                    <?= (int)$produto['disponivel'] === 1 ? 'checked' : '' ?>
                                                >
                                                Disponível
                                            </label>
                                        </td>

                                        <td class="acoes">
                                            <button type="submit" class="btn-primary">
                                                Guardar
                                            </button>
                                    </form>

                                            <form 
                                                method="POST" 
                                                action="painelrestaurante.php" 
                                                onsubmit="return confirm('Tem a certeza que pretende remover este produto?');"
                                            >
                                                <input type="hidden" name="acao" value="remover_produto">
                                                <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">

                                                <button type="submit" class="btn-danger">
                                                    Remover
                                                </button>
                                            </form>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>
            </section>

            <section class="gestao-encomendas">
                <h3>Encomendas recebidas</h3>

                <?php if (empty($encomendas)): ?>

                    <p>Ainda não existem encomendas para este restaurante.</p>

                <?php else: ?>

                    <table class="tabela-restaurantes">
                        <thead>
                            <tr>
                                <th>Nº</th>
                                <th>Cliente</th>
                                <th>Contacto / Morada</th>
                                <th>Pedido</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($encomendas as $encomenda): ?>
                                <tr>
                                    <td>
                                        #<?= (int)$encomenda['id'] ?><br>
                                        <span><?= h($encomenda['data']) ?></span>
                                    </td>

                                    <td>
                                        <strong><?= h($encomenda['cliente_nome']) ?></strong><br>
                                        <span><?= h($encomenda['cliente_email']) ?></span>
                                    </td>

                                    <td>
                                        <strong>Contacto:</strong><br>
                                        <?= h($encomenda['contacto_cliente'] ?? '') ?><br><br>

                                        <strong>Morada:</strong><br>
                                        <?= h($encomenda['morada_entrega'] ?? '') ?>
                                    </td>

                                    <td>
                                        <strong>Itens:</strong><br>
                                        <?= h($encomenda['itens'] ?? 'Sem itens') ?>

                                        <?php if (!empty($encomenda['observacoes'])): ?>
                                            <br><br>
                                            <strong>Observações:</strong><br>
                                            <?= h($encomenda['observacoes']) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="estado aberto">
                                            <?= h($encomenda['estado']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format((float)$encomenda['total'], 2, ',', '.') ?> €
                                        </strong>
                                    </td>

                                    <td>
                                        <form method="POST" action="painelrestaurante.php">
                                            <input type="hidden" name="acao" value="atualizar_estado">
                                            <input type="hidden" name="encomenda_id" value="<?= (int)$encomenda['id'] ?>">

                                            <select name="estado">
                                                <option value="recebida" <?= $encomenda['estado'] === 'recebida' ? 'selected' : '' ?>>
                                                    Recebida
                                                </option>

                                                <option value="em preparação" <?= $encomenda['estado'] === 'em preparação' ? 'selected' : '' ?>>
                                                    Em preparação
                                                </option>

                                                <option value="concluída" <?= $encomenda['estado'] === 'concluída' ? 'selected' : '' ?>>
                                                    Concluída
                                                </option>

                                                <option value="cancelada" <?= $encomenda['estado'] === 'cancelada' ? 'selected' : '' ?>>
                                                    Cancelada
                                                </option>
                                            </select>

                                            <button type="submit" class="btn-primary">
                                                Atualizar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>
            </section>

        <?php endif; ?>
    </section>
</main>

<footer>
    <p>© 2026 FoodToGo - Todos os direitos reservados.</p>
</footer>

</body>
</html>