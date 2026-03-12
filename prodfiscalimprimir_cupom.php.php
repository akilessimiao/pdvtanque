<?php
// prod/fiscal/imprimir_cupom.php - Preparação para NFC-e
// NOTA: Para emissão fiscal real, integrar com NFePHP e certificado digital [[17]]

require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/security.php';
require_once __DIR__.'/../../config/fiscal.php';

class CupomFiscal {
    
    private $pdo, $empresa;
    
    public function __construct($pdo, $empresa_id) {
        $this->pdo = $pdo;
        $this->carregarEmpresa($empresa_id);
    }
    
    private function carregarEmpresa($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
        $this->empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Gerar cupom não-fiscal (sempre disponível)
    public function imprimirNaoFiscal($venda_id) {
        $stmt = $this->pdo->prepare("
            SELECT v.*, u.nome as vendedor, c.nome as cliente,
                   GROUP_CONCAT(
                       CONCAT('{"nome":"', p.nome, '","qtd":', vi.quantidade, 
                              '","preco":', vi.preco_unitario, '}')
                       SEPARATOR '|'
                   ) as itens_json
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN venda_itens vi ON v.id = vi.venda_id
            LEFT JOIN produtos p ON vi.produto_id = p.id
            WHERE v.id = ? AND v.empresa_id = ?
            GROUP BY v.id
        ");
        $stmt->execute([$venda_id, $this->empresa['id']]);
        $venda = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venda) return ['erro' => 'Venda não encontrada'];
        
        // Formatar para impressão térmica (80mm)
        $itens = array_map('json_decode', explode('|', $venda['itens_json']));
        
        return [
            'tipo' => 'nao_fiscal',
            'cabecalho' => [
                'empresa' => $this->empresa['nome_fantasia'],
                'cnpj' => $this->formatarCNPJ($this->empresa['cnpj']),
                'endereco' => "{$this->empresa['endereco']}, {$this->empresa['numero']} - {$this->empresa['bairro']}",
                'cidade' => "{$this->empresa['cidade']}/{$this->empresa['uf']}",
                'cupom' => $venda['numero_cupom'],
                'data' => date('d/m/Y H:i:s', strtotime($venda['data_venda']))
            ],
            'itens' => $itens,
            'totais' => [
                'subtotal' => $venda['subtotal'],
                'desconto' => $venda['desconto'],
                'total' => $venda['total'],
                'pagamento' => strtoupper(str_replace('_', ' ', $venda['forma_pagamento']))
            ],
            'rodape' => [
                'mensagem' => 'CUPOM NÃO FISCAL - SEM VALOR TRIBUTÁRIO',
                'demo' => $this->isDemo() ? '*** VERSÃO DEMONSTRAÇÃO ***' : ''
            ]
        ];
    }
    
    // Preparar estrutura para NFC-e (requer integração SEFAZ)
    public function prepararNFCe($venda_id) {
        // ⚠️ Esta é a ESTRUTURA. Para emissão real:
        // 1. Obter certificado digital A1/A3
        // 2. Credenciar empresa na SEFAZ/RN
        // 3. Usar biblioteca NFePHP para assinar e transmitir [[17]]
        
        $venda = $this->obterVendaDetalhada($venda_id);
        if (!$venda) return ['erro' => 'Venda não encontrada'];
        
        // Estrutura base conforme manual SEFAZ [[5]]
        $nfce = [
            'versao' => '4.00',
            'ide' => [
                'cUF' => '24', // RN
                'natOp' => 'Venda ao consumidor',
                'mod' => '65', // NFC-e
                'serie' => '1',
                'nNF' => $this->gerarNumeroNFCe(),
                'dhEmi' => date('Y-m-d\TH:i:s-03:00'),
                'tpNF' => '1', // Saída
                'idDest' => '1', // Operação interna
                'cMunFG' => '240810', // Natal
                'tpImp' => '4', // DANFE simplificado
                'tpEmis' => '1', // Normal
                'cDV' => '' // Será calculado
            ],
            'emit' => [
                'CNPJ' => preg_replace('/[^0-9]/', '', $this->empresa['cnpj']),
                'xNome' => $this->empresa['razao_social'],
                'xFant' => $this->empresa['nome_fantasia'],
                'enderEmit' => [
                    'xLgr' => $this->empresa['endereco'],
                    'nro' => $this->empresa['numero'],
                    'xBairro' => $this->empresa['bairro'],
                    'cMun' => '240810',
                    'xMun' => 'Natal',
                    'UF' => 'RN',
                    'CEP' => preg_replace('/[^0-9]/', '', $this->empresa['cep'])
                ],
                'IE' => $this->empresa['ie'],
                'CRT' => '1' // Simples Nacional
            ],
            'det' => [], // Itens da venda
            'total' => [
                'ICMSTot' => [
                    'vNF' => number_format($venda['total'], 2, '.', ''),
                    // ... outros campos tributários conforme reforma 2026 [[1]][[5]]
                ]
            ],
            'pag' => [
                'detPag' => [[
                    'tPag' => $this->mapearFormaPagamentoMP($venda['forma_pagamento']),
                    'vPag' => number_format($venda['total'], 2, '.', '')
                ]]
            ]
        ];
        
        // Adicionar itens
        foreach ($venda['itens'] as $item) {
            $nfce['det'][] = [
                'prod' => [
                    'cProd' => $item['codigo_interno'],
                    'cEAN' => $item['codigo_barras'] ?? 'SEM GTIN',
                    'xProd' => substr($item['nome'], 0, 120),
                    'NCM' => $item['ncm'] ?? '00000000',
                    'CFOP' => '5102', // Venda interna
                    'uCom' => $item['unidade'],
                    'qCom' => number_format($item['quantidade'], 3, '.', ''),
                    'vUnCom' => number_format($item['preco_unitario'], 2, '.', ''),
                    'vProd' => number_format($item['subtotal'], 2, '.', ''),
                    'indTot' => '1'
                ],
                'imposto' => [
                    // Cálculos tributários conforme reforma 2026 [[1]][[5]]
                    'ICMS' => ['ICMSSN102' => [
                        'orig' => '0',
                        'CSOSN' => '102'
                    ]]
                ]
            ];
        }
        
        // Gerar QR Code conforme padrão SEFAZ RN
        $nfce['infAdic'] = [
            'qrCode' => $this->gerarQRCodeNFCe($nfce)
        ];
        
        return [
            'tipo' => 'nfce_preparado',
            'estrutura' => $nfce,
            'proximos_passos' => [
                '1. Assinar XML com certificado digital',
                '2. Transmitir para SEFAZ/RN',
                '3. Obter protocolo de autorização',
                '4. Imprimir DANFE com QR Code'
            ],
            'biblioteca_recomendada' => 'https://github.com/nfephp-org/sped-nfe'
        ];
    }
    
    private function gerarQRCodeNFCe($nfce) {
        // Implementar conforme manual SEFAZ RN
        // URL: https://portal.sefaz.rn.gov.br/nfce/qrcode
        $chave = $this->calcularChaveAcesso($nfce);
        $url = "https://portal.sefaz.rn.gov.br/nfce/qrcode?p={$chave}|2|1|{$this->empresa['csc']}";
        return base64_encode($url);
    }
    
    private function calcularChaveAcesso($nfce) {
        // cUF + AAMM + CNPJ + mod + serie + nNF + tpEmis + cNF
        $cUF = $nfce['ide']['cUF'];
        $AAMM = date('ym', strtotime($nfce['ide']['dhEmi']));
        $CNPJ = str_pad(preg_replace('/[^0-9]/', '', $this->empresa['cnpj']), 14, '0', STR_PAD_LEFT);
        $mod = $nfce['ide']['mod'];
        $serie = str_pad($nfce['ide']['serie'], 3, '0', STR_PAD_LEFT);
        $nNF = str_pad($nfce['ide']['nNF'], 9, '0', STR_PAD_LEFT);
        $tpEmis = $nfce['ide']['tpEmis'];
        $cNF = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        $chave = "{$cUF}{$AAMM}{$CNPJ}{$mod}{$serie}{$nNF}{$tpEmis}{$cNF}";
        
        // Calcular dígito verificador
        $soma = 0;
        for ($i = 0; $i < 43; $i++) {
            $soma += intval($chave[$i]) * (2 + ($i % 2));
        }
        $dv = 11 - ($soma % 11);
        if ($dv >= 10) $dv = 0;
        
        return $chave . $dv;
    }
    
    private function formatarCNPJ($cnpj) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    
    private function mapearFormaPagamentoMP($forma) {
        $map = [
            'dinheiro' => '01',
            'cartao_debito' => '02',
            'cartao_credito' => '03',
            'pix' => '17'
        ];
        return $map[$forma] ?? '99';
    }
    
    private function isDemo() {
        return ($_ENV['APP_ENV'] ?? 'production') === 'demo';
    }
    
    private function obterVendaDetalhada($id) {
        $stmt = $this->pdo->prepare("
            SELECT v.*, 
                   GROUP_CONCAT(
                       JSON_OBJECT(
                           'nome', p.nome,
                           'codigo_interno', p.codigo_interno,
                           'codigo_barras', p.codigo_barras,
                           'ncm', p.ncm,
                           'unidade', p.unidade,
                           'quantidade', vi.quantidade,
                           'preco_unitario', vi.preco_unitario,
                           'subtotal', vi.subtotal
                       ) SEPARATOR '|'
                   ) as itens_json
            FROM vendas v
            JOIN venda_itens vi ON v.id = vi.venda_id
            JOIN produtos p ON vi.produto_id = p.id
            WHERE v.id = ?
            GROUP BY v.id
        ");
        $stmt->execute([$id]);
        $venda = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venda) return null;
        
        $venda['itens'] = array_map('json_decode', explode('|', $venda['itens_json']));
        unset($venda['itens_json']);
        return $venda;
    }
    
    private function gerarNumeroNFCe() {
        // Gerar número sequencial único por série
        $stmt = $this->pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(numero_cupom, 4) AS UNSIGNED)) as ultimo
            FROM vendas 
            WHERE empresa_id = ? AND numero_cupom LIKE 'NFC%'
        ");
        $stmt->execute([$this->empresa['id']]);
        $ultimo = $stmt->fetchColumn() ?: 0;
        return $ultimo + 1;
    }
}

// Endpoint API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!Security::validarCSRF($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['erro' => 'CSRF inválido']);
        exit;
    }
    
    $db = (new Database())->connect();
    $cupom = new CupomFiscal($db, $input['empresa_id']);
    
    if ($input['tipo'] === 'nfce' && ($_ENV['APP_ENV'] ?? '') !== 'demo') {
        echo json_encode($cupom->prepararNFCe($input['venda_id']));
    } else {
        echo json_encode($cupom->imprimirNaoFiscal($input['venda_id']));
    }
}
?>