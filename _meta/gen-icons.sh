#!/usr/bin/env bash
#
# Genera los iconos requeridos por Plesk (_meta/icons/{32,64,128}x{32,64,128}.png)
# recortando la burbuja del logo del servidor y redimensionando con ImageMagick.
#
set -euo pipefail

SRC="${1:-../../enterprisechat/src/EnterpriseChat.Server/wwwroot/logo.png}"
HERE="$(cd "$(dirname "$0")" && pwd)"

if [[ ! -f "$SRC" ]]; then
    echo "ERROR: logo fuente no encontrado: $SRC" >&2
    exit 1
fi

for size in 32 64 128; do
    convert "$SRC" \
        -crop 419x419+0+0 +repage \
        -resize "${size}x${size}" \
        -background none -gravity center -extent "${size}x${size}" \
        "$HERE/icons/${size}x${size}.png"
done

identify "$HERE/icons/"*.png
