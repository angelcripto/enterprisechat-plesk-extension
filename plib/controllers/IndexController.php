<?php
/**
 * Pantalla principal de la extensión EnterpriseChat dentro de Plesk.
 *
 * - indexAction              ->  resumen estado servicio + tarjeta licencia +
 *                                lista de dominios enlazados.
 * - domainsAction            ->  overview de dominios + 2 sub-actions:
 *                                * createSubdomainAction
 *                                * bindExistingAction
 * - unbindAction             ->  endpoint POST para retirar un binding.
 * - refreshLicenseAction     ->  fuerza relectura de /license y vuelve a index.
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
                'title'      => pm_Locale::lmsg('tabDomains'),
                'action'     => 'domains',
                'controller' => 'index',
            ],
            [
                'title'      => pm_Locale::lmsg('tabConfig'),
                'controller' => 'config',
                'action'     => 'edit',
            ],
        ];
    }

    // -------------------------------------------------------------------
    // Status (home tab)
    // -------------------------------------------------------------------

    public function indexAction()
    {
        $status  = Modules_Enterprisechat_EnterpriseChatService::status();
        $license = Modules_Enterprisechat_EnterpriseChatService::licenseInfo();

        $this->view->serviceStatus = $status;
        $this->view->licenseStatus = self::normalizeLicense($license);
        $this->view->firstPassword = Modules_Enterprisechat_EnterpriseChatService::consumeFirstPassword();
        $this->view->bindings      = Modules_Enterprisechat_ApacheConfig::listBindings();

        // Banner instalación pendiente: el servicio está inactivo Y el binario
        // ni siquiera está en disco => Plesk demotó post-install.php a psaadm
        // y el .deb nunca llegó a apt-get.
        $deployed = is_dir('/opt/enterprisechat');
        $this->view->installPending = !$status['active'] && !$deployed;
        $this->view->installScript  = '/usr/local/psa/admin/bin/modules/enterprisechat/install';
    }

    public function refreshLicenseAction()
    {
        // licenseInfo() ya hace un HTTP GET fresco a /license del server, no hay
        // caché PHP. Esta acción solo fuerza la recarga del índice para que el
        // usuario vea un "tick" de feedback.
        $this->_status->addMessage('info', pm_Locale::lmsg('msgLicenseRefreshed'));
        $this->_helper->redirector('index');
    }

    // -------------------------------------------------------------------
    // Domains overview + sub-actions
    // -------------------------------------------------------------------

    public function domainsAction()
    {
        $this->view->bindings = Modules_Enterprisechat_ApacheConfig::listBindings();
    }

    public function createSubdomainAction()
    {
        $form = $this->buildSubdomainForm();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            try {
                $prefix = strtolower((string)$form->getValue('prefix'));
                $parent = strtolower((string)$form->getValue('parent'));

                $r = pm_ApiCli::callSbin(
                    'subdomain-create',
                    [$prefix, $parent],
                    pm_ApiCli::RESULT_FULL
                );
                if ((int)($r['code'] ?? 1) !== 0) {
                    throw new pm_Exception(
                        pm_Locale::lmsg('errSubdomainCreate') . ' ' .
                        trim((string)($r['stderr'] ?? $r['stdout'] ?? ''))
                    );
                }

                $fqdn = trim((string)($r['stdout'] ?? "$prefix.$parent"));
                Modules_Enterprisechat_ApacheConfig::bind($fqdn, '/');

                $this->_status->addMessage(
                    'info',
                    pm_Locale::lmsg('msgBound', ['d' => $fqdn])
                );
                $this->_helper->json([
                    'redirect' => pm_Context::getActionUrl('index', 'domains'),
                ]);
                return;
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }
        }

        $this->view->form = $form;
    }

    public function bindExistingAction()
    {
        $form = $this->buildBindExistingForm();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            try {
                $domain   = strtolower((string)$form->getValue('domain'));
                $location = (string)$form->getValue('location');
                Modules_Enterprisechat_ApacheConfig::bind($domain, $location);

                $this->_status->addMessage(
                    'info',
                    pm_Locale::lmsg('msgBound', ['d' => $domain])
                );
                $this->_helper->json([
                    'redirect' => pm_Context::getActionUrl('index', 'domains'),
                ]);
                return;
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }
        }

        $this->view->form = $form;
    }

    public function unbindAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->_helper->json(['ok' => false, 'error' => 'POST required']);
            return;
        }

        $domain = strtolower(trim((string)$this->getRequest()->getParam('domain', '')));
        try {
            Modules_Enterprisechat_ApacheConfig::unbind($domain);
            $this->_status->addMessage(
                'info',
                pm_Locale::lmsg('msgUnbound', ['d' => $domain])
            );
        } catch (Exception $e) {
            $this->_status->addMessage('error', $e->getMessage());
        }
        $this->_helper->redirector('domains');
    }

    // -------------------------------------------------------------------
    // Form builders
    // -------------------------------------------------------------------

    private function buildSubdomainForm(): pm_Form_Simple
    {
        $form = new pm_Form_Simple();

        $form->addElement('description', 'help', [
            'description' => pm_Locale::lmsg('createSubdomainHelp'),
            'escape'      => false,
            'ignore'      => true,
        ]);

        $form->addElement('text', 'prefix', [
            'label'      => pm_Locale::lmsg('lblSubdomainPrefix'),
            'required'   => true,
            'value'      => 'chat',
            'validators' => [
                ['Regex', false, ['/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/']],
            ],
        ]);

        $form->addElement('select', 'parent', [
            'label'       => pm_Locale::lmsg('lblParentDomain'),
            'required'    => true,
            'multiOptions' => self::domainOptions(),
        ]);

        $form->addControlButtons([
            'sendTitle'  => pm_Locale::lmsg('btnCreateAndBind'),
            'cancelLink' => pm_Context::getActionUrl('index', 'domains'),
        ]);

        return $form;
    }

    private function buildBindExistingForm(): pm_Form_Simple
    {
        $form = new pm_Form_Simple();

        $form->addElement('description', 'help', [
            'description' => pm_Locale::lmsg('bindExistingHelp'),
            'escape'      => false,
            'ignore'      => true,
        ]);

        $form->addElement('select', 'domain', [
            'label'        => pm_Locale::lmsg('lblDomain'),
            'required'     => true,
            'multiOptions' => array_merge(
                ['' => '— ' . pm_Locale::lmsg('lblDomain') . ' —'],
                self::domainOptions()
            ),
        ]);

        $form->addElement('text', 'location', [
            'label'       => pm_Locale::lmsg('lblLocation'),
            'required'    => true,
            'value'       => '/chat/',
            'description' => pm_Locale::lmsg('hintLocationSubpath'),
            'validators'  => [
                ['Regex', false, ['~^/([a-zA-Z0-9_\-]+/)*$~']],
            ],
        ]);

        $form->addControlButtons([
            'sendTitle'  => pm_Locale::lmsg('btnBind'),
            'cancelLink' => pm_Context::getActionUrl('index', 'domains'),
        ]);

        return $form;
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    private static function domainOptions(): array
    {
        $opts = [];
        foreach (pm_Domain::getAllDomains(true) as $d) {
            $name = $d->getName();
            $opts[$name] = $name;
        }
        ksort($opts);
        return $opts;
    }

    /**
     * El server emite el enum LicenseEdition como entero (0=Free, 1=Pro)
     * porque no tiene configurado el StringEnumConverter. Mapeamos a string
     * en PHP para que el panel muestre "Free" / "Pro" en vez de "0" / "1".
     */
    private static function normalizeLicense(?array $license): ?array
    {
        if ($license === null) {
            return null;
        }
        $edition = $license['edition'] ?? null;
        $map = [0 => 'Free', 1 => 'Pro'];
        $license['edition'] = is_string($edition)
            ? $edition
            : ($map[(int)$edition] ?? 'Unknown');
        return $license;
    }
}
