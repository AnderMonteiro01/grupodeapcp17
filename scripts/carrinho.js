document.addEventListener('DOMContentLoaded', function() {

    // 1. Capturar todos os elementos
    const btnAdicionar = document.getElementById('btn-adicionar');
    const corpoCarrinho = document.getElementById('corpo-carrinho');
    const textoTotal = document.getElementById('texto-total');
    const caixaCarrinho = document.getElementById('caixa-carrinho');
    const txtObservacoes = document.querySelector('textarea');
    const corpoHistorico = document.getElementById('corpo-historico');
    const btnConfirmar = document.getElementById('btn-confirmar');
    const btnGrupo = document.getElementById('btn-grupo');
    const infoGrupo = document.getElementById('info-grupo');

    let total = 0.00;
    let quantidade = 0;

    // 2. Lógica do Histórico Global (com proteção anti-crash)
    function atualizarTabelaHistorico() {
        if (!corpoHistorico) return; // PROTEÇÃO: Se não houver tabela no HTML, sai da função e não crasha

        corpoHistorico.innerHTML = ''; 
        const historico = JSON.parse(localStorage.getItem('encomendasGlobais')) || [];

        historico.forEach(enc => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = "1px solid #eee";
            tr.innerHTML = `
                <td style="padding: 10px;"><strong>${enc.utilizador}</strong></td>
                <td style="padding: 10px;">${enc.pedido}</td>
                <td style="padding: 10px;">${enc.total}</td>
                <td style="padding: 10px;">${enc.data}</td>
            `;
            corpoHistorico.appendChild(tr);
        });
    }

    atualizarTabelaHistorico();

    // 3. Lógica de Adicionar ao Carrinho
    if (btnAdicionar && corpoCarrinho) {
        btnAdicionar.addEventListener('click', function() {
            const novaLinha = document.createElement('tr');
            novaLinha.innerHTML = `
                <td>Combo Sabor Caseiro</td>
                <td>1</td>
                <td>9,50 €</td>
            `;
            corpoCarrinho.appendChild(novaLinha);

            total += 9.50;
            quantidade += 1;
            textoTotal.innerHTML = `<strong>Total: ${total.toFixed(2).replace('.', ',')} €</strong>`;
            caixaCarrinho.style.border = "none";
        });
    }

    // 4. Lógica de Confirmar Encomenda
    if (btnConfirmar && caixaCarrinho) {
        btnConfirmar.addEventListener('click', function(event) {
            if (corpoCarrinho.children.length === 0) {
                event.preventDefault();
                caixaCarrinho.style.border = "2px solid red";
                alert("ERRO: O seu carrinho está vazio! Por favor, adicione um menu antes de confirmar a encomenda.");
            } else {
                let nomeUtilizador = prompt("Simulação de Login: Qual é o seu nome de utilizador?", "Cliente");
                if (!nomeUtilizador) nomeUtilizador = "Cliente Anónimo";

                alert("Sucesso! A sua encomenda foi enviada para a cozinha.");
                
                // Só tenta guardar no histórico se a tabela do histórico existir no HTML
                if (corpoHistorico) {
                    const historico = JSON.parse(localStorage.getItem('encomendasGlobais')) || [];
                    historico.push({
                        utilizador: nomeUtilizador,
                        pedido: `${quantidade}x Combo Sabor Caseiro`,
                        total: `${total.toFixed(2).replace('.', ',')} €`,
                        data: new Date().toLocaleString('pt-PT')
                    });
                    localStorage.setItem('encomendasGlobais', JSON.stringify(historico));
                    atualizarTabelaHistorico();
                }

                const observacoes = txtObservacoes ? txtObservacoes.value : '';
                caixaCarrinho.innerHTML = `
                    <h3>Encomenda Confirmada ✅</h3>
                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <p><strong>Produto:</strong> ${quantidade}x Combo Sabor Caseiro</p>
                        <p><strong>Total Pago:</strong> ${total.toFixed(2).replace('.', ',')} €</p>
                        <p><strong>Observações:</strong> ${observacoes ? observacoes : 'Nenhuma'}</p>
                    </div>
                    <h4>Estado da Encomenda</h4>
                    <p style="color: #2e7d32;"><strong>A ser preparada...</strong></p>
                    <ol style="line-height: 1.8;">
                        <li><strong>✓ Recebida</strong></li>
                        <li><strong>✓ Em preparação</strong></li>
                        <li>Pronta para recolha / entrega</li>
                        <li>Concluída</li>
                    </ol>
                `;
            }
        });
    }

    // 5. Lógica da Informação do Grupo
    if (btnGrupo && infoGrupo) {
        btnGrupo.addEventListener('click', function() {
            if (infoGrupo.style.display === "none") {
                infoGrupo.style.display = "block";
                btnGrupo.innerText = "Esconder Info do Grupo";
            } else {
                infoGrupo.style.display = "none";
                btnGrupo.innerText = "Sobre o Grupo";
            }
        });
    }

});