#!/usr/bin/env bash
#
# Backup hook: lo invoca el panel Plesk al programar / lanzar un backup que
# incluya esta extensión. Recibe el directorio destino como $1.
#
# Estrategia simple y correcta:
#   - Detener servicio (consistencia SQLite WAL).
#   - tar -czf  data/ + logs/ + appsettings.Production.json.
#   - Reanudar.
#
set -euo pipefail

DEST="${1:-}"
if [[ -z "$DEST" || ! -d "$DEST" ]]; then
    echo "ERROR: destino inválido: '$DEST'" >&2
    exit 1
fi

ARCHIVE="$DEST/enterprisechat-$(date -u +%Y%m%dT%H%M%SZ).tar.gz"

was_active=0
if systemctl is-active --quiet enterprisechat.service; then
    was_active=1
    systemctl stop enterprisechat.service
fi

# El servicio está parado: cualquier escritor SQLite ha cerrado el WAL.
tar -czf "$ARCHIVE" -C /opt/enterprisechat \
    data logs appsettings.Production.json 2>/dev/null

if [[ "$was_active" == "1" ]]; then
    systemctl start enterprisechat.service
fi

echo "$ARCHIVE"
