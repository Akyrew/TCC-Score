document.getElementById("formCadastro").addEventListener("submit", function (event) {
  let email = document.getElementById("email").value;
  let confirmarEmail = document.getElementById("confirmar_email").value;
  let senha = document.getElementById("senha").value;
  let confirmarSenha = document.getElementById("confirmar_senha").value;

  let emailValido = validarEmail(email);
  let senhaValida = senha.length >= 6;

  let erros = false;

  if (email !== confirmarEmail) {
    marcarErro("confirmar_email", "E-mails não coincidem");
    erros = true;
  } else if (!emailValido) {
    marcarErro("email", "E-mail inválido");
    erros = true;
  } else {
    limparErro("email");
    limparErro("confirmar_email");
  }

  if (senha !== confirmarSenha) {
    marcarErro("confirmar_senha", "Senhas não coincidem");
    erros = true;
  } else if (!senhaValida) {
    marcarErro("senha", "Senha deve ter pelo menos 6 caracteres");
    erros = true;
  } else {
    limparErro("senha");
    limparErro("confirmar_senha");
  }

  if (erros) {
    event.preventDefault();
  }
});

function validarEmail(email) {
  let re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

function marcarErro(campoId, mensagem) {
  let campo = document.getElementById(campoId);
  let grupo = campo.parentElement;
  grupo.classList.add("error");
  let msg = grupo.querySelector(".error-message");
  if (msg) msg.textContent = mensagem;
}

function limparErro(campoId) {
  let campo = document.getElementById(campoId);
  let grupo = campo.parentElement;
  grupo.classList.remove("error");
}
