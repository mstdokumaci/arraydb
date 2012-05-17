<?php

	require_once('../lib/adb.php');
	require_once('social_model.php');

	$db_config=array(
		'hostname'=>'localhost', 'database'=>'social', 'username'=>'root', 'password'=>''
	);

	$cache_config=array(
		'type'=>'file', 'path'=>dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'cache'
	);

	DB::init($db_config);
	CACHE::init($cache_config);

	ADB::init($model);

	$adb=ADB::get_instance();

	$adb->create_tables();

	$names=array(
		'Jacob', 'Sophia', 'Mason', 'Isabella', 'William', 'Emma', 'Jayden', 'Olivia',
		'Noah', 'Ava', 'Michael', 'Emily', 'Ethan', 'Abigail', 'Alexander', 'Madison',
		'Aiden', 'Mia', 'Daniel', 'Chloe', 'Anthony', 'Elizabeth', 'Matthew', 'Ella',
		'Elijah', 'Addison', 'Joshua', 'Natalie', 'Liam', 'Lily'
	);

	$surnames=array(
		'Smith', 'Johnson', 'Wiliiams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson',
		'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin',
		'Thompson', 'Garcia', 'Martinez', 'Robinson', 'Clark', 'Lewis', 'Lee', 'Walker'
	);

	$name_count=count($names)-1;
	$surname_count=count($surnames)-1;

	for ($i=1;$i<100;$i++) {
		$name=$names[mt_rand(0, $name_count)];
		$surname=$surnames[mt_rand(0, $surname_count)];

		$user[$i]=$adb->create('user', array(
			'email'=>strtolower($name) . '.' . strtolower($surname) . '@gmail.com',
			'password'=>strtolower($surname) . '936',
			'name' => $name . ' ' . $surname
		));
	}

/*
	$adb->relate('user', 'friend', $user_1, $user_3);
	$adb->relate('user', 'friend', $user_2, $user_3);

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

	$adb->relate('user', 'liked_post', $user_1, $post_2);
	$adb->relate('user', 'liked_post', $user_1, $post_3);
	$adb->relate('user', 'liked_post', $user_3, $post_3);
	$adb->relate('user', 'liked_post', $user_3, $post_1);
	$adb->relate('user', 'liked_post', $user_2, $post_2);

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

	$adb->relate('user', 'liked_comment', $user_3, $comment_2);

*/

	foreach ($adb->id_list('user') as $id) {
		$user=$adb->load('user', $id);
		echo '<h1>' . $user['name'] . '</h1>' . "\n";

		echo '<h2>Friends: </h1>' . "\n";
		echo '<ul>' . "\n";
		foreach ($user['friend'] as $fid) {
			$friend=$adb->load('user', $fid);
			echo '<li>' . $friend['name'] . '</li>' . "\n";
		}
		echo '</ul>' . "\n";

		echo '<h2>Posts: </h1>' . "\n";
		echo '<ul>' . "\n";
		foreach ($user['post'] as $pid) {
			$post=$adb->load('post', $pid);
			$likers=array();
			foreach ($post['liker'] as $liker) {
				$liker=$adb->load('user', $liker);
				$likers[]=$liker['name'];
			}
			$likers=(count($likers)) ? '<br />' . implode(', ', $likers) . ' liked.' : '';
			echo '<li>' . $post['text'] . ' ' . $likers . '</li>' . "\n";
			if (empty($post['comment'])) continue;

			echo '<div style="margin-left:20px;">' . "\n";
			echo '<h3>Comments: </h1>' . "\n";
			echo '<ul>' . "\n";
			foreach ($post['comment'] as $cid) {
				$comment=$adb->load('comment', $cid);
				$commenter=$adb->load('user', $comment['writer']);
				$likers=array();
				foreach ($comment['liker'] as $liker) {
					$liker=$adb->load('user', $liker);
					$likers[]=$liker['name'];
				}
				$likers=(count($likers)) ? '<br />' . implode(', ', $likers) . ' liked.' : '';
				echo '<li>' . $commenter['name'] . ' commented: ' . $comment['text'] . ' ' . $likers . '</li>' . "\n";
			}
			echo '</ul>' . "\n";
			echo '</div>' . "\n";
		}
		echo '</ul>' . "\n";
	}
