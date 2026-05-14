<?php
/**
 * Plesk SDK hook: ejecutado en contexto CLI tras desplegar la extensión.
 *
 * Según la documentación oficial de Plesk Obsidian
 * (https://docs.plesk.com/.../commandline-interface.71088/), los scripts
 * colocados en /sbin/ del zip se despliegan en
 *   /usr/local/psa/admin/bin/modules/<id>/
 * con privilegios elevados, y se invocan desde scripts CLI (como este)
 * vía pm_ApiCli::callSbin('<name>').
 */

$result = pm_ApiCli::callSbin('install', [], pm_ApiCli::RESULT_FULL);

if (!empty($result['stdout'])) {
    fwrite(STDOUT, $result['stdout']);
}
if (!empty($result['stderr'])) {
    fwrite(STDERR, $result['stderr']);
}

exit((int)($result['code'] ?? 1));
