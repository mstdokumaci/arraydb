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

	$post_1=$adb->create('post', array(
		'writer'=>$user_2,
		'text'=>'At Starbucks for a Frappucino'
	));

	$post_2=$adb->create('post', array(
		'writer'=>$user_3,
		'text'=>'What a wonderful world!'
	));

	$post_3=$adb->create('post', array(
		'writer'=>$user_2,
		'text'=>'I love being social.'
	));

	$adb->relate('user', $user_1, 'post', $post_2, 'liked_post');
	$adb->relate('user', $user_1, 'post', $post_3, 'liked_post');
	$adb->relate('user', $user_3, 'post', $post_3, 'liked_post');
	$adb->relate('user', $user_3, 'post', $post_1, 'liked_post');
	$adb->relate('user', $user_2, 'post', $post_2, 'liked_post');

	echo 'a';