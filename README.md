# arrayDB, new easy PHP ORM

Define your data model as an array, reach your data as an array.

For a complete example, please have a look at [this folder](https://github.com/mstdokumaci/arraydb/tree/master/sample/social).

For a sneak peak, follow the document.

## Simple usage

### Getting some relational data

	foreach($adb->id_list('user') as $uid) {
		$user=$adb->load('user', $uid);
		echo 'User Name: ' . $user['name'] . "<br />\n";

		foreach ($user['friend'] as $fid) {
			$friend=$adb->load('user', $fid);
			echo 'Friend Name: ' . $friend['name'] . "<br />\n";
		}

		echo "<br />\n";
	}

### Adding / updating some data

	$uid1=$adb->create('user', array('name'=>'Mustafa'));

	$user1=$adb->load('user', $uid1);

	$user1['name']='Mercan';
	// no save needed, data saved automatically


### Setting relations

	$uid2=$adb->create('user', array('name'=>'Mustafa'));

	$adb->relate('user', 'friend', $uid1, $uid2);

### Removing items

	$adb->delete('user', $uid1);
