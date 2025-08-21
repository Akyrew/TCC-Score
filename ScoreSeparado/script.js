document.getElementById("btnLogin").addEventListener("click", function () {
    const rm = document.getElementById("rm").value.trim();
    const senha = document.getElementById("senha").value.trim();
    const mensagem = document.getElementById("mensagem");

    if (rm === "" || senha === "") {
        mensagem.textContent = "Preencha o RM e a senha.";
        return;
    }

    fetch("login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `rm=${encodeURIComponent(rm)}&senha=${encodeURIComponent(senha)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.sucesso) {
            mensagem.style.color = "green";
            mensagem.textContent = "Login realizado com sucesso!";
            // Redireciona para outra página
            setTimeout(() => {
                window.location.href = "principal/index.html";
            }, 1000);
        } else {
            mensagem.style.color = "red";
            mensagem.textContent = "RM ou senha incorretos.";
        }
    })
    .catch(err => {
        mensagem.textContent = "Erro ao conectar com o servidor.";
    });
});
