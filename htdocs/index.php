<?php
/**
 * Entry point web de la extensión EnterpriseChat para Plesk.
 *
 * Sin este archivo el panel no genera el botón "Abrir" en
 * Extensions → Mis extensiones, porque Plesk asume que la extensión no
 * expone interfaz. Aquí arrancamos el bootstrap del módulo y delegamos
 * en el front controller Zend que Plesk hereda.
 *
 * En Plesk moderno (PHP 8+) pm_Application::run no es estático: hay que
 * instanciar primero. Llamarlo estáticamente lanzaba un fatal:
 *   "Non-static method pm_Application::run() cannot be called statically".
 */

require_once 'pm/Bootstrap.php';
(new pm_Application())->run();
