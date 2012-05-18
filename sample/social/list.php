<?php

	require_once('config.php');

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

	echo '<h1>Top 10 Users with Most Posts</h1>' . "\n";
	echo '<ul>' . "\n";
	foreach ($adb->id_list('user', false, 'post DESC', 10) as $id) {
		$user=$adb->load('user', $id);
		echo '<li>' . $user['name'] . '(' . count($user['post']) . ')</li>' . "\n";
	}
	echo '</ul>' . "\n";

	echo '<h1>Top 10 Posts with Most Likes</h1>' . "\n";
	echo '<ul>' . "\n";
	foreach ($adb->id_list('post', false, 'liker DESC', 10) as $id) {
		$post=$adb->load('post', $id);
		echo '<li>' . $post['text'] . '(' . count($post['liker']) . ')</li>' . "\n";
	}
	echo '</ul>' . "\n";

	echo '<h1>Top 10 Posts with Most Comments</h1>' . "\n";
	echo '<ul>' . "\n";
	foreach ($adb->id_list('post', false, 'comment DESC', 10) as $id) {
		$post=$adb->load('post', $id);
		echo '<li>' . $post['text'] . '(' . count($post['comment']) . ')</li>' . "\n";
	}
	echo '</ul>' . "\n";
	echo '<h1>Top 10 Comments with Most Likes</h1>' . "\n";
	echo '<ul>' . "\n";
	foreach ($adb->id_list('comment', false, 'liker DESC', 10) as $id) {
		$comment=$adb->load('comment', $id);
		echo '<li>' . $comment['text'] . '(' . count($comment['liker']) . ')</li>' . "\n";
	}
	echo '</ul>' . "\n";
