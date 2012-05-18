<?php

	require_once('config.php');

	set_time_limit(100);

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

	for ($i=1;$i<201;$i++) {
		$name=$names[mt_rand(0, $name_count)];
		$surname=$surnames[mt_rand(0, $surname_count)];

		$user[$i]=$adb->create('user', array(
			'email'=>strtolower($name) . '.' . strtolower($surname) . '@gmail.com',
			'password'=>strtolower($surname) . '936',
			'name' => $name . ' ' . $surname
		));
	}

	for ($i=1;$i<401;$i++) {
		$adb->relate('user', 'friend', $user[mt_rand(1,200)], $user[mt_rand(1, 200)]);
	}

	for ($i=1;$i<401;$i++) {
		$post[$i]=$adb->create('post', array(
			'writer'=>$user[mt_rand(1,200)],
			'text'=>$post_texts[mt_rand(0, $post_text_count)]
		));
	}

	for ($i=1;$i<801;$i++) {
		$adb->relate('user', 'liked_post', $user[mt_rand(1,200)], $post[mt_rand(1, 400)]);
	}

	for ($i=1;$i<601;$i++) {
		$comment[$i]=$adb->create('comment', array(
			'post'=>$post[mt_rand(1,400)],
			'writer'=>$user[mt_rand(1,200)],
			'text'=>$comment_texts[mt_rand(0, $comment_text_count)]
		));
	}

	for ($i=1;$i<1201;$i++) {
		$adb->relate('user', 'liked_comment', $user[mt_rand(1,200)], $comment[mt_rand(1, 600)]);
	}

	for ($i=1;$i<401;$i++) {
		do {
			$l_user=$adb->load('user', mt_rand(1, 200));
			$comments=$l_user['liked_comment'];
		} while (!count($comments));
		$adb->unrelate('user', 'liked_comment', $l_user['id'], $comments[mt_rand(0, count($comments)-1)]);
	}

	for ($i=1;$i<151;$i++) {
		$adb->delete('comment', mt_rand(0, 600));
	}

	for ($i=1;$i<201;$i++) {
		do {
			$l_user=$adb->load('user', mt_rand(1, 200));
			$posts=$l_user['liked_post'];
		} while (!count($posts));
		$adb->unrelate('user', 'liked_post', $l_user['id'], $posts[mt_rand(0, count($posts)-1)]);
	}

	for ($i=1;$i<101;$i++) {
		$adb->delete('post', mt_rand(0, 400));
	}

	for ($i=1;$i<101;$i++) {
		do {
			$l_user=$adb->load('user', mt_rand(1, 200));
			$friends=$l_user['friend'];
			} while (!count($friends));
		$adb->unrelate('user', 'friend', $l_user['id'], $friends[mt_rand(0, count($friend)-1)]);
	}

	for ($i=1;$i<51;$i++) {
		$adb->delete('user', mt_rand(0, 200));
	}
