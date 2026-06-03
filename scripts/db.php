<?php
try {
    $db = new SQLite3(__DIR__ . '/../data/foodtogo.db');
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
} catch (Exception $e) {
    die('Erro ao ligar à base de dados: ' . $e->getMessage());
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function require_login($tipo = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
    if ($tipo !== null && ($_SESSION['tipo'] ?? '') !== $tipo) {
        header('Location: index.html');
        exit;
    }
}
?>
