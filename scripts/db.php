<?php
try {
    $db = new SQLite3(__DIR__ . '/../data/foodtogo.db');
    $db->enableExceptions(true);
} catch (Exception $e) {
    die("Erro ao ligar à base de dados: " . $e->getMessage());
}
?>