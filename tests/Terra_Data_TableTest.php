<?php

require_once 'PHPUnit/Framework.php';
error_reporting(E_ALL | E_STRICT);
require_once dirname(__FILE__) . '/../lib/Terra/Autoload.php';

class Terra_Data_TableTest extends PHPUnit_Framework_TestCase {
    function testTableObjectCanBeUsedAsArray() {
        $table = new Terra_Data_Table('users');
        $table->addField('ID');
        
        $this->assertEquals($table['Name'], 'users');
        $this->assertArrayHasKey('ID', $table['Fields']);
    }
   
}