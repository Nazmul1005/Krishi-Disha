#!/bin/sh
set -eu
umask 077
backup_destination=${1:-storage/backups}
mkdir -p "$backup_destination"
backup_stamp=$(date -u +%Y%m%dT%H%M%SZ)
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -u root --single-transaction --routines --triggers krishidisha' > "$backup_destination/database-$backup_stamp.sql"
docker compose exec -T web tar -C /app/storage -czf - . > "$backup_destination/media-$backup_stamp.tar.gz"
printf 'Private backup saved in %s\n' "$backup_destination"
