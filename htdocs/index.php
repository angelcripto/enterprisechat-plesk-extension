<?php
/**
 * Entry point web de la extensión EnterpriseChat para Plesk.
 *
 * Sin este archivo el panel NO genera el botón "Abrir" en
 * Extensions → Mis extensiones, porque Plesk asume que la extensión no
 * expone interfaz. Aquí simplemente arrancamos el bootstrap del módulo y
 * delegamos en el dispatcher Zend que Plesk hereda — el routing por
 * defecto lleva /modules/enterprisechat/ a IndexController::indexAction.
 */

require_once 'pm/Bootstrap.php';
pm_Application::run();
