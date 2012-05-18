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
