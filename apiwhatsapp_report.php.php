<?php
// api/whatsapp_report.php - Geração de relatórios formatados para WhatsApp
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/security.php';

header('Content-Type: application/json');
Security::aplicarHeadersSeguranca();

$input = json_decode(file_get_contents('php://input'), true);

if (!Security::validarCSRF($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$db = (new Database())->connect();

// Validar permissão
$stmt = $db->prepare("
    SELECT l.permite_relatorios, e.nome_fantasia 
    FROM licencas l 
    JOIN empresas e ON l.empresa_id = e.id 
    WHERE l.empresa_id = ? AND l.status = 'ativa'
");
$stmt->execute([$input['empresa_id']]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados || !$dados['permite_relatorios']) {
    http_response_code(403);
    echo json_encode(['erro' => 'Relatórios disponíveis apenas no plano PRO']);
    exit;
}

class WhatsAppReport {
    
    private $pdo, $empresa_id, $empresa_nome;
    
    public function __construct($pdo, $empresa_id, $empresa_nome) {
        $this->pdo = $pdo;
        $this->empresa_id = $empresa_id;
        $this->empresa_nome = $empresa_nome;
    }
    
    public function gerarRelatorioVendas($periodo = 'hoje') {
        // Definir intervalo de datas
        switch($periodo) {
            case 'hoje':
                $inicio = date('Y-m-d 00:00:00');
                $fim = date('Y-m-d 23:59:59');
                break;
            case 'ontem':
                $inicio = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $fim = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'semana':
                $inicio = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $fim = date('Y-m-d 23:59:59');
                break;
            default:
                $inicio = $periodo['inicio'] ?? date('Y-m-01');
                $fim = $periodo['fim'] ?? date('Y-m-t');
        }
        
        // Buscar dados consolidados
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_vendas,
                SUM(total) as faturamento,
                forma_pagamento,
                GROUP_CONCAT(DISTINCT DATE(data_venda)) as datas
            FROM vendas 
            WHERE empresa_id = ? 
            AND status = 'finalizada'
            AND data_venda BETWEEN ? AND ?
            GROUP BY forma_pagamento
        ");
        $stmt->execute([$this->empresa_id, $inicio, $fim]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatar para WhatsApp
        $mensagem = "*📊 Relatório de Vendas - {$this->empresa_nome}*\n\n";
        $mensagem .= "_Período: " . $this->formatarPeriodo($inicio, $fim) . "_\n\n";
        
        $total_geral = 0;
        $vendas_geral = 0;
        
        foreach ($resultados as $r) {
            $forma = str_replace('_', ' ', strtoupper($r['forma_pagamento']));
            $mensagem .= "💳 *{$forma}*\n";
            $mensagem .= "   🧾 Vendas: {$r['total_vendas']}\n";
            $mensagem .= "   💰 Valor: R$ " . number_format($r['faturamento'], 2, ',', '.') . "\n\n";
            $total_geral += $r['faturamento'];
            $vendas_geral += $r['total_vendas'];
        }
        
        $mensagem .= "─────────────────\n";
        $mensagem .= "*🎯 TOTAL GERAL*\n";
        $mensagem .= "🧾 {$vendas_geral} vendas\n";
        $mensagem .= "💰 *R$ " . number_format($total_geral, 2, ',', '.') . "*\n\n";
        
        // Top 3 produtos (se solicitado)
        if ($input['incluir_produtos'] ?? false) {
            $stmt = $this->pdo->prepare("
                SELECT p.nome, SUM(vi.quantidade) as qtd_vendida
                FROM venda_itens vi
                JOIN produtos p ON vi.produto_id = p.id
                JOIN vendas v ON vi.venda_id = v.id
                WHERE v.empresa_id = ? AND v.data_venda BETWEEN ? AND ?
                GROUP BY vi.produto_id
                ORDER BY qtd_vendida DESC
                LIMIT 3
            ");
            $stmt->execute([$this->empresa_id, $inicio, $fim]);
            $top = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $mensagem .= "*🔥 Mais Vendidos*\n";
            foreach ($top as $i => $prod) {
                $mensagem .= ($i+1) . "º {$prod['nome']} ({$prod['qtd_vendida']} un)\n";
            }
            $mensagem .= "\n";
        }
        
        $mensagem .= "_Gerado em " . date('d/m/Y H:i') . "_\n";
        $mensagem .= "🔗 tanquedigital.com.br";
        
        return [
            'mensagem' => $mensagem,
            'url_whatsapp' => 'https://wa.me/?text=' . urlencode($mensagem),
            'dados_brutos' => $resultados
        ];
    }
    
    private function formatarPeriodo($inicio, $fim) {
        if ($inicio === $fim) {
            return date('d/m/Y', strtotime($inicio));
        }
        return date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
    }
}

// Executar
$report = new WhatsAppReport($db, $input['empresa_id'], $dados['nome_fantasia']);
echo json_encode($report->gerarRelatorioVendas($input['periodo'] ?? 'hoje'));

// Log de auditoria
Security::logAuditoria($db, $input['empresa_id'], $input['usuario_id'] ?? 0, 'relatorio_whatsapp', [
    'periodo' => $input['periodo'] ?? 'hoje'
]);
?>