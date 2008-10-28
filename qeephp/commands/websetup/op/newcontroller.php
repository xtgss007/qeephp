<?php
// $Id$

if (empty($_GET['name']))
{
	echo 'invalid parameters';
	exit;
}

require MANAGED_APP_ROOT_DIR . '/config/boot.php';

Q::import(dirname(__FILE__) . '/../../');
$generator = new QGenerator_Controller(ROOT_DIR);
$opts = array($_GET['name']);
$generator->execute($opts);
