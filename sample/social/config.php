<?php

	require_once('../../lib/adb.php');
	require_once('model.php');

	$db_config=array(
		'hostname'=>'localhost', 'database'=>'social', 'username'=>'root', 'password'=>''
	);

	$cache_config=array(
		'type'=>'file', 'path'=>dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'cache'
	);

	arraydb\DB::init($db_config);
	arraydb\CACHE::init($cache_config);

	arraydb\ADB::init($model);

	$adb=arraydb\ADB::get_instance();
