<?php
// config/security.php - Proteção contra ataques comuns [[26]][[31]]

class Security {
    
    // Sanitizar input contra XSS
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    // Validar CPF/CNPJ brasileiro
    public static function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) return false;
        
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }
    
    public static function validarCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1+$/', $cnpj)) return false;
        
        $pesos = [5,4,3,2,9,8,7,6,5,4,3,2];
        for ($t = 0; $t < 2; $t++) {
            $soma = 0;
            for ($i = 0; $i < 12 + $t; $i++) $soma += $cnpj[$i] * $pesos[$i + $t];
            $digito = ($soma * 10) % 11;
            if ($digito === 10) $digito = 0;
            if ($digito != $cnpj[12 + $t]) return false;
        }
        return true;
    }
    
    // Gerar token CSRF
    public static function gerarTokenCSRF() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Validar token CSRF
    public static function validarCSRF($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
    }
    
    // Hash de senha seguro (password_hash já usa bcrypt por padrão) [[26]]
    public static function hashSenha($senha) {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    public static function verificarSenha($senha, $hash) {
        return password_verify($senha, $hash);
    }
    
    // Rate limiting básico por IP
    public static function verificarRateLimit($acao, $limite = 60, $janela = 60) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $chave = "ratelimit:{$acao}:{$ip}";
        
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        $attempts = $redis->get($chave) ?? 0;
        if ($attempts >= $limite) return false;
        
        $redis->incr($chave);
        $redis->expire($chave, $janela);
        return true;
    }
    
    // Log de auditoria
    public static function logAuditoria($pdo, $empresa_id, $usuario_id, $acao, $detalhes = []) {
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (empresa_id, usuario_id, acao, dados_novos, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresa_id,
            $usuario_id,
            $acao,
            json_encode($detalhes, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    // Headers de segurança HTTP
    public static function aplicarHeadersSeguranca() {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self'  https:;");
    }
}
?>