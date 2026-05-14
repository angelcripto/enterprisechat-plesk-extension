<?php
/**
 * Plesk SDK hook ejecutado en contexto CLI antes de retirar la extensión.
 * Llama al wrapper /sbin/uninstall (deploya en /admin/bin/modules/<id>/)
 * vía pm_ApiCli::callSbin con privilegios elevados.
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
