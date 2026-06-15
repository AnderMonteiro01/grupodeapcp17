/*
    FoodToGo - carrinho.js

    Lógica de browser para o carrinho:
    - normalização do contacto;
    - controlo de quantidades;
    - cálculo de subtotais e total;
    - validação antes de confirmar a encomenda.
*/

function setupCarrinho() {
    const form = document.getElementById('form-carrinho');

    if (!form) {
        return;
    }

    const contactoInput = document.getElementById('contacto_cliente');

    if (contactoInput) {
        contactoInput.addEventListener('input', () => {
            contactoInput.value = contactoInput.value
                .replace(/\D/g, '')
                .slice(0, 9);
        });
    }

    const linhas = form.querySelectorAll('tbody tr');
    const totalCarrinho = document.getElementById('total-carrinho');
    const limiteTotalPedido = parseInt(form.dataset.limiteTotal || '20', 10);

    function formatarEuro(valor) {
        return valor.toFixed(2).replace('.', ',') + ' €';
    }

    function calcularTotalQuantidade() {
        let totalQuantidade = 0;

        form.querySelectorAll('[data-quantidade]').forEach(input => {
            const quantidade = parseInt(input.value || '0', 10);

            if (!Number.isNaN(quantidade)) {
                totalQuantidade += quantidade;
            }
        });

        return totalQuantidade;
    }

    function atualizarTotais() {
        let total = 0;

        linhas.forEach(linha => {
            const input = linha.querySelector('[data-quantidade]');
            const subtotalElemento = linha.querySelector('[data-subtotal]');

            if (!input || !subtotalElemento) {
                return;
            }

            const preco = parseFloat(input.dataset.preco || '0');
            const quantidade = parseInt(input.value || '0', 10);
            const subtotal = preco * quantidade;

            total += subtotal;
            subtotalElemento.textContent = formatarEuro(subtotal);
        });

        if (totalCarrinho) {
            totalCarrinho.textContent = formatarEuro(total);
        }
    }

    linhas.forEach(linha => {
        const input = linha.querySelector('[data-quantidade]');
        const btnMais = linha.querySelector('[data-mais]');
        const btnMenos = linha.querySelector('[data-menos]');

        if (!input || !btnMais || !btnMenos) {
            return;
        }

        btnMais.addEventListener('click', () => {
            const maximoProduto = parseInt(input.getAttribute('max') || '10', 10);
            const valorAtual = parseInt(input.value || '0', 10);
            const totalAtual = calcularTotalQuantidade();

            if (valorAtual >= maximoProduto) {
                mostrarMensagem('Limite máximo por produto atingido.', 'erro');
                return;
            }

            if (totalAtual >= limiteTotalPedido) {
                mostrarMensagem('Cada encomenda pode ter no máximo ' + limiteTotalPedido + ' unidades no total.', 'erro');
                return;
            }

            input.value = valorAtual + 1;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            atualizarTotais();
        });

        btnMenos.addEventListener('click', () => {
            const valorAtual = parseInt(input.value || '0', 10);

            if (valorAtual > 0) {
                input.value = valorAtual - 1;
            }

            input.dispatchEvent(new Event('input', { bubbles: true }));
            atualizarTotais();
        });

        input.addEventListener('input', () => {
            const maximoProduto = parseInt(input.getAttribute('max') || '10', 10);
            let valor = parseInt(input.value || '0', 10);

            if (valor < 0 || Number.isNaN(valor)) {
                valor = 0;
            }

            if (valor > maximoProduto) {
                valor = maximoProduto;
                mostrarMensagem('Limite máximo por produto atingido.', 'erro');
            }

            input.value = valor;

            const totalQuantidade = calcularTotalQuantidade();

            if (totalQuantidade > limiteTotalPedido) {
                const excesso = totalQuantidade - limiteTotalPedido;
                input.value = Math.max(0, valor - excesso);

                mostrarMensagem('Cada encomenda pode ter no máximo ' + limiteTotalPedido + ' unidades no total.', 'erro');
            }

            atualizarTotais();

            if (typeof window.validarCampo === 'function') {
                window.validarCampo(input);
            }
        });
    });

    form.addEventListener('submit', event => {
        const contacto = contactoInput ? contactoInput.value.trim() : '';
        const totalQuantidade = calcularTotalQuantidade();

        if (!/^9[0-9]{8}$/.test(contacto)) {
            event.preventDefault();
            mostrarMensagem('O telemóvel deve ter exatamente 9 dígitos e começar por 9.', 'erro');
            return;
        }

        if (totalQuantidade <= 0) {
            event.preventDefault();
            mostrarMensagem('Selecione pelo menos um produto antes de confirmar a encomenda.', 'erro');
            return;
        }

        if (totalQuantidade > limiteTotalPedido) {
            event.preventDefault();
            mostrarMensagem('Cada encomenda pode ter no máximo ' + limiteTotalPedido + ' unidades no total.', 'erro');
        }
    });

    atualizarTotais();
}

document.addEventListener('DOMContentLoaded', setupCarrinho);
