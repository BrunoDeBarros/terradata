<?php

error_reporting(E_ALL);

$_GET['page'] = isset($_GET['page']) ? $_GET['page'] : null;
$_GET['rows'] = isset($_GET['rows']) ? $_GET['rows'] : null;

# Start the Terra libraries
require_once('../lib/Terra/Autoload.php');

# Boot up the database
$connection = mysql_connect('localhost', 'root', '');
mysql_select_db('terra_data_test', $connection);

# Keep the connection in a global container so it can be accessed anywhere for creating new Terra_Data instances.
Terra_Data_Connection::setConnection($connection);

include('../tests/Sample_Terra_Data_Configs.php');

$db = new Terra_Data($connection, 'users', $Fields);
$baseURL = 'http://localhost/terradata/examples/scaffolding.php';
$urls = array(
    'Create' => $baseURL.'?action=create',
    'Edit' => $baseURL.'?action=edit&id={ID}',
    'Restore' => $baseURL.'?action=restore&id={ID}',
    'Delete' => $baseURL.'?action=delete&id={ID}',
    'Manage' => $baseURL.'?action=manage&page={PAGE}&rows={ROWS_PER_PAGE}',
);
$scaffolding = new Terra_Data_Scaffolding($db, $urls);

switch (isset($_GET['action']) ? $_GET['action'] : 'manage') {
    case 'delete':
        $scaffolding->DeleteController($_GET['id']);
        break;
    case 'restore':
        $scaffolding->RestoreController($_GET['id']);
        break;
        break;
    case 'edit':
        $scaffolding->EditController($_GET['id']);
        break;
    case 'create':
        $scaffolding->CreateController();
        break;
    case 'view':
        $scaffolding->ViewController($_GET['id']);
        break;
    case 'manage':
        if ($_GET['page'] == 0) {
            $_GET['page'] = 1;
        }
        if ($_GET['rows'] == 0) {
            $_GET['rows'] = 10;
        }
        $scaffolding->ManageController($_GET['page'], $_GET['rows']);
        break;
}