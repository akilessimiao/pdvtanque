<?php
// install/instalador.php
session_start();
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados do banco
    $host = $_POST['db_host'] ?? 'myshared0786';
    $dbname = $_POST['db_name'] ?? 'pdvtanque';
    $user = $_POST['db_user'] ?? 'pdvtanque';
    $pass = $_POST['db_pass'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Executar schema
        $schema = file_get_contents(__DIR__.'/../sql/schema.sql');
        $pdo->exec($schema);
        
        // Criar .env
        $env = file_get_contents(__DIR__.'/../config/.env.example');
        $env = str_replace('SUA_SENHA_SEGURA_AQUI', $pass, $env);
        $env = str_replace('myshared0786', $host, $env);
        file_put_contents(__DIR__.'/../config/.env', $env);
        
        $success[] = "✅ Banco configurado com sucesso!";
        $success[] = "✅ Schema importado!";
        $success[] = "✅ Arquivo .env criado!";
        
        $_SESSION['instalado'] = true;
        
    } catch(PDOException $e) {
        $errors[] = "❌ Erro na conexão: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔧 Instalador PDV Tanque Digital</title>
    <style>
        body{font-family:system-ui,sans-serif;max-width:600px;margin:50px auto;padding:20px}
        .card{border:1px solid #ddd;border-radius:8px;padding:20px;margin:10px 0}
        .success{background:#d4edda;border-color:#c3e6cb;color:#155724}
        .error{background:#f8d7da;border-color:#f5c6cb;color:#721c24}
        input{width:100%;padding:10px;margin:5px 0 15px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box}
        button{background:#007bff;color:white;padding:12px 24px;border:none;border-radius:4px;cursor:pointer;font-size:16px}
        button:hover{background:#0056b3}
    </style>
</head>
<body>
    <h1>🔧 Instalador PDV Tanque Digital</h1>
    
    <?php foreach($success as $msg): ?>
        <div class="card success"><?= $msg ?></div>
    <?php endforeach; ?>
    
    <?php foreach($errors as $msg): ?>
        <div class="card error"><?= $msg ?></div>
    <?php endforeach; ?>
    
    <?php if(!isset($_SESSION['instalado'])): ?>
    <form method="POST" class="card">
        <h3>🗄️ Configuração do Banco</h3>
        
        <label>Servidor (hostname)</label>
        <input type="text" name="db_host" value="myshared0786" required>
        
        <label>Nome do Banco</label>
        <input type="text" name="db_name" value="pdvtanque" required>
        
        <label>Usuário</label>
        <input type="text" name="db_user" value="pdvtanque" required>
        
        <label>Senha</label>
        <input type="password" name="db_pass" placeholder="••••••••" required>
        
        <button type="submit">🚀 Instalar Agora</button>
    </form>
    <?php else: ?>
        <div class="card success">
            <h3>✅ Instalação Concluída!</h3>
            <p>Redirecionando para ativação...</p>
            <script>setTimeout(() => window.location='../install/ativacao.php', 2000)</script>
        </div>
    <?php endif; ?>
    
    <p style="text-align:center;margin-top:30px;font-size:14px;color:#666">
        PDV Tanque Digital © <?= date('Y') ?> - Desenvolvido por Akiles Simião
    </p>
</body>
</html>
<?php
// Proteger instalador após uso
if (isset($_SESSION['instalado']) && $_SESSION['instalado']) {
    // Opcional: deletar instalador após sucesso
    // unlink(__FILE__);
}
?>