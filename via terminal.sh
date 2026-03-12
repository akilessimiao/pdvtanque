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