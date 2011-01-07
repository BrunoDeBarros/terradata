<?php
/**
 * Terra Duo Transaction
 *
 * Provides an API for managing transactions on a website.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.3.16
 * @package TD
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class TD_Transaction extends TD_Table_MySQL {

    public static $table_prefix = 'td_';

    function __construct() {

        /**
         * CREATE TABLE  `test`.`td_transactions` (
         `ID` INT( 64 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
         `IS_COMPLETED` BOOL NOT NULL ,
         `CREATED` DATETIME NOT NULL ,
         `IS_DELETED` BOOL NOT NULL ,
         `IS_SHIPPED` BOOL NOT NULL ,
         `IS_SHIPPABLE` BOOL NOT NULL ,
         `OWNER_ID` INT( 64 ) NOT NULL ,
         `SHIPPING_COSTS` DOUBLE NOT NULL ,
         `TOTAL` DOUBLE NOT NULL ,
         `COUPON_ID` INT( 64 ) NOT NULL ,
         `SHIPPING_ADDRESS` TEXT NOT NULL ,
         `SHIPPING_ZIPCODE` TEXT NOT NULL ,
         `SHIPPING_STATE` TEXT NOT NULL ,
         `SHIPPING_COUNTRY` TEXT NOT NULL ,
         `SHIPPING_FULL_NAME` TEXT NOT NULL ,
         `PAYMENT_STATUS` VARCHAR( 255 ) NOT NULL ,
         `PAYMENT_DATE` DATETIME NOT NULL ,
         `PAYMENT_GATEWAY_ID` INT( 64 ) NOT NULL ,
         `IS_SUBSCRIPTION_PAYMENT` BOOL NOT NULL ,
         `SUBSCRIPTION_ID` INT( 64 ) NOT NULL ,
         `PAYMENT_GATEWAY_TRANSACTION_ID` VARCHAR( 255 ) NOT NULL
         ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

         */

        $this->setTableName('transaction');
        $this->setTableData(array(
                'ID' => array(),
                'IS_COMPLETED' => array(),
                'CREATED' => array (
                        'Default' => 'NOW()'
                ),
                'IS_DELETED' => array(),
                'IS_SHIPPED' => array(),
                'IS_SHIPPABLE' => array(),
                'OWNER_ID' => array(),
                'SHIPPING_COSTS' => array(),
                'TOTAL' => array(),
                'COUPON_ID' => array(),
                'SHIPPING_ADDRESS' => array(),
                'SHIPPING_ZIPCODE' => array(),
                'SHIPPING_STATE' => array(),
                'SHIPPING_COUNTRY' => array(),
                'SHIPPING_FULL_NAME' => array(),
                'PAYMENT_STATUS' => array(),
                'PAYMENT_DATE' => array(),
                'PAYMENT_GATEWAY_ID' => array(),
                'IS_SUBSCRIPTION_PAYMENT' => array(),
                'SUBSCRIPTION_ID' => array(),
                'PAYMENT_GATEWAY_TRANSACTION_ID' => array()
        ));
    }

}