<?php

error_reporting(E_ALL);
session_start();

$_GET['page'] = isset($_GET['page']) ? $_GET['page'] : 1;
$_GET['rows'] = isset($_GET['rows']) ? $_GET['rows'] : 10;

# Start the Terra libraries
require_once('../lib/Terra/Autoload.php');

# Boot up the database
$connection = mysql_connect('localhost', 'root', '');
mysql_select_db('sg', $connection);

# Keep the connection in a global container so it can be accessed anywhere for creating new Terra_Data instances.
Terra_Data_Connection::setConnection($connection);
Terra_Data::logAllQueries();

$Table = new Terra_Data_Table('clients', true);

$Table->allowField('ID', 'Manage');
$Table->allowField('NAME', 'Manage');
$Table->allowField('FULL_NAME', 'Manage');

$Table->allowField('NAME', 'Create');
$Table->allowField('FULL_NAME', 'Create');

$Table->addValidationRule('NAME', 'Required');

$Table->setSingular('client');
$Table->setPlural('clients');
$Table->setHtmlTemplate('default');
$Table->setConfirmDelete(true);

$baseURL = 'http://localhost/terradata/examples/scaffolding.php';
$Table->setCreateUrl($baseURL . '?action=create');
$Table->setDeleteUrl($baseURL . '?action=delete&id={ID}');
$Table->setEditUrl($baseURL . '?action=edit&id={ID}');
$Table->setManageUrl($baseURL . '?action=manage&page={PAGE}&rows={ROWS_PER_PAGE}');
$Table->setRestoreUrl($baseURL . '?action=restore&id={ID}');
$Table->setViewUrl($baseURL . '?action=view&id={ID}');

$db = new Terra_Data($Table);

if (isset($_SESSION['page'])) {
    $db->setPage($_SESSION['page']);
} 

if (isset($_SESSION['rows'])) {
    $db->setRowsPerPage($_SESSION['page']);
}

switch (isset($_GET['action']) ? $_GET['action'] : 'manage') {
    case 'delete':
        $db->DeleteController($_GET['id']);
        break;
    case 'restore':
        $db->RestoreController($_GET['id']);
        break;
    case 'edit':
        $db->EditController($_GET['id']);
        break;
    case 'create':
        $db->CreateController();
        break;
    case 'view':
        $db->ViewController($_GET['id']);
        break;
    case 'manage':
        $_SESSION['page'] = $_GET['page'];
        $_SESSION['rows'] = $_GET['rows'];

        $db->ManageController($_GET['page'], $_GET['rows']);
        break;
}