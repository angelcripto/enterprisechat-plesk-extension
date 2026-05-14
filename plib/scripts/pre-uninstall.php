<?php
/**
 * Plesk SDK hook: ejecutado por el panel antes de retirar la extensión.
 *
 * Por defecto conservamos los datos. Si el admin pulsa "purgar datos" en
 * la UI, la acción correspondiente del controller exporta la variable de
 * entorno KEEP_DATA=0 antes de disparar la desinstalación.
 */

$moduleDir = realpath(__DIR__ . '/../..');
if ($moduleDir === false) {
    fwrite(STDERR, "pre-uninstall: cannot resolve module directory\n");
    exit(1);
}

$hook = $moduleDir . '/hooks/pre-uninstall.sh';
if (!is_file($hook)) {
    fwrite(STDERR, "pre-uninstall: hook not found: $hook\n");
    exit(1);
}

@chmod($hook, 0755);

$keepData = getenv('KEEP_DATA') ?: '1';
$cmd = 'KEEP_DATA=' . escapeshellarg($keepData)
     . ' bash ' . escapeshellarg($hook) . ' 2>&1';

passthru($cmd, $code);
exit((int)$code);
