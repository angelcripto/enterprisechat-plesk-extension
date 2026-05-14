<?php
/**
 * Plesk SDK hook: ejecutado por el panel tras desplegar el zip.
 *
 * Plesk Obsidian puede ejecutar este script como root (instalación CLI
 * disparada desde SSH como root) o como `psaadm` (instalación desde la
 * UI del panel). Para el segundo caso no podemos llamar apt-get ni
 * systemctl: solo mostramos al admin el comando exacto a ejecutar por
 * SSH y devolvemos 0 para que la extensión quede registrada y se pueda
 * finalizar a mano.
 */

$sbinDir = realpath(__DIR__ . '/../sbin');
if ($sbinDir === false) {
    fwrite(STDERR, "post-install: sbin directory missing\n");
    exit(1);
}
$installScript = $sbinDir . '/install';

@chmod($installScript, 0755);
@chmod($sbinDir . '/uninstall', 0755);
@chmod($sbinDir . '/systemctl-wrap', 0755);

$uid = function_exists('posix_geteuid') ? posix_geteuid() : (int)trim((string)shell_exec('id -u'));

if ($uid !== 0) {
    fwrite(STDOUT,
        "EnterpriseChat se ha cargado en Plesk, pero el panel ejecutó este\n" .
        "hook como un usuario sin privilegios (UID $uid). Para terminar la\n" .
        "instalación (apt-get del paquete .deb, secretos y arranque del\n" .
        "servicio), conéctate al VPS por SSH como root y ejecuta:\n" .
        "\n" .
        "    sudo bash $installScript\n" .
        "\n" .
        "Después, refresca la página de la extensión en el panel para que\n" .
        "aparezca el botón 'Abrir' y la contraseña inicial.\n"
    );
    exit(0);
}

// Somos root: ejecutamos directamente el wrapper.
passthru('bash ' . escapeshellarg($installScript) . ' 2>&1', $code);
exit((int)$code);
