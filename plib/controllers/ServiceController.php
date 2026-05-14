<?php
/**
 * Endpoints AJAX (JSON) para los botones Start / Stop / Restart y para el
 * polling de estado de la pantalla principal.
 *
 * Cualquier excepción en el camino se convierte en una respuesta JSON
 * `{ok:false, error: ...}` con HTTP 200 — si dejábamos que la excepción
 * subiera, Plesk respondía con HTTP 500 + página HTML, y fetch().json()
 * en la UI rompía con "Error HTTP 500" en lugar de mostrar el motivo
 * real (callSbin no encuentra el wrapper, permisos, etc.).
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
        $this->safeJson(function () {
            return [
                'service' => Modules_Enterprisechat_EnterpriseChatService::status(),
                'license' => Modules_Enterprisechat_EnterpriseChatService::licenseInfo(),
            ];
        });
    }

    public function startAction()   { $this->verbAction('start');   }
    public function stopAction()    { $this->verbAction('stop');    }
    public function restartAction() { $this->verbAction('restart'); }

    private function verbAction(string $verb): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->_helper->json(['ok' => false, 'error' => 'POST required']);
            return;
        }

        $this->safeJson(function () use ($verb) {
            $r = Modules_Enterprisechat_EnterpriseChatService::{$verb}();
            return [
                'ok'    => (int)($r['code'] ?? 0) === 0,
                'code'  => $r['code'] ?? null,
                'out'   => (string)($r['stdout'] ?? ''),
                'err'   => (string)($r['stderr'] ?? ''),
            ];
        });
    }

    private function safeJson(callable $fn): void
    {
        try {
            $this->_helper->json($fn());
        } catch (Throwable $e) {
            $this->_helper->json([
                'ok'    => false,
                'error' => $e->getMessage(),
                'trace' => basename($e->getFile()) . ':' . $e->getLine(),
            ]);
        }
    }
}
