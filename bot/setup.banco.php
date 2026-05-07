<?php

// Use as MESMAS credenciais que você colocou no chat.php
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$port = $_ENV['DB_PORT'];
$dsn = "pgsql:host=$host;port=$port;dbname=$db;";  

try {
    echo "Conectando ao banco de dados...\n";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // 1. Instala a extensão de IA (pgvector)
    echo "Ativando a extensão pgvector...\n";
    $pdo->exec("CREATE EXTENSION IF NOT EXISTS vector;");

    // 2. Cria a tabela
    echo "Criando a tabela embeddings...\n";
    $sql = "CREATE TABLE IF NOT EXISTS embeddings (
        id SERIAL PRIMARY KEY,
        content TEXT NOT NULL,
        embedding vector(1536)
    );";
    $pdo->exec($sql);

    echo "\n✅ Tudo pronto! O banco de dados foi configurado com sucesso.";

} catch (PDOException $e) {
    echo "\n❌ Erro no banco de dados: " . $e->getMessage();
}

?>