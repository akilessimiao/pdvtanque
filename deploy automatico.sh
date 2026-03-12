#!/bin/bash
# deploy.sh - Deploy automático para produção

set -e

echo "🚀 Iniciando deploy PDV Tanque Digital..."

# 1. Backup do banco atual
echo "📦 Criando backup..."
mysqldump -h myshared0786 -u pdvtanque -p'${DB_PASSWORD}' pdvtanque | gzip > backups/pdvtanque_$(date +%Y%m%d_%H%M%S).sql.gz

# 2. Pull das alterações
echo "🔄 Atualizando código..."
git pull origin main

# 3. Instalar dependências PHP
echo "📦 Instalando dependências..."
composer install --no-dev --optimize-autoloader

# 4. Limpar cache
echo "🧹 Limpando cache..."
php -r "if(file_exists('cache/')) array_map('unlink', glob('cache/*'));"

# 5. Verificar migrações pendentes
if [ -f "sql/migrate_*.sql" ]; then
    echo "🔄 Aplicando migrações..."
    mysql -h myshared0786 -u pdvtanque -p'${DB_PASSWORD}' pdvtanque < sql/migrate_*.sql
fi

# 6. Reiniciar serviços (se usar Docker)
if [ -f "docker/docker-compose.yml" ]; then
    echo "🔄 Reiniciando containers..."
    docker-compose -f docker/docker-compose.yml restart pdv-app
fi

# 7. Teste de saúde
echo "✅ Verificando saúde do sistema..."
curl -f https://tanquedigital.com.br/pdv/api/health || exit 1

echo "✨ Deploy concluído com sucesso!"
echo "📊 Acesse: https://tanquedigital.com.br/pdv"