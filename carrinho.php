<?php
session_start();
require_once __DIR__ . '/scripts/db.php';

if (!function_exists('h')) {
    function h($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] ?? '') !== 'cliente') {
    header('Location: login.html');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$mensagem = '';
$erro = '';

$limitePorProduto = 10;
$limiteTotalPedido = 20;

$restauranteId = isset($_GET['restaurante_id']) ? (int)$_GET['restaurante_id'] : 0;
$modoConsultaHistorico = ($restauranteId <= 0);

/* Buscar restaurante apenas se vier restaurante_id */
$restaurante = null;

if (!$modoConsultaHistorico) {
    $stmt = $db->prepare("
        SELECT id, nome, categoria, morada, ativo
        FROM restaurantes
        WHERE id = :id
    ");

    $stmt->bindValue(':id', $restauranteId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $restaurante = $result->fetchArray(SQLITE3_ASSOC);

    if (!$restaurante) {
        $erro = 'Restaurante não encontrado. Volte à página de restaurantes e escolha uma opção válida.';
    } elseif ((int)$restaurante['ativo'] !== 1) {
        $erro = 'Este restaurante não está ativo de momento.';
    }
}

/* Buscar produtos disponíveis do restaurante */
$produtos = [];

if (!$modoConsultaHistorico && $restaurante && $erro === '') {
    $stmt = $db->prepare("
        SELECT id, nome, descricao, preco, disponivel
        FROM produtos
        WHERE restaurante_id = :restaurante_id
          AND disponivel = 1
        ORDER BY nome ASC
    ");

    $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    while ($produto = $result->fetchArray(SQLITE3_ASSOC)) {
        $produtos[(int)$produto['id']] = $produto;
    }

    if (empty($produtos)) {
        $erro = 'Este restaurante ainda não tem produtos disponíveis.';
    }
}

/* Confirmar encomenda */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['acao'] ?? '') === 'confirmar' &&
    !$modoConsultaHistorico &&
    $restaurante &&
    $erro === ''
) {
    $quantidadesRecebidas = $_POST['quantidade'] ?? [];
    $moradaEntrega = trim($_POST['morada_entrega'] ?? '');
    $contactoCliente = trim($_POST['contacto_cliente'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    $itensSelecionados = [];
    $total = 0;
    $totalQuantidade = 0;

    foreach ($quantidadesRecebidas as $produtoId => $quantidade) {
        $produtoId = (int)$produtoId;
        $quantidade = (int)$quantidade;

        if ($quantidade < 0) {
            $quantidade = 0;
        }

        if ($quantidade > $limitePorProduto) {
            $erro = "Cada produto pode ter no máximo {$limitePorProduto} unidades por encomenda.";
            break;
        }

        if ($quantidade > 0 && isset($produtos[$produtoId])) {
            $preco = (float)$produtos[$produtoId]['preco'];
            $subtotal = $preco * $quantidade;

            $itensSelecionados[] = [
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'preco_unitario' => $preco
            ];

            $total += $subtotal;
            $totalQuantidade += $quantidade;
        }
    }

    if ($erro === '' && $totalQuantidade > $limiteTotalPedido) {
        $erro = "Cada encomenda pode ter no máximo {$limiteTotalPedido} unidades no total. Selecionou {$totalQuantidade} unidades.";
    }

    if ($erro === '' && empty($itensSelecionados)) {
        $erro = 'Selecione pelo menos um produto para confirmar a encomenda.';
    }

    if ($erro === '' && $moradaEntrega === '') {
        $erro = 'A morada de entrega é obrigatória.';
    }

    if ($erro === '' && $contactoCliente === '') {
        $erro = 'O contacto do cliente é obrigatório.';
    }

    if ($erro === '' && !preg_match('/^9[0-9]{8}$/', $contactoCliente)) {
        $erro = 'O telemóvel deve ter exatamente 9 dígitos e começar por 9.';
    }

    /*
        Só grava na base de dados se NÃO houver erro.
        Isto impede encomendas acima do limite mesmo que o JavaScript falhe.
    */
    if ($erro === '') {
        $data = date('Y-m-d H:i:s');

        $stmt = $db->prepare("
            INSERT INTO encomendas (
                utilizador_id,
                restaurante_id,
                data,
                estado,
                total,
                morada_entrega,
                contacto_cliente,
                observacoes
            )
            VALUES (
                :utilizador_id,
                :restaurante_id,
                :data,
                'recebida',
                :total,
                :morada_entrega,
                :contacto_cliente,
                :observacoes
            )
        ");

        $stmt->bindValue(':utilizador_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':restaurante_id', $restauranteId, SQLITE3_INTEGER);
        $stmt->bindValue(':data', $data, SQLITE3_TEXT);
        $stmt->bindValue(':total', $total, SQLITE3_FLOAT);
        $stmt->bindValue(':morada_entrega', $moradaEntrega, SQLITE3_TEXT);
        $stmt->bindValue(':contacto_cliente', $contactoCliente, SQLITE3_TEXT);
        $stmt->bindValue(':observacoes', $observacoes, SQLITE3_TEXT);
        $stmt->execute();

        $encomendaId = $db->lastInsertRowID();

        foreach ($itensSelecionados as $item) {
            $stmt = $db->prepare("
                INSERT INTO encomenda_itens (
                    encomenda_id,
                    produto_id,
                    quantidade,
                    preco_unitario
                )
                VALUES (
                    :encomenda_id,
                    :produto_id,
                    :quantidade,
                    :preco_unitario
                )
            ");

            $stmt->bindValue(':encomenda_id', $encomendaId, SQLITE3_INTEGER);
            $stmt->bindValue(':produto_id', $item['produto_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':quantidade', $item['quantidade'], SQLITE3_INTEGER);
            $stmt->bindValue(':preco_unitario', $item['preco_unitario'], SQLITE3_FLOAT);
            $stmt->execute();
        }

        $mensagem = 'Encomenda confirmada com sucesso.';

        /*
            Depois de confirmar, não faz sentido manter o formulário ativo.
            A página passa a mostrar apenas histórico e botão para nova encomenda.
        */
        $modoConsultaHistorico = true;
        $restaurante = null;
        $produtos = [];
    }
}

/* Últimas encomendas do cliente */
$ultimasEncomendas = [];

$stmt = $db->prepare("
    SELECT 
        e.id,
        e.data,
        e.estado,
        e.total,
        e.morada_entrega,
        e.contacto_cliente,
        e.observacoes,
        r.nome AS restaurante_nome,
        GROUP_CONCAT(p.nome || ' x' || ei.quantidade, ', ') AS itens
    FROM encomendas e
    LEFT JOIN restaurantes r ON r.id = e.restaurante_id
    LEFT JOIN encomenda_itens ei ON ei.encomenda_id = e.id
    LEFT JOIN produtos p ON p.id = ei.produto_id
    WHERE e.utilizador_id = :utilizador_id
    GROUP BY e.id
    ORDER BY e.data DESC
    LIMIT 5
");

$stmt->bindValue(':utilizador_id', $userId, SQLITE3_INTEGER);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $ultimasEncomendas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho | FoodToGo</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="pagina-carrinho">

<header>
    <h1 class="logo">Food<span>ToGo</span></h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="restaurantes.php">Restaurantes</a>
        <a href="carrinho.php">Carrinho</a>

        <span class="nav-utilizador">
            Olá, <?= h($_SESSION['nome']) ?>
        </span>

        <a href="scripts/logout.php" class="nav-destaque">Sair</a>
    </nav>
</header>

<main>
    <section class="carrinho-container">
        <h2>Carrinho / Confirmação de Encomenda</h2>

        <?php if ($mensagem !== ''): ?>
            <div class="mensagem-ok">
                <?= h($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($modoConsultaHistorico && $mensagem === ''): ?>
            <div class="mensagem-info">
                Não existem itens no carrinho neste momento. Para fazer uma nova encomenda, escolha um restaurante.
            </div>

            <p>
                <a href="restaurantes.php" class="botao-verde">Escolher restaurante</a>
            </p>
        <?php elseif ($erro !== ''): ?>
            <div class="mensagem-erro">
                <?= h($erro) ?>
            </div>

            <p>
                <a href="restaurantes.php" class="botao-verde">Voltar aos restaurantes</a>
            </p>
        <?php endif; ?>

        <?php if (!$modoConsultaHistorico && $erro === '' && $restaurante): ?>
            <form method="POST" action="carrinho.php?restaurante_id=<?= (int)$restauranteId ?>" id="form-carrinho">
                <input type="hidden" name="acao" value="confirmar">

                <div class="layout-carrinho">

                    <section class="area-menu">
                        <h3><?= h($restaurante['nome']) ?></h3>

                        <?php if (!empty($restaurante['categoria'])): ?>
                            <p><strong>Categoria:</strong> <?= h($restaurante['categoria']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($restaurante['morada'])): ?>
                            <p><strong>Morada do restaurante:</strong> <?= h($restaurante['morada']) ?></p>
                        <?php endif; ?>

                        <h3>Menu disponível</h3>

                        <table>
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Preço</th>
                                    <th>Quantidade</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($produtos as $produto): ?>
                                    <tr>
                                        <td>
                                            <strong><?= h($produto['nome']) ?></strong><br>

                                            <?php if (!empty($produto['descricao'])): ?>
                                                <span><?= h($produto['descricao']) ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span data-preco="<?= (float)$produto['preco'] ?>">
                                                <?= number_format((float)$produto['preco'], 2, ',', '.') ?> €
                                            </span>
                                        </td>

                                        <td>
                                            <div class="quantidade-controlos">
                                                <button type="button" class="btn-quantidade" data-menos>-</button>

                                                <input 
                                                    type="number"
                                                    name="quantidade[<?= (int)$produto['id'] ?>]"
                                                    value="0"
                                                    min="0"
                                                    max="<?= (int)$limitePorProduto ?>"
                                                    class="input-quantidade"
                                                    data-quantidade
                                                    data-preco="<?= (float)$produto['preco'] ?>"
                                                >

                                                <button type="button" class="btn-quantidade" data-mais>+</button>
                                            </div>
                                        </td>

                                        <td>
                                            <strong>
                                                <span data-subtotal>0,00 €</span>
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>

                    <aside class="area-carrinho">
                        <h3>Resumo da encomenda</h3>

                        <p>
                            Escolha as quantidades dos produtos que pretende encomendar.
                        </p>

                        <p class="small">
                            Limite: máximo de <?= (int)$limitePorProduto ?> unidades por produto e 
                            <?= (int)$limiteTotalPedido ?> unidades no total por encomenda.
                        </p>

                        <p class="total">
                            <strong>Total: <span id="total-carrinho">0,00 €</span></strong>
                        </p>

                        <h4>Dados para entrega/contacto</h4>

                        <div class="dados-entrega-grid">
                            <div class="campo-form">
                                <label for="morada_entrega">Morada de entrega</label>
                                <input
                                    type="text"
                                    id="morada_entrega"
                                    name="morada_entrega"
                                    placeholder="Ex: Rua das Flores, nº 10, Porto"
                                    required
                                >
                            </div>

                            <div class="campo-form">
                                <label for="contacto_cliente">Telemóvel / Contacto</label>
                                <input
                                    type="tel"
                                    id="contacto_cliente"
                                    name="contacto_cliente"
                                    placeholder="Ex: 912345678"
                                    maxlength="9"
                                    minlength="9"
                                    pattern="9[0-9]{8}"
                                    inputmode="numeric"
                                    required
                                >
                            </div>
                        </div>

                        <h4>Observações</h4>
                        <textarea 
                            name="observacoes" 
                            rows="4" 
                            placeholder="Ex: sem cebola, entregar à porta..."
                        ></textarea>

                        <br><br>

                        <button type="submit" class="btn-primary">
                            Confirmar encomenda
                        </button>
                    </aside>

                </div>
            </form>
        <?php endif; ?>

        <section class="ultimas-encomendas">
            <h3>Últimas encomendas</h3>

            <?php if (empty($ultimasEncomendas)): ?>

                <p>Ainda não existem encomendas registadas neste perfil.</p>

            <?php else: ?>

                <table class="tabela-restaurantes">
                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Data</th>
                            <th>Restaurante</th>
                            <th>Itens</th>
                            <th>Morada</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ultimasEncomendas as $encomenda): ?>
                            <tr>
                                <td>#<?= (int)$encomenda['id'] ?></td>

                                <td>
                                    <?= h($encomenda['data']) ?>
                                </td>

                                <td>
                                    <?= h($encomenda['restaurante_nome'] ?? '') ?>
                                </td>

                                <td>
                                    <?= h($encomenda['itens'] ?? 'Sem itens') ?>

                                    <?php if (!empty($encomenda['observacoes'])): ?>
                                        <br>
                                        <span>
                                            Obs.: <?= h($encomenda['observacoes']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($encomenda['morada_entrega'] ?? '') ?>
                                </td>

                                <td>
                                    <?= h($encomenda['contacto_cliente'] ?? '') ?>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </section>
    </section>
</main>

<footer>
    <p>© 2026 FoodToGo - Todos os direitos reservados.</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form-carrinho");

    if (!form) {
        return;
    }

    const contactoInput = document.getElementById("contacto_cliente");

    if (contactoInput) {
        contactoInput.addEventListener("input", function () {
            contactoInput.value = contactoInput.value
                .replace(/\D/g, "")
                .slice(0, 9);
        });
    }

    const linhas = form.querySelectorAll("tbody tr");
    const totalCarrinho = document.getElementById("total-carrinho");
    const limiteTotalPedido = <?= (int)$limiteTotalPedido ?>;

    function formatarEuro(valor) {
        return valor.toFixed(2).replace(".", ",") + " €";
    }

    function calcularTotalQuantidade() {
        let totalQuantidade = 0;

        form.querySelectorAll("[data-quantidade]").forEach(function (input) {
            const quantidade = parseInt(input.value || "0", 10);

            if (!isNaN(quantidade)) {
                totalQuantidade += quantidade;
            }
        });

        return totalQuantidade;
    }

    function atualizarTotais() {
        let total = 0;

        linhas.forEach(function (linha) {
            const input = linha.querySelector("[data-quantidade]");
            const subtotalElemento = linha.querySelector("[data-subtotal]");

            const preco = parseFloat(input.dataset.preco || "0");
            const quantidade = parseInt(input.value || "0", 10);

            const subtotal = preco * quantidade;
            total += subtotal;

            subtotalElemento.textContent = formatarEuro(subtotal);
        });

        totalCarrinho.textContent = formatarEuro(total);
    }

    linhas.forEach(function (linha) {
        const input = linha.querySelector("[data-quantidade]");
        const btnMais = linha.querySelector("[data-mais]");
        const btnMenos = linha.querySelector("[data-menos]");

        btnMais.addEventListener("click", function () {
            const maximoProduto = parseInt(input.getAttribute("max") || "10", 10);
            const valorAtual = parseInt(input.value || "0", 10);
            const totalAtual = calcularTotalQuantidade();

            if (valorAtual >= maximoProduto) {
                alert("Limite máximo por produto atingido.");
                return;
            }

            if (totalAtual >= limiteTotalPedido) {
                alert("Cada encomenda pode ter no máximo " + limiteTotalPedido + " unidades no total.");
                return;
            }

            input.value = valorAtual + 1;
            atualizarTotais();
        });

        btnMenos.addEventListener("click", function () {
            const valorAtual = parseInt(input.value || "0", 10);

            if (valorAtual > 0) {
                input.value = valorAtual - 1;
            }

            atualizarTotais();
        });

        input.addEventListener("input", function () {
            const maximoProduto = parseInt(input.getAttribute("max") || "10", 10);
            let valor = parseInt(input.value || "0", 10);

            if (valor < 0 || isNaN(valor)) {
                valor = 0;
            }

            if (valor > maximoProduto) {
                valor = maximoProduto;
                alert("Limite máximo por produto atingido.");
            }

            input.value = valor;

            let totalQuantidade = calcularTotalQuantidade();

            if (totalQuantidade > limiteTotalPedido) {
                const excesso = totalQuantidade - limiteTotalPedido;
                const novoValor = Math.max(0, valor - excesso);

                input.value = novoValor;

                alert("Cada encomenda pode ter no máximo " + limiteTotalPedido + " unidades no total.");
            }

            atualizarTotais();
        });
    });

    form.addEventListener("submit", function (event) {
        const contacto = document.getElementById("contacto_cliente").value.trim();

        if (!/^9[0-9]{8}$/.test(contacto)) {
            event.preventDefault();
            alert("O telemóvel deve ter exatamente 9 dígitos e começar por 9.");
            return;
        }

        const totalQuantidade = calcularTotalQuantidade();

        if (totalQuantidade <= 0) {
            event.preventDefault();
            alert("Selecione pelo menos um produto antes de confirmar a encomenda.");
            return;
        }

        if (totalQuantidade > limiteTotalPedido) {
            event.preventDefault();
            alert("Cada encomenda pode ter no máximo " + limiteTotalPedido + " unidades no total. Selecionou " + totalQuantidade + " unidades.");
            return;
        }
    });

    atualizarTotais();
});
</script>

</body>
</html>