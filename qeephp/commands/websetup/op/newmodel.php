<?php
// $Id$

if (empty($_GET['name']) || empty($_GET['table_name']))
{
	echo 'invalid parameters';
	exit;
}

require MANAGED_APP_ROOT_DIR . '/config/boot.php';

Q::import(dirname(__FILE__) . '/../../');
$generator = new QGenerator_Model(ROOT_DIR);
$opts = array($_GET['name'], $_GET['table_name']);
$generator->execute($opts);
