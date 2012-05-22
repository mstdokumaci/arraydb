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
