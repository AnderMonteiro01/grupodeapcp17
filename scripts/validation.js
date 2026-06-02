document.addEventListener("DOMContentLoaded", function () {

    const formRegisto = document.querySelector('form[action="scripts/novoregisto.php"]');

    formRegisto.addEventListener("submit", function(e) {

        let nome = document.querySelector('input[name="nome"]');
        let username = document.querySelector('input[name="username"]');
        let email = document.querySelector('input[name="email"]');
        let password = document.querySelector('input[name="password"]');

        let erros = [];

        let pass = password.value;

        if (pass.length < 5) {
            erros.push("Min 5 caracteres");
        }

        if (!/[0-9]/.test(pass)) {
            erros.push("Precisa de número");
        }

        if (!pass.includes("@")) {
            erros.push("Precisa de @");
        }

        if (/[áàâãéèêíìîóòôõúùûç]/i.test(pass)) {
            erros.push("Sem acentos");
        }

        if (erros.length > 0) {
            e.preventDefault(); 
            mostrarNotificacao(erros.join("\n"), "erro");
            return;
        }

        e.preventDefault();
        mostrarNotificacao("Conta criada com sucesso!", "sucesso");
    });


    function mostrarNotificacao(msg, tipo) {
        let notif = document.createElement("div");
        notif.innerText = msg;

        notif.style.position = "fixed";
        notif.style.top = "20px";
        notif.style.right = "20px";
        notif.style.padding = "15px";
        notif.style.color = "white";
        notif.style.borderRadius = "8px";
        notif.style.zIndex = "9999";

        notif.style.backgroundColor = tipo === "erro" ? "red" : "green";

        document.body.appendChild(notif);

        setTimeout(() => notif.remove(), 3000);
    }

});

