<?php
/**
 * Registra los puntos de entrada visibles de EnterpriseChat en la propia
 * navegación de Plesk para que la extensión no quede sepultada en
 * "Extensions → Mis extensiones".
 *
 * Tras la doc oficial (Add Custom Buttons, 73624):
 *   - PLACE_ADMIN_NAVIGATION  →  ítem en la barra lateral del admin.
 *   - PLACE_ADMIN_TOOLS_AND_SETTINGS  →  entrada en Herramientas y
 *     configuración → Servicios adicionales.
 *
 * El icon se sirve desde htdocs/icon.png (Plesk resuelve URLs relativas a
 * pm_Context::getBaseUrl()). El _meta/icons/ es solo para el catálogo.
 */
class Modules_Enterprisechat_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        $icon = pm_Context::getBaseUrl() . 'icon.png';
        $link = pm_Context::getBaseUrl() . 'index.php/index/index';

        return [
            [
                'place'       => self::PLACE_ADMIN_NAVIGATION,
                'title'       => 'EnterpriseChat',
                'description' => 'Chat empresarial autoalojado',
                'icon'        => $icon,
                'link'        => $link,
            ],
            [
                'place'       => self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
                'title'       => 'EnterpriseChat',
                'description' => 'Configurar el servidor de chat empresarial',
                'icon'        => $icon,
                'link'        => $link,
            ],
        ];
    }
}
