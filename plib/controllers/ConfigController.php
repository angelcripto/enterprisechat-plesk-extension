<?php
/**
 * Pestaña Configuración. Por decisión de producto el panel solo expone una
 * cosa al admin del VPS: cambiar la contraseña del usuario "admin" del
 * chat. El resto de ajustes (JWT, licensing, etc.) se gestionan dentro del
 * propio servidor o en su appsettings.Production.json.
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
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }
            $this->_helper->redirector('edit');
            return;
        }
    }

    private function applyPost(array $p): void
    {
        $action = (string)($p['do'] ?? '');
        if ($action !== 'reset-admin-password') {
            return;
        }

        $new     = (string)($p['newPassword']     ?? '');
        $confirm = (string)($p['confirmPassword'] ?? '');

        if ($new === '' || $new !== $confirm) {
            throw new pm_Exception(pm_Locale::lmsg('errPasswordMismatch'));
        }
        if (strlen($new) < 8) {
            throw new pm_Exception(pm_Locale::lmsg('errPasswordTooShort'));
        }

        $r = Modules_Enterprisechat_EnterpriseChatService::resetAdminPassword($new);

        if ((int)($r['code'] ?? 1) !== 0) {
            $err = trim((string)($r['stderr'] ?? $r['stdout'] ?? ''));
            throw new pm_Exception(
                pm_Locale::lmsg('errResetFailed') . ' ' . $err
            );
        }

        $this->_status->addMessage('info', pm_Locale::lmsg('msgPasswordReset'));
    }
}
