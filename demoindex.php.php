<?php
// demo/index.php - PDV Versão DEMO
session_start();
require_once __DIR__.'/../config/database.php';

// Verificar licença
if (!isset($_SESSION['licenca_valida'])) {
    header('Location: ../install/ativacao.php');
    exit;
}

// Restrições DEMO
$limites = [
    'max_produtos' => 10,
    'max_vendas_diarias' => 50,
    'permite_fiscal' => false,
    'marca_dagua' => 'VERSÃO DEMO'
];

// Contar vendas do dia
$db = (new Database())->connect();
$hoje = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) FROM vendas WHERE empresa_id = ? AND DATE(data_venda) = ?");
$stmt->execute([$_SESSION['empresa_id'], $hoje]);
$vendas_hoje = $stmt->fetchColumn();

if ($vendas_hoje >= $limites['max_vendas_diarias']) {
    die("<h2>⚠️ Limite diário da DEMO atingido!</h2>
         <p>Para remover limitações, ative a versão PRO em 
         <a href='https://tanquedigital.com.br/pdv/gerador.php'>gerador.php</a></p>");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV Tanque Digital - DEMO</title>
    <link rel="stylesheet" href="../assets/css/pdv.css">
    <style>.demo-badge{position:fixed;top:10px;right:10px;background:#ffc107;color:#000;padding:5px 15px;border-radius:20px;font-weight:bold;z-index:9999}</style>
</head>
<body>
    <div class="demo-badge">🧪 VERSÃO DEMO</div>
    
    <header class="pdv-header">
        <h1>💻 PDV Tanque Digital</h1>
        <div class="user-info">
            <span><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
            <a href="../api/logout.php">Sair</a>
        </div>
    </header>

    <main class="pdv-main">
        <!-- Busca de Produto -->
        <section class="busca-produto">
            <input type="text" id="busca" placeholder="🔍 Buscar produto ou código de barras..." autofocus>
            <div id="resultados-busca" class="resultados"></div>
        </section>

        <!-- Carrinho -->
        <section class="carrinho">
            <h3>🛒 Itens</h3>
            <table id="tabela-itens">
                <thead><tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Subtotal</th><th>Ação</th></tr></thead>
                <tbody></tbody>
            </table>
            <div class="totais">
                <p>Subtotal: <span id="subtotal">R$ 0,00</span></p>
                <p><strong>Total: <span id="total">R$ 0,00</span></strong></p>
            </div>
        </section>

        <!-- Pagamento -->
        <section class="pagamento">
            <h3>💳 Finalizar</h3>
            <div class="opcoes-pagamento">
                <button data-pag="dinheiro">💵 Dinheiro</button>
                <button data-pag="cartao">💳 Cartão</button>
                <button data-pag="pix" disabled title="Apenas na versão PRO">🔷 PIX</button>
            </div>
            <button id="btn-finalizar" class="btn-primary">✅ Finalizar Venda</button>
        </section>
    </main>

    <script src="../assets/js/pdv-demo.js"></script>
    <script>
        // Avisos DEMO
        document.querySelectorAll('[data-pag="pix"]').forEach(btn => {
            btn.title = "PIX disponível apenas na versão PRO";
        });
        console.log("🧪 Executando em modo DEMO - Limitações ativas");
    </script>
</body>
</html>