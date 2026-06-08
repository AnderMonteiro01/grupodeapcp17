<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodToGo</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
<header>
    <h1>FoodToGo</h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="restaurantes.php">Restaurantes</a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <?php if ($_SESSION['tipo'] === 'cliente'): ?>
                <a href="carrinho.php">Carrinho</a>
            <?php endif; ?>

            <span class="nav-utilizador">
                Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?>
            </span>

            <a href="scripts/logout.php" class="nav-destaque">Sair</a>

        <?php else: ?>

            <a href="login.html" class="nav-destaque">Login/Registo</a>

        <?php endif; ?>
    </nav>
</header>
<main class="home-main">
    <div><img src="images/imagem.jpg" alt="Comida FoodToGo" onerror="this.style.display='none'"></div>
    <div>
        <h2>Peça a sua comida favorita online, de forma simples e rápida.</h2>
        <p>Descubra restaurantes, consulte menus e faça encomendas com uma conta registada.</p>
        <br>
        <a href="restaurantes.php" class="botao-verde">Ver Restaurantes &rarr;</a>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.html" class="botao-laranja">Entrar / Registar</a>
        <?php endif; ?>
    </div>
</main>
<footer><p>© 2026 FoodToGo - Todos os direitos reservados.</p></footer>
<script src="scripts/app.js"></script>
</body>
</html>
