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
