<?php
/**
 * Wrapper sobre `systemctl` + endpoints HTTP locales del servidor
 * EnterpriseChat. Lecturas (is-active, is-enabled, show) funcionan sin
 * privilegios; las acciones (start/stop/restart) requieren root y por eso
 * la UI Plesk solo las habilita cuando el panel corre privilegiado, o el
 * admin tiene que ejecutarlas por SSH.
 */
class Modules_Enterprisechat_EnterpriseChatService
{
    const SERVICE      = 'enterprisechat.service';
    const INSTALL_DIR  = '/opt/enterprisechat';
    const LOCAL_URL    = 'http://127.0.0.1:5080';
    const FIRST_PWD    = '/opt/enterprisechat/.first-admin-password';

    public static function status(): array
    {
        $active  = self::run(['is-active',  self::SERVICE]);
        $enabled = self::run(['is-enabled', self::SERVICE]);

        $pidOut   = self::run(['show', '-p', 'MainPID',              '--value', self::SERVICE]);
        $sinceOut = self::run(['show', '-p', 'ActiveEnterTimestamp', '--value', self::SERVICE]);

        $pid   = trim($pidOut['out']);
        $since = trim($sinceOut['out']);

        return [
            'active'  => trim($active['out'])  === 'active',
            'enabled' => trim($enabled['out']) === 'enabled',
            'pid'     => ($pid !== '' && $pid !== '0') ? $pid : null,
            'since'   => $since !== '' ? $since : null,
            'raw'     => trim($active['out']) !== '' ? trim($active['out']) : 'unknown',
        ];
    }

    public static function start(): array   { return self::run(['start',   self::SERVICE]); }
    public static function stop(): array    { return self::run(['stop',    self::SERVICE]); }
    public static function restart(): array { return self::run(['restart', self::SERVICE]); }

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

    private static function run(array $argv): array
    {
        $cmd = 'systemctl';
        foreach ($argv as $a) {
            $cmd .= ' ' . escapeshellarg((string)$a);
        }
        $cmd .= ' 2>&1';

        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        return [
            'ok'   => $code === 0,
            'code' => $code,
            'out'  => implode("\n", $out),
        ];
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
