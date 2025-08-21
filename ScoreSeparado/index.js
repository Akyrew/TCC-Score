// Validação visual do campo RM
const rmInput = document.getElementById("rm");
const rmGroup = document.getElementById("userGroup");

rmInput.addEventListener("input", () => {
    const valor = rmInput.value;

    if (/[^0-9]/.test(valor)) { // Contém letras ou caracteres inválidos
        rmGroup.classList.remove("success");
        rmGroup.classList.add("error");
    } else if (valor.trim() !== "") { // Apenas números e não vazio
        rmGroup.classList.remove("error");
        rmGroup.classList.add("success");
    } else { // Campo vazio
        rmGroup.classList.remove("error", "success");
    }
});

// Evento de login
document.getElementById("btnLogin").addEventListener("click", function () {
    const rm = rmInput.value.trim();
    const senha = document.getElementById("senha").value.trim();
    const mensagem = document.getElementById("mensagem");

    if (rm === "" || senha === "") {
        mensagem.style.color = "red";
        mensagem.textContent = "Preencha o RM e a senha.";
        return;
    }

    if (/[^0-9]/.test(rm)) {
        mensagem.style.color = "red";
        mensagem.textContent = "O RM deve conter apenas números.";
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
            setTimeout(() => {
                window.location.href = "principal/index.html";
            }, 1000);
        } else {
            mensagem.style.color = "red";
            mensagem.textContent = "RM ou senha incorretos.";
        }
    })
    .catch(err => {
        mensagem.style.color = "red";
        mensagem.textContent = "Erro ao conectar com o servidor.";
    });
});
