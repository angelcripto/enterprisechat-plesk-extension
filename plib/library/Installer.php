<?php
/**
 * Disparador desde código PHP (controllers UI) para los wrappers que
 * residen en plib/sbin/. pm_ApiCli::callSbin no resuelve sbin propios de
 * la extensión en Obsidian, así que hacemos passthru con ruta absoluta.
 *
 * Si Plesk demota el proceso PHP a `psaadm`, los wrappers fallarán al
 * intentar systemctl / apt-get. El controller debe interpretar el código
 * de salida no-cero y mostrar el comando manual al admin.
 */
class Modules_Enterprisechat_Installer
{
    public static function sbinDir(): string
    {
        return pm_Context::getPlibDir() . '/sbin';
    }

    public static function runPostInstall(): array
    {
        return self::run(self::sbinDir() . '/install', []);
    }

    public static function runPreUninstall(bool $purgeData = false): array
    {
        $keep = $purgeData ? '0' : '1';
        return self::run(self::sbinDir() . '/uninstall', ['--keep-data=' . $keep]);
    }

    public static function isRoot(): bool
    {
        if (function_exists('posix_geteuid')) {
            return posix_geteuid() === 0;
        }
        return (int)trim((string)shell_exec('id -u')) === 0;
    }

    private static function run(string $script, array $args): array
    {
        if (!is_file($script)) {
            throw new pm_Exception("Wrapper sbin no encontrado: $script");
        }

        $cmd = 'bash ' . escapeshellarg($script);
        foreach ($args as $a) {
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
}
