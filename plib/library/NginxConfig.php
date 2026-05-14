<?php
/**
 * Genera y registra snippets nginx para mapear un dominio o subdominio Plesk
 * al servidor EnterpriseChat local (127.0.0.1:5080). Crítico para que
 * SignalR funcione:
 *
 *   - proxy_http_version 1.1
 *   - headers Upgrade / Connection
 *   - read/send timeouts largos (long-lived WS)
 *   - proxy_buffering off
 *
 * El snippet se escribe en /etc/nginx/plesk.conf.d/vhosts/enterprisechat-<id>.conf
 * y luego se llama a `plesk sbin httpdmng --reconfigure-domain <dominio>`
 * para que Plesk regenere el vhost incluyendo el archivo.
 */
class Modules_Enterprisechat_NginxConfig
{
    const SNIPPETS_DIR = '/etc/nginx/plesk.conf.d/vhosts';
    const BACKEND      = '127.0.0.1:5080';

    /**
     * @param string $domain   Nombre exacto del dominio Plesk (ej. chat.cliente.com)
     * @param string $location Ruta base donde montar el chat (ej. "/" o "/chat/")
     */
    public static function bind(string $domain, string $location = '/'): void
    {
        $domain = self::sanitizeDomain($domain);
        $location = self::sanitizeLocation($location);

        if (!is_dir(self::SNIPPETS_DIR)) {
            pm_ApiCli::callSbin(
                'filemng',
                ['root', 'mkdir', '-p', self::SNIPPETS_DIR]
            );
        }

        $path = self::snippetPath($domain);
        $content = self::render($domain, $location);

        $tmp = tempnam(sys_get_temp_dir(), 'ecnginx_');
        file_put_contents($tmp, $content);
        pm_ApiCli::callSbin('filemng', ['root', 'cp2perm', $tmp, $path, '0644']);
        @unlink($tmp);

        pm_ApiCli::callSbin(
            'httpdmng',
            ['--reconfigure-domain', $domain]
        );
    }

    public static function unbind(string $domain): void
    {
        $domain = self::sanitizeDomain($domain);
        $path = self::snippetPath($domain);
        if (file_exists($path)) {
            pm_ApiCli::callSbin('filemng', ['root', 'rm', $path]);
            pm_ApiCli::callSbin('httpdmng', ['--reconfigure-domain', $domain]);
        }
    }

    /**
     * Devuelve listado de dominios actualmente bindeados a EnterpriseChat
     * leyendo el directorio de snippets.
     */
    public static function listBindings(): array
    {
        $out = [];
        foreach ((array)@glob(self::SNIPPETS_DIR . '/enterprisechat-*.conf') as $file) {
            if (preg_match('~/enterprisechat-(.+)\.conf$~', $file, $m)) {
                $out[] = $m[1];
            }
        }
        sort($out);
        return $out;
    }

    // ----- internos ----------------------------------------------------

    private static function snippetPath(string $domain): string
    {
        return self::SNIPPETS_DIR . '/enterprisechat-' . $domain . '.conf';
    }

    private static function render(string $domain, string $location): string
    {
        $backend = self::BACKEND;
        return <<<NGINX
# Managed by Plesk extension EnterpriseChat — do not edit by hand.
# Domain: {$domain}

location {$location} {
    proxy_pass http://{$backend}/;
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
