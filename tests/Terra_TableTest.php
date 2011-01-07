<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../Terra/Autoload.php';

class Terra_TableTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Terra_Table_Interface
     */
    protected $TableManager;

    /**
     *
     * @var Terra_Table_Interface
     */
    protected $CountryManager;

    /**
     *
     * @var array
     */
    protected $users;
    /**
     *
     * @var array
     */
    protected $countries;

    protected static $connection;

    public static function setUpBeforeClass() {
        self::$connection = mysql_connect('localhost', 'root', '');
        mysql_select_db('test', self::$connection);
        mysql_query("CREATE TABLE IF NOT EXISTS `users` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `USERNAME` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `PASSWORD` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `CREATED` datetime NOT NULL,
  `UPDATED` datetime NOT NULL,
  `IS_DELETED` tinyint(1) NOT NULL,
  `COUNTRY_ID` int(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;", self::$connection);
        mysql_query("CREATE TABLE IF NOT EXISTS `countries` (
`ID` int(255) NOT NULL AUTO_INCREMENT,
`NAME` varchar(255) NOT NULL,
PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;", self::$connection);
        mysql_query("CREATE TABLE IF NOT EXISTS `reviews` (
`ID` INT NOT NULL AUTO_INCREMENT ,
`CONTENTS` TEXT NOT NULL ,
`USER_ID` INT NOT NULL ,
PRIMARY KEY (  `ID` )
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;
");
        $table = Terra_Table_Manager::Factory('MySQL');
        $table->setDatabaseConnection(self::$connection);
    }

    function setUp() {
        mysql_query("TRUNCATE TABLE  `users`");
        mysql_query("TRUNCATE TABLE  `countries`");
        mysql_query("TRUNCATE TABLE  `reviews`");

        $this->TableManager = Terra_Table_Manager::Factory('MySQL');
        $this->TableManager->resetQueryStats();
        $this->TableManager->resetTableData();
        $this->users = array(
                'Name' => 'users',
                'OrderBy' => array(
                        'Order' => 'ID',
                        'Field' => 'DESC'
                ),
                'Fields' => array(
                        new Terra_Table_Field('ID'),
                        new Terra_Table_Field('USERNAME'),
                        new Terra_Table_Field('PASSWORD'),
                        new Terra_Table_Field('CREATED'),
                        new Terra_Table_Field('UPDATED'),
                        new Terra_Table_Field('COUNTRY_ID'),
                        new Terra_Table_Field('IS_DELETED'),
                )
        );
        $this->TableManager->setTableData($this->users);

        $this->CountryManager = Terra_Table_Manager::Factory('MySQL');
        $this->CountryManager->resetQueryStats();
        $this->CountryManager->resetTableData();
        $this->countries = array(
                'Name' => 'countries',
                'Fields' => array(
                        new Terra_Table_Field('ID'),
                        new Terra_Table_Field('NAME'),
                        new Terra_Table_Field('USERS')
                )
        );
        $this->CountryManager->setTableData($this->countries);
    }

    function tearDown() {
        Terra_Table_Field::getAllErrors();
    }

    function testCreatingRecordsOnlyInsertsEnabledFields() {
        $this->TableManager->getField('PASSWORD')->Disabled = true;

        $insertID = $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass'));
        $row = $this->TableManager->getWhere(array('ID' => $insertID));
        $this->assertEquals('bruno', $row[0]['USERNAME']);
        $this->assertEquals('', $row[0]['PASSWORD'], "Password is disabled, but Table Manager still stored it in the database.");
    }

    function testEditingRecordsOnlyUpdatesEnabledFields() {
        $insertID = $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass'));
        $row = $this->TableManager->getWhere(array('ID' => $insertID));
        $this->assertEquals('pass', $row[0]['PASSWORD'], "Password is enabled, but was not stored in the database.");
        $this->TableManager->getField('PASSWORD')->Disabled = true;
        $this->TableManager->edit($insertID, array('PASSWORD' => 'newpass'));
        $row = $this->TableManager->getWhere(array('ID' => $insertID));
        $this->assertEquals('pass', $row[0]['PASSWORD'], "Password is disabled, but Table Manager still updated it in the database.");
    }

    function testGettingRecordsGetsEvenTheDisabledFields() {

        $insertID = $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass'));

        $this->TableManager->getField('PASSWORD')->Disabled = true;
        $this->TableManager->getField('USERNAME')->Disabled = true;
        $row = $this->TableManager->getWhere(array('ID' => $insertID));
        $this->assertEquals('pass', $row[0]['PASSWORD'], "Password is disabled and Terra_Table did not read it from the database.");
    }

    function testCreatingRecordsForcesRequiredDataToBePresent() {
        $this->TableManager->getField('USERNAME')->Required();

        $insertID = $this->TableManager->create(array('PASSWORD' => 'pass'));
        $this->assertFalse($insertID);
    }

    function testCreatingRowsOfRandomDataReturnsNumberOfRowsCreated() {

        $rowsCreated = $this->TableManager->createFromRandom(array(
                'USERNAME' => array('bruno', 'mike', 'grace'),
                'PASSWORD' => array('MYXPASS')
                ), 10);
        $this->assertEquals(10, $rowsCreated);
        $this->assertEquals(10, count($this->TableManager->getWhere(array('PASSWORD' => 'MYXPASS'))));
    }

    function testCountRowsUsingWhereClause() {
        $this->TableManager->createFromRandom(array('USERNAME' => array('bruno', 'mike')), 50);

        $count = $this->TableManager->count(array('USERNAME' => 'bruno', 'OR USERNAME' => 'mike'));
        $this->assertEquals(50, $count);
    }

    function testCountRowsUsingWhereClauseWithRepeatedFieldNames() {
        $this->TableManager->createFromRandom(array('USERNAME' => array('bruno', 'mike', 'grace', 'iain')), 50);

        $count = $this->TableManager->count(array('USERNAME' => 'bruno', 'OR USERNAME' => 'mike', 'OR USERNAME 2' => 'grace', 'OR USERNAME 3' => 'iain'));
        $count = $this->TableManager->count(array('OR USERNAME' => 'bruno', 'OR USERNAME 4' => 'mike', 'OR USERNAME 2' => 'grace', 'OR USERNAME 3' => 'iain'));
        $this->assertEquals(50, $count);
    }

    function testGetWithoutWhereClause() {
        $this->TableManager->create(array('USERNAME' => 'bruno'));
        $get = $this->TableManager->get(array(
                'Fields' => array('USERNAME')
        ));
        $this->assertEquals(array(array('USERNAME' => 'bruno')), $get);
    }

    function testQueryStats() {
        $this->assertEquals('', $this->TableManager->getLastQuery());
        $sql = 'SELECT * FROM users';
        $this->TableManager->query($sql);
        $this->assertEquals(1, $this->TableManager->getQueryCount());
        $this->assertEquals($sql, $this->TableManager->getLastQuery());
    }

    function testDeleteRowWithOutIsDeletedField() {
        $this->TableManager->getField('IS_DELETED')->Disabled = true;
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno'));
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno')));
        $this->TableManager->delete($ID);
        $this->assertEquals(0, $this->TableManager->count(array('USERNAME' => 'bruno')));
        $this->TableManager->getField('IS_DELETED')->Disabled = false;
    }

    function testDeleteRowWithIsDeletedField() {        
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno'));
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno')));
        $this->TableManager->delete($ID);
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno')));
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno', 'IS_DELETED' => true)));
        $this->assertEquals(0, $this->TableManager->count(array('USERNAME' => 'bruno', 'IS_DELETED' => false)));
    }

    function testTimeSpentQuerying() {
        $this->assertEquals(0, $this->TableManager->getTimeSpentQuerying());
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno'));
        $this->assertNotEquals(0, $this->TableManager->getTimeSpentQuerying());
    }

    function testMethodQueryLanguage() {
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass'));
        $this->assertEquals('bruno', $this->TableManager->getUsernameById($ID));
    }

    function testSetAndGetDatabaseConnection() {
        $c = mysql_connect('localhost', 'root', '');
        $this->assertType('resource', $c);
        $this->TableManager->setDatabaseConnection($c);
        $this->assertType('resource', $this->TableManager->getDatabaseConnection());
    }

    function testGetWithLeftJoinAndMethodQueryLanguage() {
        $countryID = $this->CountryManager->create(array('NAME' => 'Portugal'));
        $this->assertEquals(1, $this->CountryManager->count(array('NAME' => 'Portugal')));
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'COUNTRY_ID' => $countryID));
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'COUNTRY_ID' => $countryID)));

        $this->TableManager->getField('COUNTRY_ID')->ExistsIn('countries', 'ID', 'NAME');

        $this->assertEquals('Portugal', $this->TableManager->getCountryById($ID));
        $this->assertEquals($countryID, $this->TableManager->getCountryIdById($ID));
        $this->assertTrue(array_key_exists('ID', $this->TableManager->getById($ID)));
        $record = $this->TableManager->getUsernameAndIdAndPasswordById($ID);
        $this->assertTrue(array_key_exists('ID', $record));
        $this->assertTrue(array_key_exists('USERNAME', $record));
        $this->assertTrue(array_key_exists('PASSWORD', $record));
        $row = $this->TableManager->get();
        $row = $row[0];
        $this->assertEquals('Portugal', $row['COUNTRY']);
        $this->assertEquals($countryID, $row['COUNTRY_ID']);
    }

    /**
     * @depends testGetWithLeftJoinAndMethodQueryLanguage
     * @depends testQueryStats
     */
    function testGetWithLeftJoinAndWhereClauses() {
        $countryID = $this->CountryManager->create(array('NAME' => 'Portugal'));
        $this->assertEquals(1, $this->CountryManager->count(array('NAME' => 'Portugal')));
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'COUNTRY_ID' => $countryID));
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'COUNTRY_ID' => $countryID)));

        $this->TableManager->getField('COUNTRY_ID')->ExistsIn('countries', 'ID', 'NAME', array('NAME !=' => 'Ireland', 'NAME != 3' => 'John'), create_function('$args', ' $args["Where"]["NAME != 2"] = "Spain";'));

        $this->assertEquals('Portugal', $this->TableManager->getCountryById($ID));
        $matches = array();
        $this->assertEquals(2, preg_match_all("/NAME != '[Spain|Ireland]+'/", $this->TableManager->getLastQuery(), $matches));
    }

    function testRestoreDeletedRecord() {
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno'));
        $this->TableManager->delete($ID);
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno', 'IS_DELETED' => true)));
        $this->TableManager->restore($ID);
        $this->assertEquals(0, $this->TableManager->count(array('USERNAME' => 'bruno', 'IS_DELETED' => true)));
    }

    function testRestoreWithoutIsDeleted() {
        $this->TableManager->getField('IS_DELETED')->Disabled = true;
        $ID = $this->TableManager->create(array('USERNAME' => 'bruno'));
        $this->assertEquals(1, $this->TableManager->count(array('USERNAME' => 'bruno')));
        $this->TableManager->delete($ID);
        $this->assertEquals(0, $this->TableManager->count(array('USERNAME' => 'bruno')));

        $this->assertFalse($this->TableManager->restore($ID));
    }

    function testInvalidSqlThrowsAnException() {
        $this->setExpectedException('Terra_Table_DatabaseException');
        $this->TableManager->query('SELECT * ERROR');
    }

    function testCountOfInvalidQueryIsZero() {
        $this->setExpectedException('Terra_Table_DatabaseException');
        $this->assertEquals(0, $this->TableManager->count(array('UNEXISTING' => 1)));
    }

    function testGetWhereWithArgumentOptions() {
        $this->TableManager->createFromRandom(array('USERNAME' => array('bruno', 'mike', 'grace', 'iain')), 50);
        $rows = $this->TableManager->getWhere(array('USERNAME' => 'bruno', 'OR USERNAME' => 'mike', 'OR USERNAME 2' => 'grace', 'OR USERNAME 3' => 'iain'), array(
                'Fields' => array('USERNAME', 'PASSWORD', 'ID'),
                'OrderBy' => array('Field' => 'ID', 'Order' => 'DESC'),
                'Page' => 2,
                'Rows' => 30
        ));
        $this->assertEquals(20, count($rows));
        $this->assertEquals(20, $rows[0]['ID']);
        $this->assertEquals(3, count($rows[0]));
    }

    function testGetMultipleRecordsWithMethodQueryLanguage() {
        $this->assertEquals(50, $this->TableManager->createFromRandom(array('USERNAME' => array('bruno')), 50));
        $this->assertEquals(50, count($this->TableManager->getByUsername('bruno', array('Rows' => 50))));
    }

    function testRequiredValidationRule() {
        $this->TableManager->getField('USERNAME')->Required();
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => '')));
    }

    function testInArrayValidationRule() {
        $this->TableManager->getField('USERNAME')->InArray(array('black', 'blue'));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'black')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno')));
    }

    function testRegexValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Regex('/^\+44[0-9]{10}$/');
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => '+441234567890')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => '+3531234567890')));
    }

    function testMatchesValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Matches('PASSWORD');
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'bruno')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass')));
    }

    function testExistsInValidationRule() {
        $this->TableManager->getField('COUNTRY_ID')->resetValidationRules()->ExistsIn('countries', 'ID', 'NAME');
        $countryID = $this->CountryManager->create(array('NAME' => 'Portugal'));
        $this->assertTrue((bool) $this->TableManager->create(array('COUNTRY_ID' => $countryID)));
        $this->assertFalse($this->TableManager->create(array('COUNTRY_ID' => 'random')));
    }

    function testNotExistsInValidationRule() {
        $this->TableManager->getField('COUNTRY_ID')->resetValidationRules()->NotExistsIn('countries', 'ID', 'NAME');
        $countryID = $this->CountryManager->create(array('NAME' => 'Portugal'));
        $this->assertTrue((bool) $this->TableManager->create(array('COUNTRY_ID' => 'random')));
        $this->assertFalse($this->TableManager->create(array('COUNTRY_ID' => $countryID)));
    }

    function testCustomValidationRule() {
        $limitUsers = create_function('$Array', '
if ($Array["Terra_Table"]->count(array()) > $Array["Arg"]) {
    $Array["Error"] = $Array["Field"]."DOESNOTWORK";
}
');
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Callback($limitUsers, 5);
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno')));
    }

    function testUniqueValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Unique();
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno')));
    }

    function testHashValidationRule() {
        $this->TableManager->getField('PASSWORD')->resetValidationRules()->Hash('sha256');
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'hashvalidation', 'PASSWORD' => 'pass')));
        $this->assertEquals(hash('sha256', 'pass'), $this->TableManager->getPasswordByUsername('hashvalidation'));
    }

    function testMinCharsValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->MinChars(3);
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'b')));
    }

    function testMaxCharsValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->MaxChars(3);
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bru')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno')));
    }

    function testCallingAnInvalidFunctionThrowsAnException() {
        $this->setExpectedException('Terra_Table_Exception');
        $this->TableManager->invalidFunction();
    }

    function testProcessRowsWithTerraEvents() {
        Terra_Events::resetEvent('GetRow');
        $function = create_function('$row', '$row["MESSING"] = $row["USERNAME"]; $row["USERNAME"] = "WOO ".$row["USERNAME"];');
        Terra_Events::addCallback('GetRow', $function);
        $this->TableManager->create(array('USERNAME' => 'Terra Event Test'));
        $this->assertEquals('Terra Event Test', $this->TableManager->getMessingByUsername('Terra Event Test'));
        $this->assertEquals('WOO Terra Event Test', $this->TableManager->getUsernameByUsername('Terra Event Test'));
        Terra_Events::resetEvent('GetRow');
    }

    function testExecutingAQueryWithoutSettingADatabaseConnectionThrowsAnException() {
        $NewManager = Terra_Table_Manager::Factory('MySQL');
        $NewManager->resetDefaultDatabaseConnection();
        $NewManager->setTableName('users');
        $this->setExpectedException('Terra_Table_Exception');
        $NewManager->getById(1);
    }

    function testNewTableManagersCanGetRecordsWithOnlyTheTableName() {
        $this->TableManager->setDatabaseConnection(self::$connection);
        $NewManager = Terra_Table_Manager::Factory('MySQL');
        $NewManager->setTableName('users');
        $ID = $this->TableManager->create(array('USERNAME' => 'Terra Empty Test'));
        $this->assertEquals('Terra Empty Test', $NewManager->getUsernameById($ID));
    }

    function testNewTableManagerInheritTableDataThatWasSetInAnotherInstanceOfTheSameTable() {
        $testfield = new Terra_Table_Field('TESTFIELD');
        $this->TableManager->setField($testfield);
        $new = Terra_Table_Manager::Factory("MySQL", "users");
        $this->assertTrue($new->getField('TESTFIELD') == $testfield);
        $this->TableManager->unsetField('TESTFIELD');
    }

    function testDefaultValuesAreUsedWhenFieldsHaveNoValue() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Default = "somerandomusername";
        $insertID = $this->TableManager->create(array());
        $this->assertEquals('somerandomusername', $this->TableManager->getUsernameById($insertID));
    }

    function testSettingUpdateIfEmptyToTrueMeansThatAFieldWillBeUpdatedEvenIfAnEmptyStringIsProvided() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->UpdateIfEmpty = true;
        $insertID = $this->TableManager->create(array('USERNAME' => 'somerandomusername'));
        $this->TableManager->edit($insertID, array('USERNAME' => '', 'PASSWORD' => 'pass'));
        $this->assertEquals('', $this->TableManager->getUsernameById($insertID));
    }

    function testSettingUpdateIfEmptyToFalseMeansThatAFieldWillNotBeUpdatedIfAnEmptyStringIsProvided() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->UpdateIfEmpty = false;
        $insertID = $this->TableManager->create(array('USERNAME' => 'somerandomusername'));
        $this->TableManager->edit($insertID, array('USERNAME' => '', 'PASSWORD' => 'pass'));
        $this->assertEquals('somerandomusername', $this->TableManager->getUsernameById($insertID));
    }


    function testAlphanumericValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Alphanumeric();
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno333')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno 333')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno323!')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno ? ')));
    }

    function testEmailValidationRule() {
        $this->TableManager->getField('USERNAME')->resetValidationRules()->Email();
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno@terraduo.co.uk')));
        $this->assertTrue((bool) $this->TableManager->create(array('USERNAME' => 'bruno.debarros@terraduo.co.uk')));
        $this->assertFalse($this->TableManager->create(array('USERNAME' => 'bruno@')));
    }

    function testUpdatingRecordsWithMethodQueryLanguage() {
        $ID = $this->TableManager->create(array('USERNAME' => 'mql', 'PASSWORD' => 'pass'));
        $this->TableManager->setPasswordAndUsernameById($ID, 'pass2', 'root');
        $this->assertEquals('root', $this->TableManager->getUsernameById($ID));
        $this->assertEquals('pass2', $this->TableManager->getPasswordById($ID));
    }

    function testGetRecordsFromHasManyRelationshipWithoutRelationshipTable() {
        $this->markTestIncomplete('Relationships haven\'t been implemented yet.');
        $this->TableManager->hasMany('reviews', 'USER_ID');
        $user = $this->TableManager->create(array('USERNAME' => 'Bruno', 'PASSWORD' => 'pass'));
        $reviews = Terra_Table_Manager::Factory("MySQL", "reviews");
        $reviews->setField(new Terra_Table_Field('CONTENTS'));
        $reviews->setField(new Terra_Table_Field('USER_ID'));
        $reviewID = $reviews->create(array('CONTENTS' => 'test review', 'USER_ID' => $user));
        $reviews = $this->TableManager->getReviews($user);
        $this->assertEquals($reviewID, $reviews[0]['ID']);
    }

    function testManipulateRecordsInHasManyRelationshipWithRelationshipTable() {
        $this->markTestIncomplete('Relationships haven\'t been implemented yet.');
        $this->TableManager->hasManyWithRelTable('countries', 'visited_countries');

        $user = $this->TableManager->create(array('USERNAME' => 'Bruno', 'PASSWORD' => 'pass'));
        $country = $this->CountryManager->create(array('NAME' => 'Portugal'));

        $user->addVisitedCountries($user, $country);
        $countries = $user->getVisitedCountries($user);
        $this->assertEquals($country, $countries[0]['ID']);

        $user->removeVisitedCountries($user, $country);
        $this->assertEquals(array(), $user->getVisitedCountries($user));
    }
}