<?php
session_start();
require_once __DIR__ . '/scripts/db.php';

if (!function_exists('h')) {
    function h($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: login.html');
    exit;
}

$mensagem = '';
$erro = '';

function utilizadorAssociadoARestaurante($db, $utilizadorId) {
    $stmt = $db->prepare("
        SELECT id, nome
        FROM restaurantes
        WHERE utilizador_id = :utilizador_id
        LIMIT 1
    ");

    $stmt->bindValue(':utilizador_id', $utilizadorId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    return $result->fetchArray(SQLITE3_ASSOC);
}

function utilizadorJaAssociadoAOutroRestaurante($db, $utilizadorId, $restauranteAtualId = 0) {
    $stmt = $db->prepare("
        SELECT id, nome
        FROM restaurantes
        WHERE utilizador_id = :utilizador_id
          AND id != :restaurante_atual_id
        LIMIT 1
    ");

    $stmt->bindValue(':utilizador_id', $utilizadorId, SQLITE3_INTEGER);
    $stmt->bindValue(':restaurante_atual_id', $restauranteAtualId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    return $result->fetchArray(SQLITE3_ASSOC);
}

function validarUtilizadorParaRestaurante($db, $utilizadorId, $restauranteAtualId = 0) {
    if ($utilizadorId <= 0) {
        return;
    }

    $stmt = $db->prepare("
        SELECT id, nome, username, tipo
        FROM utilizadores
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->bindValue(':id', $utilizadorId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $utilizador = $result->fetchArray(SQLITE3_ASSOC);

    if (!$utilizador) {
        throw new Exception('O utilizador selecionado não existe.');
    }

    if ($utilizador['tipo'] === 'admin') {
        throw new Exception('Não é possível associar um administrador a um restaurante.');
    }

    $restauranteAssociado = utilizadorJaAssociadoAOutroRestaurante($db, $utilizadorId, $restauranteAtualId);

    if ($restauranteAssociado) {
        throw new Exception(
            'Este utilizador já está associado ao restaurante "' .
            $restauranteAssociado['nome'] .
            '". Cada utilizador só pode estar associado a um restaurante.'
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'atualizar_utilizador') {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');

            $tiposPermitidos = ['cliente', 'restaurante', 'admin'];

            if ($id <= 0) {
                throw new Exception('Utilizador inválido.');
            }

            if ($nome === '' || $username === '' || $email === '') {
                throw new Exception('Nome, username e email são obrigatórios.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido.');
            }

            if (!in_array($tipo, $tiposPermitidos, true)) {
                throw new Exception('Tipo de utilizador inválido.');
            }

            if ($id === (int)$_SESSION['user_id'] && $tipo !== 'admin') {
                throw new Exception('Não pode remover o seu próprio perfil de administrador.');
            }

            $restauranteAssociado = utilizadorAssociadoARestaurante($db, $id);

            /*
                Regra:
                - Um utilizador só se torna restaurante através da associação
                  feita na aba Restaurantes.
                - Se já está associado a restaurante, fica bloqueado como restaurante.
                - Se não está associado, não pode ser alterado manualmente para restaurante aqui.
            */

            if ($restauranteAssociado && $tipo !== 'restaurante') {
                throw new Exception(
                    'Este utilizador está associado ao restaurante "' .
                    $restauranteAssociado['nome'] .
                    '". Não pode voltar a cliente/admin diretamente. Para suspender a atividade, coloque o restaurante como inativo.'
                );
            }

            if (!$restauranteAssociado && $tipo === 'restaurante') {
                throw new Exception(
                    'Não altere o utilizador para restaurante nesta aba. Para transformar um utilizador em restaurante, associe-o a um restaurante na aba Restaurantes.'
                );
            }

            $stmt = $db->prepare("
                UPDATE utilizadores
                SET nome = :nome,
                    username = :username,
                    email = :email,
                    tipo = :tipo
                WHERE id = :id
            ");

            $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':tipo', $tipo, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $mensagem = 'Utilizador atualizado com sucesso.';
        }

        if ($acao === 'remover_utilizador') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('Utilizador inválido.');
            }

            if ($id === (int)$_SESSION['user_id']) {
                throw new Exception('Não pode remover a sua própria conta de administrador.');
            }

            $temEncomendas = (int)$db->querySingle("
                SELECT COUNT(*)
                FROM encomendas
                WHERE utilizador_id = $id
            ");

            if ($temEncomendas > 0) {
                throw new Exception('Este utilizador tem encomendas associadas e não pode ser removido para não quebrar o histórico.');
            }

            $stmt = $db->prepare("
                UPDATE restaurantes
                SET utilizador_id = NULL
                WHERE utilizador_id = :id
            ");

            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $stmt = $db->prepare("
                DELETE FROM utilizadores
                WHERE id = :id
                  AND id != :me
            ");

            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':me', (int)$_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->execute();

            $mensagem = 'Utilizador removido com sucesso.';
        }

        if ($acao === 'adicionar_restaurante') {
            $nome = trim($_POST['nome'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $morada = trim($_POST['morada'] ?? '');
            $ativo = (int)($_POST['ativo'] ?? 1);
            $utilizadorId = (int)($_POST['utilizador_id'] ?? 0);

            if ($nome === '') {
                throw new Exception('O nome do restaurante é obrigatório.');
            }

            if (!in_array($ativo, [0, 1], true)) {
                $ativo = 1;
            }

            validarUtilizadorParaRestaurante($db, $utilizadorId, 0);

            $stmt = $db->prepare("
                INSERT INTO restaurantes (
                    nome,
                    categoria,
                    morada,
                    ativo,
                    utilizador_id
                )
                VALUES (
                    :nome,
                    :categoria,
                    :morada,
                    :ativo,
                    :utilizador_id
                )
            ");

            $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
            $stmt->bindValue(':categoria', $categoria, SQLITE3_TEXT);
            $stmt->bindValue(':morada', $morada, SQLITE3_TEXT);
            $stmt->bindValue(':ativo', $ativo, SQLITE3_INTEGER);

            if ($utilizadorId > 0) {
                $stmt->bindValue(':utilizador_id', $utilizadorId, SQLITE3_INTEGER);
            } else {
                $stmt->bindValue(':utilizador_id', null, SQLITE3_NULL);
            }

            $stmt->execute();

            /*
                Ao associar um utilizador cliente ao restaurante,
                ele passa automaticamente a ser do tipo restaurante.
            */
            if ($utilizadorId > 0) {
                $stmt = $db->prepare("
                    UPDATE utilizadores
                    SET tipo = 'restaurante'
                    WHERE id = :id
                ");

                $stmt->bindValue(':id', $utilizadorId, SQLITE3_INTEGER);
                $stmt->execute();
            }

            $mensagem = 'Restaurante criado com sucesso.';
        }

        if ($acao === 'atualizar_restaurante') {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $morada = trim($_POST['morada'] ?? '');
            $ativo = (int)($_POST['ativo'] ?? 1);
            $utilizadorId = (int)($_POST['utilizador_id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('Restaurante inválido.');
            }

            if ($nome === '') {
                throw new Exception('O nome do restaurante é obrigatório.');
            }

            if (!in_array($ativo, [0, 1], true)) {
                $ativo = 1;
            }

            validarUtilizadorParaRestaurante($db, $utilizadorId, $id);

            $stmt = $db->prepare("
                UPDATE restaurantes
                SET nome = :nome,
                    categoria = :categoria,
                    morada = :morada,
                    ativo = :ativo,
                    utilizador_id = :utilizador_id
                WHERE id = :id
            ");

            $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
            $stmt->bindValue(':categoria', $categoria, SQLITE3_TEXT);
            $stmt->bindValue(':morada', $morada, SQLITE3_TEXT);
            $stmt->bindValue(':ativo', $ativo, SQLITE3_INTEGER);

            if ($utilizadorId > 0) {
                $stmt->bindValue(':utilizador_id', $utilizadorId, SQLITE3_INTEGER);
            } else {
                $stmt->bindValue(':utilizador_id', null, SQLITE3_NULL);
            }

            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            if ($utilizadorId > 0) {
                $stmt = $db->prepare("
                    UPDATE utilizadores
                    SET tipo = 'restaurante'
                    WHERE id = :id
                ");

                $stmt->bindValue(':id', $utilizadorId, SQLITE3_INTEGER);
                $stmt->execute();
            }

            $mensagem = 'Restaurante atualizado com sucesso.';
        }

        if ($acao === 'remover_restaurante') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('Restaurante inválido.');
            }

            $temEncomendas = (int)$db->querySingle("
                SELECT COUNT(*)
                FROM encomendas
                WHERE restaurante_id = $id
            ");

            if ($temEncomendas > 0) {
                $stmt = $db->prepare("
                    UPDATE restaurantes
                    SET ativo = 0
                    WHERE id = :id
                ");

                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();

                $mensagem = 'Este restaurante já tem encomendas associadas, por isso foi colocado como inativo em vez de ser apagado.';
            } else {
                $stmt = $db->prepare("
                    DELETE FROM produtos
                    WHERE restaurante_id = :id
                ");

                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();

                $stmt = $db->prepare("
                    DELETE FROM restaurantes
                    WHERE id = :id
                ");

                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();

                $mensagem = 'Restaurante removido com sucesso.';
            }
        }

    } catch (Throwable $e) {
        $erro = 'Erro: ' . $e->getMessage();
    }
}

/* =========================
   DADOS PARA APRESENTAÇÃO
========================= */

$utilizadores = $db->query("
    SELECT 
        u.id,
        u.nome,
        u.username,
        u.email,
        u.tipo,
        u.ultimo_acesso,
        r.id AS restaurante_associado_id,
        r.nome AS restaurante_associado_nome
    FROM utilizadores u
    LEFT JOIN restaurantes r ON r.utilizador_id = u.id
    ORDER BY u.id ASC
");

$restaurantes = $db->query("
    SELECT 
        r.id,
        r.nome,
        r.categoria,
        r.morada,
        r.ativo,
        r.utilizador_id,
        u.nome AS dono_nome,
        u.username AS dono_username
    FROM restaurantes r
    LEFT JOIN utilizadores u ON u.id = r.utilizador_id
    ORDER BY r.nome ASC
");

$utilizadoresParaAssociar = [];

$result = $db->query("
    SELECT 
        u.id,
        u.nome,
        u.username,
        u.tipo,
        r.id AS restaurante_associado_id
    FROM utilizadores u
    LEFT JOIN restaurantes r ON r.utilizador_id = u.id
    WHERE u.tipo IN ('cliente', 'restaurante')
    ORDER BY u.nome ASC
");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $utilizadoresParaAssociar[] = $row;
}

$encomendas = $db->query("
    SELECT 
        e.id,
        e.data,
        e.estado,
        e.total,
        u.nome AS cliente,
        r.nome AS restaurante
    FROM encomendas e
    JOIN utilizadores u ON u.id = e.utilizador_id
    JOIN restaurantes r ON r.id = e.restaurante_id
    ORDER BY e.data DESC
    LIMIT 20
");

$totalUsers = $db->querySingle("SELECT COUNT(*) FROM utilizadores");
$totalRest = $db->querySingle("SELECT COUNT(*) FROM restaurantes");
$totalEnc = $db->querySingle("SELECT COUNT(*) FROM encomendas");

function optionsUtilizadoresRestaurante($utilizadoresParaAssociar, $utilizadorAtualId, $restauranteAtualId) {
    echo '<option value="0">Sem associação</option>';

    foreach ($utilizadoresParaAssociar as $u) {
        $uid = (int)$u['id'];
        $associadoId = $u['restaurante_associado_id'] !== null ? (int)$u['restaurante_associado_id'] : 0;

        /*
            Mostra:
            - utilizadores sem restaurante;
            - o utilizador já associado ao restaurante atual.
            Esconde utilizadores associados a outros restaurantes.
        */
        if ($associadoId !== 0 && $associadoId !== (int)$restauranteAtualId) {
            continue;
        }

        $selected = ((int)$utilizadorAtualId === $uid) ? ' selected' : '';
        $tipoTexto = $u['tipo'] === 'cliente' ? 'cliente → restaurante' : 'restaurante';

        echo '<option value="' . $uid . '"' . $selected . '>';
        echo h($u['nome']) . ' (@' . h($u['username']) . ') - ' . h($tipoTexto);
        echo '</option>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin | FoodToGo</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="pagina-painel">

<header>
    <h1 class="logo">Food<span>ToGo</span></h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="restaurantes.php">Restaurantes</a>
        <span class="nav-utilizador">Admin: <?= h($_SESSION['nome']) ?></span>
        <a href="scripts/logout.php" class="nav-destaque">Sair</a>
    </nav>
</header>

<main>
    <section class="painel-container">
        <div class="titulo-pagina">
            <h2>Painel Administrador</h2>
            <p>
                Gestão da plataforma: utilizadores, restaurantes e encomendas.
                A associação do utilizador ao restaurante é feita diretamente na gestão de restaurantes.
            </p>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem-ok"><?= h($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="mensagem-erro"><?= h($erro) ?></div>
        <?php endif; ?>

        <div class="painel-grid">
            <div class="card">
                <h3><?= (int)$totalUsers ?></h3>
                <p>Utilizadores</p>
            </div>

            <div class="card">
                <h3><?= (int)$totalRest ?></h3>
                <p>Restaurantes</p>
            </div>

            <div class="card">
                <h3><?= (int)$totalEnc ?></h3>
                <p>Encomendas</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="tab-utilizadores">1. Utilizadores</button>
            <button class="tab-btn" data-tab="tab-restaurantes">2. Restaurantes</button>
            <button class="tab-btn" data-tab="tab-encomendas">3. Encomendas</button>
        </div>

        <div id="tab-utilizadores" class="tab-content active">
            <h3>Gerir Utilizadores</h3>

            <p class="small">
                O tipo Restaurante não é escolhido manualmente nesta aba.
                Para transformar um utilizador em restaurante, associe-o a um restaurante na aba Restaurantes.
                Se já estiver associado, o tipo fica bloqueado.
            </p>

            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Restaurante associado</th>
                        <th>Último acesso</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($u = $utilizadores->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <form method="POST">
                                <td>
                                    <?= (int)$u['id'] ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                </td>

                                <td>
                                    <input name="nome" value="<?= h($u['nome']) ?>" required>
                                </td>

                                <td>
                                    <input name="username" value="<?= h($u['username']) ?>" required>
                                </td>

                                <td>
                                    <input type="email" name="email" value="<?= h($u['email']) ?>" required>
                                </td>

                                <td>
                                    <select name="tipo">
                                        <?php if ($u['restaurante_associado_id']): ?>

                                            <option value="restaurante" selected>
                                                Restaurante
                                            </option>

                                        <?php else: ?>

                                            <option value="cliente" <?= $u['tipo'] === 'cliente' ? 'selected' : '' ?>>
                                                Cliente
                                            </option>

                                            <option value="admin" <?= $u['tipo'] === 'admin' ? 'selected' : '' ?>>
                                                Admin
                                            </option>

                                        <?php endif; ?>
                                    </select>

                                    <?php if ($u['restaurante_associado_id']): ?>
                                        <br>
                                        <span class="small">
                                            Tipo bloqueado por associação.
                                        </span>
                                    <?php else: ?>
                                        <br>
                                        <span class="small">
                                            Para tornar restaurante, associe na aba Restaurantes.
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($u['restaurante_associado_nome']): ?>
                                        <?= h($u['restaurante_associado_nome']) ?>
                                    <?php else: ?>
                                        <span class="small">Sem restaurante</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($u['ultimo_acesso'] ?? 'Sem registo') ?>
                                </td>

                                <td class="acoes">
                                    <button class="btn-primary" name="acao" value="atualizar_utilizador">
                                        Guardar
                                    </button>

                                    <button 
                                        class="btn-secondary" 
                                        name="acao" 
                                        value="remover_utilizador"
                                        onclick="return confirm('Remover utilizador?')"
                                    >
                                        Remover
                                    </button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="tab-restaurantes" class="tab-content">
            <h3>Criar Restaurante</h3>

            <form method="POST" class="form-admin">
                <input name="nome" placeholder="Nome do restaurante" required>
                <input name="categoria" placeholder="Categoria">
                <input name="morada" placeholder="Morada">

                <label>Utilizador associado</label>
                <select name="utilizador_id">
                    <?php optionsUtilizadoresRestaurante($utilizadoresParaAssociar, 0, 0); ?>
                </select>

                <label>Estado</label>
                <select name="ativo">
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>

                <button class="btn-primary" name="acao" value="adicionar_restaurante">
                    Criar restaurante
                </button>
            </form>

            <h3>Gerir Restaurantes</h3>

            <p class="small">
                Cada restaurante pode ter no máximo um utilizador associado.
                Cada utilizador também só pode estar associado a um restaurante.
                Ao associar um cliente a um restaurante, o sistema altera automaticamente o tipo desse utilizador para Restaurante.
            </p>

            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Morada</th>
                        <th>Estado</th>
                        <th>Utilizador associado</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($r = $restaurantes->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <form method="POST">
                                <td>
                                    <?= (int)$r['id'] ?>
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                </td>

                                <td>
                                    <input name="nome" value="<?= h($r['nome']) ?>" required>
                                </td>

                                <td>
                                    <input name="categoria" value="<?= h($r['categoria'] ?? '') ?>">
                                </td>

                                <td>
                                    <input name="morada" value="<?= h($r['morada'] ?? '') ?>">
                                </td>

                                <td>
                                    <select name="ativo">
                                        <option value="1" <?= (int)$r['ativo'] === 1 ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= (int)$r['ativo'] === 0 ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </td>

                                <td>
                                    <select name="utilizador_id">
                                        <?php optionsUtilizadoresRestaurante(
                                            $utilizadoresParaAssociar,
                                            (int)($r['utilizador_id'] ?? 0),
                                            (int)$r['id']
                                        ); ?>
                                    </select>

                                    <?php if ($r['dono_nome']): ?>
                                        <br>
                                        <span class="small">
                                            Atual: <?= h($r['dono_nome']) ?> (@<?= h($r['dono_username']) ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="acoes">
                                    <button class="btn-primary" name="acao" value="atualizar_restaurante">
                                        Guardar
                                    </button>

                                    <button 
                                        class="btn-secondary" 
                                        name="acao" 
                                        value="remover_restaurante"
                                        onclick="return confirm('Remover restaurante? Se já tiver encomendas, será apenas colocado como inativo.')"
                                    >
                                        Remover/Inativar
                                    </button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="tab-encomendas" class="tab-content">
            <h3>Últimas encomendas da plataforma</h3>

            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Cliente</th>
                        <th>Restaurante</th>
                        <th>Data</th>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $tem = false;
                    while ($e = $encomendas->fetchArray(SQLITE3_ASSOC)):
                        $tem = true;
                    ?>
                        <tr>
                            <td>#<?= (int)$e['id'] ?></td>
                            <td><?= h($e['cliente']) ?></td>
                            <td><?= h($e['restaurante']) ?></td>
                            <td><?= h($e['data']) ?></td>
                            <td><?= h($e['estado']) ?></td>
                            <td><?= number_format((float)$e['total'], 2, ',', '.') ?> €</td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (!$tem): ?>
                        <tr>
                            <td colspan="6">Sem encomendas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>
</main>

<footer>
    <p>© 2026 FoodToGo</p>
</footer>

<script src="scripts/app.js"></script>
</body>
</html>