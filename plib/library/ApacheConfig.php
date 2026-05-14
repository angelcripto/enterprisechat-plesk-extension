<?php
/**
 * Gestiona el reverse proxy del subdominio elegido hacia el servidor
 * EnterpriseChat (127.0.0.1:5080) mediante directivas Apache con soporte
 * WebSocket (mod_proxy_wstunnel) para SignalR.
 *
 * Estrategia Plesk Obsidian:
 *   - El admin del panel sigue gestionando el dominio en Plesk como
 *     cualquier otro: DNS, TLS (Let's Encrypt), etc.
 *   - Esta clase escribe el snippet Apache vía el wrapper privilegiado
 *     `/sbin/apache-snippet` (Plesk lo despliega como setuid root) en
 *     `/var/www/vhosts/system/<dom>/conf/vhost.conf` + `vhost_ssl.conf`,
 *     que Plesk incluye dentro del `<VirtualHost>` del dominio al regenerar.
 *   - Si Apache `-t` falla tras escribir, el wrapper hace rollback. La UI
 *     recibe `{ok:false, error: ...}` para informar al admin sin dejar
 *     Apache roto.
 *
 * Por qué Apache (no nginx):
 *   - Apache está SIEMPRE presente en Plesk (es el backend default).
 *   - nginx es opcional y requiere `plesk sbin nginxmng --enable`, lo que
 *     fuerza un cambio global en el servidor. La extensión debe funcionar
 *     con cualquier instalación Plesk razonable.
 *
 * Módulos Apache requeridos: proxy, proxy_http, proxy_wstunnel, rewrite,
 * headers. El wrapper sbin valida que están cargados y aborta con error
 * legible si falta alguno.
 */
class Modules_Enterprisechat_ApacheConfig
{
    const BACKEND = '127.0.0.1:5080';

    public static function bind(string $domain, string $location = '/'): void
    {
        $domain   = self::sanitizeDomain($domain);
        $location = self::sanitizeLocation($location);

        $content = self::render($domain, $location);
        $b64     = base64_encode($content);

        $r = pm_ApiCli::callSbin(
            'apache-snippet',
            ['write', $domain, $b64],
            pm_ApiCli::RESULT_FULL
        );
        if ((int)($r['code'] ?? 1) !== 0) {
            throw new pm_Exception(
                'apache-snippet write falló: ' .
                trim((string)($r['stderr'] ?? $r['stdout'] ?? ''))
            );
        }
    }

    public static function unbind(string $domain): void
    {
        $domain = self::sanitizeDomain($domain);

        $r = pm_ApiCli::callSbin(
            'apache-snippet',
            ['rm', $domain],
            pm_ApiCli::RESULT_FULL
        );
        if ((int)($r['code'] ?? 1) !== 0) {
            throw new pm_Exception(
                'apache-snippet rm falló: ' .
                trim((string)($r['stderr'] ?? $r['stdout'] ?? ''))
            );
        }
    }

    public static function listBindings(): array
    {
        $r = pm_ApiCli::callSbin(
            'apache-snippet',
            ['list'],
            pm_ApiCli::RESULT_FULL
        );
        if ((int)($r['code'] ?? 1) !== 0) {
            return [];
        }
        $out = trim((string)($r['stdout'] ?? ''));
        if ($out === '') {
            return [];
        }
        $domains = preg_split('/\s+/', $out) ?: [];
        sort($domains);
        return array_values(array_unique(array_filter($domains)));
    }

    // ----- helpers internos ----------------------------------------------

    private static function render(string $domain, string $location): string
    {
        $backend = self::BACKEND;

        // Si el admin elige montar en la raíz "/", emitimos las directivas
        // sin envolver en <Location />: Apache las aplica a todo el vhost.
        // Si elige un sub-path (por ejemplo /chat/), envolvemos en
        // <Location /chat/> para acotar el alcance y no romper otras URLs
        // que pueda servir el sitio.
        if ($location === '/') {
            $body = <<<APACHE
ProxyPreserveHost On
ProxyRequests Off

# WebSocket upgrade (SignalR). Debe ir ANTES del ProxyPass general.
RewriteEngine On
RewriteCond %{HTTP:Upgrade} =websocket [NC]
RewriteRule /(.*) ws://{$backend}/\$1 [P,L]

ProxyPass        / http://{$backend}/ retry=0 timeout=3600 connectiontimeout=5
ProxyPassReverse / http://{$backend}/

RequestHeader set X-Forwarded-Proto "https" env=HTTPS
APACHE;
        } else {
            // location ya viene con slashes leading + trailing por sanitizeLocation.
            $body = <<<APACHE
<Location {$location}>
    ProxyPreserveHost On
    ProxyRequests Off
    ProxyPass        http://{$backend}{$location} retry=0 timeout=3600 connectiontimeout=5
    ProxyPassReverse http://{$backend}{$location}
    RequestHeader set X-Forwarded-Proto "https" env=HTTPS
</Location>

# WebSocket upgrade (SignalR) — restringido al location.
RewriteEngine On
RewriteCond %{HTTP:Upgrade} =websocket [NC]
RewriteRule "^{$location}(.*)" "ws://{$backend}{$location}\$1" [P,L]
APACHE;
        }

        return <<<HEAD
# Managed by Plesk extension EnterpriseChat — do not edit by hand.
# Domain: {$domain}
# Backend: {$backend}

{$body}

HEAD;
    }

    private static function sanitizeDomain(string $d): string
    {
        $d = strtolower(trim($d));
        if (!preg_match('/^[a-z0-9](?:[a-z0-9\-\.]*[a-z0-9])?$/', $d)) {
            throw new pm_Exception("Dominio inválido: $d");
        }
        return $d;
    }

    private static function sanitizeLocation(string $loc): string
    {
        $loc = '/' . trim($loc, '/');
        if ($loc !== '/' && !preg_match('~^/[a-zA-Z0-9_\-/]+/?$~', $loc)) {
            throw new pm_Exception("Ruta inválida: $loc");
        }
        if ($loc !== '/' && substr($loc, -1) !== '/') {
            $loc .= '/';
        }
        return $loc;
    }
}
