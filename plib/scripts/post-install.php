<?php
/**
 * Plesk SDK hook: ejecutado por el panel tras desplegar el zip de la
 * extensión en /usr/local/psa/admin/plib/modules/enterprisechat/.
 *
 * Plesk corre ESTE script (PHP), no nuestros hooks/*.sh — la convención
 * SDK es plib/scripts/<phase>.php. El trabajo de verdad sigue en bash
 * (auditable + ejecutable a mano por SSH), así que aquí solo
 * shell-out al hook correspondiente y propagamos el código de salida.
 *
 * Se ejecuta como root cuando el panel está en modo administrador
 * (suficiente para apt-get install + systemctl + escribir en /opt/).
 */

$moduleDir = realpath(__DIR__ . '/../..');
if ($moduleDir === false) {
    fwrite(STDERR, "post-install: cannot resolve module directory\n");
    exit(1);
}

$hook = $moduleDir . '/hooks/post-install.sh';
if (!is_file($hook)) {
    fwrite(STDERR, "post-install: hook not found: $hook\n");
    exit(1);
}

// CRLF en el script bash rompe el shebang. Como salvavidas, normaliza el
// archivo a LF antes de ejecutar (idempotente).
$buf = file_get_contents($hook);
if ($buf !== false && strpos($buf, "\r") !== false) {
    file_put_contents($hook, str_replace("\r", '', $buf));
}
@chmod($hook, 0755);
@chmod($moduleDir . '/hooks/pre-uninstall.sh', 0755);
@chmod($moduleDir . '/hooks/backup.sh',        0755);

passthru('bash ' . escapeshellarg($hook) . ' 2>&1', $code);
exit((int)$code);
