<?php
/**
 * Terra Duo Table Database Exception
 *
 * Called whenever an error occurs with the database.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.3.21
 * @package TD
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class TD_Table_DatabaseException extends TD_Table_Exception {

    /**
     * Create a new TD_Table_DatabaseException with $message and $code.
     * @param string $message
     * @param string $code
     */
    public function __construct($message, $code = null) {
        parent::__construct($message, $code);
    }

}
