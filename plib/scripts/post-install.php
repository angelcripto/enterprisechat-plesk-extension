<?php
/**
 * Plesk SDK hook: ejecutado por el panel tras desplegar el zip de la
 * extensión. Plesk corre este script como `psaadm` (no root) por política
 * de seguridad. Para acciones que requieren privilegios (apt-get install,
 * systemctl, escritura en /opt/...) usamos un sbin wrapper que Plesk
 * despliega con setuid root automáticamente desde plib/sbin/install.
 */

$result = pm_ApiCli::callSbin('install', [], pm_ApiCli::RESULT_FULL);

if (!empty($result['stdout'])) {
    fwrite(STDOUT, $result['stdout']);
}
if (!empty($result['stderr'])) {
    fwrite(STDERR, $result['stderr']);
}

exit((int)($result['code'] ?? 1));
