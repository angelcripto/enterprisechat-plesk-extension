#!/usr/bin/env bash
#
# Hook pre-uninstall: detiene el servicio, limpia snippets nginx generados,
# opcionalmente purga datos según variable KEEP_DATA (1 por defecto = conservar).
#
# La UI Plesk fija KEEP_DATA=0 si el admin marca la casilla "borrar también
# base de datos y adjuntos".
#
set -euo pipefail

KEEP_DATA="${KEEP_DATA:-1}"

systemctl disable --now enterprisechat.service 2>/dev/null || true

# Snippets nginx generados por la extensión
shopt -s nullglob
for f in /etc/nginx/plesk.conf.d/vhosts/enterprisechat-*.conf; do
    rm -f "$f"
done

# Forzar reload nginx via Plesk (no `systemctl reload nginx` directo)
if command -v plesk >/dev/null 2>&1; then
    plesk sbin httpdmng --reconfigure-all || true
fi

if [[ "$KEEP_DATA" == "0" ]]; then
    echo "==> Purga total solicitada — eliminando paquete y datos"
    apt-get remove -y --purge enterprisechat || true
    rm -rf /opt/enterprisechat
else
    echo "==> Conservando /opt/enterprisechat (KEEP_DATA=1)"
    apt-get remove -y enterprisechat || true
fi
