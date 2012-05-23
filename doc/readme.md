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
				'liked_posts'=>array('type'=>'post', 'foreign_name'=>'liker'),
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

With this model, we want to