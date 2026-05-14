# Changelog — EnterpriseChat Plesk Extension

## [0.1.0] — 2026-05-14

### Added
- Esqueleto inicial: manifest, controllers, library, views, locales.
- `build.sh`: empaqueta server .NET 8 self-contained en `.deb` + zip extension.
- Hook `post-install.sh`: instala paquete, genera JWT signing key y contraseña
  admin inicial, arranca systemd unit.
- Hook `pre-uninstall.sh`: detiene servicio, limpia snippets nginx, conserva
  datos por defecto.
- Hook `backup.sh`: vuelca `data/`, `logs/`, `appsettings.Production.json`.
- `NginxConfig`: bind a dominio Plesk con reverse proxy y soporte WebSocket
  (proxy_http_version 1.1, Upgrade/Connection headers, timeouts 1h).
- UI panel: estado servicio, contraseña inicial (one-shot reveal), license
  key, dominio bindeado.
- i18n es-ES / en-US.
