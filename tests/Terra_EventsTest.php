<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../Terra/Autoload.php';

class Terra_EventsTest extends PHPUnit_Framework_TestCase {

    function testGettingCallbacksForAnEventBeforeAnyCallbacksAreAddedReturnsEmptyArray() {
        $this->assertEquals(array(), Terra_Events::getCallbacks('testEvent'));
    }

    function testStringsWithFunctionNamesCanBeAddedAsCallbacks() {
        $functionName = create_function('', 'return "wohoo";');
        Terra_Events::addCallback('testEvent', $functionName);
        $this->assertEquals(array($functionName), Terra_Events::getCallbacks('testEvent'));
    }

    function testArraysWithObjectAndMethodStringCanBeAddedAsCallbacks() {
        $array = array(new SimpleObject(), 'stuff');
        Terra_Events::addCallback('arraysAsCallbacks', $array);
        $this->assertEquals(array($array), Terra_Events::getCallbacks('arraysAsCallbacks'));
    }

    function testArraysWithoutObjectAndAMethodStringCannotBeAddedAsCallbacks() {
        $this->setExpectedException('Exception');
        Terra_Events::addCallback('noBadArrays', array(1,2,3));
        $this->assertEquals(array(), Terra_Events::getCallbacks('noBadArrays'));
    }

    function testIntegersCannotBeAddedAsCallbacks() {
        $this->setExpectedException('Exception');
        Terra_Events::addCallback('newEvent', 50);
        $this->assertEquals(array(), Terra_Events::getCallbacks('newEvent'));
    }

    /**
     * @depends testStringsWithFunctionNamesCanBeAddedAsCallbacks
     * @depends testArraysWithObjectAndMethodStringCanBeAddedAsCallbacks
     */
    function testTriggeringAnEventCallsAllItsCallbacksAndArgumentsPassedByReferenceCanBeModifiedByCallbacks() {
        $functionName = create_function('$args', ' $args["value"] = "wohoo";');
        Terra_Events::addCallback('MyTestEvent', $functionName);
        Terra_Events::addCallback('MyTestEvent', array(new SimpleObject(), 'stuff'));
        $value = 'nu';
        $value2 = 'nu2';
        $array = array(
                'value' => &$value,
                'value2' => &$value2
        );
        Terra_Events::trigger('MyTestEvent', $array);
        $this->assertEquals("wohoo", $value);
        $this->assertEquals("wohoo 2", $value2);
    }

    function testResettingAnEventReturnsItToTheInitialConditions() {
        $functionName = create_function('$args', ' $args["value"] = "wohoo";');
        Terra_Events::addCallback('A Test Event', $functionName);
        Terra_Events::addCallback('A Test Event', array(new SimpleObject(), 'stuff'));

        Terra_Events::resetEvent('A Test Event');
        $this->assertEquals(array(), Terra_Events::getCallbacks('A Test Event'));
    }

    function testQuickEventAddsACallbackAndTriggersAnEventAllInOne() {
        $functionName = create_function('$args', ' $args["value"] = "wohoo";');

        $value = 'nu';

        $array = array('value' => &$value);

        Terra_Events::QuickEvent($functionName, $array);
        
        $this->assertEquals("wohoo", $value);
    }

    function testTriggeringAnEmptyEventDoesNothing() {
        $var = array();
        Terra_Events::trigger('EmptyEvent', $var);
    }
}

class SimpleObject {
    function stuff($args) {
        $args["value2"] = "wohoo 2";
    }
}