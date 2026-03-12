<?php
// api/validar_licenca.php
header('Content-Type: application/json');
require_once __DIR__.'/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$empresa_id = $input['empresa_id'] ?? 0;

if (!$token || !$empresa_id) {
    http_response_code(400);
    echo json_encode(['valido' => false, 'erro' => 'Token e empresa_id obrigatórios']);
    exit;
}

$db = (new Database())->connect();

// Verificar licença no banco local
$stmt = $db->prepare("
    SELECT l.*, e.nome_fantasia, e.status as empresa_status 
    FROM licencas l 
    JOIN empresas e ON l.empresa_id = e.id 
    WHERE l.token_unico = ? AND l.empresa_id = ?
");
$stmt->execute([$token, $empresa_id]);
$licenca = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$licenca) {
    // Tentar validar remotamente com gerador.php (webhook)
    $response = file_get_contents(
        $_ENV['GERADOR_URL'] . "?token=" . urlencode($token) . "&empresa_id=" . $empresa_id
    );
    $remoto = json_decode($response, true);
    
    if ($remoto['valido'] ?? false) {
        // Atualizar banco local com dados do gerador
        echo json_encode(['valido' => true, 'dados' => $remoto, 'fonte' => 'remoto']);
        exit;
    }
    
    echo json_encode(['valido' => false, 'erro' => 'Licença não encontrada']);
    exit;
}

// Validar dados locais
$agora = new DateTime();
$expiracao = new DateTime($licenca['data_expiracao']);

if ($licenca['status'] !== 'ativa') {
    echo json_encode(['valido' => false, 'erro' => "Licença {$licenca['status']}"]);
    exit;
}

if ($agora > $expiracao) {
    // Atualizar status para expirada
    $update = $db->prepare("UPDATE licencas SET status = 'expirada' WHERE id = ?");
    $update->execute([$licenca['id']]);
    echo json_encode(['valido' => false, 'erro' => 'Licença expirada']);
    exit;
}

// Atualizar último acesso
$last = $db->prepare("UPDATE licencas SET ultimo_acesso = NOW(), ip_ultimo_acesso = ? WHERE id = ?");
$last->execute([$_SERVER['REMOTE_ADDR'], $licenca['id']]);

// Registrar auditoria
$audit = $db->prepare("INSERT INTO auditoria (empresa_id, acao, ip_address) VALUES (?, 'validacao_licenca', ?)");
$audit->execute([$empresa_id, $_SERVER['REMOTE_ADDR']]);

echo json_encode([
    'valido' => true,
    'dados' => [
        'empresa' => $licenca['nome_fantasia'],
        'plano' => $licenca['permite_fiscal'] ? 'pro' : 'basico',
        'expiracao' => $licenca['data_expiracao'],
        'permissoes' => [
            'fiscal' => (bool)$licenca['permite_fiscal'],
            'pix' => (bool)$licenca['permite_pix'],
            'relatorios' => (bool)$licenca['permite_relatorios'],
            'max_produtos' => (int)$licenca['max_produtos']
        ]
    ]
]);
?>