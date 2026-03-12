<?php
// prod/pix/gerar_qrcode.php - Geração de PIX via Mercado Pago
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/security.php';

header('Content-Type: application/json');
Security::aplicarHeadersSeguranca();

$input = json_decode(file_get_contents('php://input'), true);

// Validar token CSRF e licença
if (!Security::validarCSRF($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Token CSRF inválido']);
    exit;
}

$db = (new Database())->connect();

// Verificar se empresa tem permissão para PIX
$stmt = $db->prepare("
    SELECT l.permite_pix, e.cnpj 
    FROM licencas l 
    JOIN empresas e ON l.empresa_id = e.id 
    WHERE l.empresa_id = ? AND l.status = 'ativa' AND l.data_expiracao > NOW()
");
$stmt->execute([$input['empresa_id']]);
$licenca = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$licenca || !$licenca['permite_pix']) {
    http_response_code(403);
    echo json_encode(['erro' => 'PIX não habilitado para esta conta']);
    exit;
}

// Configurações Mercado Pago (usar variáveis de ambiente)
$mp_token = $_ENV['MP_ACCESS_TOKEN'] ?? '';
$mp_base = 'https://api.mercadopago.com';

if (!$mp_token) {
    // Modo sandbox para DEMO
    $mp_base = 'https://api.mercadopago.com';
    $mp_token = $_ENV['MP_SANDBOX_TOKEN'] ?? '';
}

// Criar pagamento PIX
$payload = [
    'transaction_amount' => round($input['total'], 2),
    'description' => "Venda PDV #{$input['venda_id']}",
    'payment_method_id' => 'pix',
    'payer' => [
        'email' => $input['cliente_email'] ?? 'cliente@tanquedigital.com.br'
    ],
    'external_reference' => $input['venda_id'],
    'notification_url' => $_ENV['APP_URL'] . '/api/pix_webhook.php'
];

$ch = curl_init("{$mp_base}/v1/payments");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $mp_token,
        'X-Idempotency-Key: ' . bin2hex(random_bytes(16))
    ],
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 201) {
    error_log("MP API Error: $response");
    echo json_encode(['erro' => 'Erro ao gerar PIX']);
    exit;
}

$data = json_decode($response, true);

// Atualizar venda com dados do PIX
$update = $db->prepare("
    UPDATE vendas 
    SET pix_txid = ?, pix_qrcode = ?, pix_copia_cola = ?, status = 'aguardando_pix'
    WHERE id = ? AND empresa_id = ?
");
$update->execute([
    $data['id'],
    $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '',
    $data['point_of_interaction']['transaction_data']['qr_code'] ?? '',
    $input['venda_id'],
    $input['empresa_id']
]);

// Registrar auditoria
Security::logAuditoria($db, $input['empresa_id'], $input['usuario_id'], 'pix_gerado', [
    'venda_id' => $input['venda_id'],
    'mp_payment_id' => $data['id'],
    'valor' => $input['total']
]);

echo json_encode([
    'sucesso' => true,
    'pix_qrcode' => $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '',
    'pix_copia_cola' => $data['point_of_interaction']['transaction_data']['qr_code'] ?? '',
    'payment_id' => $data['id'],
    'expires_at' => $data['date_of_expiration'] ?? null
]);
?>