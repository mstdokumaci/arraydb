<?php

	require_once('config.php');

	set_time_limit(200);

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

	$post_texts=array(
		'At Starbucks for a frappuccino.',
		'What a wonderful world.',
		'I love my cat!',
		'Have a nice day everyone.',
		'Will be in Istanbul on 17th.',
		'To be or not to be: that\'s the question.',
		'But oh the road is long, the stones that you are walking on have gone.',
		'And the night that you got locked in was the time to decide stop chasing shadows just enjoy the ride.'
	);

	$comment_texts=array(
		'WTF?',
		'You sure?',
		'Love this!',
		'Which one',
		'Totally agreed'
	);

	$name_count=count($names)-1;
	$surname_count=count($surnames)-1;
	$post_text_count=count($post_texts)-1;
	$comment_text_count=count($comment_texts)-1;

	$user_count=5;
	$friend_count=10;
	$post_count=10;
	$post_like_count=20;
	$comment_count=15;
	$comment_like_count=30;

	for ($i=1;$i<=$user_count;$i++) {
		$name=$names[mt_rand(0, $name_count)];
		$surname=$surnames[mt_rand(0, $surname_count)];

		$user[$i]=$adb->create('user', array(
			'email'=>strtolower($name) . '.' . strtolower($surname) . '@gmail.com',
			'password'=>strtolower($surname) . '936',
			'name' => $name . ' ' . $surname
		));
	}

	for ($i=1;$i<=$friend_count;$i++) {
		$adb->relate('user', 'friend', $user[mt_rand(1, $user_count)], $user[mt_rand(1, $user_count)]);
	}

	for ($i=1;$i<=$post_count;$i++) {
		$post[$i]=$adb->create('post', array(
			'writer'=>$user[mt_rand(1, $user_count)],
			'text'=>$post_texts[mt_rand(0, $post_text_count)]
		));
	}

	for ($i=1;$i<=$post_like_count;$i++) {
		$adb->relate('user', 'liked_post', $user[mt_rand(1, $user_count)], $post[mt_rand(1, $post_count)]);
	}

	for ($i=1;$i<=$comment_count;$i++) {
		$comment[$i]=$adb->create('comment', array(
			'post'=>$post[mt_rand(1, $post_count)],
			'writer'=>$user[mt_rand(1, $user_count)],
			'text'=>$comment_texts[mt_rand(0, $comment_text_count)]
		));
	}

	for ($i=1;$i<=$comment_like_count;$i++) {
		$adb->relate('user', 'liked_comment', $user[mt_rand(1, $user_count)], $comment[mt_rand(1, $comment_count)]);
	}

	for ($i=1;$i<=($comment_like_count/4);$i++) {
		do {
			$l_user=$adb->load('user', mt_rand(1, $user_count));
			$comments=$l_user['liked_comment'];
		} while (!count($comments));
		shuffle($comments);
		$adb->unrelate('user', 'liked_comment', $l_user['id'], array_shift($comments));
	}

	for ($i=1;$i<=($comment_count/4);$i++) {
		try {
			$adb->delete('comment', mt_rand(1, $comment_count), true);
		} catch (Exception $e) {
			echo $e->getMessage() . "<br />\n";
		}
	}

	for ($i=1;$i<=($post_like_count/4);$i++) {
		do {
			$l_user=$adb->load('user', mt_rand(1, $user_count));
			$posts=$l_user['liked_post'];
		} while (!count($posts));
		shuffle($posts);
		$adb->unrelate('user', 'liked_post', $l_user['id'], array_shift($posts));
	}

	for ($i=1;$i<=($post_count/4);$i++) {
		try {
			$adb->delete('post', mt_rand(1, $post_count), true);
		} catch (Exception $e) {
			echo $e->getMessage() . "<br />\n";
		}
	}

	for ($i=1;$i<=($friend_count/4);$i++) {
		do {
			$l_user=$adb->load('user', mt_rand(1, $user_count));
			$friends=$l_user['friend'];
		} while (!count($friends));
		shuffle($friends);
		$adb->unrelate('user', 'friend', $l_user['id'], array_shift($friends));
	}

	for ($i=1;$i<=($user_count/4);$i++) {
		try {
			$adb->delete('user', mt_rand(1, $user_count), true);
		} catch (Exception $e) {
			echo $e->getMessage() . "<br />\n";
		}
	}
