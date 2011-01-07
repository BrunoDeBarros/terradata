<?php

require_once 'PHPUnit/Framework.php';
error_reporting(E_ALL | E_STRICT);
require_once dirname(__FILE__) . '/../lib/Terra/Autoload.php';

class Terra_DataTest extends PHPUnit_Framework_TestCase {

    /**
     * Terra Data for Users
     * @var Terra_Data
     */
    public $Users;
    /**
     * Terra Data for Countries
     * @var Terra_Data
     */
    public $Countries;
    /**
     * Terra Data for Articles
     * @var Terra_Data
     */
    public $Articles;

    function setUp() {
        $con = mysql_connect('localhost', 'root', '');
        mysql_select_db('terra_data_test', $con);
        mysql_query("TRUNCATE TABLE  `users`");
        mysql_query("TRUNCATE TABLE  `countries`");
        mysql_query("TRUNCATE TABLE  `countries_users`");
        mysql_query("TRUNCATE TABLE  `articles`");
        mysql_query("TRUNCATE TABLE  `articles_users`");

        require('Sample_Terra_Data_Configs.php');

        $this->Users = new Terra_Data($con, 'users', $Fields);
        $this->Countries = new Terra_Data($con, 'countries', array('NAME' => array('ValidationRules' => array('MaxChars' => 255))));
        $this->Articles = new Terra_Data($con, 'articles', array(
            'NAME' => array('ValidationRules' => array('MaxChars' => 255)),
            'CONTENTS' => array('ValidationRules' => array('MinChars' => 5)))); # @todo program more sophisticated text content validation (safe HTML, etc.)
    }

    function testCreatingTerraDataWithSimpleArrayOfFields() {
        $this->Countries = new Terra_Data($this->Countries->getMySQLConnection(), 'countries', array('NAME', 'ANOTHER'));
        $this->assertEquals('NAME', $this->Countries->Fields['NAME']['Name']);
        $this->assertEquals('ANOTHER', $this->Countries->Fields['ANOTHER']['Name']);
    }

    function testCreatingRecordsOnlyInsertsEnabledFields() {

        # Tests Disabled/Enabled Fields
        # Tests Method Query Language
        # Tests Result Treatment
        # Tests Single Record Array

        $this->Users->Fields['PASSWORD']['Disabled'] = true;
        $insertID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $row = $this->Users->getById($insertID, array('Format' => Terra_Data::SINGLE_RECORD_ARRAY, 'Rows' => 1));
        $this->assertEquals('bruno', $row['USERNAME']);
        $this->assertEquals('', $row['PASSWORD'], "Password is disabled, but Terra Data still stored it in the database.");
    }

    function testEditingRecordsOnlyUpdatesEnabledFields() {

        # Tests Record Array
        # Tests Method Query Language for Updating data

        $insertID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $row = $this->Users->getById($insertID);
        $this->assertEquals('bruno', $row['USERNAME'], "Username is enabled, but was not stored in the database.");
        $this->Users->Fields['USERNAME']['Disabled'] = true;
        $this->Users->setUsernameById($insertID, 'newuser');
        $row = $this->Users->getWhere(Terra_Data::WhereFactory('users')->_and('ID', $insertID), array('Rows' => 1));
        $this->assertEquals('newuser', $row[0]['USERNAME'], "Username is disabled, but Terra Data still updated it in the database.");
    }

    function testUpdatingRecordsWithMethodQueryLanguage() {
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->Users->setEmailAndUsernameById($ID, 'email@mail.com', 'root');
        $this->assertEquals('root', $this->Users->getUsernameById($ID));
        $this->assertEquals('email@mail.com', $this->Users->getEmailById($ID));
    }

    function testGettingRecordsGetsEvenTheDisabledFields() {

        $insertID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));

        $this->Users->Fields['USERNAME']['Disabled'] = true;
        $row = $this->Users->getWhere(Terra_Data::WhereFactory('users')->_and('ID', $insertID), array('Rows' => 1));
        $this->assertEquals('bruno', $row[0]['USERNAME'], "Username is disabled and Terra_Data did not read it from the database.");
    }

    function testCreatingRecordsForcesRequiredDataToBePresent() {
        $this->setExpectedException('Terra_Data_ValidationException');
        $insertID = $this->Users->create(array('PASSWORD' => 'pass'));
        $this->assertFalse($insertID);
    }

    function testCreatingRowsOfRandomDataReturnsNumberOfRowsCreated() {
        $rowsCreated = $this->Users->createRandom(array(
                    'USERNAME' => array('bruno', 'mike', 'grace'),
                    'PASSWORD' => array('MYXPASS'),
                    'EMAIL' => array('random@terraduo.com')
                        ), 10);
        $this->assertEquals(10, $rowsCreated);
        $this->assertEquals(10, count($this->Users->getWhere(array('EMAIL' => 'random@terraduo.com'), array('Rows' => 10))));
    }

    function testCountRowsUsingWhereClause() {
        $this->Users->createRandom(array(
            'USERNAME' => array('bruno', 'mike'),
            'PASSWORD' => array('MYXPASS'),
            'EMAIL' => array('random@terraduo.com')
                ), 50);

        $count = $this->Users->count(Terra_Data::WhereFactory('users')->_and('USERNAME', array('mike', 'bruno')));
        $this->assertEquals(50, $count);
    }

    function testCountRowsUsingWhereClauseWithRepeatedFieldNames() {
        $this->Users->createRandom(array(
            'USERNAME' => array('bruno', 'mike', 'grace', 'iain'),
            'PASSWORD' => array('MYXPASS'),
            'EMAIL' => array('random@terraduo.com')
                ), 50);

        $count = $this->Users->count(Terra_Data::WhereFactory('users')->_and('USERNAME', array('mike', 'grace', 'iain', 'bruno')));
        $this->assertEquals(50, $count);
    }

    function testGetWithoutWhereClause() {
        $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $get = $this->Users->get(array(
                    'Fields' => array('USERNAME'),
                    'Rows' => 1
                ));
        $this->assertEquals(array(array('USERNAME' => 'bruno')), $get);
    }

    function testDeleteRowWithOutIsDeletedField() {
        $this->Users->Fields['IS_DELETED']['Disabled'] = true;
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->assertEquals(1, $this->Users->count(array('USERNAME' => 'bruno')));
        $this->Users->delete($ID);
        $this->assertEquals(0, $this->Users->count(array('USERNAME' => 'bruno')));
    }

    function testDeleteRowWithIsDeletedField() {
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->assertEquals(1, $this->Users->count(array('USERNAME' => 'bruno')));
        $this->Users->delete($ID);
        $this->assertEquals(1, $this->Users->count(array('USERNAME' => 'bruno')));
        $this->assertEquals(1, $this->Users->count(array('USERNAME' => 'bruno', 'IS_DELETED' => true)));
        $this->assertEquals(0, $this->Users->count(array('USERNAME' => 'bruno', 'IS_DELETED' => false)));
    }

    function testRestoreDeletedRecord() {
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->Users->delete($ID);
        $this->assertEquals(1, $this->Users->count(array('USERNAME' => 'bruno', 'IS_DELETED' => true)));
        $this->Users->restore($ID);
        $this->assertEquals(0, $this->Users->count(array('USERNAME' => 'bruno', 'IS_DELETED' => true)));
    }

    function testRestoreWithoutIsDeleted() {
        $this->Users->Fields['IS_DELETED']['Disabled'] = true;
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->assertEquals(1, $this->Users->count(array('USERNAME' => 'bruno')));
        $this->Users->delete($ID);
        $this->assertEquals(0, $this->Users->count(array('USERNAME' => 'bruno')));
        $this->setExpectedException('Terra_DataException');
        $this->assertFalse($this->Users->restore($ID));
    }

    function testMethodQueryLanguage() {
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->assertEquals('bruno', $this->Users->getUsernameById($ID));

        $ID = $this->Users->getUsernameAndIdAndPasswordById($ID);
        $this->assertTrue(array_key_exists('ID', $ID));
        $this->assertTrue(array_key_exists('USERNAME', $ID));
        $this->assertTrue(array_key_exists('PASSWORD', $ID));
    }

    function testSetAndGetDatabaseConnection() {
        $c = mysql_connect('localhost', 'root', '');
        $this->assertType('resource', $c);
        $this->Users->setMySQLConnection($c);
        $this->assertType('resource', $this->Users->getMySQLConnection());
    }

    function testExistsIn() {
        $countryID = $this->Countries->create(array('NAME' => 'Ireland'));
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'NATIONALITY_ID' => $countryID));
        $nationality = $this->Users->getNationalityById($ID);
        $this->assertEquals('Ireland', $nationality);
        $this->assertEquals($countryID, $this->Users->getNationalityIdById($ID));

        $row = $this->Users->get(array('Format' => Terra_Data::SINGLE_RECORD_ARRAY, 'Rows' => 1));
        $this->assertEquals('Ireland', $row['NATIONALITY']);
        $this->assertEquals($countryID, $row['NATIONALITY_ID']);
    }

    function testOneRelationshipField() {
        $countryID = $this->Countries->create(array('NAME' => 'Portugal'));
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'COUNTRY_ID' => $countryID));
        $this->assertTrue((bool) $ID, "Creating a record with a related field that exists in the database should be allowed.");
        $this->assertEquals($countryID, $this->Users->getCountryIdById($ID), "Creating a record with a related field that exists in the database should automatically create a relationship between the two records.");
        $this->assertEquals('Portugal', $this->Users->getCountryById($ID), "Creating a record with a related field that exists in the database should automatically create a relationship between the two records.");

        $SecondCountryID = $this->Countries->create(array('NAME' => 'Ireland'));
        $this->Users->edit($ID, array('COUNTRY_ID' => $SecondCountryID));

        $this->assertEquals($SecondCountryID, $this->Users->getCountryIdById($ID));
        $this->assertEquals('Ireland', $this->Users->getCountryById($ID));

        $relationship = new Terra_Data($this->Users->getMySQLConnection(), 'countries_users', array('USER_ID', 'COUNTRY_ID'));
        $rel = $relationship->getWhere(array('USER_ID' => $ID), array('Format' => Terra_Data::SINGLE_RECORD_ARRAY, 'Rows' => 0, 'OrderBy' => array('Order' => 'ASC', 'Field' => 'USER_ID')));
        $this->assertEquals($SecondCountryID, $rel['COUNTRY_ID']);
        # Okay. So that means that there is a relationship. Now I want to delete the user, and see the relationship go away.
        $this->Users->Fields['IS_DELETED']['Disabled'] = true; # Disable IS_DELETED so it's possible to TRULY delete the record.
        $this->Users->delete($ID);
        $this->assertEquals(0, $relationship->count(array('USER_ID' => $ID)));

        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'COUNTRY_ID' => $countryID + 10)), "Creating a user with a related field that doesn't exist in the database (in this case COUNTRY) is not allowed.");
    }

    function testHasManyRelationshipField() {
        $articles = array(
            $this->Articles->create(array('NAME' => 'test 1', 'CONTENTS' => 'blah blah')),
            $this->Articles->create(array('NAME' => 'test 2', 'CONTENTS' => 'blah blah')),
            $this->Articles->create(array('NAME' => 'test 3', 'CONTENTS' => 'blah blah')),
        );
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'ARTICLES' => $articles));
        $this->assertTrue((bool) $ID);
        $this->assertEquals($articles, $this->Users->getArticlesById($ID));

        $articles[] = $this->Articles->create(array('NAME' => 'test 4', 'CONTENTS' => 'blah blah'));
        $this->Users->edit($ID, array('ARTICLES' => $articles));

        $this->assertEquals($articles, $this->Users->getArticlesById($ID));

        $relationship = new Terra_Data($this->Users->getMySQLConnection(), 'articles_users', array('USER_ID', 'ARTICLE_ID'));
        $rel = $relationship->count(array('USER_ID' => $ID));
        $this->assertEquals(count($articles), $rel);
        # Okay. So that means that there are relationships. Now I want to delete the user, and see the relationships go away.
        $this->Users->Fields['IS_DELETED']['Disabled'] = true; # Disable IS_DELETED so it's possible to TRULY delete the record.
        $this->Users->delete($ID);
        $this->assertEquals(0, $relationship->count(array('USER_ID' => $ID)));

        $this->setExpectedException('Terra_Data_ValidationException');
        $articles[] = 500;
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'ARTICLES' => $articles)));
    }

    function testDeletingRecordsWithRelatedRecordsDeletesRelatedRecordsAsWell() {
        $this->Users->Fields['IS_DELETED']['Disabled'] = true; # Disable IS_DELETED so it's possible to TRULY delete the record.

        $countryID = $this->Countries->create(array('NAME' => 'Portugal'));
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'COUNTRY_ID' => $countryID));

        $this->Users->Fields['COUNTRY_ID']['ForbidOrphans'] = true; # Tell Terra Data to delete the related country, if the user is deleted.

        $this->Users->delete($ID);
        $this->assertEquals(0, $this->Countries->count(array('ID' => $countryID))); # Assert that the country was deleted due to the user being deleted.

        $articles = array(
            $this->Articles->create(array('NAME' => 'test 1', 'CONTENTS' => 'blah blah')),
            $this->Articles->create(array('NAME' => 'test 2', 'CONTENTS' => 'blah blah')),
            $this->Articles->create(array('NAME' => 'test 3', 'CONTENTS' => 'blah blah')),
        );
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'ARTICLES' => $articles));

        $this->assertEquals(3, $this->Articles->count(array('ID' => $articles))); # Confirm that there's three articles before deleting the user.
        $this->Users->Fields['ARTICLES']['ForbidOrphans'] = true;
        $this->Users->delete($ID);
        $this->assertEquals(0, $this->Articles->count(array('ID' => $articles))); # Assert that the articles were deleted due to the user being deleted.
    }

    function testGettingOrphanRecordFieldReturnsDefaultFieldValue() {
        # In relationships, the default is used for the 'alias' field. As for the original field, its value is unchanged.
        /*
         * If, for example, a user had 'Ireland' as its country, but the record is deleted,
         * when trying to display it in a webpage, you'd want it to say "Unknown Country" or something, no?
         * I mean, I doubt you'd want an error occuring, or an empty string.
         */
        $this->Users->Fields['COUNTRY_ID']['Default'] = 'Unknown';
        $countryID = $this->Countries->create(array('NAME' => 'Ireland'));
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'COUNTRY_ID' => $countryID));
        $this->assertEquals('Ireland', $this->Users->getCountryById($ID));
        $this->Countries->delete($countryID);
        /* Okay, so now we've deleted the country that the user was related to.
         * Because our Countries Terra Data instance doesn't have any relationship data, it won't delete the relationship automatically,
         * thus causing all the users related to it to become orphans (i.e. you can't get their country anymore).
         *
         * NOTE: If it deleted the relationship automatically, the record would still be an orphan, since it would have no country.
         *
         * Terra Data uses the default values on orphan fields to avoid errors.
         */
        $this->Users->getCountryById($ID);
        $this->assertEquals('Unknown', $this->Users->getCountryById($ID));

        # But wait. What about users without a country, then? Let's test that it works for them too.

        $ID = $this->Users->create(array('USERNAME' => 'countryless_user', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->assertEquals('Unknown', $this->Users->getCountryById($ID));
    }

    function testInvalidSqlThrowsAnException() {
        $this->setExpectedException('Terra_Data_QueryException');
        $this->Users->query('SELECT * ERROR');
    }

    function testCountOfInvalidQueryIsZero() {
        $this->setExpectedException('Terra_Data_QueryException');
        $this->assertEquals(0, $this->Users->count(array('UNEXISTING' => 1)));
    }

    function testGetMultipleRecordsWithMethodQueryLanguage() {
        $this->assertEquals(50, $this->Users->createRandom(array(
                    'USERNAME' => array('bruno'),
                    'PASSWORD' => array('MYXPASS'),
                    'EMAIL' => array('random@terraduo.com')
                        ), 50));
        $this->assertEquals(50, count($this->Users->getByUsername('bruno', array('Rows' => 50))));
    }

    function testGetWhereWithArgumentOptions() {
        $this->Users->createRandom(array(
            'USERNAME' => array('bruno', 'mike', 'grace', 'iain'),
            'PASSWORD' => array('MYXPASS'),
            'EMAIL' => array('random@terraduo.com')
                ), 50);

        $rows = $this->Users->getWhere(Terra_Data::WhereFactory('users')->_and('USERNAME', array('bruno', 'mike', 'grace', 'iain')), array(
                    'Fields' => array('USERNAME', 'PASSWORD', 'ID'),
                    'OrderBy' => array('Field' => 'ID', 'Order' => 'DESC'),
                    'Page' => 2,
                    'Rows' => 30
                ));
        $this->assertEquals(20, count($rows));
        $this->assertEquals(20, $rows[0]['ID']);
        $this->assertEquals(3, count($rows[0]));
    }

    function testRequiredValidationRule() {
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => '', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testInArrayValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['InArray'] = array('black', 'blue');
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'black', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testRegexValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['Regex'] = '/^\+44[0-9]{10}$/';
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => '+441234567890', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => '+3531234567890', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testMatchesValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['Matches'] = 'PASSWORD';
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'bruno', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass')));
    }

    function testExistsInValidationRule() {
        $countryID = $this->Countries->create(array('NAME' => 'Ireland'));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'NATIONALITY_ID' => $countryID)));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'NATIONALITY_ID' => $countryID + 1)));
    }

    function testNotExistsInValidationRule() {
        $countryID = $this->Countries->create(array('NAME' => 'Ireland'));
        unset($this->Users->Fields['NATIONALITY_ID']['ValidationRules']['ExistsIn']);
        $this->Users->Fields['NATIONALITY_ID']['ValidationRules']['NotExistsIn'] = array(
            'Table' => 'countries',
            'Field' => 'ID',
            'Alias' => 'NATIONALITY',
            'ValueField' => 'NAME'
        );
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'NATIONALITY_ID' => $countryID + 1)));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'NATIONALITY_ID' => $countryID)));
    }

    function testCustomValidationRule() {
        $limitUsers = create_function('$Array', '
if ($Array["Terra_Data"]->count(array()) >= $Array["Arg"]) {
    $Array["Error"] = $Array["Field"]."DOESNOTWORK";
}
');
        $this->Users->Fields['USERNAME']['ValidationRules'][$limitUsers] = 5;
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testUniqueValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['Unique'] = true;
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testHashValidationRule() {
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $array = $this->Users->getPasswordByUsername('bruno', array('Rows' => 1));
        $this->assertEquals(hash('sha256', 'pass'), $array[0]['PASSWORD']);
        $this->assertEquals(hash('sha256', 'pass'), $this->Users->getPasswordByUsername('bruno', array('Format' => Terra_Data::SINGLE_RECORD_ARRAY, 'Rows' => 1)));
    }

    function testMinCharsValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['MinChars'] = 3;
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'b', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testAlphanumericValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['Alphanumeric'] = true;
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno333', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno 333', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno323!', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno ? ', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo')));
    }

    function testEmailValidationRule() {
        $this->Users->Fields['EMAIL']['ValidationRules']['Email'] = true;
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.co.uk')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno.terraduo@gmail.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo')));
    }

    function testMaxCharsValidationRule() {
        $this->Users->Fields['USERNAME']['ValidationRules']['MaxChars'] = 3;
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'br', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->assertTrue((bool) $this->Users->create(array('USERNAME' => 'bru', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
        $this->setExpectedException('Terra_Data_ValidationException');
        $this->assertFalse($this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com')));
    }

    function testCallingAnInvalidFunctionThrowsAnException() {
        $this->setExpectedException('Terra_DataException');
        $this->Users->invalidFunction();
    }

    function testDefaultValuesAreUsedWhenFieldsHaveNoValue() {
        $this->Users->Fields['USERNAME']['Default'] = 'somerandomusername';
        $insertID = $this->Users->create(array('PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->assertTrue((bool) $insertID);
        $this->assertEquals('somerandomusername', $this->Users->getUsernameById($insertID));
    }

    function testSettingUpdateIfEmptyToTrueMeansThatAFieldWillBeUpdatedEvenIfAnEmptyStringIsProvided() {
        $this->Users->Fields['USERNAME']['UpdateIfEmpty'] = true;
        $insertID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->setExpectedException('Terra_Data_ValidationException'); # Because it's trying to update to an empty value, which isn't allowed by the validation rules.
        $this->assertFalse($this->Users->edit($insertID, array('USERNAME' => '', 'PASSWORD' => 'pass')));
        $this->Users->Fields['USERNAME']['Required'] = false;
        $this->Users->edit($insertID, array('USERNAME' => '', 'PASSWORD' => 'pass')); # Should be allowed now.
        $this->assertEquals('', $this->Users->getUsernameById($insertID));
    }

    function testSettingUpdateIfEmptyToFalseMeansThatAFieldWillNotBeUpdatedIfAnEmptyStringIsProvided() {
        $this->Users->Fields['USERNAME']['UpdateIfEmpty'] = false;
        $insertID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com'));
        $this->Users->edit($insertID, array('USERNAME' => '', 'PASSWORD' => 'pass')); # Should be allowed now.
        $this->assertEquals('bruno', $this->Users->getUsernameById($insertID));
    }

    function testNotSettingPrimaryKeyDoesNotCauseError() {
        $con = mysql_connect('localhost', 'root', '');
        mysql_select_db('terra_data_test', $con);
        $users = new Terra_Data($con, 'users', array('ID', 'USERNAME'));
        $users->get(array('Rows' => 30));
    }

    function testFieldNameIsNotRequiredInFieldArray() {
        $con = mysql_connect('localhost', 'root', '');
        mysql_select_db('terra_data_test', $con);
        $users = new Terra_Data($con, 'users', array('ID' => array('PrimaryKey' => true), 'USERNAME'));
        $users->get(array('Rows' => 30));
    }

    function testValidationRulesAreRequiredForEveryField() {
        $con = mysql_connect('localhost', 'root', '');
        mysql_select_db('terra_data_test', $con);
        $users = new Terra_Data($con, 'users', array('ID' => array('PrimaryKey' => true), 'USERNAME'));
        $this->setExpectedException('Terra_Data_ValidationException');
        $users->create(array('USERNAME' => 'Joe'));
    }

    function testAddAndRemoveRelatedRecords() {
        $articles = array(
            $this->Articles->create(array('NAME' => 'test 1', 'CONTENTS' => 'blah blah')),
            $this->Articles->create(array('NAME' => 'test 2', 'CONTENTS' => 'blah blah')),
            $this->Articles->create(array('NAME' => 'test 3', 'CONTENTS' => 'blah blah')),
        );
        $ID = $this->Users->create(array('USERNAME' => 'bruno', 'PASSWORD' => 'pass', 'EMAIL' => 'bruno@terraduo.com', 'ARTICLES' => $articles));
        $this->assertTrue((bool) $ID);
        $this->assertEquals($articles, $this->Users->getArticlesById($ID));

        $article = $this->Articles->create(array('NAME' => 'test 4', 'CONTENTS' => 'blah blah'));
        $original_articles = $articles;
        $articles[] = $article;
        $original_articles2 = $articles;
        $this->Users->addArticlesById($ID, $article); # Works with an ID.
        $this->assertEquals($articles, $this->Users->getArticlesById($ID));
        $article1 = $this->Articles->create(array('NAME' => 'test 5', 'CONTENTS' => 'blah blah'));
        $article2 = $this->Articles->create(array('NAME' => 'test 6', 'CONTENTS' => 'blah blah'));
        $articles[] = $article1;
        $articles[] = $article2;
        $this->Users->addArticlesById($ID, array($article1, $article2)); # Works with an array of IDs.
        $this->assertEquals($articles, $this->Users->getArticlesById($ID));

        $this->Users->removeArticlesById($ID, array($article1, $article2)); # Works with an array of IDs.
        $this->assertEquals($original_articles2, $this->Users->getArticlesById($ID));
        $this->Users->removeArticlesById($ID, $article); # Works with an ID.
        $this->assertEquals($original_articles, $this->Users->getArticlesById($ID));
    }

    function testValidationRulesDoNotAlwaysRequireAnArgument() {
        $con = mysql_connect('localhost', 'root', '');
        mysql_select_db('terra_data_test', $con);
        $users = new Terra_Data($con, 'users', array('ID' => array('PrimaryKey' => true), 'USERNAME' => array('ValidationRules' => array('Unique')))); # Notice I didn't use Unique = true. That's the bug that we're fixing.
        $users->create(array('USERNAME' => 'bruno'));
        $this->setExpectedException('Terra_Data_ValidationException');
        $users->create(array('USERNAME' => 'bruno'));
    }

    function testUsingAnUnknownFieldCausesError() {
        $this->setExpectedException('Terra_DataException');
        $this->Users->create(array(
            'USERNAME' => 'bruno',
            'PASSWORD' => 'stuff',
            'EMAIL' => 'bruno@terraduo.com',
            'RANDOM_FIELD_THAT_DOESNT_EXIST' => 'yay'
        ));
    }

}

