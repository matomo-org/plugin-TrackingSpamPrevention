<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Log;
use Piwik\Mail;
use Piwik\Piwik;
use Piwik\SettingsPiwik;
use Piwik\View;

class BanIpNotificationEmail
{
    public function send($ip, $email, $maxActionsAllowed, $locationData, $nowDateTime)
    {
        if (empty($email) || !Piwik::isValidEmailString($email)) {
            return;
        }

        $mail = new Mail();
        $mail->addTo($email);
        $mail->setSubject(Piwik::translate('TrackingSpamPrevention_BanIpNotificationMailSubject'));
        $mail->setDefaultFromPiwik();

        $view = new View('@TrackingSpamPrevention/notificationBanIpEmail.twig');
        $view->instanceId = SettingsPiwik::getPiwikInstanceId();
        $view->maxActionsAllowed = $maxActionsAllowed;
        $view->ipBanned = $ip;
        $view->ipHeader = \Piwik\IP::getIpFromHeader();
        $view->nowDataTime = $nowDateTime;
        $view->geoIpInfo = $locationData;

        if (!empty($_GET)) {
            $get = $_GET;
            if (isset($get['token_auth'])) {
                $get['token_auth'] = 'XYZANONYMIZED';
            }
        } else {
            $get = [];
        }

        if (!empty($_POST)) {
            $post = $_POST;
            if (isset($post['token_auth'])) {
                $post['token_auth'] = 'XYZANONYMIZED';
            }
        } else {
            $post = [];
        }

        $view->getRequest = $get;
        $view->postRequest = $post;
        $mail->setBodyText($view->render());

        $testMode = (defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE);
        if ($testMode) {
            Log::info($mail->getSubject() .':' . $mail->getBodyText());
        } else {
            $mail->send();
        }

        return $mail->getBodyText();
    }

}
