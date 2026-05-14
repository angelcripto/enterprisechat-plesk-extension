<?php
/**
 * Pantalla principal: resumen de estado del servicio, contraseña admin
 * inicial (one-shot), licencia activa y dominios bindeados.
 */
class IndexController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->view->pageTitle = pm_Locale::lmsg('pageTitle');

        $this->view->tabs = [
            [
                'title'  => pm_Locale::lmsg('tabStatus'),
                'action' => 'index',
            ],
            [
                'title'  => pm_Locale::lmsg('tabDomains'),
                'action' => 'domains',
                'controller' => 'index',
            ],
            [
                'title'  => pm_Locale::lmsg('tabConfig'),
                'controller' => 'config',
                'action' => 'edit',
            ],
        ];
    }

    public function indexAction()
    {
        $this->view->serviceStatus = Modules_Enterprisechat_EnterpriseChatService::status();
        $this->view->licenseStatus = Modules_Enterprisechat_EnterpriseChatService::licenseInfo();
        $this->view->firstPassword = Modules_Enterprisechat_EnterpriseChatService::consumeFirstPassword();
        $this->view->bindings      = Modules_Enterprisechat_NginxConfig::listBindings();
    }

    public function domainsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $domain = trim((string)$request->getParam('domain', ''));
            $path   = trim((string)$request->getParam('location', '/'));
            $action = (string)$request->getParam('do', 'bind');

            try {
                if ($action === 'unbind') {
                    Modules_Enterprisechat_NginxConfig::unbind($domain);
                    $this->_status->addMessage('info', pm_Locale::lmsg('msgUnbound', ['d' => $domain]));
                } else {
                    Modules_Enterprisechat_NginxConfig::bind($domain, $path);
                    $this->_status->addMessage('info', pm_Locale::lmsg('msgBound', ['d' => $domain]));
                }
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }

            $this->_helper->redirector('domains');
            return;
        }

        $this->view->bindings = Modules_Enterprisechat_NginxConfig::listBindings();
        $this->view->domains  = $this->listPleskDomains();
    }

    private function listPleskDomains(): array
    {
        $domains = [];
        foreach (pm_Domain::getAllDomains(true) as $d) {
            $domains[] = $d->getName();
        }
        sort($domains);
        return $domains;
    }
}
