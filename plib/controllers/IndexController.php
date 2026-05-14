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
            // El tab "Dominios" queda deshabilitado hasta que sepamos
            // configurar bien un reverse proxy en Plesk (mod_proxy en Apache
            // o nginx delante, sin obligar al admin a tocar el switch
            // global). Mientras tanto el servicio escucha en :5080 y se
            // accede por IP+puerto, o el admin configura el proxy a mano.
            // [
            //     'title'      => pm_Locale::lmsg('tabDomains'),
            //     'action'     => 'domains',
            //     'controller' => 'index',
            // ],
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
        $this->view->bindings      = Modules_Enterprisechat_NginxConfig::listBindings();

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
        // Feature de dominios enlazados pausada (ver init()): el reverse
        // proxy automático en Plesk se mete en el avispero de "nginx
        // delante de Apache" vs "Apache solo" + duplicate location + mod
        // requirements. Hasta tener una implementación robusta dejamos el
        // acceso directo por IP:5080 o configuración manual.
        $this->_helper->redirector('index');
    }

    public function createSubdomainAction() { $this->_helper->redirector('index'); }
    public function bindExistingAction()    { $this->_helper->redirector('index'); }

    public function unbindAction()
    {
        // Feature pausada. Acción sigue existiendo solo para no romper
        // URLs viejas; redirige a la home.
        $this->_helper->redirector('index');
        return;

        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->_helper->json(['ok' => false, 'error' => 'POST required']);
            return;
        }

        $domain = strtolower(trim((string)$this->getRequest()->getParam('domain', '')));
        try {
            Modules_Enterprisechat_NginxConfig::unbind($domain);
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
