# EnterpriseChat — Plesk Extension

Extensión para **Plesk Obsidian** (18.0.50+) que instala y gestiona el
servidor [EnterpriseChat](https://enterprisechat.es) en un servidor
administrado con Plesk (Ubuntu, Debian, AlmaLinux, RockyLinux, CloudLinux).

> Este repositorio es independiente del repo del servidor
> (`enterprisechat/`, AGPLv3). Se mantiene aquí para no mezclar el código
> PHP de la extensión con la base .NET del producto, y para poder publicarlo
> con licencia y ciclo de versionado propios.

La extensión empaqueta un `.deb` con el servidor .NET 8 self-contained y,
una vez instalada desde el panel:

- Despliega el binario en `/opt/enterprisechat/`.
- Registra y arranca el `systemd unit` `enterprisechat.service`.
- Genera secretos (`Jwt:SigningKey`, `Bootstrap:AdminPassword`) y los escribe
  en `appsettings.Production.json`.
- Permite enlazar el servicio a un dominio o subdominio Plesk y configura el
  reverse proxy nginx con soporte WebSocket para SignalR.
- Expone una UI dentro del panel para iniciar, detener, reiniciar, ver el
  estado de la licencia y activar una clave Pro.
- Integra hook de backup para incluir DB SQLite + adjuntos en los volcados
  Plesk.

## Layout esperado

```
aprender-programacion/
├── enterprisechat/                    Repo servidor (.NET 8 + WPF + web)
└── enterprisechat-plesk-extension/    Este repo
```

`build.sh` busca el repo del servidor como hermano (`../enterprisechat`)
por defecto. Si está en otra ruta, exporta `ENTERPRISECHAT_REPO`:

```bash
ENTERPRISECHAT_REPO=/ruta/al/repo ./build.sh
```

## Estructura

```
enterprisechat-plesk-extension/
├── meta.xml            Manifest Plesk
├── plib/               Backend PHP (controllers, library, views, locales)
├── htdocs/             Recursos estáticos servidos al navegador
├── hooks/              Scripts shell pre/post install y backup
├── debian/             Plantillas .deb (control, postinst, prerm)
├── build.sh            Empaqueta servidor + extension zip
└── README.md
```

## Compilar el zip

Requiere `dotnet 8 SDK`, `node 20+`, `dpkg-deb`, `zip` (Linux o WSL).

```bash
./build.sh
# Resultado: build/enterprisechat-plesk-0.1.0.zip
```

El zip se sube en Plesk → *Extensions → Upload Extension*.

## Desarrollo

Para iterar el código PHP sin reempaquetar el `.deb`:

1. Subir el zip una vez (con el `.deb` válido).
2. Editar archivos en `/usr/local/psa/admin/plib/modules/enterprisechat/`
   directamente en el servidor de pruebas.
3. Limpiar caché Plesk: `plesk bin extension --reload`.

## Soporte

Issues en el repo del servidor o `soporte@enterprisechat.es`.
