<?php
/**
 * Endpoints AJAX (JSON) para los botones start / stop / restart y para el
 * polling de estado de la pantalla principal.
 */
class ServiceController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getHelper('layout')->disableLayout();
    }

    public function statusAction()
    {
        $this->_helper->json([
            'service' => Modules_Enterprisechat_EnterpriseChatService::status(),
            'license' => Modules_Enterprisechat_EnterpriseChatService::licenseInfo(),
        ]);
    }

    public function startAction()
    {
        $this->guardPost();
        $r = Modules_Enterprisechat_EnterpriseChatService::start();
        $this->_helper->json($this->result($r));
    }

    public function stopAction()
    {
        $this->guardPost();
        $r = Modules_Enterprisechat_EnterpriseChatService::stop();
        $this->_helper->json($this->result($r));
    }

    public function restartAction()
    {
        $this->guardPost();
        $r = Modules_Enterprisechat_EnterpriseChatService::restart();
        $this->_helper->json($this->result($r));
    }

    private function guardPost(): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->_helper->json(['ok' => false, 'error' => 'POST required']);
        }
    }

    private function result(array $r): array
    {
        return [
            'ok'    => (int)($r['code'] ?? 0) === 0,
            'code'  => $r['code']   ?? null,
            'out'   => $r['stdout'] ?? '',
            'err'   => $r['stderr'] ?? '',
        ];
    }
}
