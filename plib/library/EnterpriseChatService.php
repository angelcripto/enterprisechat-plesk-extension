<?php
/**
 * Wrapper sobre `systemctl` + endpoints HTTP locales del servidor
 * EnterpriseChat. Llamado desde controllers para start/stop/restart/status,
 * consulta de licencia y captura one-shot de la contraseña admin inicial.
 */
class Modules_Enterprisechat_EnterpriseChatService
{
    const SERVICE      = 'enterprisechat.service';
    const INSTALL_DIR  = '/opt/enterprisechat';
    const LOCAL_URL    = 'http://127.0.0.1:5080';
    const FIRST_PWD    = '/opt/enterprisechat/.first-admin-password';

    public static function status(): array
    {
        $active  = self::systemctl('is-active');
        $enabled = self::systemctl('is-enabled');
        $pid     = trim((string)self::systemctlShow('MainPID'));
        $since   = trim((string)self::systemctlShow('ActiveEnterTimestamp'));

        $activeOut  = trim((string)($active['stdout']  ?? ''));
        $enabledOut = trim((string)($enabled['stdout'] ?? ''));

        return [
            'active'  => $activeOut  === 'active',
            'enabled' => $enabledOut === 'enabled',
            'pid'     => $pid !== '0' ? $pid : null,
            'since'   => $since !== '' ? $since : null,
            'raw'     => $activeOut !== '' ? $activeOut : 'unknown',
        ];
    }

    public static function start(): array   { return self::systemctl('start'); }
    public static function stop(): array    { return self::systemctl('stop'); }
    public static function restart(): array { return self::systemctl('restart'); }

    /**
     * Lee /license del propio servidor para mostrar edición + cap actual.
     * Devuelve null si el server está caído.
     */
    public static function licenseInfo(): ?array
    {
        $json = self::httpGet('/license', 3);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Devuelve la contraseña admin inicial UNA SOLA VEZ y la borra del disco
     * para que no quede en claro. Si ya se consumió, devuelve null.
     */
    public static function consumeFirstPassword(): ?string
    {
        if (!is_readable(self::FIRST_PWD)) {
            return null;
        }
        $pwd = trim((string)@file_get_contents(self::FIRST_PWD));
        @unlink(self::FIRST_PWD);
        return $pwd !== '' ? $pwd : null;
    }

    // ----- internos ----------------------------------------------------

    private static function systemctl(string $verb): array
    {
        return pm_ApiCli::callSbin(
            'systemctl-wrap',
            [$verb, self::SERVICE],
            pm_ApiCli::RESULT_FULL
        );
    }

    private static function systemctlShow(string $property): string
    {
        $r = pm_ApiCli::callSbin(
            'systemctl-wrap',
            ['show', '-p', $property, '--value', self::SERVICE],
            pm_ApiCli::RESULT_FULL
        );
        return $r['stdout'] ?? '';
    }

    private static function httpGet(string $path, int $timeout): ?string
    {
        $ch = curl_init(self::LOCAL_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FAILONERROR    => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_errno($ch);
        curl_close($ch);
        return $err === 0 ? (string)$body : null;
    }
}
