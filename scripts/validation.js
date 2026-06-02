const usernamesExistentes = ["admin", "andre", "user123"];
document.querySelector('form[action="scripts/novoregisto.php"]').addEventListener("submit", function(e) {

    let nome = document.querySelector('input[name="nome"]');
    let username = document.querySelector('input[name="username"]');
    let email = document.querySelector('input[name="email"]');
    let password = document.querySelector('input[name="password"]');

    let erros = [];

    resetStyles([nome, username, email, password]);
    if (nome.value.trim() === "") {
        erros.push("Nome é obrigatório");
        marcarErro(nome);
    }
    if (username.value.trim() === "") {
        erros.push("Username é obrigatório");
        marcarErro(username);
    } else if (usernamesExistentes.includes(username.value.toLowerCase())) {
        erros.push("Username já existe");
        marcarErro(username);
    }
    if (!email.value.includes("@")) {
        erros.push("Email inválido");
        marcarErro(email);
    }
    let pass = password.value;


    if (pass.length < 5) {
        erros.push("Password deve ter pelo menos 5 caracteres");
        marcarErro(password);
    }


    if (!/[0-9]/.test(pass)) {
        erros.push("Password deve conter pelo menos 1 número");
        marcarErro(password);
    }


    if (!pass.includes("@")) {
        erros.push("Password deve conter pelo menos um '@'");
        marcarErro(password);
    }

    if (/[áàâãéèêíìîóòôõúùûç]/i.test(pass)) {
        erros.push("Password não pode conter acentos");
        marcarErro(password);
    }

    if (erros.length > 0) {
        e.preventDefault();
        alert(erros.join("\n"));
    }
});
function marcarErro(input) {
    input.style.border = "2px solid red";
    input.style.backgroundColor = "#ffe6e6";
}

function resetStyles(inputs) {
    inputs.forEach(input => {
        input.style.border = "";
        input.style.backgroundColor = "";
    });
}
``
