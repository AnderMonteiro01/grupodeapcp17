<?php
session_start();
require_once __DIR__ . '/scripts/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] ?? '') !== 'restaurante') { header('Location: login.html'); exit; }
$mensagem=''; $erro=''; $userId=(int)$_SESSION['user_id'];
$stmt=$db->prepare("SELECT * FROM restaurantes WHERE utilizador_id=:uid");
$stmt->bindValue(':uid',$userId,SQLITE3_INTEGER);
$restaurante=$stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($_SERVER['REQUEST_METHOD']==='POST' && $restaurante) {
    $acao=$_POST['acao'] ?? '';
    try {
        if ($acao==='atualizar_restaurante') {
            $stmt=$db->prepare("UPDATE restaurantes SET nome=:nome,categoria=:categoria,morada=:morada,ativo=:ativo WHERE id=:id AND utilizador_id=:uid");
            $stmt->bindValue(':nome',trim($_POST['nome']),SQLITE3_TEXT);
            $stmt->bindValue(':categoria',trim($_POST['categoria']),SQLITE3_TEXT);
            $stmt->bindValue(':morada',trim($_POST['morada']),SQLITE3_TEXT);
            $stmt->bindValue(':ativo',(int)$_POST['ativo'],SQLITE3_INTEGER);
            $stmt->bindValue(':id',(int)$restaurante['id'],SQLITE3_INTEGER);
            $stmt->bindValue(':uid',$userId,SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Dados do restaurante atualizados.';
        }
        if ($acao==='adicionar_produto') {
            $stmt=$db->prepare("INSERT INTO produtos (restaurante_id,nome,descricao,preco,disponivel) VALUES (:rid,:nome,:descricao,:preco,:disp)");
            $stmt->bindValue(':rid',(int)$restaurante['id'],SQLITE3_INTEGER);
            $stmt->bindValue(':nome',trim($_POST['nome']),SQLITE3_TEXT);
            $stmt->bindValue(':descricao',trim($_POST['descricao']),SQLITE3_TEXT);
            $stmt->bindValue(':preco',(float)str_replace(',','.',$_POST['preco']),SQLITE3_FLOAT);
            $stmt->bindValue(':disp',(int)$_POST['disponivel'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Produto adicionado ao menu.';
        }
        if ($acao==='atualizar_produto') {
            $stmt=$db->prepare("UPDATE produtos SET nome=:nome,descricao=:descricao,preco=:preco,disponivel=:disp WHERE id=:pid AND restaurante_id=:rid");
            $stmt->bindValue(':nome',trim($_POST['nome']),SQLITE3_TEXT);
            $stmt->bindValue(':descricao',trim($_POST['descricao']),SQLITE3_TEXT);
            $stmt->bindValue(':preco',(float)str_replace(',','.',$_POST['preco']),SQLITE3_FLOAT);
            $stmt->bindValue(':disp',(int)$_POST['disponivel'],SQLITE3_INTEGER);
            $stmt->bindValue(':pid',(int)$_POST['produto_id'],SQLITE3_INTEGER);
            $stmt->bindValue(':rid',(int)$restaurante['id'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Produto atualizado.';
        }
        if ($acao==='remover_produto') {
            $stmt=$db->prepare("DELETE FROM produtos WHERE id=:pid AND restaurante_id=:rid");
            $stmt->bindValue(':pid',(int)$_POST['produto_id'],SQLITE3_INTEGER);
            $stmt->bindValue(':rid',(int)$restaurante['id'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Produto removido.';
        }
        if ($acao==='atualizar_estado') {
            $permitidos=['recebida','em preparação','concluída','cancelada'];
            $estado=$_POST['estado'] ?? 'recebida';
            if(!in_array($estado,$permitidos,true)){ throw new Exception('Estado inválido.'); }
            $stmt=$db->prepare("UPDATE encomendas SET estado=:estado WHERE id=:eid AND restaurante_id=:rid");
            $stmt->bindValue(':estado',$estado,SQLITE3_TEXT);
            $stmt->bindValue(':eid',(int)$_POST['encomenda_id'],SQLITE3_INTEGER);
            $stmt->bindValue(':rid',(int)$restaurante['id'],SQLITE3_INTEGER);
            $stmt->execute(); $mensagem='Estado da encomenda atualizado.';
        }
        $stmt=$db->prepare("SELECT * FROM restaurantes WHERE utilizador_id=:uid");
        $stmt->bindValue(':uid',$userId,SQLITE3_INTEGER); $restaurante=$stmt->execute()->fetchArray(SQLITE3_ASSOC);
    } catch(Exception $e){ $erro='Erro: '.$e->getMessage(); }
}
$produtos=null; $encomendas=null;
if($restaurante){
    $stmt=$db->prepare("SELECT * FROM produtos WHERE restaurante_id=:rid ORDER BY nome ASC");
    $stmt->bindValue(':rid',(int)$restaurante['id'],SQLITE3_INTEGER); $produtos=$stmt->execute();
    $stmt=$db->prepare("SELECT e.id,e.data,e.estado,e.total,e.observacoes,u.nome AS cliente FROM encomendas e JOIN utilizadores u ON u.id=e.utilizador_id WHERE e.restaurante_id=:rid ORDER BY e.data DESC LIMIT 20");
    $stmt->bindValue(':rid',(int)$restaurante['id'],SQLITE3_INTEGER); $encomendas=$stmt->execute();
}
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Painel Restaurante | FoodToGo</title><link rel="stylesheet" href="styles/styles.css"></head>
<body class="pagina-painel"><header><h1>FoodToGo</h1><nav><a href="index.html">Home</a><a href="restaurantes.php">Restaurantes</a><span class="nav-utilizador">Restaurante: <?= h($_SESSION['nome']) ?></span><a href="scripts/logout.php" class="nav-destaque">Sair</a></nav></header>
<main><section class="painel-container"><div class="titulo-pagina"><h2>Painel Restaurante</h2><p>Gestão dos dados, menu e encomendas do restaurante associado à sua conta.</p></div>
<?php if($mensagem):?><div class="mensagem-ok"><?=h($mensagem)?></div><?php endif;?><?php if($erro):?><div class="mensagem-erro"><?=h($erro)?></div><?php endif;?>
<?php if(!$restaurante): ?><div class="mensagem-info">Ainda não existe restaurante associado à sua conta. Contacte o administrador.</div>
<?php else: ?>
<div class="tabs"><button class="tab-btn active" data-tab="tab-dados">Dados do restaurante</button><button class="tab-btn" data-tab="tab-menu">Menu</button><button class="tab-btn" data-tab="tab-encomendas-rest">Encomendas</button></div>
<div id="tab-dados" class="tab-content active"><h3>Dados principais</h3><form method="POST" class="form-bloco"><label>Nome</label><input name="nome" value="<?=h($restaurante['nome'])?>" required><label>Categoria</label><input name="categoria" value="<?=h($restaurante['categoria'])?>"><label>Morada</label><input name="morada" value="<?=h($restaurante['morada'])?>"><label>Disponibilidade do restaurante</label><select name="ativo"><option value="1" <?= (int)$restaurante['ativo']===1?'selected':'' ?>>Ativo</option><option value="0" <?= (int)$restaurante['ativo']===0?'selected':'' ?>>Inativo</option></select><button class="btn-primary" name="acao" value="atualizar_restaurante">Guardar dados</button></form></div>
<div id="tab-menu" class="tab-content"><h3>Adicionar produto</h3><form method="POST" class="form-inline"><input name="nome" placeholder="Produto" required><input name="descricao" placeholder="Descrição"><input name="preco" placeholder="Preço" required><select name="disponivel"><option value="1">Disponível</option><option value="0">Indisponível</option></select><button class="btn-primary" name="acao" value="adicionar_produto">Adicionar</button></form><h3>Produtos do menu</h3><table class="tabela-admin"><thead><tr><th>Produto</th><th>Descrição</th><th>Preço</th><th>Disponibilidade</th><th>Ações</th></tr></thead><tbody><?php $tem=false; while($p=$produtos->fetchArray(SQLITE3_ASSOC)): $tem=true; ?><tr><form method="POST"><td><input name="nome" value="<?=h($p['nome'])?>" required><input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>"></td><td><input name="descricao" value="<?=h($p['descricao'])?>"></td><td><input name="preco" value="<?=number_format((float)$p['preco'],2,'.','')?>" required></td><td><select name="disponivel"><option value="1" <?= (int)$p['disponivel']===1?'selected':'' ?>>Disponível</option><option value="0" <?= (int)$p['disponivel']===0?'selected':'' ?>>Indisponível</option></select></td><td class="acoes"><button class="btn-primary" name="acao" value="atualizar_produto">Guardar</button><button class="btn-secondary" name="acao" value="remover_produto" onclick="return confirm('Remover produto?')">Remover</button></td></form></tr><?php endwhile; if(!$tem): ?><tr><td colspan="5">Sem produtos no menu.</td></tr><?php endif; ?></tbody></table></div>
<div id="tab-encomendas-rest" class="tab-content"><h3>Encomendas recebidas</h3><table class="tabela-admin"><thead><tr><th>Nº</th><th>Cliente</th><th>Data</th><th>Observações</th><th>Total</th><th>Estado</th><th>Ação</th></tr></thead><tbody><?php $tem=false; while($e=$encomendas->fetchArray(SQLITE3_ASSOC)): $tem=true; ?><tr><form method="POST"><td>#<?= (int)$e['id'] ?><input type="hidden" name="encomenda_id" value="<?= (int)$e['id'] ?>"></td><td><?=h($e['cliente'])?></td><td><?=h($e['data'])?></td><td><?=h($e['observacoes'])?></td><td><?=number_format((float)$e['total'],2,',','.')?> €</td><td><select name="estado"><option value="recebida" <?= $e['estado']==='recebida'?'selected':'' ?>>Recebida</option><option value="em preparação" <?= $e['estado']==='em preparação'?'selected':'' ?>>Em preparação</option><option value="concluída" <?= $e['estado']==='concluída'?'selected':'' ?>>Concluída</option><option value="cancelada" <?= $e['estado']==='cancelada'?'selected':'' ?>>Cancelada</option></select></td><td><button class="btn-primary" name="acao" value="atualizar_estado">Atualizar</button></td></form></tr><?php endwhile; if(!$tem): ?><tr><td colspan="7">Sem encomendas.</td></tr><?php endif; ?></tbody></table></div>
<?php endif; ?></section></main><footer><p>© 2026 FoodToGo</p></footer><script src="scripts/app.js"></script></body></html>
