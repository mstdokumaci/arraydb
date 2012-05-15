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

	$user_1=$adb->create('user', array(
		'email'=>'john@gmail.com',
		'password'=>'123john',
		'name'=>'John'
	));

	$user_2=$adb->create('user', array(
		'email'=>'jane@gmail.com',
		'password'=>'123jane',
		'name'=>'Jane'
	));

	$user_3=$adb->create('user', array(
		'email'=>'peter@gmail.com',
		'password'=>'123peter',
		'name'=>'Peter'
	));

	echo 'a';