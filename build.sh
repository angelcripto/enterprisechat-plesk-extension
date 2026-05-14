#!/usr/bin/env bash
#
# Empaqueta EnterpriseChat como extensión Plesk:
#   1. dotnet publish self-contained linux-x64
#   2. Construye .deb (incluye binario + systemd unit)
#   3. Empaqueta zip con manifest, plib, htdocs, hooks y el .deb dentro
#
# Uso:  ./build.sh [version]
#
set -euo pipefail

VERSION="${1:-0.1.0}"
EXT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Repo del servidor. Por defecto se busca como hermano del repo de la
# extensión (../enterprisechat). Override con ENTERPRISECHAT_REPO=/ruta.
SERVER_REPO="${ENTERPRISECHAT_REPO:-$(cd "$EXT_DIR/../enterprisechat" 2>/dev/null && pwd || true)}"

if [[ -z "$SERVER_REPO" || ! -d "$SERVER_REPO/src/EnterpriseChat.Server" ]]; then
    echo "ERROR: no se encuentra el repo del servidor." >&2
    echo "       Esperado en: $EXT_DIR/../enterprisechat" >&2
    echo "       o exporta ENTERPRISECHAT_REPO=/ruta/al/repo y reintenta." >&2
    exit 1
fi

BUILD="$EXT_DIR/build"
PUBLISH="$BUILD/publish"
PKG="$BUILD/pkg"
EXT_STAGE="$BUILD/ext"

rm -rf "$BUILD"
mkdir -p "$PUBLISH" "$PKG/DEBIAN" "$PKG/opt/enterprisechat" "$PKG/etc/systemd/system" "$EXT_STAGE/payload"

# --- 1. Publish .NET self-contained ----------------------------------------
echo "==> dotnet publish (linux-x64, self-contained)"
dotnet publish "$SERVER_REPO/src/EnterpriseChat.Server/EnterpriseChat.Server.csproj" \
    -c Release -r linux-x64 --self-contained true \
    -p:PublishSingleFile=true -p:PublishTrimmed=false \
    -o "$PUBLISH"

# --- 2. .deb layout --------------------------------------------------------
echo "==> Constructing .deb layout"
cp -r "$PUBLISH/." "$PKG/opt/enterprisechat/"
cp "$SERVER_REPO/src/EnterpriseChat.Server/scripts/enterprisechat.service" \
   "$PKG/etc/systemd/system/enterprisechat.service"

cat > "$PKG/DEBIAN/control" <<EOF
Package: enterprisechat
Version: $VERSION
Section: net
Priority: optional
Architecture: amd64
Maintainer: EnterpriseChat <soporte@enterprisechat.es>
Depends: libicu70 | libicu72 | libicu74, libssl3 | libssl1.1
Description: EnterpriseChat self-hosted corporate chat server
 ASP.NET Core + SignalR + SQLite. Runs as systemd unit on port 5080.
EOF

cp "$EXT_DIR/debian/postinst" "$PKG/DEBIAN/postinst"
cp "$EXT_DIR/debian/prerm"    "$PKG/DEBIAN/prerm"
chmod 0755 "$PKG/DEBIAN/postinst" "$PKG/DEBIAN/prerm"

DEB="$BUILD/enterprisechat_${VERSION}_amd64.deb"
echo "==> dpkg-deb --build"
dpkg-deb --build --root-owner-group "$PKG" "$DEB"

# --- 3. Extension zip ------------------------------------------------------
echo "==> Staging extension zip"
cp -r "$EXT_DIR/meta.xml" "$EXT_DIR/plib" "$EXT_DIR/hooks" "$EXT_STAGE/"
[[ -d "$EXT_DIR/htdocs" ]] && cp -r "$EXT_DIR/htdocs" "$EXT_STAGE/"
cp "$DEB" "$EXT_STAGE/payload/"

ZIP="$BUILD/enterprisechat-plesk-${VERSION}.zip"
( cd "$EXT_STAGE" && zip -rq "$ZIP" . )

echo
echo "Done."
echo "  .deb : $DEB"
echo "  zip  : $ZIP"
