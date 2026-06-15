<?php
try {
    $db = new SQLite3(__DIR__ . '/../data/foodtogo.db');
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
} catch (Exception $e) {
    error_log('Erro ao ligar à base de dados: ' . $e->getMessage());
    http_response_code(500);
    die('Erro ao ligar à base de dados. Tente novamente mais tarde.');
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrf_token() {
    ensure_session_started();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_validate($token) {
    ensure_session_started();

    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_login($tipo = null) {
    ensure_session_started();

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
    if ($tipo !== null && ($_SESSION['tipo'] ?? '') !== $tipo) {
        header('Location: index.php');
        exit;
    }
}
?>
