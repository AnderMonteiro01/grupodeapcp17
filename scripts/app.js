/*
    FoodToGo - app.js

    Este ficheiro contém a lógica JavaScript geral da aplicação:
    - mensagens visuais ao utilizador;
    - tratamento do login com fetch/AJAX;
    - leitura de respostas JSON vindas do PHP;
    - mensagens após registo;
    - controlo das abas do painel de administração.

    A lógica específica do carrinho está no carrinho.php,
    porque depende diretamente de dados vindos do PHP e da base de dados.
*/

/* =========================
   MENSAGENS VISUAIS
========================= */

function mostrarMensagem(mensagem, tipo = 'erro') {
    let caixa = document.getElementById('mensagem-js');

    if (!caixa) {
        caixa = document.createElement('div');
        caixa.id = 'mensagem-js';
        caixa.className = 'mensagem-js';
        document.body.prepend(caixa);
    }

    caixa.textContent = mensagem;
    caixa.className = 'mensagem-js ' + tipo;
    caixa.style.display = 'block';

    clearTimeout(caixa.dataset.timeoutId);

    const timeoutId = setTimeout(() => {
        caixa.style.display = 'none';
    }, 4000);

    caixa.dataset.timeoutId = timeoutId;
}

/* =========================
   MENSAGENS DO REGISTO
========================= */

function processLoginMessages() {
    const params = new URLSearchParams(window.location.search);
    const registo = params.get('registo');

    if (!registo) {
        return;
    }

    const mensagens = {
        sucesso: 'Conta criada com sucesso. Já pode iniciar sessão.',
        campos: 'Preencha todos os campos do registo.',
        email_invalido: 'O email inserido não é válido.',
        duplicado: 'O username ou email já existe.',
        erro: 'Ocorreu um erro ao criar a conta.'
    };

    if (mensagens[registo]) {
        const tipo = registo === 'sucesso' ? 'sucesso' : 'erro';
        mostrarMensagem(mensagens[registo], tipo);

        /*
            Remove o parâmetro ?registo=... do URL depois de mostrar a mensagem,
            para a mensagem não voltar a aparecer ao recarregar a página.
        */
        window.history.replaceState({}, document.title, 'login.html');
    }
}

/* =========================
   LOGIN COM FETCH + JSON
========================= */

function setupLoginAjax() {
    const formLogin = document.getElementById('form-login');

    if (!formLogin) {
        return;
    }

    formLogin.addEventListener('submit', event => {
        event.preventDefault();

        const dados = new FormData(formLogin);

        fetch('scripts/login.php', {
            method: 'POST',
            body: dados
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor.');
            }

            return response.json();
        })
        .then(data => {
            if (data.sucesso) {
                window.location.href = data.redirect;
            } else {
                mostrarMensagem(data.mensagem || 'Credenciais inválidas.', 'erro');
            }
        })
        .catch(error => {
            console.error(error);
            mostrarMensagem('Ocorreu um erro ao tentar iniciar sessão.', 'erro');
        });
    });
}

/* =========================
   ABAS DO PAINEL ADMIN
========================= */

function setupTabsAdmin() {
    document.addEventListener('click', event => {
        const tabBtn = event.target.closest('[data-tab]');

        if (!tabBtn) {
            return;
        }

        document.querySelectorAll('[data-tab]').forEach(botao => {
            botao.classList.remove('active');
        });

        document.querySelectorAll('.tab-content').forEach(conteudo => {
            conteudo.classList.remove('active');
        });

        tabBtn.classList.add('active');

        const alvo = document.getElementById(tabBtn.dataset.tab);

        if (alvo) {
            alvo.classList.add('active');
        }
    });
}

/* =========================
   INICIALIZAÇÃO
========================= */

document.addEventListener('DOMContentLoaded', () => {
    processLoginMessages();
    setupLoginAjax();
    setupTabsAdmin();
});