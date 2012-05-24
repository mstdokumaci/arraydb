# Introduction

I've seen many PHP ORM libraries. Most of them makes you write a new class for each item you want to keep in DB. This seems repetition of same things to me. Extending this, extending that for no different logic.

Items have fields of data in similar kind and have similar relations among. So a well-written class can be used for all. If you need a library which makes things easier, this is my approach.

arrayDB ORM library has only 5 classes. You mostly use a singleton of one, others used internally, that's all. Caching and keeping cache synchronised with DB is all automated. You don't need to keep track of these.

To start using this library, you have to do 3 simple definitions.

- Define your data model (what items you need to keep, what are their fields and their relations with each other).
- Define your MySQL access.
- Define your cache config.

## Defining data model

All data model definiton is writing down an array like this:

	$model=array(
		'user'=>array(
			'conf'=>array('len'=>7),
			'fields'=>array(
				'name'=>array('len'=>50)
			),
			'has_many'=>array(
				'posts'=>array('type'=>'post', 'foreign_name'=>'writer')
			),
			'many_to_many'=>array(
				'liked_posts'=>array('type'=>'post', 'foreign_name'=>'likers'),
			),
			'self_ref'=>array('friends')
		),

		'post'=>array(
			'conf'=>array('len'=>10),
			'fields'=>array(
				'text'=>array('len'=>200),
				'view_count'=>array('type'=>'numeric', 'len'=>5)
				// default field type is text, define if numeric
			)
		)
	);

We have 2 items here: User and post.

Users has names, many written posts and many liked posts. Posts has texts, view counts and likers.

Users also has many users as friends.

With this model, we want to reach posts of auser as $user['posts'] and writers of apost as $post['writer']. This is a one-to-many relation.

We also want to reach liked posts of a user as $user['liked_posts'] and likers of a post as $post['likers']. This is a many-to-many relation.

Finally we want to reach friends of a user as $user['friends']. This is a self-referencing relation.

## Defining MySQL access

This is also writing an array like this:

	$db_config=array(
		'hostname'=>'localhost', 'database'=>'social', 'username'=>'root', 'password'=>''
	);

## Defining cache config

For now, we have three cache types implemented: APC, Memcached and plain text file.

To use APC, this config array is enough:

	$cache_config=array('type'=>'apc');

To use Memcached, you need to give some parameters:

	$cache_config=array('type'=>'memcached', 'host'=>'127.0.0.1', 'port'=>11211, 'timeout'=>1);

To use plain text files, you need to create a readable and writable directory and provide the absolute path:

	$cache_config=array('type'=>'file', 'path'=>'/tmp/my_project_cache');

There is an optional "prefix" parameter. It applied to cache keys if given.

## Start Using

Time to use what we all defined. We just need these lines to initialize the library.

	DB::init($db_config);
	CACHE::init($cache_config);

	ADB::init($model);
	$adb=ADB::get_instance();

## Creating Tables

This is a one time job. We tell the library to create the DB tables as required. It does all the hard work for relations and etc.

	$adb->create_tables();

We need to run this method once when we deploy it to somewhere and never again. It causes data loss if called after inserting data.

## Using Items

We have this $adb instance in hand. We will reach all the data through that.

### Creating Items

We provide the name of item and an array of data by field names to create an item.

	$uid1=$adb->create('user', array('name'=>'John'));
	$uid2=$adb->create('user', array('name'=>'Marry'));

	$pid1=$adb->create('post', array(
		'writer'=>$uid1,
		'text'=>'What a wonderful world!'
	));

	$pid2=$adb->create('post', array(
		'writer'=>$uid2,
		'text'=>'Life is beautiful!'
	));

### Creating Many-to-many Relations

First parameter is the name of item. Second parameter is the local name of related item. Third parameter is id of item. Fourth parameter is id of related item.

	$adb->relate('user', 'friends', $uid1, $uid2);

	$adb->relate('user', 'liked_posts', $uid1, $pid1); // self post liking :)
	$adb->relate('user', 'liked_posts', $uid1, $pid2);

	$adb->relate('user', 'liked_posts', $uid2, $pid1);

### Listing Data

We can list all users with posts and their likers in a simple loop:

	foreach ($adb->id_list('user') as $uid) {
		// load user
		$user=$adb->load('user', $uid);
		echo '<h1>' . $user['name'] . '</h1>' . "\n";

		echo '<h2>Posts: </h2>' . "\n";
		echo '<ul>' . "\n";

		foreach ($user['posts'] as $pid) {
			//load post of user
			$post=$adb->load('post', $pid);
			$likers=array();

			foreach ($post['likers'] as $lid) {
				// load liker of post
				$liker=$adb->load('user', $lid);
				$likers[]=$liker['name'];
			}
			$likers=(count($likers)) ? '<br />' . implode(', ', $likers) . ' liked.' : '';
			echo '<li>' . $post['text'] . ' ' . $likers . '</li>' . "\n";
		}

		echo '</ul>' . "\n";
	}

### Updating Items

We can update any field of any item like this:

	$user1=$adb->load('user', $uid1);
	$user1['name']='Jack';
	// no save method needed, save and keeping cache updated is all automated.

If we need to update more than a field at a time, this is the alternative:

	$post1=$adb->load('post', $pid1);
	$post1->update(array('writer'=>$uid2, 'text'=>'Not a wonderful world!'));

### Removing Relations

It's just same as relating:

	$adb->unrelate('user', 'friends', $uid1, $uid2);

	$adb->unrelate('user', 'liked_posts', $uid1, $pid1);

### Removing Items

We can delete items with keeping belongings or removing belongings.

	$adb->delete('user', $uid1);
	// user deleted, posts remain unowned.

	$adb->delete('user', $uid1, true);
	// user and all posts of user deleted.

### Querying More

We want to get 5 most liked posts for example. All we need is this:

	foreach ($adb->id_list('post', false, 'comment DESC', 5) as $pid) {
		$post=$adb->load('post', $pid);
		// do anything with post
	}

Or we want to get posts of a user with more than 5 likes. Here it is:

	$user=$adb->load('user', $uid1);
	foreach ($user->id_list('post', 'view_count>5') as $pid) {
		$post=$adb->load('post', $pid);
		// do anything with post
	}

# The Main Goal

For a separate post page, our code will be this simple:

	$post=$adb->load('post', $pid1);
	$writer=$adb->load('user', $post['writer']);

	echo $writer['name'] . ' wrote' . "<br />\n";
	echo $post['text'] . "<br />\n";

	$post['view_count']++;
	// yes, increasing view_count is this simple

Is there any queries or cache logics in this code? No, that's the main goal, simplicity.

There are a lot of famous alternatives to use. They are documented better and supported better. This library is not one of them, not a well oiled machine yet. But the most simple and easy to learn approach in my opinion. If the goal of ORM libraries is to isolate coder from DB logic, this one is the most assertive newcoming. jQuery was the easiest javascript framework and became a standard just because of that. So, an easy to use PHP ORM library has a chance that way.

All ideas and contributions are welcomed.

Thanks for the interest, sorry for inadequate English.

Mustafa Dokumacı