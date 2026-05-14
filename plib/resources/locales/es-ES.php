<?php
// Plesk SDK acepta los dos contratos:
//   - file_get_contents + eval con $messages en alcance global
//   - include con return de array
// Por eso asignamos a $messages y lo retornamos a la vez.
$messages = [
    // Cabecera y tabs
    'pageTitle'             => 'EnterpriseChat',
    'tabStatus'             => 'Estado',
    'tabDomains'            => 'Dominios',
    'tabConfig'             => 'Configuración',

    // Tarjeta servicio
    'serviceTitle'          => 'Servicio',
    'lblState'              => 'Estado',
    'lblEnabled'            => 'Arranque al boot',
    'lblSince'              => 'Activo desde',

    // Tarjeta licencia
    'licenseTitle'          => 'Licencia',
    'licenseUnavailable'    => 'El servidor no responde. Comprueba el estado del servicio.',
    'lblEdition'            => 'Edición',
    'lblMaxUsers'           => 'Max users',
    'accessTitle'           => 'Acceso al chat',
    'accessHelp'            => 'El servidor escucha en el puerto 5080 de la máquina. Por defecto solo es accesible desde la propia red.',
    'accessUrlHint'         => 'URL del chat:',
    'accessFirewallHint'    => 'Para exponerlo a Internet abre el puerto 5080 en el firewall, o configura manualmente un reverse proxy en un dominio Plesk con TLS.',
    'lblExpiresAt'          => 'Caduca',
    'lblLicensedTo'         => 'Licenciado a',
    'btnConfigEdit'         => 'Editar configuración',
    'btnRefreshLicense'     => 'Refrescar licencia',
    'btnStart'              => 'Iniciar',
    'btnStop'               => 'Parar',
    'btnRestart'            => 'Reiniciar',
    'msgLicenseRefreshed'   => 'Licencia recargada desde el servidor.',
    'btnCreateSubdomain'    => 'Crear subdominio nuevo',
    'btnBindExisting'       => 'Enlazar dominio existente',
    'hintLocationSubpath'   => 'Debe terminar con "/" (por ejemplo /chat/). Si vas a usar la raíz del dominio, mejor crea un subdominio dedicado.',
    'errSubdomainCreate'    => 'No se pudo crear el subdominio:',

    // Tarjeta dominios enlazados
    'bindingsTitle'         => 'Dominios enlazados',
    'bindingsEmpty'         => 'Ningún dominio enlazado todavía.',
    'btnManageDomains'      => 'Gestionar dominios',
    'btnBack'               => 'Volver al estado',
    'createSubdomainTitle'  => 'Crear un subdominio nuevo',
    'createSubdomainHelp'   => 'Recomendado: crea un subdominio dedicado (por ejemplo chat.tudominio.com) para no convivir con el sitio principal.',
    'lblSubdomainPrefix'    => 'Prefijo del subdominio',
    'lblParentDomain'       => 'Dominio padre',
    'btnCreateAndBind'      => 'Crear y enlazar',
    'bindExistingTitle'     => 'Enlazar sobre un dominio existente',
    'bindExistingHelp'      => 'Monta el chat en una ruta del dominio elegido (por defecto /chat/). Útil si no quieres crear un subdominio.',
    'bindNewTitle'          => 'Enlazar dominio',
    'currentBindingsTitle'  => 'Enlaces actuales',
    'lblDomain'             => 'Dominio',
    'lblLocation'           => 'Ruta (por defecto /chat/)',
    'btnBind'               => 'Enlazar',
    'btnUnbind'             => 'Desenlazar',
    'confirmUnbind'         => '¿Eliminar el reverse proxy de este dominio?',
    'msgBound'              => 'Dominio %%d%% enlazado correctamente.',
    'msgUnbound'            => 'Dominio %%d%% desenlazado.',

    // Banner instalación incompleta
    'installPendingTitle'   => 'Instalación pendiente de terminar',
    'installPendingHelp'    => 'Plesk no pudo ejecutar el instalador con privilegios de root. Conéctate al VPS por SSH como root y ejecuta este comando para terminar el despliegue:',
    'installPendingAfter'   => 'Tras ejecutarlo, recarga esta página: aparecerá la contraseña inicial del administrador.',

    // Reveal contraseña inicial
    'firstPasswordTitle'    => 'Contraseña inicial del administrador',
    'firstPasswordHelp'     => 'Usuario "admin". Cámbiala en cuanto inicies sesión.',
    'firstPasswordOnce'     => 'Esta contraseña solo se muestra una vez. Si la pierdes, podrás regenerarla desde la configuración.',

    // Pantalla configuración
    'configTitle'           => 'Configuración',
    'passwordResetTitle'    => 'Cambiar contraseña del administrador',
    'passwordResetHelp'     => 'Restablece la contraseña del usuario "admin" del chat. El cambio toma efecto inmediato; las sesiones existentes seguirán siendo válidas hasta que expire el JWT.',
    'lblNewPassword'        => 'Nueva contraseña',
    'lblConfirmPassword'    => 'Confirmar contraseña',
    'btnResetPassword'      => 'Restablecer contraseña',
    'msgPasswordReset'      => 'Contraseña del administrador restablecida correctamente.',
    'errPasswordMismatch'   => 'Las dos contraseñas no coinciden.',
    'errPasswordTooShort'   => 'La contraseña debe tener al menos 8 caracteres.',
    'errResetFailed'        => 'No se pudo restablecer la contraseña:',

    'configTitleLegacy'     => 'Configuración del servidor',
    'sectionJwt'            => 'Sesión / JWT',
    'lblLifetime'           => 'Duración del token (minutos)',
    'hintLifetime'          => 'Entre 5 y 1440. Por defecto 60.',
    'lblRotateKey'          => 'Rotar clave de firma JWT',
    'hintRotateKey'         => 'Invalidará todas las sesiones activas y reiniciará el servicio.',

    'sectionLicense'        => 'Licencia',
    'licenseKeyStored'      => 'Hay una clave Pro almacenada (no se muestra por seguridad).',
    'lblLicenseKey'         => 'Clave de licencia',

    'btnSave'               => 'Guardar',
    'btnCancel'             => 'Cancelar',
    'msgConfigSaved'        => 'Configuración guardada. El servicio se reinicia si era necesario.',
    'errLifetimeRange'      => 'La duración debe estar entre 5 y 1440 minutos.',
];
