const CART_KEY = 'foodtogo_cart';

function getCart(){
    try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch(e){ return []; }
}
function saveCart(cart){ localStorage.setItem(CART_KEY, JSON.stringify(cart)); }
function clearCart(){ localStorage.removeItem(CART_KEY); }
function addProduct(product){
    const cart = getCart();
    const existing = cart.find(item => item.produto_id === product.produto_id);
    if(existing){ existing.quantidade += 1; }
    else { cart.push({...product, quantidade: 1}); }
    saveCart(cart);
    alert('Produto adicionado ao carrinho.');
    updateCartCounter();
}
function updateCartCounter(){
    const el = document.querySelector('[data-cart-count]');
    if(!el) return;
    const total = getCart().reduce((sum,item)=>sum + Number(item.quantidade || 0), 0);
    el.textContent = total;
}
function renderCart(){
    const tbody = document.querySelector('#cart-body');
    const totalEl = document.querySelector('#cart-total');
    const input = document.querySelector('#cart-json');
    if(!tbody || !totalEl) return;
    const cart = getCart();
    tbody.innerHTML = '';
    let total = 0;
    if(cart.length === 0){
        tbody.innerHTML = '<tr><td colspan="5">Carrinho vazio.</td></tr>';
    } else {
        cart.forEach((item, index) => {
            const subtotal = Number(item.preco) * Number(item.quantidade);
            total += subtotal;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${escapeHtml(item.produto_nome)}</strong><br><span>${escapeHtml(item.restaurante_nome)}</span></td>
                <td>${Number(item.preco).toFixed(2)} €</td>
                <td><input type="number" min="1" value="${item.quantidade}" data-cart-qty="${index}" style="width:80px"></td>
                <td>${subtotal.toFixed(2)} €</td>
                <td><button type="button" class="btn-secondary" data-cart-remove="${index}">Remover</button></td>`;
            tbody.appendChild(tr);
        });
    }
    totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
    if(input) input.value = JSON.stringify(cart);
}
function escapeHtml(str){
    return String(str).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
}

document.addEventListener('click', e => {
    const addBtn = e.target.closest('[data-add-product]');
    if(addBtn){
        addProduct({
            produto_id: Number(addBtn.dataset.produtoId),
            restaurante_id: Number(addBtn.dataset.restauranteId),
            restaurante_nome: addBtn.dataset.restauranteNome,
            produto_nome: addBtn.dataset.produtoNome,
            preco: Number(addBtn.dataset.preco)
        });
    }
    const removeBtn = e.target.closest('[data-cart-remove]');
    if(removeBtn){
        const cart = getCart();
        cart.splice(Number(removeBtn.dataset.cartRemove), 1);
        saveCart(cart);
        renderCart(); updateCartCounter();
    }
    const tabBtn = e.target.closest('[data-tab]');
    if(tabBtn){
        document.querySelectorAll('[data-tab]').forEach(b=>b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
        tabBtn.classList.add('active');
        const target = document.getElementById(tabBtn.dataset.tab);
        if(target) target.classList.add('active');
    }
});

document.addEventListener('input', e => {
    const qty = e.target.closest('[data-cart-qty]');
    if(qty){
        const cart = getCart();
        const idx = Number(qty.dataset.cartQty);
        cart[idx].quantidade = Math.max(1, Number(qty.value || 1));
        saveCart(cart); renderCart(); updateCartCounter();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    updateCartCounter(); renderCart();
    if(document.body.dataset.clearCart === '1') { clearCart(); renderCart(); updateCartCounter(); }
});
