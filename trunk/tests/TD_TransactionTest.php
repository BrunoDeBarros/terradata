<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../../TD/Autoload.php';

class TD_TransactionTest extends PHPUnit_Framework_TestCase {
    function testCreateTransaction() {
        $this->markTestIncomplete('Waiting for TD_Table to support hasOne and hasMany relationships');
        return;
        TD_Transaction_Purchase::setDefaultCurrency(1); # Default Currency is Euro.

        $transaction = new TD_Transaction_Purchase();
        $transaction->addItem(1, 'Book XYZ', 2, 10.99, 200); # Unique ID, Name, Quantity, Unit Price, Unit Weight (in grams).
        $transaction->addItem(2, 'Book ABC', 1,  5, 100); # Unique ID, Name, Quantity, Unit Price, Unit Weight (in grams).
        $transaction->setBuyerCurrency(2); # IDs are from currencies table.
        $transaction->setCoupon(2); # Sets discounts and all that stuff.        
        
    }
}