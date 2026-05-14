# Build — empaquetar la extensión Plesk

Genera el zip que se sube en *Plesk → Extensions → Upload Extension*. El zip
contiene el manifest, el código PHP de la extensión y, dentro de `payload/`,
un `.deb` con el servidor EnterpriseChat .NET 8 self-contained para
`linux-x64`.

## Entorno recomendado

WSL Ubuntu 20.04 / 22.04 / 24.04 o un Linux nativo equivalente. **No**
intentes compilar desde PowerShell o cmd: el paso `dotnet publish` produce
un binario Linux, y el `.deb` solo se construye con `dpkg-deb`.

### Dependencias

```bash
# .NET 8 SDK (repo Microsoft — funciona en Ubuntu 20.04, 22.04 y 24.04)
wget https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/packages-microsoft-prod.deb
sudo dpkg -i packages-microsoft-prod.deb && rm packages-microsoft-prod.deb
sudo apt update
sudo apt install -y dotnet-sdk-8.0

# Node.js 20 LTS (Ubuntu 20.04 trae 10 por defecto; Vite no funciona)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Empaquetado
sudo apt install -y dpkg-dev zip openssl
```

Verifica versiones:

```bash
dotnet --version    # 8.0.x
node --version      # v20.x
npm --version       # 10.x
dpkg-deb --version  # 1.x
```

## Ubicación de los repos

`build.sh` busca el repo del servidor como hermano del repo de la extensión:

```
aprender-programacion/
├── enterprisechat/                    Repo servidor (.NET 8 + WPF + web)
└── enterprisechat-plesk-extension/    Este repo
```

Si el repo del servidor está en otra ruta, exporta `ENTERPRISECHAT_REPO`:

```bash
ENTERPRISECHAT_REPO=/ruta/al/server ./build.sh
```

## Build

```bash
cd enterprisechat-plesk-extension
./build.sh                # versión por defecto 0.1.0
./build.sh 0.2.0          # versión explícita
```

Salida en `build/`:

- `enterprisechat_<version>_amd64.deb` — paquete del servidor (~80-90 MB).
- `enterprisechat-plesk-<version>.zip` — zip listo para subir a Plesk.

## Inspeccionar artefactos

```bash
dpkg-deb --info     build/enterprisechat_0.1.0_amd64.deb
dpkg-deb --contents build/enterprisechat_0.1.0_amd64.deb | head
unzip -l            build/enterprisechat-plesk-0.1.0.zip
```

El zip debe contener `meta.xml`, `plib/`, `htdocs/`, `hooks/` y
`payload/enterprisechat_<version>_amd64.deb`.

## Notas

- **No instales el `.deb` en WSL** para probar el servicio: WSL no arranca
  `systemd` por defecto, y las dependencias `libssl3` no están en Ubuntu
  20.04. La prueba real va en un VPS con Plesk Obsidian.
- **Filesystem WSL vs `/mnt/c/`:** si tienes los repos en `/mnt/c/...`
  (montaje desde Windows), `npm` y `vite` van mucho más lentos por el
  bridge NTFS. Para iteración rápida, clona dentro de `~/`. Para una build
  puntual sirve `/mnt/c/`.
- **Errores de versión de dependencias** al hacer `dpkg-deb --build`: revisa
  `debian/control`. Si el target tiene `libssl` o `libicu` con números
  distintos a los listados (`libssl3 | libssl1.1`, `libicu70 | libicu72 |
  libicu74`), añade la versión nueva al campo `Depends:`.
- **CRLF en hooks:** si los `.sh` se corrompen tras pasar por Windows
  (shebang `^M` que rompe `bash`), `.gitattributes` ya fuerza LF para todos
  los scripts y archivos PHP. Si manualmente editas con un editor que
  reinjecta CRLF, conviértelos antes con `dos2unix hooks/*.sh debian/*`.
