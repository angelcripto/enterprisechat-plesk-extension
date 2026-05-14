<?php
/**
 * Gestiona snippets nginx por dominio para enrutar el reverse proxy hasta
 * el servidor local en 127.0.0.1:5080 con soporte WebSocket para SignalR.
 *
 * El snippet vive en /etc/nginx/plesk.conf.d/vhosts/enterprisechat-<dom>.conf
 * y se escribe / borra mediante el wrapper privilegiado /sbin/nginx-snippet
 * (Plesk no permite usar filemng cp2perm escribiendo en /etc/nginx).
 *
 * Crítico SignalR: el snippet incluye proxy_http_version 1.1 + headers
 * Upgrade/Connection + timeouts 1h. Sin esto el cliente cae a long-polling.
 */
class Modules_Enterprisechat_NginxConfig
{
    const BACKEND      = '127.0.0.1:5080';
    const SNIPPETS_DIR = '/etc/nginx/plesk.conf.d/vhosts';

    /**
     * @param string $domain   FQDN (chat.cliente.com, etc.)
     * @param string $location Ruta base ("/" o "/chat/", etc.)
     */
    public static function bind(string $domain, string $location = '/'): void
    {
        $domain   = self::sanitizeDomain($domain);
        $location = self::sanitizeLocation($location);

        $content = self::render($domain, $location);
        $b64     = base64_encode($content);

        $r = pm_ApiCli::callSbin(
            'nginx-snippet',
            ['write', $domain, $b64],
            pm_ApiCli::RESULT_FULL
        );
        if ((int)($r['code'] ?? 1) !== 0) {
            throw new pm_Exception(
                'nginx-snippet write falló: ' . ($r['stderr'] ?? $r['stdout'] ?? '')
            );
        }
    }

    public static function unbind(string $domain): void
    {
        $domain = self::sanitizeDomain($domain);

        $r = pm_ApiCli::callSbin(
            'nginx-snippet',
            ['rm', $domain],
            pm_ApiCli::RESULT_FULL
        );
        if ((int)($r['code'] ?? 1) !== 0) {
            throw new pm_Exception(
                'nginx-snippet rm falló: ' . ($r['stderr'] ?? $r['stdout'] ?? '')
            );
        }
    }

    /**
     * Listado de dominios bindeados. psaadm no puede leer
     * /etc/nginx/plesk.conf.d/vhosts/ directamente; lo enumera el wrapper.
     */
    public static function listBindings(): array
    {
        $r = pm_ApiCli::callSbin(
            'nginx-snippet',
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

    // ----- helpers privados ----------------------------------------------

    private static function render(string $domain, string $location): string
    {
        $backend = self::BACKEND;

        // Plesk ya genera su propio `location /` (prefix) en el vhost del
        // dominio para hacer proxy a Apache. Si declaramos otra location `/`
        // como prefix nginx falla con
        //   nginx: [emerg] duplicate location "/"
        // y httpdmng revierte el vhost a Apache solo (sin nginx delante).
        //
        // Usamos una location regex (~) o exact (=) — nginx prioriza ambas
        // sobre la prefix de Plesk y no genera duplicado.
        //   - Si el usuario monta en "/" usamos `location ~ "^/"`, que
        //     captura cualquier path vía regex (mayor prioridad que prefix).
        //   - Si el usuario monta en /chat/ usamos `location ^~ /chat/`
        //     (prefix preferred, también supera al prefix `/` por
        //     especificidad).
        if ($location === '/') {
            $locDirective = 'location ~ "^/"';
            // Sin URI en proxy_pass para que nginx mantenga el path
            // original al hacer match por regex.
            $proxyPass = "http://{$backend}";
        } else {
            $locDirective = 'location ^~ ' . $location;
            // Path-mounted: trailing slash mantiene el strip del prefijo
            // del location antes de llegar al backend.
            $proxyPass = "http://{$backend}/";
        }

        return <<<NGINX
# Managed by Plesk extension EnterpriseChat — do not edit by hand.
# Domain: {$domain}

{$locDirective} {
    proxy_pass {$proxyPass};
    proxy_http_version 1.1;
    proxy_set_header Upgrade \$http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_read_timeout 3600s;
    proxy_send_timeout 3600s;
    proxy_buffering off;
}

NGINX;
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
