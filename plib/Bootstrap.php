<?php
/**
 * Hook de bootstrap requerido por Plesk para autoloadear las clases de la
 * extensión (Modules_Enterprisechat_*) y dejar el dispatcher Zend listo
 * antes de que IndexController atienda peticiones.
 *
 * Plesk instancia esta clase al recibir el primer request de la extensión.
 * Heredar de pm_Bootstrap es suficiente: la base ya registra el
 * autoloader, el router y las traducciones (plib/locales).
 */
class Bootstrap extends pm_Bootstrap
{
}
