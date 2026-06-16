<?php
function garantir_encomendas_permitem_cliente_apagado($db) {
    $existe = (int)$db->querySingle("
        SELECT COUNT(*)
        FROM sqlite_master
        WHERE type = 'table'
          AND name = 'encomendas'
    ");

    if ($existe === 0) {
        return;
    }

    $colunas = [];
    $utilizadorObrigatorio = false;
    $result = $db->query("PRAGMA table_info(encomendas)");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $colunas[] = $row['name'];

        if ($row['name'] === 'utilizador_id') {
            $utilizadorObrigatorio = ((int)$row['notnull'] === 1);
        }
    }

    if (!$utilizadorObrigatorio) {
        return;
    }

    $colunasNecessarias = [
        'id',
        'utilizador_id',
        'restaurante_id',
        'data',
        'estado',
        'total',
        'morada_entrega',
        'contacto_cliente',
        'observacoes'
    ];

    foreach ($colunasNecessarias as $coluna) {
        if (!in_array($coluna, $colunas, true)) {
            return;
        }
    }

    $db->exec('PRAGMA foreign_keys = OFF');

    try {
        $db->exec('BEGIN TRANSACTION');
        $db->exec('DROP TABLE IF EXISTS encomendas_migracao_cliente_apagado');
        $db->exec("
            CREATE TABLE encomendas_migracao_cliente_apagado (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                utilizador_id INTEGER,
                restaurante_id INTEGER NOT NULL,
                data TEXT NOT NULL,
                estado TEXT NOT NULL DEFAULT 'recebida',
                total REAL NOT NULL DEFAULT 0,
                morada_entrega TEXT,
                contacto_cliente TEXT,
                observacoes TEXT,
                FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL,
                FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
            )
        ");
        $db->exec("
            INSERT INTO encomendas_migracao_cliente_apagado (
                id,
                utilizador_id,
                restaurante_id,
                data,
                estado,
                total,
                morada_entrega,
                contacto_cliente,
                observacoes
            )
            SELECT
                id,
                utilizador_id,
                restaurante_id,
                data,
                estado,
                total,
                morada_entrega,
                contacto_cliente,
                observacoes
            FROM encomendas
        ");
        $db->exec('DROP TABLE encomendas');
        $db->exec('ALTER TABLE encomendas_migracao_cliente_apagado RENAME TO encomendas');
        $db->exec('COMMIT');
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        $db->exec('PRAGMA foreign_keys = ON');
        throw $e;
    }

    $db->exec('PRAGMA foreign_keys = ON');
}

try {
    $databaseDir = __DIR__ . '/../data';

    if (!is_dir($databaseDir) && !mkdir($databaseDir, 0775, true) && !is_dir($databaseDir)) {
        throw new Exception('Não foi possível criar a pasta da base de dados.');
    }

    $db = new SQLite3($databaseDir . '/foodtogo.db');
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

function estado_encomenda_texto($estado) {
    $textos = [
        'recebida' => 'Recebida',
        'em preparação' => 'Em Preparação',
        'em preparacao' => 'Em Preparação',
        'concluída' => 'Concluída',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada'
    ];

    return $textos[$estado] ?? ucfirst((string)$estado);
}

function estado_encomenda_classe($estado) {
    $classes = [
        'recebida' => 'estado-recebida',
        'em preparação' => 'estado-preparacao',
        'em preparacao' => 'estado-preparacao',
        'concluída' => 'estado-concluida',
        'concluida' => 'estado-concluida',
        'cancelada' => 'estado-cancelada'
    ];

    return $classes[$estado] ?? 'estado-desconhecido';
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
