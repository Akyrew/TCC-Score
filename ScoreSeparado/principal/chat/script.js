/* ===========================================
   Utilitários de Base64 e ArrayBuffer
   =========================================== */
   function abToB64(ab) {
    const bytes = new Uint8Array(ab);
    let bin = "";
    for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
    return btoa(bin);
  }
  function b64ToAb(b64) {
    const bin = atob(b64);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return bytes.buffer;
  }
  function encText(txt) {
    return new TextEncoder().encode(txt);
  }
  function decText(ab) {
    return new TextDecoder().decode(ab);
  }
  
  /* ===========================================
     Chaves RSA do usuário (armazenadas localmente)
     =========================================== */
  async function gerarParDeChavesSeNecessario() {
    let chavePrivadaB64 = localStorage.getItem("chavePrivada");
    let chavePublicaB64 = localStorage.getItem("chavePublica");
    if (chavePrivadaB64 && chavePublicaB64) return { chavePrivadaB64, chavePublicaB64 };
  
    const keyPair = await crypto.subtle.generateKey(
      {
        name: "RSA-OAEP",
        modulusLength: 2048,
        publicExponent: new Uint8Array([1, 0, 1]),
        hash: "SHA-256",
      },
      true,
      ["encrypt", "decrypt"]
    );
    const spki = await crypto.subtle.exportKey("spki", keyPair.publicKey);
    const pkcs8 = await crypto.subtle.exportKey("pkcs8", keyPair.privateKey);
    chavePublicaB64 = abToB64(spki);
    chavePrivadaB64 = abToB64(pkcs8);
    localStorage.setItem("chavePublica", chavePublicaB64);
    localStorage.setItem("chavePrivada", chavePrivadaB64);
    return { chavePrivadaB64, chavePublicaB64 };
  }
  
  async function importarChavePrivada() {
    const pkcs8b64 = localStorage.getItem("chavePrivada");
    if (!pkcs8b64) throw new Error("Chave privada ausente");
    const pkcs8 = b64ToAb(pkcs8b64);
    return crypto.subtle.importKey(
      "pkcs8",
      pkcs8,
      { name: "RSA-OAEP", hash: "SHA-256" },
      false,
      ["decrypt"]
    );
  }
  
  async function importarChavePublicaB64(spkiB64) {
    const spki = b64ToAb(spkiB64);
    return crypto.subtle.importKey(
      "spki",
      spki,
      { name: "RSA-OAEP", hash: "SHA-256" },
      true,
      ["encrypt"]
    );
  }
  
  /* ===========================================
     Backend helpers
     =========================================== */
  async function publicarChavePublica(chavePublicaB64) {
    const body = new URLSearchParams({ chavePublica: chavePublicaB64 });
    const r = await fetch("publicar_chave.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
    });
    if (!r.ok) throw new Error("Falha ao publicar chave pública");
  }
  
  async function obterChavePublicaDoUsuario(rm) {
    const r = await fetch("get_chave_publica.php?rm=" + encodeURIComponent(rm));
    if (!r.ok) throw new Error("Não foi possível obter a chave pública do destinatário");
    const j = await r.json();
    if (!j || !j.chavePublica) throw new Error("Destinatário sem chave pública");
    return j.chavePublica;
  }
  
  /* ===========================================
     Criptografia de mensagens (AES-GCM + RSA-OAEP)
     =========================================== */
  async function criptografarParaDestinatario(plaintext, spkiB64) {
    const aesKey = await crypto.subtle.generateKey({ name: "AES-GCM", length: 256 }, true, ["encrypt", "decrypt"]);
    const iv = crypto.getRandomValues(new Uint8Array(12));
  
    const cipherBuf = await crypto.subtle.encrypt(
      { name: "AES-GCM", iv },
      aesKey,
      encText(plaintext)
    );
  
    const rawAes = await crypto.subtle.exportKey("raw", aesKey);
    const pubKey = await importarChavePublicaB64(spkiB64);
    const chaveAESCifrada = await crypto.subtle.encrypt(
      { name: "RSA-OAEP" },
      pubKey,
      rawAes
    );
  
    return {
      mensagemB64: abToB64(cipherBuf),
      chaveB64: abToB64(chaveAESCifrada),
      ivB64: abToB64(iv.buffer),
    };
  }
  
  async function descriptografarMensagem(mensagemB64, chaveAESCriptografadaB64, ivB64) {
    const privKey = await importarChavePrivada();
    const rawAes = await crypto.subtle.decrypt(
      { name: "RSA-OAEP" },
      privKey,
      b64ToAb(chaveAESCriptografadaB64)
    );
  
    const aesKey = await crypto.subtle.importKey("raw", rawAes, "AES-GCM", false, ["decrypt"]);
    const plaintextBuf = await crypto.subtle.decrypt(
      { name: "AES-GCM", iv: new Uint8Array(b64ToAb(ivB64)) },
      aesKey,
      b64ToAb(mensagemB64)
    );
    return decText(plaintextBuf);
  }
  
  /* ===========================================
     UI helpers
     =========================================== */
  function appendMensagemLocal({ texto, rmAutor, rmDestino, mensagemB64, chaveB64, ivB64 }) {
    const chat = document.getElementById("chatBody");
    if (!chat) return;
    const minha = window.__CHAT__.rmLogado === rmAutor;
    const div = document.createElement("div");
    div.className = "mensagem " + (minha ? "minha" : "deles");
    div.dataset.rm = rmAutor;
    div.dataset.rmDestino = rmDestino;
    div.dataset.ciphertext = mensagemB64 || "";
    div.dataset.chave = chaveB64 || "";
    div.dataset.iv = ivB64 || "";
    div.innerHTML = `<span class="cadeado">🔒</span> ${texto ? texto.replace(/</g, "&lt;") : "mensagem cifrada"}`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
  }
  
  async function tentarDescriptografarMensagens() {
    const elementos = document.querySelectorAll(".mensagem[data-ciphertext]");
    for (const el of elementos) {
      const mensagemB64 = el.dataset.ciphertext;
      const chaveB64 = el.dataset.chave;
      const ivB64 = el.dataset.iv;
      if (!mensagemB64 || !chaveB64 || !ivB64) continue;
  
      try {
        const texto = await descriptografarMensagem(mensagemB64, chaveB64, ivB64);
        el.innerHTML = `${texto.replace(/</g, "&lt;")}<small>🔒 E2EE</small>`;
        el.classList.add("decriptada");
      } catch (e) {
        // Ignora se não conseguir descriptografar
      }
    }
  }
  
  /* ===========================================
     Envio de mensagem (form)
     =========================================== */
  async function enviarMensagem(plaintext) {
    const rmDestino = window.__CHAT__.rmDestino;
    if (!rmDestino) throw new Error("Nenhum destinatário selecionado.");
  
    const spkiB64 = await obterChavePublicaDoUsuario(rmDestino);
    const { mensagemB64, chaveB64, ivB64 } = await criptografarParaDestinatario(plaintext, spkiB64);
  
    const body = new URLSearchParams({
      rmDestino: String(rmDestino),
      mensagem: mensagemB64,
      chave: chaveB64,
      iv: ivB64,
    });
    const r = await fetch("enviar_mensagem.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
    });
    if (!r.ok) throw new Error("Falha ao enviar mensagem");
  
    appendMensagemLocal({
      texto: plaintext,
      rmAutor: window.__CHAT__.rmLogado,
      rmDestino,
      mensagemB64,
      chaveB64,
      ivB64,
    });
  }
  
  /* ===========================================
     Boot
     =========================================== */
     document.addEventListener("DOMContentLoaded", async () => {
    try {
      const { chavePublicaB64 } = await gerarParDeChavesSeNecessario();
      await publicarChavePublica(chavePublicaB64);
    } catch (e) {
      console.error("Falha ao preparar E2EE:", e);
    }
  
    try {
      await tentarDescriptografarMensagens();
    } catch (e) {
      console.error("Erro ao descriptografar:", e);
    }
  
    const form = document.getElementById("formEnvio");
    const textarea = document.getElementById("texto");
  
    if (form && textarea) {
      form.addEventListener("submit", async (ev) => {
        ev.preventDefault(); // impede recarregamento
        const texto = (textarea.value || "").trim();
        if (!texto) return;
        textarea.value = "";
        try {
          await enviarMensagem(texto);
        } catch (e) {
          alert("Não foi possível enviar: " + e.message);
        }
      });
    }
  });