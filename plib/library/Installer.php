<?php
/**
 * Punto único para ejecutar los hooks shell desde código PHP. Se llama desde
 * los handlers Plesk pm_Hook_Install / pm_Hook_Uninstall y desde los botones
 * de "reparar instalación" / "purgar datos" en la UI.
 *
 * Mantenemos la lógica real en bash (hooks/*.sh) para que sea trivial
 * auditarla y, llegado el caso, ejecutarla a mano por SSH.
 */
class Modules_Enterprisechat_Installer
{
    public static function moduleDir(): string
    {
        return pm_Context::getPlibDir() . '/..';
    }

    public static function runPostInstall(): array
    {
        return self::runHook('hooks/post-install.sh', []);
    }

    public static function runPreUninstall(bool $purgeData = false): array
    {
        $env = $purgeData ? ['KEEP_DATA' => '0'] : ['KEEP_DATA' => '1'];
        return self::runHook('hooks/pre-uninstall.sh', [], $env);
    }

    public static function runBackup(string $dest): array
    {
        return self::runHook('hooks/backup.sh', [$dest]);
    }

    private static function runHook(string $relPath, array $args, array $env = []): array
    {
        $script = self::moduleDir() . '/' . $relPath;
        if (!is_file($script)) {
            throw new pm_Exception("Hook no encontrado: $relPath");
        }

        $cmd = ['bash', $script];
        foreach ($args as $a) {
            $cmd[] = (string)$a;
        }

        $envPairs = [];
        foreach ($env as $k => $v) {
            $envPairs[] = $k . '=' . $v;
        }

        return pm_ApiCli::callSbin(
            'systemctl_wrap',
            array_merge(['env'], $envPairs, $cmd),
            pm_ApiCli::RESULT_FULL
        );
    }
}
