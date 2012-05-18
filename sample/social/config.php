<?php

	require_once('../../lib/adb.php');
	require_once('model.php');

	$db_config=array(
		'hostname'=>'localhost', 'database'=>'social', 'username'=>'root', 'password'=>''
	);

	$cache_config=array(
		'type'=>'file', 'path'=>dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'cache'
	);

	DB::init($db_config);
	CACHE::init($cache_config);

	ADB::init($model);

	$adb=ADB::get_instance();
