<?php
set_time_limit(0);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); 

require_once '../vendor/autoload.php';

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__)); 
$dotenv->load();

$pergunta = $_GET['pergunta'] ?? '';
if (empty($pergunta)) {
    echo "data: " . json_encode("Erro: Pergunta vazia") . "\n\n";
    exit;
}

// 1. Gerador de Vetores (Mantemos a LLPhant só para isso)
$config = new OpenAIConfig();
$config->apiKey = $_ENV['OPENROUTER_API_KEY'];
$config->client = \OpenAI::factory()
    ->withApiKey($config->apiKey)
    ->withBaseUri('https://openrouter.ai/api/v1')
    ->make();
$embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($config);

// 2. Conexão PDO e Busca no Banco
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$port = $_ENV['DB_PORT'];
$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $vetorPergunta = $embeddingGenerator->embedText($pergunta);
    $vetorString = '[' . implode(',', $vetorPergunta) . ']';

    $stmt = $pdo->prepare("SELECT content FROM embeddings ORDER BY embedding <=> :vetor LIMIT 20");
    $stmt->execute([':vetor' => $vetorString]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contexto = "";
    foreach ($resultados as $linha) {
        $contexto .= $linha['content'] . "\n---\n";
    }

    if (empty(trim($contexto))) {
        echo "data: " . json_encode(["debug" => "O banco de dados retornou 0 resultados!"]) . "\n\n";
        echo "data: " . json_encode("Não encontrei nenhuma informação no meu banco de dados para te ajudar com isso.") . "\n\n";
        ob_flush(); flush();
        exit;
    } 
    
    // Se TEM contexto, manda o debug oculto e continua a IA
    echo "data: " . json_encode(["debug" => "O banco encontrou " . count($resultados) . " fragmento(s)."]) . "\n\n";
    ob_flush(); flush();

    // ========================================================
    // 3. PARTE DO CHAT
    // ========================================================
    
    // Preparando os dados para a API
$dados = [
        "model" => "x-ai/grok-4.1-fast", 
        "messages" => [
            [
                "role" => "system", 
                "content" => "Você é um assistente inteligente. Responda à pergunta baseando-se APENAS no contexto fornecido.\n\nContexto:\n$contexto"
            ],
            [
                "role" => "user", 
                "content" => $pergunta 
            ]
        ],
        "stream" => true
    ];

    // Iniciando o cURL
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_ENV['OPENROUTER_API_KEY']
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));

    // A mágica: Lendo o stream pedaço por pedaço
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
        
        // 1. "ESCUDO ANTIMISTÉRIO": Se a API retornar um erro JSON puro, joga na tela!
        if (strpos(trim($chunk), '{"error"') === 0 || strpos(trim($chunk), '{"message"') === 0) {
            echo "data: " . json_encode("[ERRO DA API]: " . trim($chunk)) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
            return strlen($chunk);
        }

        // 2. Processamento normal do Stream
        $linhas = explode("\n", $chunk);
        
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            
            // O OpenRouter às vezes manda pings para manter a conexão ativa (": OPENROUTER")
            if (empty($linha) || strpos($linha, ':') === 0) continue; 

            if (strpos($linha, 'data: ') === 0) {
                $jsonStr = substr($linha, 6);
                
                if ($jsonStr === '[DONE]') continue; // Fim do stream
                
                $json = json_decode($jsonStr, true);
                
                // Se existe uma palavra nova no pacote...
                if (isset($json['choices'][0]['delta']['content'])) {
                    $palavra = $json['choices'][0]['delta']['content'];
                    
                    // BLINDAGEM: Codifica em JSON para proteger as quebras de linha
                    $palavraBlindada = json_encode($palavra);
                    
                    echo "data: " . $palavraBlindada . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            }
        }
        return strlen($chunk); // O PHP exige retornar o tamanho processado
    });

    // Executa a requisição
    curl_exec($ch);
    
    if (curl_errno($ch)) {
        // Blindado
        echo "data: " . json_encode("[ERRO CURL]: " . curl_error($ch)) . "\n\n";
    }
    
} catch (\Throwable $e) {
    // Blindado
    echo "data: " . json_encode("[ERRO CRÍTICO]: " . $e->getMessage()) . "\n\n";
}

?>