#!/usr/bin/env bash
# Contoh backup dasar. Simpan salinan di luar server dan UJI restore secara berkala.
set -euo pipefail
: "${DB_NAME:?}" "${DB_USER:?}" "${DB_PASS:?}"
OUT="${BACKUP_DIR:-/var/backups/classora}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"; umask 077
mysqldump --single-transaction --routines --triggers -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$OUT/db-$STAMP.sql.gz"
tar -czf "$OUT/storage-$STAMP.tar.gz" -C /var/www/classora/apps/api storage/app
find "$OUT" -type f -mtime +14 -delete
echo "Backup selesai: $OUT ($STAMP)"
