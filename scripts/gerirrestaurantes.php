<?php
session_start();
require_once 'db.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $morada = trim($_POST['morada'] ?? '');
        $ativo = (int)($_POST['ativo'] ?? 0);

        if ($id > 0 && $nome !== '') {
            $stmt = $db->prepare("
                UPDATE restaurantes
                SET nome = :nome,
                    categoria = :categoria,
                    morada = :morada,
                    ativo = :ativo
                WHERE id = :id
            ");

            $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
            $stmt->bindValue(':categoria', $categoria, SQLITE3_TEXT);
            $stmt->bindValue(':morada', $morada, SQLITE3_TEXT);
            $stmt->bindValue(':ativo', $ativo, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $mensagem = 'Restaurante atualizado com sucesso.';
            } else {
                $mensagem = 'Erro ao atualizar restaurante.';
            }
        } else {
            $mensagem = 'Dados inválidos para atualização.';
        }
    }

    if ($acao === 'remover') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM produtos WHERE restaurante_id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $stmt = $db->prepare("DELETE FROM restaurantes WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $mensagem = 'Restaurante removido com sucesso.';
            } else {
                $mensagem = 'Erro ao remover restaurante.';
            }
        }
    }
}

$result = $db->query("
    SELECT id, utilizador_id, nome, categoria, morada, ativo
    FROM restaurantes
    ORDER BY nome ASC
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gerir Restaurantes - FoodToGo</title>
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
        <h2>Gerir Restaurantes</h2>
        <p>Consultar, editar, ativar/desativar ou remover restaurantes.</p>
    </div>

    <?php if ($mensagem !== ''): ?>
        <p class="mensagem-erro"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <table class="tabela-restaurantes">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Morada</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($restaurante = $result->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <form method="POST" action="gerirrestaurantes.php">
                        <td>
                            <?php echo (int)$restaurante['id']; ?>
                            <input type="hidden" name="id" value="<?php echo (int)$restaurante['id']; ?>">
                        </td>

                        <td>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($restaurante['nome']); ?>" required>
                        </td>

                        <td>
                            <input type="text" name="categoria" value="<?php echo htmlspecialchars($restaurante['categoria'] ?? ''); ?>">
                        </td>

                        <td>
                            <input type="text" name="morada" value="<?php echo htmlspecialchars($restaurante['morada'] ?? ''); ?>">
                        </td>

                        <td>
                            <select name="ativo">
                                <option value="1" <?php echo ((int)$restaurante['ativo'] === 1) ? 'selected' : ''; ?>>Ativo</option>
                                <option value="0" <?php echo ((int)$restaurante['ativo'] === 0) ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </td>

                        <td>
                            <button type="submit" name="acao" value="atualizar" class="btn-primary">Guardar</button>
                            <button type="submit" name="acao" value="remover" class="btn-secondary" onclick="return confirm('Tem a certeza que pretende remover este restaurante?');">Remover</button>
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