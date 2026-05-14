<?php
return [
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
    'lblMaxUsers'           => 'Usuarios concurrentes máx.',
    'btnConfigEdit'         => 'Editar configuración',

    // Tarjeta dominios enlazados
    'bindingsTitle'         => 'Dominios enlazados',
    'bindingsEmpty'         => 'Ningún dominio enlazado todavía.',
    'btnManageDomains'      => 'Gestionar dominios',
    'bindNewTitle'          => 'Enlazar dominio',
    'currentBindingsTitle'  => 'Enlaces actuales',
    'lblDomain'             => 'Dominio',
    'lblLocation'           => 'Ruta (por defecto /)',
    'btnBind'               => 'Enlazar',
    'btnUnbind'             => 'Desenlazar',
    'confirmUnbind'         => '¿Eliminar el reverse proxy de este dominio?',
    'msgBound'              => 'Dominio %d% enlazado correctamente.',
    'msgUnbound'            => 'Dominio %d% desenlazado.',

    // Banner instalación incompleta
    'installPendingTitle'   => 'Instalación pendiente de terminar',
    'installPendingHelp'    => 'Plesk no pudo ejecutar el instalador con privilegios de root. Conéctate al VPS por SSH como root y ejecuta este comando para terminar el despliegue:',
    'installPendingAfter'   => 'Tras ejecutarlo, recarga esta página: aparecerá la contraseña inicial del administrador.',

    // Reveal contraseña inicial
    'firstPasswordTitle'    => 'Contraseña inicial del administrador',
    'firstPasswordHelp'     => 'Usuario "admin". Cámbiala en cuanto inicies sesión.',
    'firstPasswordOnce'     => 'Esta contraseña solo se muestra una vez. Si la pierdes, podrás regenerarla desde la configuración.',

    // Pantalla configuración
    'configTitle'           => 'Configuración del servidor',
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
