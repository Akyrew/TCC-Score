document.addEventListener("DOMContentLoaded", () => {
    const botoes = document.querySelectorAll(".btn-local");

    botoes.forEach(botao => {
        botao.addEventListener("click", () => {
            const local = botao.getAttribute("data-local");
            alert(`Você selecionou: ${local}`);
            // Aqui você pode redirecionar, por exemplo:
            // window.location.href = `detalhes.php?local=${encodeURIComponent(local)}`;
        });
    });
});
