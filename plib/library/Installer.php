<?php
/**
 * Disparador desde código PHP (controllers, hooks UI) para los wrappers
 * sbin que Plesk despliega con setuid root. Centraliza el contrato con el
 * panel: el resto de la extensión nunca llama a `systemctl` ni a `apt-get`
 * directamente — siempre pasa por aquí.
 *
 * Wrappers correspondientes en plib/sbin/:
 *   - install         lógica de post-install (apt-get + secretos + start)
 *   - uninstall       parada + remove (--keep-data=0|1)
 *   - systemctl-wrap  control de enterprisechat.service
 */
class Modules_Enterprisechat_Installer
{
    public static function runPostInstall(): array
    {
        return pm_ApiCli::callSbin('install', [], pm_ApiCli::RESULT_FULL);
    }

    public static function runPreUninstall(bool $purgeData = false): array
    {
        $keep = $purgeData ? '0' : '1';
        return pm_ApiCli::callSbin(
            'uninstall',
            ['--keep-data=' . $keep],
            pm_ApiCli::RESULT_FULL
        );
    }
}
