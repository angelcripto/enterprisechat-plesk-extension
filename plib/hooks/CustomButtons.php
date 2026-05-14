<?php
/**
 * Registra los puntos de entrada visibles de EnterpriseChat en la propia
 * navegación de Plesk. Modelo verificado contra el repo oficial
 * https://github.com/plesk/ext-custom-buttons/blob/master/plib/hooks/CustomButtons.php
 *
 *   - PLACE_ADMIN_NAVIGATION  →  ítem en la barra lateral del admin.
 *     Plesk 17.0+ exige el campo `section` (texto plano: 'hosting',
 *     'general', etc.). Sin section el botón no aparece en la sidebar.
 *   - PLACE_ADMIN_TOOLS_AND_SETTINGS  →  entrada en Herramientas y
 *     configuración → Servicios adicionales.
 *   - PLACE_ADMIN_HOME  →  tile en la home del administrador.
 */
class Modules_Enterprisechat_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        $icon  = pm_Context::getBaseUrl() . 'icon.png';
        $link  = pm_Context::getActionUrl('index');
        $title = 'EnterpriseChat';
        $desc  = 'Chat empresarial autoalojado';

        return [
            [
                'place'       => self::PLACE_ADMIN_NAVIGATION,
                // SECTION_NAV_HOSTING ('hosting') es el grupo "Hosting" de la
                // sidebar — el mismo en el que viven WordPress Toolkit, Git,
                // Node.js Toolkit, etc.
                'section'     => self::SECTION_NAV_HOSTING,
                'order'       => 50,
                'title'       => $title,
                'description' => $desc,
                'icon'        => $icon,
                'link'        => $link,
            ],
            [
                'place'       => self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
                // SECTION_ADMIN_TOOLS_ADDITIONAL_SERVICES ('customButtons').
                // Antes usaba 'toolsButtons' que es una sección de RESELLER,
                // no de ADMIN — por eso no aparecía en Herramientas y
                // configuración del administrador.
                'section'     => self::SECTION_ADMIN_TOOLS_ADDITIONAL_SERVICES,
                'order'       => 50,
                'title'       => $title,
                'description' => 'Configurar el servidor de chat empresarial',
                'icon'        => $icon,
                'link'        => $link,
            ],
            [
                'place'       => self::PLACE_ADMIN_HOME,
                'title'       => $title,
                'description' => $desc,
                'icon'        => $icon,
                'link'        => $link,
            ],
        ];
    }
}
