<?php
/**
 * Terra Data Query Exception
 *
 * Thrown whenever a query fails due to MySQL problems.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 1.0
 * @package Terra
 * @subpackage Data
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Data_QueryException extends Terra_DataException {

    /**
     * Create a new Terra_Data_QueryException with $message and $code.
     * @param string $message
     * @param string $code
     */
    public function __construct($message, $code = null) {
        parent::__construct($message, $code);
    }

}
