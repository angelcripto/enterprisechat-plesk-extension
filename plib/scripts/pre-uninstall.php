<?php
/**
 * Plesk SDK hook ejecutado antes de retirar la extensión. Plesk lo corre
 * como `psaadm`; las operaciones reales (systemctl disable, apt-get remove,
 * limpieza de snippets nginx) viven en el sbin wrapper `uninstall`.
 *
 * KEEP_DATA viene en el entorno cuando el admin pulsa "purgar también
 * datos" en la UI. Si no se pasa, se conserva /opt/enterprisechat/.
 */

$keepData = getenv('KEEP_DATA');
$keepData = ($keepData === '0') ? '0' : '1';

$result = pm_ApiCli::callSbin(
    'uninstall',
    ['--keep-data=' . $keepData],
    pm_ApiCli::RESULT_FULL
);

if (!empty($result['stdout'])) {
    fwrite(STDOUT, $result['stdout']);
}
if (!empty($result['stderr'])) {
    fwrite(STDERR, $result['stderr']);
}

exit((int)($result['code'] ?? 1));
