<?php
# Define the starting time.
define('TIME_START', microtime());
# I want to see and fix all possible errors.
error_reporting(E_ALL);
# Register the autoloader.
require_once dirname(__FILE__).'/../TD/Autoload.php';

# Start the layout (minimum configuration)
$Layout = new TD_Layout();
$Layout->setLayoutFile(dirname(__FILE__).'/../templates/BlackWhiteAndRed/layout.php');

# Set additional configuration
$Layout->setStartTime(TIME_START);
$Layout->setBaseURL('http://localhost/TerraDuoLibraries/');
$templatedir = 'http://localhost/TerraDuoLibraries/templates/BlackWhiteAndRed/';
$Layout->addStylesheet($templatedir.'default.css');
$Layout->addStylesheet($templatedir.'red.css');
$Layout->addJavascript($templatedir.'jquery.1.4.1.min.js');
$Layout->addJavascript($templatedir.'jquery.cookie.js');
$Layout->addJavascript($templatedir.'main.js');
$Layout->Footer = "Copyright &copy; 2010 Bruno De Barros";
$Layout->Title = "Example of a TD_Layout.";
$Layout->HomeLink = 'http://localhost/TerraDuoLibraries/Examples/Layout.php';

# Start the output buffer
$Layout->start();

$Layout->ShowExtraInfo = true;
$Layout->QueryCount = 0;
$Layout->LastQuery = 'meh';


### Example of using a TD_Layout inside a TD_Layout.
$ExamplePage = new TD_Layout();
$ExamplePage->setLayoutFile('ExampleFileData/Layout_ExamplePage.php');
$ExamplePage->SomeVariable = 'Some variable for testing.';
$ExamplePage->end();
###


# Flush the layout and its contents.
$Layout->end();