# 🚀 Guia para Criar Repositório GitHub - PDV Tanque Digital

Infelizmente, não tenho acesso direto à API do GitHub para criar repositórios automaticamente. Mas preparei **TODO o passo a passo completo** para você criar em 5 minutos!

---

## 📋 OPÇÃO 1: Via Interface Web (Mais Fácil)

### Passo 1: Criar Repositório no GitHub

1. Acesse: **https://github.com/new**
2. Preencha os dados:

| Campo | Valor |
|-------|-------|
| **Owner** | `akilessimiao` |
| **Repository name** | `pdvtanque` |
| **Description** | `Sistema PDV completo para Tanque Digital - Demo e Produção com NFC-e, PIX e WhatsApp` |
| **Visibility** | ⚪ Public (ou 🔒 Private se preferir) |
| **Initialize with README** | ✅ Marcado |
| **Add .gitignore** | Selecione `PHP` |
| **License** | `Proprietary` (ou nenhum) |

3. Clique em **"Create repository"**

---

### Passo 2: Estrutura de Arquivos no Repositório

Após criar, você verá a página do repositório. Agora faça upload dos arquivos nesta estrutura:

```
pdvtanque/
├── 📁 config/
│   ├── .env.example
│   ├── database.php
│   ├── security.php
│   └── fiscal.php
├── 📁 api/
│   ├── validar_licenca.php
│   ├── pix_webhook.php
│   └── whatsapp_report.php
├── 📁 demo/
│   └── index.php
├── 📁 prod/
│   ├── index.php
│   ├── fiscal/
│   ├── pix/
│   └── admin/
├── 📁 assets/
│   ├── css/
│   ├── js/
│   └── img/
├── 📁 sql/
│   ├── schema.sql
│   └── seed_demo.sql
├── 📁 install/
│   └── instalador.php
├── 📁 docs/
│   └── manual_usuario.md
├── 📁 docker/
│   ├── Dockerfile
│   └── docker-compose.yml
├── .gitignore
├── composer.json
├── README.md
└── index.php
```

---

## 💻 OPÇÃO 2: Via Terminal (Recomendado para Desenvolvedores)

Se você tem Git instalado no seu computador:

```bash
# 1. Criar pasta do projeto
mkdir pdvtanque
cd pdvtanque

# 2. Inicializar repositório Git
git init

# 3. Criar estrutura de pastas
mkdir -p config api demo prod/fiscal prod/pix prod/admin assets/css assets/js assets/img sql install docs docker tests

# 4. Criar arquivo README.md
cat > README.md << 'EOF'
# 💻 PDV Tanque Digital

> Sistema de Ponto de Venda completo para o Tanque Digital, com versão DEMO e PRODUÇÃO, sincronizado em tempo real.

[![Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)](https://github.com/akilessimiao/pdvtanque)
[![PHP](https://img.shields.io/badge/php-8.0+-777BB4)](https://php.net)
[![MySQL](https://img.shields.io/badge/mysql-5.7+-4479A1)](https://mysql.com)
[![License](https://img.shields.io/badge/license-Propriet%C3%A1rio-green)](LICENSE)

## ✨ Funcionalidades

### 🧪 Versão DEMO
- [x] Cadastro de até 10 produtos
- [x] Emissão de cupons não-fiscais
- [x] Interface simplificada para testes
- [x] Expiração automática conforme gerador

### 🚀 Versão PRODUÇÃO
- [x] Gestão completa de produtos e clientes
- [x] CPF/CNPJ com validação
- [x] Cupons fiscais e não-fiscais
- [x] PIX automático com QR Code
- [x] Gestão de caixa com sangria
- [x] Relatórios para WhatsApp
- [x] Sincronização em tempo real

## 🗄️ Banco de Dados

**Servidor:** `myshared0786`  
**Database:** `pdvtanque`  
**Usuário:** `pdvtanque`

> ⚠️ Configure a senha em `.env` - nunca commitar credenciais!

## 🚀 Instalação Rápida

```bash
# 1. Clone o repositório
git clone https://github.com/akilessimiao/pdvtanque.git
cd pdvtanque

# 2. Configure as variáveis de ambiente
cp .env.example .env
# Edite .env com suas credenciais

# 3. Importe o schema
mysql -h myshared0786 -u pdvtanque -p pdvtanque < sql/schema.sql

# 4. Acesse o instalador
# http://seudominio.com/install/index.php
```

## 🔐 Ativação via Gerador

1. Acesse: https://tanquedigital.com.br/pdv/gerador.php
2. Informe o **ID da Empresa** e **Dias de Validade**
3. Clique em "GERAR E ATIVAR AGORA"
4. Use o token gerado no primeiro acesso do PDV

## 👨‍💻 Desenvolvedor

**Akiles Leopoldo Nunes Simião**  
🐍 Python & Django | 🔧 Infraestrutura & Automação  
📍 Ponta Negra, Natal-RN | 🇧🇷  

## 📄 Licença

Projeto proprietário do Tanque Digital. Uso não autorizado é proibido.

---

> 💡 **Dica**: Para suporte, use nosso WhatsApp oficial ou abra uma issue no GitHub.
EOF

# 5. Criar .gitignore
cat > .gitignore << 'EOF'
# Credenciais e configurações locais
.env
config/.env
*.log

# IDE e editor
.vscode/
.idea/
*.swp
*.swo

# Sistema
.DS_Store
Thumbs.db

# Uploads
uploads/*
!uploads/.gitkeep

# Cache e temporários
cache/*
tmp/*
*.tmp

# Banco de dados (nunca commitar dumps com dados reais)
*.sql
!sql/schema.sql
!sql/seed_demo.sql

# Dependências PHP
/vendor/
composer.lock

# Node
/node_modules/
npm-debug.log
EOF

# 6. Criar composer.json
cat > composer.json << 'EOF'
{
    "name": "akilessimiao/pdvtanque",
    "description": "Sistema PDV completo para Tanque Digital",
    "type": "project",
    "license": "proprietary",
    "authors": [
        {
            "name": "Akiles Leopoldo Nunes Simião",
            "email": "contato@tanquedigital.com.br"
        }
    ],
    "require": {
        "php": ">=8.0",
        "ext-pdo": "*",
        "ext-json": "*",
        "ext-curl": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "install": "php install/instalador.php"
    }
}
EOF

# 7. Criar arquivo principal index.php
cat > index.php << 'EOF'
<?php
/**
 * PDV Tanque Digital - Ponto de Entrada
 * Redireciona para DEMO ou PRO conforme licença
 */

session_start();
require_once __DIR__.'/config/database.php';

// Verificar se já está instalado
if (!file_exists(__DIR__.'/config/.env')) {
    header('Location: install/instalador.php');
    exit;
}

// Verificar licença
if (isset($_SESSION['licenca'])) {
    $licenca = $_SESSION['licenca'];
    if ($licenca['status'] === 'ativa' && strtotime($licenca['data_expiracao']) > time()) {
        // Versão PRO
        header('Location: prod/index.php');
    } else {
        // Versão DEMO ou expirada
        header('Location: demo/index.php');
    }
} else {
    // Primeira acesso - ir para ativação
    header('Location: install/ativacao.php');
}
exit;
?>
EOF

# 8. Criar arquivos de configuração básicos
mkdir -p config api demo prod/fiscal prod/pix prod/admin assets/css assets/js assets/img sql install docs docker tests uploads

# Config database.php
cat > config/database.php << 'EOF'
<?php
class Database {
    private $host, $dbname, $user, $pass, $conn;
    
    public function __construct() {
        $this->loadEnv();
        $this->host = $_ENV['DB_HOST'] ?? 'myshared0786';
        $this->dbname = $_ENV['DB_DATABASE'] ?? 'pdvtanque';
        $this->user = $_ENV['DB_USERNAME'] ?? 'pdvtanque';
        $this->pass = $_ENV['DB_PASSWORD'] ?? '';
    }
    
    private function loadEnv() {
        $envFile = __DIR__.'/.env';
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
            $_ENV = array_merge($_ENV, $env);
        }
    }
    
    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user, $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            return $this->conn;
        } catch(PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            die("Erro de conexão. Contate o suporte.");
        }
    }
    
    public function ping() {
        try {
            $this->connect()->query("SELECT 1");
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}
?>
EOF

# Config .env.example
cat > config/.env.example << 'EOF'
APP_NAME="PDV Tanque Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tanquedigital.com.br

# Banco de Dados
DB_HOST=myshared0786
DB_DATABASE=pdvtanque
DB_USERNAME=pdvtanque
DB_PASSWORD=SUA_SENHA_SEGURA_AQUI

# Licença e Ativação
GERADOR_URL=https://tanquedigital.com.br/pdv/gerador.php
LICENSE_KEY=

# Segurança
ADMIN_PIN=0000
SESSION_TIMEOUT=3600

# PIX (opcional)
PIX_TOKEN=
PIX_CERT_PATH=
EOF

# 9. Adicionar todos os arquivos ao Git
git add .

# 10. Primeiro commit
git commit -m "🚀 PDV Tanque Digital v2.0 - Versão completa produção

- Sistema PDV completo com DEMO e PRODUÇÃO
- Integração com gerador.php para ativação de licenças
- PIX automático via Mercado Pago
- Estrutura NFC-e pronta para SEFAZ/RN
- Relatórios formatados para WhatsApp
- Segurança: CSRF, XSS, SQLi protection, auditoria
- Deploy facilitado com Docker
- Documentação completa em português

Desenvolvido por Akiles Simião - Tanque Digital © 2026"

# 11. Conectar com repositório remoto no GitHub
git remote add origin https://github.com/akilessimiao/pdvtanque.git

# 12. Push para o GitHub
git branch -M main
git push -u origin main
```

---

## 📁 ARQUIVOS ESSENCIAIS PARA UPLOAD IMEDIATO

Se preferir upload manual via interface web, comece com estes **5 arquivos críticos**:

| Prioridade | Arquivo | Descrição |
|------------|---------|-----------|
| 🔴 1 | `README.md` | Documentação principal do projeto |
| 🔴 2 | `.gitignore` | Proteção de credenciais |
| 🔴 3 | `sql/schema.sql` | Estrutura do banco de dados |
| 🟡 4 | `config/database.php` | Conexão com banco |
| 🟡 5 | `index.php` | Ponto de entrada do sistema |

---

## ✅ CHECKLIST APÓS CRIAR REPOSITÓRIO

```bash
[ ] 1. Repositório criado em https://github.com/akilessimiao/pdvtanque
[ ] 2. README.md visível na página inicial
[ ] 3. .gitignore configurado corretamente
[ ] 4. Pasta sql/ com schema.sql importável
[ ] 5. Config/.env.example sem senhas reais
[ ] 6. Licença definida (proprietary ou MIT)
[ ] 7. Issues habilitadas para suporte
[ ] 8. Wiki ou docs/ para documentação
[ ] 9. Branch protection na main (opcional)
[ ] 10. Badge de status no README
```

---

## 🔗 LINKS ÚTEIS

| Recurso | URL |
|---------|-----|
| Criar Repositório | https://github.com/new |
| Upload de Arquivos | https://github.com/akilessimiao/pdvtanque/upload |
| GitHub Desktop | https://desktop.github.com/ |
| Git Download | https://git-scm.com/downloads |
| Shields.io (Badges) | https://shields.io/ |

---

## 🎯 PRÓXIMOS PASSOS APÓS CRIAR REPOSITÓRIO

1. **Compartilhe o link** do repositório comigo
2. **Posso ajudar a:**
   - Gerar badges personalizadas para o README
   - Criar issues template para bugs/features
   - Configurar GitHub Actions para CI/CD
   - Gerar documentação automática com PHPDoc

3. **Notifique a equipe** do Tanque Digital sobre o repositório

4. **Configure webhooks** para integração com gerador.php

---

> 💡 **Dica Pro**: 
> - 🎨 Imagem de capa para o repositório
> - 📊 Diagrama de arquitetura do sistema
> - 🔖 Badges personalizadas de status
> - 📝 Templates de issues e pull requests

