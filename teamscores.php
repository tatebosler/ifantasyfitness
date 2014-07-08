<?php
header("Location: http://www.ifantasyfitness.com"); # No humans!
require('php/db.php');

# For records with flag: Update the individual score
$user_grab = @mysqli_query($db, "SELECT * FROM records WHERE flag=1");
$checked_users = array(); #Performance - we'll only check each user once.
while($record = mysqli_fetch_array($user_grab)) {
	$uid = $record['user'];
	$rt = $record['team'];
	if(!in_array($uid.'-'.$rt, $checked_users)) { # If the user/team combo hasn't been checked yet, let's process them
		$checked_users[] = $uid.'-'.$rt; # Lock it from future checks
		$total = 0;
		$run = 0;
		$types = array('run' => 0, 'run_team' => 0, 'walk' => 0, 'hike' => 0, 'bike' => 0, 'swim' => 0, 'paddle' => 0, 'rollerski' => 0, 'strength' => 0, 'sports' => 0); # Init array
		$record_grab = @mysqli_query($db, "SELECT * FROM records WHERE user=$uid AND team=$rt");
		$query_text = "UPDATE tMembers SET flag=1";
		while($record = mysqli_fetch_array($record_grab)) {
			$total += $record['total'];
			$run += ($record['run'] + $record['run_team']) * $record['altitude'];
			foreach($types as $type => &$value) {
				$value += $record[$type . '_p'];
			}
			unset($value);
		}
		foreach($types as $type => $value) {
			$query_text .= ", `stat_$type`=$value";
		}
		$query_text .= ",season_total=$total, season_run=$run WHERE user=$uid AND team=$rt";
		$user_update = @mysqli_query($db, $query_text);
	}
}
@mysqli_query($db, "UPDATE records SET flag=0");

# For teams with id > 1: Update the team score.
$people_update = @mysqli_query($db, "SELECT * FROM tMembers WHERE flag = 1");
$checked_teams = array(); # Performance reasons

while($person = mysqli_fetch_array($people_update)) {
	$tid = $person['team'];
	if(!in_array($tid,$checked_teams)) {
		$checked_teams[] = $tid; # Don't check teams twice - Improves performance
		$total = 0;
		$run = 0;
		$record_grab = @mysqli_query($db, "SELECT * FROM records WHERE team=$tid");
		while($record = mysqli_fetch_array($record_grab)) {
			$total += $record['total'];
			$run += ($record['run'] + $record['run_team']) * $record['altitude'];
		}
		$team_update = @mysqli_query($db, "UPDATE tData SET total=$total, running=$run WHERE id=$tid");
		$deflag = @mysqli_query($db, "UPDATE tMembers SET flag=0 WHERE team=$tid");
	}
}
?>