<?php

// Remove o limite de tempo caso tenha muitos arquivos
set_time_limit(0); 

require_once '../vendor/autoload.php';

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__)); 
$dotenv->load();

// 1. Conexão com o Banco
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$port = $_ENV['DB_PORT'];
$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 2. Configura a IA para gerar os vetores
$config = new OpenAIConfig();
$config->apiKey = $_ENV['OPENROUTER_API_KEY'];
$config->client = \OpenAI::factory()
    ->withApiKey($config->apiKey)
    ->withBaseUri('https://openrouter.ai/api/v1')
    ->make();
$embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($config);

// 3. Lê os arquivos da pasta dados_rag
$pastaDados = __DIR__ . '/dados_rag';
$arquivos = glob($pastaDados . '/*.txt'); 

if (empty($arquivos)) {
    echo "Nenhum arquivo .txt encontrado na pasta $pastaDados.\n";
    exit;
}

echo "Iniciando a vetorização dos arquivos...\n";
echo "----------------------------------------\n";

foreach ($arquivos as $arquivo) {
    echo "Lendo: " . basename($arquivo) . "...\n";
    
    $conteudo = file_get_contents($arquivo);
    if (empty(trim($conteudo))) continue;

    try {
        // Gera as coordenadas matemáticas (o vetor de 1536 dimensões)
        echo "- Transformando em vetor...\n";
        $vetor = $embeddingGenerator->embedText($conteudo);
        $vetorString = '[' . implode(',', $vetor) . ']';

        // Salva o texto e o vetor na tabela
        echo "- Inserindo no PostgreSQL...\n";
        $stmt = $pdo->prepare("INSERT INTO embeddings (content, embedding) VALUES (:content, :vetor)");
        $stmt->execute([
            ':content' => $conteudo,
            ':vetor' => $vetorString
        ]);
        
        echo "[OK] Arquivo processado com sucesso!\n\n";

    } catch (Exception $e) {
        echo "[ERRO] Falha ao processar o arquivo " . basename($arquivo) . ": " . $e->getMessage() . "\n\n";
    }
}

echo "----------------------------------------\n";
echo "🎉 Ingestão de dados finalizada! Seu RAG agora tem conhecimento.\n";
?>