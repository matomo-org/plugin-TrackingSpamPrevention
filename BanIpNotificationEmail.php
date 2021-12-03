<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TrackingSpamPrevention;

use Piwik\Common;
use Piwik\Log;
use Piwik\Mail;
use Piwik\Piwik;
use Piwik\SettingsPiwik;

class BanIpNotificationEmail
{
    public function send($ip, $email, $maxActionsAllowed, $locationData, $nowDateTime)
    {
        if (empty($email) || !Piwik::isValidEmailString($email)) {
            return;
        }

        $mail = new Mail();
        $mail->addTo($email);
        $mail->setSubject('An IP was banned as too many actions were tracked.');
        $mail->setDefaultFromPiwik();

        $mailBody = 'This is for your information. The following IP was banned because visit tried to track more than ' . Common::sanitizeInputValue($maxActionsAllowed) . ' actions:';
        $mailBody .= '<br><br> "' . Common::sanitizeInputValue($ip) . '" <br>';
        $instanceId = SettingsPiwik::getPiwikInstanceId();


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

        if (!empty($instanceId)) {
            $mailBody .= '<br> Current date (UTC): ' . Common::sanitizeInputValue($nowDateTime) . '
                        <br> IP as detected in header: ' . Common::sanitizeInputValue(\Piwik\IP::getIpFromHeader()) . '
                        <br> GET request info: ' . Common::sanitizeInputValue(json_encode($get, JSON_HEX_APOS)) . '
                        <br> POST request info: ' . Common::sanitizeInputValue(json_encode($post, JSON_HEX_APOS));
        }

        if (!empty($locationData)) {
            $mailBody .= '<br> Geo IP info: ' . Common::sanitizeInputValue(json_encode($locationData, JSON_HEX_APOS));
        }

        $mail->setBodyHtml($mailBody);

        $testMode = (defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE);
        if ($testMode) {
            Log::info($mail->getSubject() . ':' . $mail->getBodyHtml());
        } else {
            $mail->send();
        }

        return $mail->getBodyHtml();
    }

}
