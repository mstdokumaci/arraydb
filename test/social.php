<?php

	require_once('../lib/adb.php');
	require_once('social_model.php');

	$db_config=array(
		'hostname'=>'localhost', 'database'=>'social', 'username'=>'root', 'password'=>''
	);

	DB::init($db_config);
	CACHE::init();

	ADB::init($model);

	$adb=ADB::get_instance();

	$adb->create_tables();

	echo 'a';