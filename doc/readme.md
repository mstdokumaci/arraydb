# Introduction

I've seen many PHP ORM libraries. Most of them makes you write a new class for each item you want to keep in DB. This seems repetition of same things to me. Extending this, extending that for no different logic.

Items have fields of data in similar kind and have similar relations among. So a well-written class can be used for all. If you need a library which makes things easier, this is my approach.

arrayDB ORM library has only 5 classes. You mostly use a singleton of one, others used internally, that's all. Caching and keeping cache synchronised with DB is all automated. You don't need to keep track of these.

To start using this library, you have to do 3 simple definitions.

- Define your data model (what items you need to keep, what are their fields and relations with each other).
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
			)
		),

		'post'=>array(
			'conf'=>array('len'=>10),
			'fields'=>array(
				'text'=>array('len'=>200)
			)
		)
	);

We have 2 items here. User and post. Users has names, many written posts and many liked posts. Posts has texts and likers.

With this model, we want to reach posts of auser as $user['posts'] and writers of apost as $post['writer']. This is a one-to-many relation.

We also want to reach liked posts of a user as $user['liked_posts'] and likers of a post as $post['likers']. This is a many-to-many relation.

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

First parameter is the name of first item. Second parameter is the local name of second item in first item. Third parameter is id of first item. Fourth parameter is id of second item.

	$adb->relate('user', 'liked_posts', $uid1, $pid1); // self post liking :)
	$adb->relate('user', 'liked_posts', $uid1, $pid2);

	$adb->relate('user', 'liked_posts', $uid2, $pid1);

### Listing All Data

