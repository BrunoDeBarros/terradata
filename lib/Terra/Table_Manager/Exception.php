<?php
/**
 * Terra Duo Table Exception
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.2.21
 * @package TD
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class TD_Table_Exception extends TD_Exception {
    
    /**
     * Create a new TD_Table_Exception with $message and $code.
     * @param string $message
     * @param string $code
     */
    public function __construct($message, $code = null) {
        parent::__construct($message, $code);
    }

}
