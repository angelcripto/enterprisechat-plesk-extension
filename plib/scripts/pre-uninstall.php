<?php
/**
 * Pre-uninstall hook. Como el post-install, intenta ejecutarse directamente
 * cuando Plesk nos lanza con root; en caso contrario, deja al admin la
 * instrucción exacta y devuelve 0 para no bloquear la desinstalación lógica
 * (el listado de Plesk).
 */

$sbinDir = realpath(__DIR__ . '/../sbin');
if ($sbinDir === false) {
    // El módulo ya pudo limpiarse parcialmente; no bloqueamos.
    exit(0);
}
$script = $sbinDir . '/uninstall';
@chmod($script, 0755);

$keepData = getenv('KEEP_DATA');
$keepData = ($keepData === '0') ? '0' : '1';

$uid = function_exists('posix_geteuid') ? posix_geteuid() : (int)trim((string)shell_exec('id -u'));

if ($uid !== 0) {
    fwrite(STDOUT,
        "Para terminar de desinstalar EnterpriseChat (parar el servicio,\n" .
        "limpiar la configuración nginx, retirar el paquete .deb), ejecuta\n" .
        "como root en el VPS:\n" .
        "\n" .
        "    sudo bash $script --keep-data=$keepData\n"
    );
    exit(0);
}

passthru('bash ' . escapeshellarg($script) . ' --keep-data=' . escapeshellarg($keepData) . ' 2>&1', $code);
exit((int)$code);
