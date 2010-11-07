<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../lib/Terra/Autoload.php';

class Terra_Data_StaticFunctionsTest extends PHPUnit_Framework_TestCase {
    public function testBuildWhere() {
        $where = new Terra_Data_Where('table');
        $where->_and('STUFF', 10);
        $this->assertEquals(" WHERE ((`table`.STUFF = '10') ) ", Terra_Data::buildWhere($where));

        $where = new Terra_Data_Where('table');
        $where->_and('COUNTRY_ID', 'countries.ID', false);
        $this->assertEquals(" WHERE ((`table`.COUNTRY_ID = countries.ID) ) ", Terra_Data::buildWhere($where));

        $whereCountryAndStuff = Terra_Data::WhereFactory('table')->_and('COUNTRY_ID', 1)->_and('STUFF', 5);
        $whereStuff = Terra_Data::WhereFactory('table')->_or('STUFF', 10)->_or('COUNTRY_ID', 'countries.ID', false)->_or($whereCountryAndStuff);
        $whereDOB = Terra_Data::WhereFactory('table')->_and('DOB >', 3600)->_and('DOB <', 7200);
        $whereFinal = Terra_Data::WhereFactory('table')->_and($whereDOB)->_and($whereStuff);

        $this->assertEquals(" WHERE (((`table`.DOB > '3600') AND (`table`.DOB < '7200') ) AND ((`table`.STUFF = '10') OR (`table`.COUNTRY_ID = countries.ID) OR ((`table`.COUNTRY_ID = '1') AND (`table`.STUFF = '5') ) ) ) ", Terra_Data::buildWhere($whereFinal));
    }

    public function testBuildingAnEmptyWhereReturnsAnEmptyString() {
        $this->assertEquals("", Terra_Data::buildWhere(new Terra_Data_Where()));
    }

    public function testBuildWhereFromArray() {
        $this->assertEquals(" WHERE ((ID = '4') ) ", Terra_Data::buildWhere(array('ID' => 4)));
    }
}