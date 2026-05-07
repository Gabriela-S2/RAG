# 🤖 RAG - Bot de Resolução de Erros

> Bot inteligente baseado na arquitetura **RAG (Retrieval-Augmented Generation)**.  
> Desenvolvido em **PHP**, o sistema lê ficheiros de texto, indexa-os numa base de dados  
> vetorial e utiliza modelos de IA para responder a dúvidas e ajudar na resolução  
> de erros de forma contextualizada.

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-pgvector-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![OpenRouter](https://img.shields.io/badge/OpenRouter-API-FF6B35?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

---
## 📌 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Demonstração](#-demonstração)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Pré-requisitos](#-pré-requisitos)
- [Como Configurar e Executar](#️-como-configurar-e-executar)
- [Como Funciona o Fluxo RAG](#-como-funciona-o-fluxo-rag)
- [Segurança e Boas Práticas](#-segurança-e-boas-práticas)
- [Contribuição](#-contribuição)
- [Licença](#-licença)

---

## 💡 Sobre o Projeto

O **RAG Bot** é um assistente inteligente criado para ajudar na **resolução de erros**  
de forma contextualizada. Em vez de depender apenas do conhecimento genérico de um  
modelo de IA, o sistema **injeta contexto real** — extraído dos seus próprios ficheiros  
de documentação ou logs — nas respostas geradas.

### ✨ Funcionalidades principais

- 📂 **Ingestão de documentos** — Lê ficheiros `.txt` e converte-os em vetores semânticos
- 🔍 **Busca semântica** — Encontra os fragmentos mais relevantes usando `pgvector`
- 🧠 **Geração contextualizada** — Envia o contexto + pergunta ao modelo `grok-4.1-fast`
- ⚡ **Streaming em tempo real** — Respostas transmitidas palavra a palavra via **SSE**
- 💬 **Interface de chat** — UI responsiva com renderização de **Markdown**

---

## 🎥 Demonstração
Utilizador: "Como resolver o erro de conexão com a base de dados?"

🤖 Bot: Com base na documentação fornecida, o erro de conexão pode
ser causado por credenciais incorretas no ficheiro .env.
Verifique os parâmetros DB_HOST, DB_USER e DB_PASS...

> 💬 As respostas aparecem **palavra a palavra** em tempo real, tal como no ChatGPT.

---

## 🚀 Tecnologias Utilizadas

### 🧠 Backend & IA

| Tecnologia | Descrição |
|---|---|
| **PHP 8.1+** | Lógica central do servidor |
| **LLPhant** | Integração com LLMs e geração de embeddings em PHP |
| **OpenRouter API** | Acesso aos modelos de IA |
| **text-embedding-3-small** | Geração de vetores de 1536 dimensões |
| **grok-4.1-fast** | Modelo de chat para geração de respostas |
| **cURL** | Leitura do stream de resposta da API |
| **Composer** | Gestor de dependências PHP |

### 🗄️ Base de Dados

| Tecnologia | Descrição |
|---|---|
| **PostgreSQL** | Base de dados relacional principal |
| **pgvector** | Extensão para armazenamento e busca semântica de vetores |

### 🎨 Frontend

| Tecnologia | Descrição |
|---|---|
| **HTML5, CSS3 & JavaScript** | Interface de chat responsiva |
| **Marked.js** | Renderização de Markdown nas respostas do bot |
| **Server-Sent Events (SSE)** | Streaming de respostas palavra a palavra |

---

## 📁 Estrutura do Projeto
~~~
RAG/
├── bot/
│ ├── botconfig.php # Endpoint de chat: busca semântica + streaming SSE
│ ├── ragconfig.php # Ingestão de dados: lê os .txt e gera embeddings
│ ├── setup.banco.php # Setup do banco: ativa pgvector e cria a tabela
│ └── dadosrag/ # 📂 Coloque aqui os ficheiros .txt de contexto
│ └── erros.txt # Exemplo de ficheiro de contexto
├── plataforma/
│ ├── index.html # Estrutura da interface de chat
│ ├── style.css # Estilização
│ ├── main.js # Lógica de interação, histórico e leitura do SSE
│ └── img/ # Ícones SVG
├── composer.json # Dependências PHP
├── composer.lock
├── .env # 🔒 Variáveis de ambiente (não versionado)
├── .gitignore
└── README.md
~~~

## ⚙️ Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- ✅ **PHP 8.1+** com extensão `pdo_pgsql` ativada
- ✅ **Composer**
- ✅ **PostgreSQL** (local ou cloud)
- ✅ **pgvector** instalado na sua instância PostgreSQL
- ✅ Conta e chave de API no **[OpenRouter](https://openrouter.ai/)**

---

## 🛠️ Como Configurar e Executar

### 1️⃣ Clonar o repositório e instalar dependências

```bash
git clone https://github.com/Gabriela-S2/RAG.git
cd RAG
composer install
```
### 2️⃣ Configurar as variáveis de ambiente
Crie um ficheiro .env na raiz do projeto:
```bash
DB_HOST=localhost
DB_NAME=seu_banco_de_dados
DB_USER=seu_utilizador
DB_PASS=sua_password
DB_PORT=5432
OPENROUTER_API_KEY=sua_chave_da_api_aqui
```
* ⚠️ Nunca commite o ficheiro .env! Ele já está no .gitignore.

### 3️⃣ Configurar a base de dados
Execute o script de setup para ativar o pgvector e criar a tabela embeddings:
```bash
php bot/setup.banco.php
```
Saída esperada:
```bash
Conectando ao banco de dados...
Ativando a extensão pgvector...
Criando a tabela embeddings...
✅ Tudo pronto! O banco de dados foi configurado com sucesso.
```
A tabela criada tem a seguinte estrutura:
```bash
CREATE TABLE embeddings (
    id        SERIAL PRIMARY KEY,
    content   TEXT NOT NULL,
    embedding vector(1536)
);
```
### 4️⃣ Ingerir os dados de contexto
Coloque os ficheiros .txt com a documentação ou logs de erros dentro de bot/dadosrag/
Execute o script de vetorização:
```bash
php bot/ragconfig.php
```
Saída esperada:
```bash
Iniciando a vetorização dos arquivos...
Lendo: erros.txt...

Transformando em vetor...
Inserindo no PostgreSQL...
[OK] Arquivo processado com sucesso!

🎉 Ingestão de dados finalizada! Seu RAG agora tem conhecimento.
```
* ℹ️ O script não tem limite de tempo (set_time_limit(0)) para suportar ficheiros grandes.

### 5️⃣ Iniciar o servidor
```bash
php -S localhost:8000
```
Abra o browser em: http://localhost:8000/plataforma/

## 🧠 Como Funciona o Fluxo RAG
```
┌─────────────────────┐      ┌──────────────────────┐      ┌──────────────────┐
│  Ficheiros .txt      │─────▶│   ragconfig.php      │─────▶│   PostgreSQL     │
│  (bot/dadosrag/)    │      │   Gera embeddings    │      │   + pgvector     │
└─────────────────────┘      │   (1536 dimensões)   │      └──────────────────┘
                             └──────────────────────┘               │
                                                                     │ Busca semântica
┌─────────────────────┐      ┌──────────────────────┐               │ (operador <=>)
│   Utilizador        │─────▶│   botconfig.php      │◀─────────────┘
│   (Pergunta via UI) │      │   Vetoriza pergunta  │
└─────────────────────┘      └──────────────────────┘
                                         │
                             ┌──────────────────────┐
                             │  Contexto relevante  │
                             │  + Pergunta enviados │
                             │  ao modelo Grok      │
                             └──────────────────────┘
                                         │
                             ┌──────────────────────┐
                             │  Resposta via SSE    │
                             │  (palavra a palavra) │
                             │  → Interface Web     │
                             └──────────────────────┘
```
## 📋 Passo a passo detalhado
| Passo | Script | Descrição |
|---|---|---|
| 1️⃣ **Vetorização** | `ragconfig.php` | Lê os `.txt`, converte em vetores de 1536 dimensões e guarda no PostgreSQL |
| 2️⃣ **Pergunta** | `main.js` | O utilizador envia uma mensagem pela interface web |
| 3️⃣ **Busca Semântica** | `botconfig.php` | Vetoriza a pergunta e usa `<=>` do pgvector para encontrar os 20 fragmentos mais relevantes |
| 4️⃣ **Geração** | `botconfig.php` | O contexto encontrado é injetado no prompt do modelo `grok-4.1-fast` via OpenRouter |
| 5️⃣ **Streaming** | `botconfig.php` + SSE | A resposta é transmitida palavra a palavra via cURL + SSE para o browser |

## 🔒 Segurança e Boas Práticas
* 🔑 Credenciais geridas via vlucas/phpdotenv — nunca expostas no código
* 🚫 Ficheiro .env incluído no .gitignore
* 🛡️ Erros da API e do cURL capturados e exibidos de forma segura no frontend
* 📦 Respostas codificadas em JSON antes do envio via SSE para proteger quebras de linha
## 🤝 Contribuição
Contribuições são bem-vindas! Siga os passos abaixo:
```bash
# 1. Faça um fork do projeto
# 2. Crie a sua branch
git checkout -b feature/nova-funcionalidade

# 3. Faça commit das suas alterações
git commit -m "feat: adiciona nova funcionalidade"

# 4. Faça push para a branch
git push origin feature/nova-funcionalidade

# 5. Abra um Pull Request
```
📄 Licença
Este projeto está licenciado sob a licença MIT.
Consulte o ficheiro LICENSE para mais detalhes.

<div align="center">

Desenvolvido com ❤️ por Gabriela-S2

⭐ Se este projeto te foi útil, considera dar uma estrela no repositório!

</div>
