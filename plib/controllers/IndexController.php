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

        // Si el servicio sigue inactive y /opt/enterprisechat no existe, es que
        // callSbin('install') falló durante post-install.php y el .deb nunca
        // llegó a apt-get. Mostramos al admin el comando manual para terminar.
        // Plesk despliega /sbin/* del zip a /admin/bin/modules/<id>/.
        $deployed = is_dir('/opt/enterprisechat');
        $this->view->installPending = !$this->view->serviceStatus['active'] && !$deployed;
        $this->view->installScript  = '/usr/local/psa/admin/bin/modules/enterprisechat/install';
    }

    public function domainsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $action = (string)$request->getParam('do', 'bind');

            try {
                switch ($action) {
                    case 'unbind':
                        $domain = trim((string)$request->getParam('domain', ''));
                        Modules_Enterprisechat_NginxConfig::unbind($domain);
                        $this->_status->addMessage('info', pm_Locale::lmsg('msgUnbound', ['d' => $domain]));
                        break;

                    case 'subdomain':
                        // Crea el subdominio en Plesk vía sbin wrapper y, si
                        // todo va bien, lo enlaza en la raíz "/".
                        $prefix = strtolower(trim((string)$request->getParam('prefix', 'chat')));
                        $parent = strtolower(trim((string)$request->getParam('parent', '')));
                        $r = pm_ApiCli::callSbin(
                            'subdomain-create',
                            [$prefix, $parent],
                            pm_ApiCli::RESULT_FULL
                        );
                        if ((int)($r['code'] ?? 1) !== 0) {
                            throw new pm_Exception(
                                'subdomain-create falló: ' . trim((string)($r['stderr'] ?? $r['stdout'] ?? ''))
                            );
                        }
                        $fqdn = trim((string)($r['stdout'] ?? "$prefix.$parent"));
                        Modules_Enterprisechat_NginxConfig::bind($fqdn, '/');
                        $this->_status->addMessage('info', pm_Locale::lmsg('msgBound', ['d' => $fqdn]));
                        break;

                    case 'bind':
                    default:
                        $domain = trim((string)$request->getParam('domain', ''));
                        $path   = trim((string)$request->getParam('location', '/chat/'));
                        Modules_Enterprisechat_NginxConfig::bind($domain, $path);
                        $this->_status->addMessage('info', pm_Locale::lmsg('msgBound', ['d' => $domain]));
                        break;
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
