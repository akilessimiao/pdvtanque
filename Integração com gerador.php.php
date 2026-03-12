// Exemplo de validação de licença
function validarLicenca($token, $empresa_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT l.*, e.nome as empresa_nome 
        FROM licencas l 
        JOIN empresas e ON l.empresa_id = e.id 
        WHERE l.token_unico = ? AND l.empresa_id = ?
    ");
    $stmt->bind_param("si", $token, $empresa_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $licenca = $resultado->fetch_assoc();
        if (strtotime($licenca['data_expiracao']) > time() && $licenca['status'] === 'ativa') {
            return ['valida' => true, 'dados' => $licenca];
        }
    }
    return ['valida' => false, 'erro' => 'Licença inválida ou expirada'];
}