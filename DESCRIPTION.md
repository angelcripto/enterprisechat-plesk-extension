# EnterpriseChat

Chat empresarial autoalojado para pymes con varios despachos. Tu servidor,
tu base de datos, tu infraestructura. Ni nube de terceros, ni APIs externas
por las que circulen las conversaciones internas. La extensión convierte
cualquier servidor administrado con Plesk en el host del chat de tu
organización.

El panel se encarga del ciclo de vida completo: instalación del paquete
.deb del servidor .NET 8, generación automática de la clave JWT y de la
contraseña inicial del administrador, configuración del reverse proxy
nginx con soporte WebSocket para SignalR, activación de la edición Pro
mediante clave de licencia e integración con los backups de Plesk.

## Versión gratuita

- Mensajería 1:1 y salas de grupo.
- Compartición de archivos y avatares.
- Historial completo en SQLite local con búsqueda.
- Cliente de escritorio Windows nativo (WPF), distribuido aparte como MSI.
- TLS extremo a extremo a través del dominio Plesk que enlaces.
- Hasta 10 usuarios concurrentes.

## Versión Pro

- Usuarios concurrentes ilimitados.
- Auto-update gestionado del servidor.
- Administración remota desde el propio panel Plesk.
- Soporte profesional por email.
- Actualizaciones de seguridad prioritarias.

## Cómo funciona

1. Instala la extensión desde el panel.
2. Plesk genera automáticamente la clave JWT y la contraseña inicial del
   administrador, mostrándola una sola vez.
3. Enlaza el chat a un dominio o subdominio Plesk; el reverse proxy nginx
   queda configurado con soporte WebSocket.
4. Reparte el cliente Windows (MSI) entre tus empleados — se conectan al
   dominio que has configurado y reconectan automáticamente.

## Sistemas operativos compatibles

- Ubuntu 20.04, 22.04 y 24.04
- Debian 11 y 12
- AlmaLinux 8 y 9
- RockyLinux 8 y 9
- CloudLinux 8 y 9
