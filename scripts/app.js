/*
    FoodToGo - app.js

    Lógica JavaScript geral:
    - mensagens visuais;
    - validação visual de formulários;
    - login com fetch/AJAX;
    - mensagens após registo;
    - abas do painel de administração;
    - painel de informação do projeto no rodapé.
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
   VALIDAÇÃO DE FORMULÁRIOS
========================= */

function campoIgnorado(campo) {
    return ['hidden', 'submit', 'button', 'reset'].includes(campo.type);
}

function mensagemErroCampo(campo) {
    if (campo.validity && campo.validity.valueMissing) {
        return 'Este campo é obrigatório.';
    }

    if (campo.type === 'email' && campo.value.trim() !== '' && !campo.checkValidity()) {
        return 'Insira um email válido.';
    }

    if (campo.type === 'tel' && campo.value.trim() !== '' && !/^9[0-9]{8}$/.test(campo.value.trim())) {
        return 'O telemóvel deve ter 9 dígitos e começar por 9.';
    }

    if (campo.name && campo.name.toLowerCase().includes('preco')) {
        const valor = Number(String(campo.value).replace(',', '.'));

        if (!Number.isFinite(valor) || valor <= 0) {
            return 'Insira um preço válido e superior a zero.';
        }
    }

    if (campo.type === 'number') {
        const valor = Number(campo.value);
        const min = campo.min !== '' ? Number(campo.min) : null;
        const max = campo.max !== '' ? Number(campo.max) : null;

        if (campo.value !== '' && (!Number.isFinite(valor) || (min !== null && valor < min) || (max !== null && valor > max))) {
            return 'Insira um valor dentro dos limites permitidos.';
        }
    }

    return '';
}

function obterCaixaErro(campo) {
    if (!campo.id) {
        campo.id = 'campo-' + Math.random().toString(36).slice(2);
    }

    const idErro = campo.id + '-erro';
    let erro = document.getElementById(idErro);

    if (!erro) {
        erro = document.createElement('small');
        erro.id = idErro;
        erro.className = 'mensagem-campo';
        campo.insertAdjacentElement('afterend', erro);
    }

    return erro;
}

function marcarCampoInvalido(campo, mensagem) {
    const erro = obterCaixaErro(campo);

    campo.classList.add('campo-invalido');
    campo.setAttribute('aria-invalid', 'true');
    campo.setAttribute('aria-describedby', erro.id);
    erro.textContent = mensagem;
}

function limparCampoInvalido(campo) {
    campo.classList.remove('campo-invalido');
    campo.removeAttribute('aria-invalid');

    const idErro = campo.id ? campo.id + '-erro' : '';
    const erro = idErro ? document.getElementById(idErro) : null;

    if (erro) {
        erro.textContent = '';
    }
}

function validarCampo(campo) {
    if (campoIgnorado(campo) || campo.disabled) {
        return true;
    }

    const mensagem = mensagemErroCampo(campo);

    if (mensagem !== '') {
        marcarCampoInvalido(campo, mensagem);
        return false;
    }

    limparCampoInvalido(campo);
    return true;
}

function validarFormulario(form) {
    const campos = Array.from(form.elements);
    let valido = true;
    let primeiroInvalido = null;

    campos.forEach(campo => {
        if (!validarCampo(campo)) {
            valido = false;
            primeiroInvalido = primeiroInvalido || campo;
        }
    });

    if (!valido && primeiroInvalido) {
        primeiroInvalido.focus();
        mostrarMensagem('Corrija os campos assinalados antes de continuar.', 'erro');
    }

    return valido;
}

function setupValidacaoFormularios() {
    document.querySelectorAll('form').forEach(form => {
        form.setAttribute('novalidate', 'novalidate');

        Array.from(form.elements).forEach(campo => {
            campo.addEventListener('input', () => {
                validarCampo(campo);
            });

            campo.addEventListener('change', () => {
                validarCampo(campo);
            });
        });

        form.addEventListener('submit', event => {
            if (!validarFormulario(form)) {
                event.preventDefault();
            }
        });
    });
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
        if (event.defaultPrevented || !validarFormulario(formLogin)) {
            event.preventDefault();
            return;
        }

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
   INFORMAÇÃO DO PROJETO
========================= */

function setupInfoGrupoRodape() {
    document.querySelectorAll('[data-info-grupo]').forEach(botao => {
        const painel = document.getElementById(botao.getAttribute('aria-controls'));

        if (!painel) {
            return;
        }

        function abrir() {
            painel.hidden = false;
            botao.setAttribute('aria-expanded', 'true');
        }

        function fechar() {
            painel.hidden = true;
            botao.setAttribute('aria-expanded', 'false');
        }

        painel.addEventListener('click', event => {
            event.stopPropagation();
        });

        botao.addEventListener('click', event => {
            event.stopPropagation();

            if (!painel.hidden) {
                fechar();
                return;
            }

            abrir();
        });

        botao.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                fechar();
                botao.focus();
            }
        });

        document.addEventListener('click', event => {
            if (!botao.contains(event.target) && !painel.contains(event.target)) {
                fechar();
            }
        });
    });
}

/* =========================
   INICIALIZAÇÃO
========================= */

window.mostrarMensagem = mostrarMensagem;
window.validarFormulario = validarFormulario;

document.addEventListener('DOMContentLoaded', () => {
    processLoginMessages();
    setupValidacaoFormularios();
    setupLoginAjax();
    setupTabsAdmin();
    setupInfoGrupoRodape();
});
