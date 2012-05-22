<?php

	$model=array(
		// User item definiton
		'user'=>array(
			'conf'=>array(
				// 7 digit length for max count of this item
				'len'=>7
			),
			'fields'=>array(
				'email'=>array('len'=>100),
				'password'=>array('type'=>'pass'),
				'name'=>array('len'=>50),
			),
			'has_many'=>array(
				// users has many posts and comments
				array('type'=>'post', 'foreign_name'=>'writer'),
				array('type'=>'comment', 'foreign_name'=>'writer')
			),
			'many_to_many'=>array(
				// users can like posts and comments
				'liked_post'=>array('type'=>'post', 'foreign_name'=>'liker'),
				'liked_comment'=>array('type'=>'comment', 'foreign_name'=>'liker')
			),
			// users can be friend with users
			'self_ref'=>array('friend')
		),

		'post'=>array(
			'conf'=>array(
				// 8 digit length for max count of this item
				'len'=>8
			),
			'fields'=>array(
				'text'=>array('len'=>5000),
			),
			'has_many'=>array(
				// users can make many comments per post
				array('type'=>'comment')
			)
		),

		'comment'=>array(
			'conf'=>array(
				// 10 digit length for max count of this item
				'len'=>10
			),
			'fields'=>array(
				'text'=>array('len'=>1000)
			)
		)
	);
