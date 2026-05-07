document.addEventListener('DOMContentLoaded', () => {
    // Referências do DOM
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');
    const chatContainer = document.getElementById('chat-container');
    const btnNovaConversa = document.getElementById('btn-nova-conversa');
    const listaConversas = document.getElementById('historico-lista');
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    const MAX_HEIGHT = 200;

    // Estado da aplicação
    let conversas = {}; 
    let idConversaAtual = null;

    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';

        if (this.scrollHeight >= MAX_HEIGHT) {
            this.style.overflowY = 'auto';
            if (fullscreenBtn) fullscreenBtn.style.display = 'block'; 
        } else {
            this.style.overflowY = 'hidden';
            if (fullscreenBtn) fullscreenBtn.style.display = 'none'; 
        }
    });

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', () => {
            alert("Abrindo editor em tela cheia para facilitar a programação...");
        });
    }

    userInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); 
            chatForm.requestSubmit(); 
        }
    });

    function renderizarMensagem(texto, remetente) {
        const div = document.createElement('div');
        div.className = remetente === 'user' ? 'input-resposta' : 'resposta-bot';
        
        if (remetente === 'bot') {
            // Usa o Marked.js com fallback caso ele não carregue
            try {
                div.innerHTML = marked.parse(texto, { breaks: true });
            } catch (e) {
                div.innerHTML = texto.replace(/\n/g, '<br>');
            }
        } else {
            div.textContent = texto;
        }
        
        chatContainer.appendChild(div);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function renderizarHistorico() {
        listaConversas.innerHTML = '';
        Object.keys(conversas).forEach(id => {
            const btn = document.createElement('button');
            btn.className = 'conversa';
            if (id === idConversaAtual) btn.style.border = 'solid 1px rgb(159, 159, 159)';
            btn.textContent = conversas[id].titulo;
            btn.onclick = () => carregarConversa(id);
            listaConversas.appendChild(btn);
        });
    }

    function carregarConversa(id) {
        idConversaAtual = id;
        chatContainer.innerHTML = '';
        conversas[id].mensagens.forEach(msg => {
            renderizarMensagem(msg.content, msg.role);
        });
        renderizarHistorico();
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const texto = userInput.value.trim();
        if (!texto) return;

        if (!idConversaAtual) {
            idConversaAtual = Date.now().toString();
            conversas[idConversaAtual] = {
                titulo: texto.split(' ').slice(0, 3).join(' ') + "...",
                mensagens: []
            };
        }

        renderizarMensagem(texto, 'user');
        conversas[idConversaAtual].mensagens.push({ role: 'user', content: texto });
        
        userInput.value = ''; 
        userInput.style.height = 'auto'; 
        userInput.style.overflowY = 'hidden'; 
        if (fullscreenBtn) fullscreenBtn.style.display = 'none'; 
    
        renderizarHistorico();

        const botMsgId = 'bot-' + Date.now();
        const divBot = document.createElement('div'); 
        divBot.className = 'resposta-bot';
        divBot.id = botMsgId;
        divBot.innerHTML = '...';
        chatContainer.appendChild(divBot);

        try {
            const response = await fetch(`../bot/bot_config.php?pergunta=${encodeURIComponent(texto)}`);
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let respostaCompleta = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value);
                const linhas = chunk.split('\n');
                
                linhas.forEach(linha => {
                    if (linha.startsWith('data: ')) {
                        const jsonStr = linha.replace('data: ', '').trim();
                        if (!jsonStr) return; 
                        
                        try {
                            const dadoReal = JSON.parse(jsonStr);
                            
                            // INTERCEPTADOR DE DEBUG: Verifica se é o objeto de debug
                            if (dadoReal && dadoReal.debug) {
                                console.log("🛠️ [INFO RAG]:", dadoReal.debug);
                            } 
                            // TEXTO DA IA: Se for texto normal, renderiza
                            else {
                                respostaCompleta += dadoReal;
                                try {
                                    divBot.innerHTML = marked.parse(respostaCompleta, { breaks: true }); 
                                } catch (err) {
                                    divBot.innerHTML = respostaCompleta.replace(/\n/g, '<br>');
                                }
                            }
                        } catch (e) {
                            // DEDODURO: Se não for JSON, joga na tela!
                            if (jsonStr !== '[DONE]') {
                                console.error("❌ Erro não-JSON recebido:", jsonStr);
                                respostaCompleta += "<br><span style='color:red;'>" + jsonStr + "</span>";
                                divBot.innerHTML = respostaCompleta;
                            }
                        }
                    }
                });
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } 

            conversas[idConversaAtual].mensagens.push({ role: 'bot', content: respostaCompleta });
            renderizarHistorico();

        } catch (error) { 
            divBot.textContent = "Erro ao conectar com o servidor.";
            console.error(error);
        }
    });

    btnNovaConversa.addEventListener('click', () => {
        idConversaAtual = null;
        chatContainer.innerHTML = '<p class="resposta-bot">Olá! Como posso ajudar você hoje?</p>';
        renderizarHistorico();
    });
});