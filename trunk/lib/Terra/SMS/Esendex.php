<?php
/**
 * Terra Duo SMS Esendex
 *
 * Handles text messaging services with Esendex.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage SMS
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Terra_SMS_Esendex implements Terra_SMS_Interface {

    public $SmsStorage;
    protected $UserPass;
    protected $AccountReference;
    protected $From;

    function __construct(Terra_Table_Interface $SmsStorage) {
        $this->SmsStorage = $SmsStorage;
        $this->SmsStorage->setTableData(array(
                'Fields' => array(
                        'ID' => array(
                                'ValidationRules' => array(

                                )
                        ),

                        'RECIPIENT' => array(
                                'ValidationRules' => array(

                                )
                        ),

                        'CONTENTS' => array(
                                'ValidationRules' => array(

                                )
                        ),

                        'CREATED' => array(
                                'ValidationRules' => array(

                                )
                        ),

                        'IS_MESSAGE_SENT' => array(
                                'ValidationRules' => array(

                                )
                        ),

                        'IS_DELIVERED' => array(
                                'ValidationRules' => array(

                                )
                        ),

                        'SMS_SERVICE_ID' => array(
                                'ValidationRules' => array(

                                )
                        )
                )
        ));
    }

    function setApiKey($key) {
        $this->UserPass = base64_encode($key);
        $fp = fsockopen("api.esendex.com", 80, $errno, $errstr, 30);
        if (!$fp) {
            throw new Terra_SMS_Exception($errstr, $errno);
            return false;
        } else {
            $out = "GET  /v1.0/accounts HTTP/1.0\r\n";
            $out .= "Host: api.esendex.com\r\n";
            $out .= "Authorization: Basic ".$this->UserPass."\r\n\r\n";
            fwrite($fp, $out);
            $buffer = '';
            while (!feof($fp)) {
                $buffer .= fgets($fp, 256);
            }
            fclose($fp);

            if (stristr($buffer, 'HTTP/1.1 200 OK')) {
                $buffer = explode("\r\n\r\n", $buffer, 2);
                $xml = new SimpleXMLElement($buffer[1]);
                $this->AccountReference = (string) $xml->account->reference;
                $this->From = (string) $xml->account->address;
                return true;
            } else {
                throw new Terra_SMS_Exception('The username:password combination you have provided is invalid.');
                return false;
            }
        }
    }

    function sendSms($to, $contents) {

        $fp = fsockopen("api.esendex.com", 80, $errno, $errstr, 30);
        if (!$fp) {
            throw new Terra_SMS_Exception($errstr, $errno);
        } else {
            $out = "POST /v1.0/messagedispatcher HTTP/1.0\r\n";
            $out .= "Host: api.esendex.com\r\n";
            $out .= "Authorization: Basic ".$this->UserPass."\r\n";
            $xml =  "<?xml version='1.0' encoding='UTF-8'?> <messages>
                <accountreference>{$this->AccountReference}</accountreference>
                <message>
                    <from>{$this->From}</from>
                    <to>$to</to>
                    <body>$contents</body>
                </message>
                </messages>";
            $out .= "Content-Length: ".strlen($xml)."\r\n";
            $out .= "Connection: Close\r\n\r\n";
            $out .= $xml;
            fwrite($fp, $out);
            $buffer = '';
            while (!feof($fp)) {
                $buffer .= fgets($fp, 256);
            }
            fclose($fp);

            if (stristr($buffer, 'HTTP/1.1 200 OK')) {
                $buffer = explode("\r\n\r\n", $buffer, 2);
                $xml = new SimpleXMLElement($buffer[1]);
                $data = array(
                    'RECIPIENT' => $to,
                    'CONTENTS' => $contents,
                    'CREATED' => 'NOW()',
                    'IS_MESSAGE_SENT' => 1,
                    'SMS_SERVICE_ID' => ''
                );
                $ID = $this->SmsStorage->create($data);
                $data['ID'] = $ID;

                # Let the rest of the operations happen.
                Terra_Events::trigger('Terra_SMS_MessageSent', $data);
                return $data;
            } else {
                throw new Terra_SMS_Exception('A problem occured while trying to send a text using Esendex.');
                return false;
            }
        }
    }

    function receiveSms() {
        switch($_REQUEST['notificationType']) {
            case 'MessageReceived':
                $text = array(
                        'RECIPIENT' => $_REQUEST['originator'],
                        'CONTENTS' => $_REQUEST['body'],
                        'IS_MESSAGE_SENT' => 0,
                        'CREATED' => $_REQUEST['receivedAt'],
                        'IS_DELIVERED' => 1,
                        'SMS_SERVICE_ID' => $_REQUEST['id']
                );
                if ($this->SmsStorage->count(array('SMS_SERVICE_ID' => $_REQUEST['id'])) == 0) {
                    $ID = $this->SmsStorage->create($text);
                } else {
                    throw new Terra_SMS_Exception("An attempt at saving a message that had already been saved before has been detected. The message was not stored.");
                }
                $text['ID'] = $ID;

                # Let the rest of the operations happen.
                Terra_Events::trigger('Terra_SMS_MessageReceived', $text);
                return $text;
                break;
            case 'MessageEvent':
                switch($_REQUEST['eventType']) {
                    case 'Delivered':
                        $this->SmsStorage->edit(array('SMS_SERVICE_ID' => $_REQUEST['id']), array('IS_DELIVERED' => 1));
                        break;
                    default:
                        throw new Terra_SMS_Exception("An invalid value for eventType was provided by Esendex. The value provided was {$_REQUEST['eventType']}.");
                        break;
                }
                break;
            case 'MessageError':
                throw new Terra_SMS_Exception("A notificationType of MessageError was provided. This notification type is still not implemented.");
                break;
            case 'SubscriptionEvent':
                throw new Terra_SMS_Exception("A notificationType of SubscriptionEvent was provided. This notification type is still not implemented.");
                break;
            default:
                throw new Terra_SMS_Exception("An invalid value for notificationType was provided by Esendex. The value provided was {$_REQUEST['notificationType']}.");
                break;
        }
    }
}