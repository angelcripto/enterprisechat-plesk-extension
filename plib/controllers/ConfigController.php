<?php
/**
 * Edición de configuración del servidor: vida útil del JWT, license key Pro
 * y herramienta de rotación de la SigningKey.
 *
 * NO permite editar la SigningKey a mano (sería poner secretos en logs de
 * Plesk). Solo rotación con valor generado por openssl.
 */
class ConfigController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->view->pageTitle = pm_Locale::lmsg('pageTitle');
    }

    public function editAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $this->applyPost($request->getParams());
                $this->_status->addMessage('info', pm_Locale::lmsg('msgConfigSaved'));
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }
            $this->_helper->redirector('edit');
            return;
        }

        $this->view->lifetimeMinutes = (int)Modules_Enterprisechat_AppSettings::get(
            'EnterpriseChat:Jwt:AccessTokenLifetimeMinutes', 60
        );
        $this->view->hasLicenseKey = (bool)Modules_Enterprisechat_AppSettings::get(
            'EnterpriseChat:Licensing:LicenseKey', false
        );
    }

    private function applyPost(array $p): void
    {
        if (isset($p['lifetimeMinutes'])) {
            $minutes = (int)$p['lifetimeMinutes'];
            if ($minutes < 5 || $minutes > 1440) {
                throw new pm_Exception(pm_Locale::lmsg('errLifetimeRange'));
            }
            Modules_Enterprisechat_AppSettings::set(
                'EnterpriseChat:Jwt:AccessTokenLifetimeMinutes', $minutes
            );
        }

        if (!empty($p['licenseKey'])) {
            $key = trim((string)$p['licenseKey']);
            Modules_Enterprisechat_AppSettings::set(
                'EnterpriseChat:Licensing:LicenseKey', $key
            );
            // Rearranca para que el server lea el nuevo settings.
            Modules_Enterprisechat_EnterpriseChatService::restart();
        }

        if (!empty($p['rotateSigningKey'])) {
            $new = base64_encode(random_bytes(48));
            Modules_Enterprisechat_AppSettings::set(
                'EnterpriseChat:Jwt:SigningKey', $new
            );
            Modules_Enterprisechat_EnterpriseChatService::restart();
        }
    }
}
