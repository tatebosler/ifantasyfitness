<?php
header("Location: http://www.ifantasyfitness.com"); # No humans!
require('php/db.php');

$now = time();
$lastWeek = $now - (7 * 24 * 60 * 60); # Last week

# Grab caps
$types = array('run','run_team','rollerski','walk','hike','bike','swim','paddle','strength','sports');
$capped_types = array();
$cap_fetcher = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'cap\_week\_%'");
while($type = mysqli_fetch_array($cap_fetcher)) {
	$capped_types[substr($type['name'], 9)] = $type['value'];
}

# Goal of script: Allow people to continue scoring points above their caps.
$people_grab = @mysqli_query($db, "SELECT * FROM tMembers");
$checked_people = array();
while($person = mysqli_fetch_array($people_grab)) {
	if(!in_array($person['user'], $checked_people)) {
		$checked_people[] = $person['user'];
		$id = $person['user'];
		$team = $person['team'];
		$update = "UPDATE tMembers SET ";
		foreach($capped_types as $type => $max) {
			$current_value_col = "week_".$type;
			$current_value = $person[$current_value_col];
			$new_value = 0;
			$records = @mysqli_query($db, "SELECT * FROM records WHERE user=$id AND team=$team AND timestamp >= $lastWeek");
			while($record = mysqli_fetch_array($records)) {
				$new_value += $type;
			}
			if($new_value > $max) $new_value = $max;
			$update .= "$current_value_col=$new_value, ";
		}
		$update .= " flag=0 WHERE user=$id AND team=$team";	
		$updater = @mysqli_query($db, $update);
	}
}
?>