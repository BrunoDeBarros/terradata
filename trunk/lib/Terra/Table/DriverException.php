<?php
/**
 * Terra Duo Table Driver Exception
 *
 * Called whenever an error occurs with the driver.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Table_DriverException extends Terra_Table_Exception {

    /**
     * Create a new Terra_Table_DriverException with $message and $code.
     * @param string $message
     * @param string $code
     */
    public function __construct($message, $code = null) {
        parent::__construct($message, $code);
    }

}
