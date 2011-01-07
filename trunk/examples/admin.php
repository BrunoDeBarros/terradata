<?php
error_reporting(E_ALL);
define('TIME_START', microtime());
session_start();
require_once('TD/TD_Table_Scaffolding.php');
require_once('TD/TD_MySQL_Table_Manager.php');
require_once('TD/TD_Admin_User_Handler.php');
require_once('TD/TD_Admin.php');
require_once 'SG_TD_Admin_Users.php';
define('CLIENT_ID', 2);
// All functions to set data in the Table Scaffolding
$Scaffolding = new TD_Table_Scaffolding();
if (!isset($_GET['db'])) {
    $_GET['db'] = 'sg';
}
if (!isset($_GET['table'])) {
    $_GET['table'] = 'statuses';
}
if (!isset ($_SESSION['page'])) {
    $_SESSION['page'] = 1;
}
if (!isset ($_SESSION['rows'])) {
    $_SESSION['rows'] = 10;
}

$Scaffolding->setTableConfigs('databases/'.$_GET['db'].'/'.$_GET['table'].'.php');

$connection = mysql_connect('localhost', 'root', '');
mysql_select_db($_GET['db'], $connection);

$Scaffolding->setDatabaseConnection($connection);
$Scaffolding->setFormTemplate('templates/default/form.php'); // The table's form.
$Scaffolding->setManageTemplate('templates/default/manage.php');
$Scaffolding->setViewTemplate('templates/default/view.php');
#$Scaffolding->setLayout('templates/default/layout.php'); // If Layout is not set, no layout is used.
$Scaffolding->setPage($_SESSION['page']);
$Scaffolding->setGlobalWhereClause(array('CLIENT_ID' => CLIENT_ID));
$Scaffolding->setRowsPerPage($_SESSION['rows']);

$Admin = new TD_Admin();
$Admin->setShowExtraInfo(true);
$Admin->setLoginRequired(true);
$Admin->setLoginFormFile('templates/default/loginForm.php');
$Admin->setUsersHandler(new SG_TD_Admin_Users());
$Admin->setLayoutFile('templates/default/layout.php');
$Admin->setAdminFooter('Staff Guardian &copy; 2010 <a href="http://www.turnkeyit.co.uk" title="TurnKey I.T Solutions">TurnKey I.T Solutions</a>. Developed by <a href="http://terraduo.com" title="Terra Duo">Terra Duo</a> for <a href="http://www.turnkeyit.co.uk" title="TurnKey I.T Solutions">TurnKey I.T Solutions</a>');
$Admin->setAdminTitle('Staff Guardian Administration');
$baseURL = 'http://localhost/table%20handler/';
$Admin->setLoginLink($baseURL.'index.php?action=login');
$Admin->setLogoutLink($baseURL.'index.php?action=logout');
$Admin->setHomeLink($baseURL);
$Admin->addStylesheet($baseURL.'templates/default/default.css');
$Admin->addStylesheet($baseURL.'templates/default/red.css');
$Admin->addStylesheet('http://localhost/sg/public/sg/notifications.css');
$Admin->addJavaScript($baseURL.'templates/default/jquery.1.4.1.min.js');
$Admin->addJavaScript($baseURL.'templates/default/jquery.cookie.js');
$Admin->addJavaScript($baseURL.'templates/default/main.js');
$Admin->addIeInclude($baseURL.'templates/default/ie6.js');
$Admin->addIeInclude($baseURL.'templates/default/ie6.css', 'lte', 6, false);
$Admin->addNavigationLink($baseURL, 'Home');
$Admin->addNavigationLink($baseURL.'index.php?action=manage&table=statuses', 'Manage Statuses');
$Admin->addNavigationLink($baseURL.'index.php?action=manage&table=users', 'Manage Users');

$Admin->setScaffolding($Scaffolding);

if (!isset ($_GET['action'])) {
    $_GET['action'] = 'index';
}

switch($_GET['action']) {
    case 'delete':
        $Admin->DeleteController($_GET['ID']);
        break;
    case 'restore':
        $Admin->RestoreController($_GET['ID']);
        break;
        break;
    case 'edit':
        $Admin->EditController($_GET['ID']);
        break;
    case 'create':
        $Admin->CreateController();
        break;
    case 'view':
        $Admin->ViewController($_GET['ID']);
        break;
    case 'manage':
        if (!isset($_GET['page'])) {
            $_GET['page'] = 1;
        }
        if (!isset($_GET['rows'])) {
            $_GET['rows'] = 10;
        }
        $_SESSION['page'] = $_GET['page'];
        $_SESSION['rows'] = $_GET['rows'];
        $Admin->ManageController($_GET['page'], $_GET['rows']);
        break;
    case 'login':
        $Admin->login();
        break;
    case 'logout':
        $Admin->logout();
        break;
    default:
        $Admin->index();
        break;
}