<?php
/**
 * Disparador desde código PHP para los wrappers en /sbin/ del zip (que
 * Plesk despliega en /usr/local/psa/admin/bin/modules/<id>/).
 *
 * Importante: según la doc de Plesk, pm_ApiCli::callSbin SOLO se puede
 * usar desde scripts (plib/scripts/*.php), NO desde controllers UI. Por
 * eso esta clase solo se utiliza en CLI; los controllers UI mantienen
 * lecturas vía exec() directo (systemctl is-active, etc.).
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
