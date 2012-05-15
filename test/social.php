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

	$adb->self_relate('user', 'friend', $user_1, $user_3);
	$adb->self_relate('user', 'friend', $user_2, $user_3);

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

	$comment_1=$adb->create('comment', array(
		'post'=>$post_1,
		'writer'=>$user_3,
		'text'=>'Which one?'
	));

	$comment_2=$adb->create('comment', array(
		'post'=>$post_1,
		'writer'=>$user_2,
		'text'=>'The one on the corner.'
	));

	$adb->relate('user', $user_3, 'comment', $comment_2, 'liked_comment');

	foreach ($adb->id_list('user') as $id) {
		$user=$adb->load('user', $id);
		echo '<h1>' . $user['name'] . '</h1>' . "\n";
		echo '<h3>Friends: </h1>' . "\n";
		echo '<ul>' . "\n";
		foreach ($user['friend'] as $fid) {
			$friend=$adb->load('user', $fid);
			echo '<li>' . $friend['name'] . '</li>' . "\n";
		}
		echo '</ul>' . "\n";
	}
