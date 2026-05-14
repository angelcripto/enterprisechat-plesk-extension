#!/usr/bin/env bash
#
# Hook ejecutado por Plesk tras desplegar la extensión en
# /usr/local/psa/admin/plib/modules/enterprisechat/. Corre como root.
#
#   1. Instala el .deb del servidor (payload/enterprisechat_*_amd64.deb).
#   2. Genera secretos (JWT signing key + admin password) si no existen.
#   3. Escribe appsettings.Production.json y arranca el servicio.
#
set -euo pipefail

MODULE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DEB="$(ls -1 "$MODULE_DIR"/payload/enterprisechat_*_amd64.deb 2>/dev/null | head -n1 || true)"

if [[ -z "$DEB" ]]; then
    echo "ERROR: no se encontró el paquete .deb en $MODULE_DIR/payload/" >&2
    exit 1
fi

INSTALL_DIR=/opt/enterprisechat
SERVICE_USER=enterprisechat
SETTINGS="$INSTALL_DIR/appsettings.Production.json"

# --- 1. Instalar / actualizar paquete --------------------------------------
echo "==> Instalando $DEB"
if dpkg -s enterprisechat >/dev/null 2>&1; then
    apt-get install -y --reinstall --allow-downgrades "$DEB" || dpkg -i "$DEB"
else
    apt-get install -y "$DEB" || { dpkg -i "$DEB"; apt-get install -fy; }
fi

# --- 2. Bootstrap secretos -------------------------------------------------
if [[ ! -f "$SETTINGS" ]]; then
    echo "==> Generando secretos iniciales"
    JWT_KEY="$(openssl rand -base64 48 | tr -d '\n')"
    ADMIN_PWD="$(openssl rand -base64 18 | tr -d '\n=+/' | head -c 16)"

    umask 027
    cat > "$SETTINGS" <<EOF
{
  "EnterpriseChat": {
    "Jwt": {
      "SigningKey": "$JWT_KEY",
      "Issuer": "EnterpriseChat.Prod",
      "Audience": "EnterpriseChat.Clients",
      "AccessTokenLifetimeMinutes": 60
    },
    "Bootstrap": { "AdminPassword": "$ADMIN_PWD" }
  }
}
EOF
    chown "$SERVICE_USER:$SERVICE_USER" "$SETTINGS"
    chmod 0640 "$SETTINGS"

    # Reveal one-shot: la UI Plesk lo muestra una vez y lo borra.
    echo "$ADMIN_PWD" > "$INSTALL_DIR/.first-admin-password"
    chown "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR/.first-admin-password"
    chmod 0600 "$INSTALL_DIR/.first-admin-password"
fi

# --- 3. Arrancar -----------------------------------------------------------
systemctl daemon-reload
systemctl enable --now enterprisechat.service
systemctl --no-pager status enterprisechat.service || true

echo "==> EnterpriseChat instalado. Abre el panel Plesk para enlazar dominio."
