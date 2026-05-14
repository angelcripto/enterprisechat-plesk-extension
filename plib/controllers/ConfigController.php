<?php
/**
 * Pestaña Configuración. Por decisión de producto el panel solo expone una
 * cosa al admin del VPS: cambiar la contraseña del usuario "admin" del
 * chat. El resto de ajustes se gestionan dentro del propio servidor.
 *
 * Usa pm_Form_Simple en vez de phtml suelto para integrarse con el estilo
 * de formularios de Plesk (labels, validación, botones de control).
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
        $form = $this->buildPasswordForm();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $new     = (string)$form->getValue('newPassword');
            $confirm = (string)$form->getValue('confirmPassword');

            if ($new !== $confirm) {
                $this->_status->addMessage('error', pm_Locale::lmsg('errPasswordMismatch'));
            } else {
                $r = Modules_Enterprisechat_EnterpriseChatService::resetAdminPassword($new);
                if ((int)($r['code'] ?? 1) !== 0) {
                    $err = trim((string)($r['stderr'] ?? $r['stdout'] ?? ''));
                    $this->_status->addMessage(
                        'error',
                        pm_Locale::lmsg('errResetFailed') . ' ' . $err
                    );
                } else {
                    $this->_status->addMessage('info', pm_Locale::lmsg('msgPasswordReset'));
                    $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
                    return;
                }
            }
        }

        $this->view->form = $form;
    }

    private function buildPasswordForm(): pm_Form_Simple
    {
        $form = new pm_Form_Simple();

        $form->addElement('description', 'help', [
            'description' => pm_Locale::lmsg('passwordResetHelp'),
            'escape'      => false,
            'ignore'      => true,
        ]);

        $form->addElement('password', 'newPassword', [
            'label'       => pm_Locale::lmsg('lblNewPassword'),
            'required'    => true,
            'validators'  => [
                ['StringLength', false, ['min' => 8]],
            ],
            'autocomplete' => 'new-password',
        ]);

        $form->addElement('password', 'confirmPassword', [
            'label'       => pm_Locale::lmsg('lblConfirmPassword'),
            'required'    => true,
            'validators'  => [
                ['StringLength', false, ['min' => 8]],
            ],
            'autocomplete' => 'new-password',
        ]);

        $form->addControlButtons([
            'sendTitle' => pm_Locale::lmsg('btnResetPassword'),
            'cancelLink' => pm_Context::getBaseUrl(),
        ]);

        return $form;
    }
}
