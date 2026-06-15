<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <h1 class="logo"><a href="index.php">Food<span>ToGo</span></a></h1>

    <nav>
        <a href="index.php">Home</a>
        <a href="restaurantes.php">Restaurantes</a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <?php if ($_SESSION['tipo'] === 'cliente'): ?>
                <a href="carrinho.php">Carrinho</a>
            <?php endif; ?>

            <?php if ($_SESSION['tipo'] === 'admin'): ?>
                <a href="paineladmin.php">Painel Admin</a>
            <?php endif; ?>

            <?php if ($_SESSION['tipo'] === 'restaurante'): ?>
                <a href="painelrestaurante.php">Painel Restaurante</a>
            <?php endif; ?>

            <span class="nav-utilizador">
                Olá, <?php echo htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?>
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
