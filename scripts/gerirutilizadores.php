<?php
session_start();
require_once 'db.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');

        if ($id > 0 && $nome !== '' && $username !== '' && $email !== '' && in_array($tipo, ['cliente', 'restaurante', 'admin'])) {
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

            try {
                $stmt->execute();
                $mensagem = 'Utilizador atualizado com sucesso.';
            } catch (Exception $e) {
                $mensagem = 'Erro ao atualizar utilizador. Username ou email já pode existir.';
            }
        } else {
            $mensagem = 'Dados inválidos para atualização.';
        }
    }

    if ($acao === 'remover') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM acessos WHERE utilizador_id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $stmt = $db->prepare("DELETE FROM utilizadores WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            try {
                $stmt->execute();
                $mensagem = 'Utilizador removido com sucesso.';
            } catch (Exception $e) {
                $mensagem = 'Erro ao remover utilizador. Pode existir informação associada a este utilizador.';
            }
        }
    }
}

$result = $db->query("
    SELECT id, nome, username, email, tipo, ultimo_acesso
    FROM utilizadores
    ORDER BY id ASC
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gerir Utilizadores - FoodToGo</title>
    <link rel="stylesheet" href="../styles/styles.css">
</head>
<body>

<header>
    <h1>FoodToGo</h1>

    <nav>
        <a href="../index.html">Home</a>
        <a href="../restaurantes.php">Restaurantes</a>
        <a href="../paineladmin.html">Painel Admin</a>
        <a href="logout.php">Sair</a>
    </nav>
</header>

<main class="restaurantes-container">
    <div class="titulo-pagina">
        <h2>Gerir Utilizadores</h2>
        <p>Consultar, editar ou remover utilizadores registados.</p>
    </div>

    <?php if ($mensagem !== ''): ?>
        <p class="mensagem-erro"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <table class="tabela-restaurantes">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Username</th>
                <th>Email</th>
                <th>Tipo</th>
                <th>Último acesso</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($utilizador = $result->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <form method="POST" action="gerirutilizadores.php">
                        <td>
                            <?php echo (int)$utilizador['id']; ?>
                            <input type="hidden" name="id" value="<?php echo (int)$utilizador['id']; ?>">
                        </td>

                        <td>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($utilizador['nome']); ?>" required>
                        </td>

                        <td>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($utilizador['username']); ?>" required>
                        </td>

                        <td>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($utilizador['email']); ?>" required>
                        </td>

                        <td>
                            <select name="tipo">
                                <option value="cliente" <?php echo ($utilizador['tipo'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                                <option value="restaurante" <?php echo ($utilizador['tipo'] === 'restaurante') ? 'selected' : ''; ?>>Restaurante</option>
                                <option value="admin" <?php echo ($utilizador['tipo'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($utilizador['ultimo_acesso'] ?? 'Sem registo'); ?>
                        </td>

                        <td>
                            <button type="submit" name="acao" value="atualizar" class="btn-primary">Guardar</button>
                            <button type="submit" name="acao" value="remover" class="btn-secondary" onclick="return confirm('Tem a certeza que pretende remover este utilizador?');">Remover</button>
                        </td>
                    </form>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

<footer>
    <p>© 2026 FoodToGo</p>
</footer>

</body>
</html>