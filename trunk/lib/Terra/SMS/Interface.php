<?php
/**
 * Terra Duo SMS Interface
 *
 * Provides an interface for SMS Providers.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage SMS
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface Terra_SMS_Interface {
    function sendSms($to, $contents);
    function setApiKey($key);
    function receiveSms();
}