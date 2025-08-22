# TCC-S.C.O.R.E

# CI/CD do S.C.O.R.E

O projeto **S.C.O.R.E** utiliza o **GitHub Actions** para automatizar testes básicos e gerar artefatos a cada push ou pull request.  
Isso garante que o código principal esteja sempre funcional e que versões atualizadas do projeto possam ser facilmente baixadas.

---

## Configuração do Workflow

O workflow está definido no arquivo:

.github/workflows/ci-cd-score.yml


Ele é disparado em qualquer **push** ou **pull request** para o repositório.

---

## Passos Executados pelo Workflow

### 1. Checkout do código
A ação `actions/checkout@v3` baixa o código do repositório na máquina virtual do GitHub Actions.

### 2. Instalação do PHP
Usando `shivammathur/setup-php@v2`, o workflow configura a versão **PHP 8.1** necessária para rodar os scripts do projeto.

### 3. Verificação de sintaxe PHP
O comando `php -l` é executado em todos os arquivos `.php` do projeto, garantindo que não haja erros de sintaxe.

### 4. Verificação de arquivos principais
O workflow confere se os arquivos essenciais estão presentes no projeto:

- `ScoreSeparado/index.html`  
- `ScoreSeparado/cadastrar/cadastrar.html`

Isso evita que partes importantes do projeto sejam acidentalmente deletadas.

### 5. Criação de artefato
Todos os arquivos do projeto são compactados e enviados como artefato (`score-projeto`) usando `actions/upload-artifact@v4`.  
O artefato pode ser baixado diretamente na aba **Actions** do GitHub, permitindo acessar rapidamente a última versão funcional do projeto.

---

## Benefícios do CI/CD no S.C.O.R.E

- **Feedback imediato:** Qualquer erro de sintaxe ou ausência de arquivos principais é identificado automaticamente.  
- **Controle de versões:** Cada push gera um artefato atualizado, pronto para distribuição ou teste.  
- **Segurança do código:** Ajuda a manter o projeto sempre funcional e confiável para desenvolvedores e avaliadores.

