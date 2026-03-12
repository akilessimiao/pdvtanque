#!/bin/bash
# backup-pdv.sh - Agendar no cron: 0 2 * * * /path/backup-pdv.sh

BACKUP_DIR="/backups/pdvtanque"
DATE=$(date +%Y%m%d_%H%M%S)
DB_USER="pdvtanque"
DB_NAME="pdvtanque"
DB_HOST="myshared0786"

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Executar backup
mysqldump -h $DB_HOST -u $DB_USER -p'SUA_SENHA' $DB_NAME | gzip > $BACKUP_DIR/pdvtanque_$DATE.sql.gz

# Manter apenas últimos 7 dias
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

# Log
echo "[$(date)] Backup realizado: pdvtanque_$DATE.sql.gz" >> $BACKUP_DIR/backup.log