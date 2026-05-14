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
     * Devuelve la contraseña admin inicial UNA SOLA VEZ y la borra del disco.
     * Plesk demota la UI a psaadm; el archivo .first-admin-password vive en
     * 0600 owner=enterprisechat. Por eso el read+unlink se delega al wrapper
     * sbin/password (consume-first), que sí corre con privilegios.
     */
    public static function consumeFirstPassword(): ?string
    {
        $r = pm_ApiCli::callSbin('password', ['consume-first'], pm_ApiCli::RESULT_FULL);
        if ((int)($r['code'] ?? 1) !== 0) {
            return null;
        }
        $pwd = trim((string)($r['stdout'] ?? ''));
        return $pwd !== '' ? $pwd : null;
    }

    /**
     * Restablece la contraseña del admin invocando el CLI del propio server
     * (--reset-admin-password) vía el wrapper privilegiado. Devuelve el
     * array RESULT_FULL para que el controller propague stderr.
     */
    public static function resetAdminPassword(string $newPassword): array
    {
        return pm_ApiCli::callSbin(
            'password',
            ['reset-admin', $newPassword],
            pm_ApiCli::RESULT_FULL
        );
    }

    // ----- internos ----------------------------------------------------

    /**
     * Invoca el wrapper privilegiado /sbin/systemctl-wrap que Plesk despliega
     * en /usr/local/psa/admin/bin/modules/enterprisechat/systemctl-wrap.
     *
     * Usamos pm_ApiCli::callSbin (no exec): el panel PHP suele tener exec()
     * deshabilitado, y los warnings mezclados rompen el JSON de las acciones
     * AJAX (start/stop/restart). callSbin además pasa por el bridge de
     * privilegios de Plesk, así que el systemctl write se ejecuta como root
     * en lugar de fallar como psaadm.
     */
    private static function run(array $argv): array
    {
        $r = pm_ApiCli::callSbin('systemctl-wrap', $argv, pm_ApiCli::RESULT_FULL);
        $code = (int)($r['code'] ?? 1);
        $out  = (string)($r['stdout'] ?? '');
        $err  = (string)($r['stderr'] ?? '');
        return [
            'ok'   => $code === 0,
            'code' => $code,
            'out'  => $out !== '' ? $out : $err,
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
